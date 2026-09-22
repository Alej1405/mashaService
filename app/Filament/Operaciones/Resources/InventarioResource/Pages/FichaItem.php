<?php

namespace App\Filament\Operaciones\Resources\InventarioResource\Pages;

use App\Filament\Operaciones\Resources\InventarioResource;
use App\Models\InventoryMovement;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

/**
 * Ficha del ítem con su kardex valorado.
 *
 * Es la pantalla del producto: cuánto hay, dónde está, cuánto vale y cómo se
 * movió su costo. Editar los datos es una acción sobre esta pantalla, no una
 * pantalla aparte.
 */
class FichaItem extends ViewRecord
{
    protected static string $resource = InventarioResource::class;
    protected static string $view     = 'filament.operaciones.ficha-item';

    public function getTitle(): string
    {
        return $this->record->nombre;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('registrar_movimiento')
                ->label('Registrar movimiento')
                ->icon('heroicon-o-arrows-right-left')
                ->url(fn (): string => \App\Filament\Operaciones\Pages\RegistrarMovimiento::getUrl() . '?item=' . $this->record->id),
            Actions\EditAction::make()->label('Editar datos')->slideOver(),
            Actions\Action::make('etiqueta')
                ->label('Etiqueta')
                ->icon('heroicon-o-qr-code')
                ->color('gray')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
                ->modalContent(fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                    '<div style="display:flex;gap:18px;align-items:center;padding:16px">'
                    . '<div style="flex-shrink:0">' . $this->record->qrSvg(140) . '</div>'
                    . '<div><p style="font-size:12px;color:#64748b">' . e($this->record->codigo) . '</p>'
                    . '<p style="font-size:24px;font-weight:700">' . e($this->ubicacion() ?? 'sin ubicar') . '</p>'
                    . '<p style="font-size:12px;color:#64748b;margin-top:8px">' . e($this->record->urlQr()) . '</p>'
                    . '<p style="margin-top:12px"><a href="' . route('inventario.etiquetas.pdf', ['item' => $this->record->id, 'empresa' => $this->record->empresa_id])
                    . '" style="display:inline-block;background:#4f46e5;color:#fff;font-weight:700;font-size:13px;padding:8px 14px;border-radius:8px;text-decoration:none">Descargar PDF</a></p>'
                    . '</div></div>'
                )),
        ];
    }

    public function ubicacion(): ?string
    {
        $stock = $this->record->stocks->firstWhere('cantidad', '>', 0) ?? $this->record->stocks->first();

        return $stock?->ubicacion?->codigo_ubicacion;
    }

    protected function getViewData(): array
    {
        $item = $this->record;

        return [
            'item'      => $item,
            'ubicacion' => $this->ubicacion(),
            'unidad'    => $item->measurementUnit->abreviatura ?? $item->measurementUnit->nombre ?? '',
            'bajo'      => $item->stock_minimo > 0 && $item->stock_actual < $item->stock_minimo,
            'kardex'    => InventoryMovement::withoutGlobalScopes()
                ->where('inventory_item_id', $item->id)
                ->orderBy('date')->orderBy('id')
                ->limit(25)
                ->get(),
        ];
    }
}
