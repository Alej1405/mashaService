<?php

namespace App\Filament\Logistics\Resources\ShipmentResource\Pages;

use App\Filament\Logistics\Resources\ShipmentResource;
use App\Models\LogisticsDocument;
use App\Models\LogisticsPackage;
use App\Models\LogisticsShipment;
use App\Models\LogisticsShipmentHistory;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateShipment extends CreateRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $empresaId = Filament::getTenant()->id;
        $data['empresa_id']      = $empresaId;
        $data['numero_embarque'] = LogisticsShipment::generarNumero($empresaId);
        $data['estado']          = 'embarque_solicitado';
        return $data;
    }

    protected function afterCreate(): void
    {
        // Registrar creación en historial
        LogisticsShipmentHistory::registrar(
            shipmentId:  $this->record->id,
            tipo:        'creacion',
            descripcion: 'Embarque ' . $this->record->numero_embarque . ' registrado.',
            estadoNuevo: $this->record->estado,
        );

        // Leer el pivot directamente para evitar que EmpresaScope filtre paquetes
        // de clientes distintos (consolidado con múltiples dueños).
        $packageIds = DB::table('logistics_shipment_packages')
            ->where('shipment_id', $this->record->id)
            ->pluck('package_id');
        if ($packageIds->isNotEmpty()) {
            LogisticsPackage::withoutGlobalScopes()
                ->whereIn('id', $packageIds)
                ->update(['estado' => 'embarque_solicitado']);

            $count = $packageIds->count();
            LogisticsShipmentHistory::registrar(
                shipmentId:  $this->record->id,
                tipo:        'paquete',
                descripcion: "{$count} paquete(s) asignado(s) al embarque.",
            );
        }

        $this->guardarDocumentos();
    }

    private function guardarDocumentos(): void
    {
        foreach ($this->data['documentosData'] ?? [] as $doc) {
            if (empty($doc['archivo_path'])) {
                continue;
            }
            LogisticsDocument::create([
                'empresa_id'        => Filament::getTenant()->id,
                'documentable_type' => LogisticsShipment::class,
                'documentable_id'   => $this->record->id,
                'tipo'              => $doc['tipo'],
                'nombre'            => $doc['nombre'],
                'archivo_path'      => $doc['archivo_path'],
                'notas'             => $doc['notas'] ?? null,
            ]);
        }
    }
}
