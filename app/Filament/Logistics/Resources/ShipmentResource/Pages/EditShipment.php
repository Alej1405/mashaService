<?php

namespace App\Filament\Logistics\Resources\ShipmentResource\Pages;

use App\Filament\Logistics\Resources\ShipmentResource;
use App\Mail\LogisticsPackageStatusMail;
use App\Models\Empresa;
use App\Models\LogisticsDocument;
use App\Models\LogisticsPackage;
use App\Models\LogisticsShipment;
use App\Models\LogisticsShipmentHistory;
use App\Models\StoreCustomer;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

class EditShipment extends EditRecord
{
    protected static string $resource = ShipmentResource::class;

    /** IDs de paquetes antes de guardar */
    private Collection $packageIdsBefore;

    /** Estado del embarque antes de guardar */
    private string $estadoAntes;

    /**
     * Mapeo: estado del embarque → estado del paquete (para Kanban y notificaciones).
     * Refleja la progresión del flujo courier SENAE.
     */
    private const ESTADO_PAQUETE = [
        'embarque_solicitado'        => 'embarque_solicitado',
        'carga_registrada'           => 'embarque_solicitado',
        'consolidando'               => 'embarque_solicitado',
        'fraccionamiento_en_proceso' => 'embarque_solicitado',
        'carga_embarcada'            => 'embarque_solicitado',
        'en_aduana'                  => 'en_aduana',
        'declaracion_transmitida'    => 'en_aduana',
        'aforo_automatico'           => 'en_aduana',
        'aforo_documental'           => 'en_aduana',
        'aforo_fisico'               => 'en_aduana',
        'liquidada'                  => 'en_aduana',
        'pagada'                     => 'en_aduana',
        'autorizado_salida'          => 'finalizado_aduana',
        'entregada'                  => 'en_entrega',
    ];

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function beforeSave(): void
    {
        $this->packageIdsBefore = $this->record->packages()
            ->pluck('logistics_packages.id');

        $this->estadoAntes = $this->record->estado;
    }

    protected function afterSave(): void
    {
        $packageIdsAfter = $this->record->packages()
            ->pluck('logistics_packages.id');

        // ── Paquetes removidos → liberar al estado en_bodega ──────────────────
        $quitados = $this->packageIdsBefore->diff($packageIdsAfter);
        if ($quitados->isNotEmpty()) {
            LogisticsPackage::withoutGlobalScopes()
                ->whereIn('id', $quitados)
                ->update(['estado' => 'en_bodega', 'estado_secundario' => null]);

            LogisticsShipmentHistory::registrar(
                shipmentId:  $this->record->id,
                tipo:        'paquete',
                descripcion: $quitados->count() . ' paquete(s) removido(s) del embarque.',
            );
        }

        // ── Paquetes añadidos → embarque_solicitado (aparecen en Kanban) ──────
        $agregados = $packageIdsAfter->diff($this->packageIdsBefore);
        if ($agregados->isNotEmpty()) {
            LogisticsPackage::withoutGlobalScopes()
                ->whereIn('id', $agregados)
                ->update(['estado' => 'embarque_solicitado', 'estado_secundario' => null]);

            LogisticsShipmentHistory::registrar(
                shipmentId:  $this->record->id,
                tipo:        'paquete',
                descripcion: $agregados->count() . ' paquete(s) agregado(s) al embarque.',
            );
        }

        // ── Cambio de estado del embarque ─────────────────────────────────────
        $estadoNuevo = $this->record->estado;

        if ($estadoNuevo !== $this->estadoAntes) {
            $packageEstado = self::ESTADO_PAQUETE[$estadoNuevo] ?? null;

            // Actualizar estado de TODOS los paquetes actuales del embarque
            if ($packageEstado && $packageIdsAfter->isNotEmpty()) {
                LogisticsPackage::withoutGlobalScopes()
                    ->whereIn('id', $packageIdsAfter)
                    ->update(['estado' => $packageEstado, 'estado_secundario' => null]);
            }

            // Registrar en historial del embarque
            LogisticsShipmentHistory::registrar(
                shipmentId:    $this->record->id,
                tipo:          'cambio_estado',
                descripcion:   'Estado actualizado: '
                    . (LogisticsShipment::ESTADOS[$this->estadoAntes]['label'] ?? $this->estadoAntes)
                    . ' → '
                    . (LogisticsShipment::ESTADOS[$estadoNuevo]['label'] ?? $estadoNuevo),
                estadoAnterior: $this->estadoAntes,
                estadoNuevo:    $estadoNuevo,
            );

            // Notificar a todos los clientes con paquetes en este embarque
            if ($packageIdsAfter->isNotEmpty()) {
                $this->notificarClientesEmbarque($packageIdsAfter, $estadoNuevo);
            }
        }

        // ── Recalcular acumulado del consignatario si entregado ───────────────
        if ($estadoNuevo === 'entregada') {
            $this->record->consignatario?->recalcularAcumulado();
        }

        $this->guardarDocumentos();
    }

    /**
     * Notifica a cada cliente dueño de un paquete en este embarque.
     * Envía un correo individual por paquete.
     */
    private function notificarClientesEmbarque(Collection $packageIds, string $estadoEmbarque): void
    {
        $empresa = Empresa::find($this->record->empresa_id);
        if (! $empresa) {
            return;
        }

        // solicitar_pago = true cuando el embarque está autorizado para salida
        $solicitarPago = $estadoEmbarque === 'autorizado_salida';

        $packages = LogisticsPackage::withoutGlobalScopes()
            ->whereIn('id', $packageIds)
            ->whereNotNull('store_customer_id')
            ->with('storeCustomer')
            ->get();

        foreach ($packages as $package) {
            $customer = $package->storeCustomer;

            if (! $customer || ! filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $mail = new LogisticsPackageStatusMail(
                $package->fresh(), // estado actualizado
                $customer,
                $empresa,
                $solicitarPago,
            );

            try {
                Resend::emails()->send([
                    'from'    => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
                    'to'      => [$customer->email],
                    'subject' => $mail->envelope()->subject,
                    'html'    => $mail->buildHtml(),
                ]);
            } catch (\Exception $e) {
                Log::error('Error notificando cliente desde embarque', [
                    'package_id'  => $package->id,
                    'shipment_id' => $this->record->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }
    }

    private function guardarDocumentos(): void
    {
        foreach ($this->data['documentosData'] ?? [] as $doc) {
            if (empty($doc['archivo_path'])) {
                continue;
            }
            LogisticsDocument::firstOrCreate(
                [
                    'documentable_type' => LogisticsShipment::class,
                    'documentable_id'   => $this->record->id,
                    'archivo_path'      => $doc['archivo_path'],
                ],
                [
                    'empresa_id' => Filament::getTenant()->id,
                    'tipo'       => $doc['tipo'],
                    'nombre'     => $doc['nombre'],
                    'notas'      => $doc['notas'] ?? null,
                ]
            );
        }
    }
}
