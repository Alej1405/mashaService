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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

class EditShipment extends EditRecord
{
    protected static string $resource = ShipmentResource::class;

    /** IDs de paquetes antes de guardar (leídos directo del pivot, sin scopes) */
    private Collection $packageIdsBefore;

    /** Estado del embarque antes de guardar */
    private string $estadoAntes;

    /**
     * Mapeo estado del embarque → estado del paquete.
     * Determina en qué columna del Kanban aparece el paquete.
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
        // Leemos el pivot directamente para no depender de EmpresaScope.
        // En embarques consolidados con paquetes de distintos clientes el scope
        // puede devolver un conjunto incompleto si el tenant no está resuelto aún.
        $this->packageIdsBefore = DB::table('logistics_shipment_packages')
            ->where('shipment_id', $this->record->id)
            ->pluck('package_id');

        $this->estadoAntes = $this->record->estado;
    }

    protected function afterSave(): void
    {
        // IDs actuales en el pivot (post-sync de Filament)
        $packageIdsAfter = DB::table('logistics_shipment_packages')
            ->where('shipment_id', $this->record->id)
            ->pluck('package_id');

        // ── Paquetes removidos → liberar ──────────────────────────────────────
        $quitados = $this->packageIdsBefore->diff($packageIdsAfter);
        if ($quitados->isNotEmpty()) {
            LogisticsPackage::withoutGlobalScopes()
                ->whereIn('id', $quitados)
                ->update(['estado' => 'registrado', 'estado_secundario' => null]);

            LogisticsShipmentHistory::registrar(
                shipmentId:  $this->record->id,
                tipo:        'paquete',
                descripcion: $quitados->count() . ' paquete(s) removido(s) del embarque.',
            );
        }

        // ── Paquetes añadidos → aparecen en columna Kanban ───────────────────
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

            // Actualizar estado de TODOS los paquetes del embarque
            if ($packageEstado && $packageIdsAfter->isNotEmpty()) {
                LogisticsPackage::withoutGlobalScopes()
                    ->whereIn('id', $packageIdsAfter)
                    ->update(['estado' => $packageEstado, 'estado_secundario' => null]);
            }

            // Historial del embarque
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

            // Notificar a cada cliente dueño de un paquete en este embarque
            if ($packageIdsAfter->isNotEmpty()) {
                $this->notificarClientesEmbarque($packageIdsAfter, $estadoNuevo);
            }
        }

        // ── Recalcular acumulado del consignatario si fue entregado ───────────
        if ($estadoNuevo === 'entregada') {
            $this->record->consignatario?->recalcularAcumulado();
        }

        $this->guardarDocumentos();
    }

    /**
     * Notifica por email a cada cliente dueño de un paquete en el embarque.
     * Carga paquetes y clientes sin scopes para garantizar que encontramos
     * TODOS los registros independientemente de empresa/tenant.
     */
    private function notificarClientesEmbarque(Collection $packageIds, string $estadoEmbarque): void
    {
        $empresa = Empresa::find($this->record->empresa_id);
        if (! $empresa) {
            return;
        }

        $solicitarPago = $estadoEmbarque === 'autorizado_salida';

        // Sin scopes: paquetes de distintos clientes siempre se cargan completos
        $packages = LogisticsPackage::withoutGlobalScopes()
            ->whereIn('id', $packageIds)
            ->whereNotNull('store_customer_id')
            ->get();

        foreach ($packages as $package) {
            // Cliente sin scope para evitar filtrado por empresa/tenant
            $customer = StoreCustomer::withoutGlobalScopes()->find($package->store_customer_id);

            if (! $customer || ! filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $mail = new LogisticsPackageStatusMail(
                $package->fresh(),
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
