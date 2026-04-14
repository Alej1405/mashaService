<?php

namespace App\Filament\Logistics\Resources\ShipmentResource\Pages;

use App\Filament\Logistics\Resources\ShipmentResource;
use App\Models\LogisticsDocument;
use App\Models\LogisticsPackage;
use App\Models\LogisticsShipment;
use App\Models\LogisticsShipmentHistory;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Collection;

class EditShipment extends EditRecord
{
    protected static string $resource = ShipmentResource::class;

    /** IDs de paquetes antes de guardar (para detectar qué se quitó) */
    private Collection $packageIdsBefore;

    /** Estado antes de guardar (para detectar cambio de estado manual) */
    private string $estadoAntes;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function beforeSave(): void
    {
        // Capturamos los paquetes actuales antes de que Filament sincronice
        $this->packageIdsBefore = $this->record->packages()
            ->pluck('logistics_packages.id');

        // Capturamos el estado actual antes de guardar
        $this->estadoAntes = $this->record->estado;
    }

    protected function afterSave(): void
    {
        $packageIdsAfter = $this->record->packages()
            ->pluck('logistics_packages.id');

        // Paquetes que se quitaron de este embarque → liberar
        $quitados = $this->packageIdsBefore->diff($packageIdsAfter);
        if ($quitados->isNotEmpty()) {
            LogisticsPackage::withoutGlobalScopes()
                ->whereIn('id', $quitados)
                ->update(['estado' => 'en_bodega']);

            LogisticsShipmentHistory::registrar(
                shipmentId:  $this->record->id,
                tipo:        'paquete',
                descripcion: $quitados->count() . ' paquete(s) removido(s) del embarque.',
            );
        }

        // Paquetes que se añadieron → marcar como asignado
        $agregados = $packageIdsAfter->diff($this->packageIdsBefore);
        if ($agregados->isNotEmpty()) {
            LogisticsPackage::withoutGlobalScopes()
                ->whereIn('id', $agregados)
                ->update(['estado' => 'asignado']);

            LogisticsShipmentHistory::registrar(
                shipmentId:  $this->record->id,
                tipo:        'paquete',
                descripcion: $agregados->count() . ' paquete(s) agregado(s) al embarque.',
            );
        }

        // Recalcular acumulado del consignatario si está entregado
        if ($this->record->estado === 'entregada') {
            $this->record->consignatario?->recalcularAcumulado();
        }

        $this->guardarDocumentos();
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
