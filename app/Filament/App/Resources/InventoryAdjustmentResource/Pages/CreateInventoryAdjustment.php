<?php

namespace App\Filament\App\Resources\InventoryAdjustmentResource\Pages;

use App\Filament\App\Resources\InventoryAdjustmentResource;
use App\Models\InventoryAdjustment;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Services\AccountingService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateInventoryAdjustment extends CreateRecord
{
    protected static string $resource = InventoryAdjustmentResource::class;

    protected function handleRecordCreation(array $data): InventoryAdjustment
    {
        return DB::transaction(function () use ($data) {
            $empresaId = Filament::getTenant()->id;

            $item = InventoryItem::lockForUpdate()->findOrFail($data['inventory_item_id']);

            $factor        = (float) ($data['factor_empaque'] ?? 1);
            $cantPres      = (float) $data['cantidad_presentacion'];
            $totalBase     = round($cantPres * $factor, 6);
            $stockAnterior = (float) $item->stock_actual;
            $costoUnitario = (float) ($data['costo_unitario'] ?? $item->purchase_price ?? 0);

            // Calcular stock nuevo según tipo
            $stockNuevo = match($data['tipo']) {
                'entrada'    => $stockAnterior + $totalBase,
                'salida'     => $stockAnterior - $totalBase,
                'correccion' => $totalBase,
                default      => $stockAnterior,
            };

            // Actualizar stock del ítem
            $item->update(['stock_actual' => $stockNuevo]);

            // Determinar el tipo de movimiento (adjustment genera asiento, inventory_load no)
            $refType   = 'adjustment';
            $movType   = match($data['tipo']) {
                'entrada'    => 'entrada',
                'correccion' => 'entrada',
                'salida'     => 'salida',
                default      => 'entrada',
            };
            $movQty = match($data['tipo']) {
                'salida' => $totalBase,
                default  => $totalBase,
            };

            // Crear movimiento de inventario → InventoryMovementObserver generará el asiento si tipo='adjustment'
            $movement = InventoryMovement::create([
                'empresa_id'        => $empresaId,
                'inventory_item_id' => $item->id,
                'type'              => $movType,
                'quantity'          => $movQty,
                'unit_price'        => $costoUnitario,
                'total'             => round($movQty * $costoUnitario, 2),
                'reference_type'    => $refType,
                'reference_id'      => null,
                'notes'             => $data['motivo'] ?? 'Ajuste de inventario',
                'date'              => now()->toDateString(),
            ]);

            // Crear registro de ajuste
            $adjustment = InventoryAdjustment::create([
                'inventory_item_id'   => $item->id,
                'empresa_id'          => $empresaId,
                'item_presentation_id' => $data['item_presentation_id'] ?? null,
                'cantidad_presentacion' => $cantPres,
                'factor_empaque'       => $factor,
                'total_unidades_base'  => $totalBase,
                'tipo'                 => $data['tipo'],
                'stock_anterior'       => $stockAnterior,
                'stock_nuevo'          => $stockNuevo,
                'costo_unitario'       => $costoUnitario ?: null,
                'motivo'               => $data['motivo'] ?? null,
                'journal_entry_id'     => $movement->journal_entry_id,
                'user_id'              => Auth::id(),
            ]);

            return $adjustment;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
