<?php

namespace App\Filament\Operaciones\Pages;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Filament\Facades\Filament;
use Filament\Pages\Page;

/**
 * Lo primero que ve quien abre la bodega.
 *
 * El orden responde a la tarea, no a la jerarquía de la información: primero
 * lo que hay que hacer, después lo que está roto, y al final el contexto.
 */
class Dashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Inicio';
    protected static ?string $title           = 'Bodega';
    protected static ?int    $navigationSort  = -2;
    protected static string  $view            = 'filament.operaciones.dashboard';

    public static function getRoutePath(): string
    {
        return '/';
    }

    /** El saludo del diseño: quien abre esto está empezando su turno. */
    public function getHeading(): string
    {
        $hora   = now()->hour;
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
        $nombre = str(auth()->user()?->name ?? '')->before(' ')->toString();

        return trim("{$saludo}, {$nombre}", ' ,');
    }

    public function getSubheading(): ?string
    {
        $bodega = \App\Models\Almacen::withoutGlobalScopes()
            ->where('empresa_id', Filament::getTenant()?->id)
            ->where('activo', true)
            ->orderBy('id')
            ->value('nombre');

        return collect(['Operaciones', $bodega, \Illuminate\Support\Carbon::now()->translatedFormat('l, j \\d\\e F \\d\\e Y')])
            ->filter()
            ->join(' · ');
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();

        $items = InventoryItem::withoutGlobalScopes()
            ->with(['stocks.ubicacion', 'measurementUnit'])
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->get();

        // Bajo mínimo: lo único que obliga a actuar hoy.
        $bajoMinimo = $items
            ->filter(fn ($i) => $i->stock_minimo > 0 && $i->stock_actual < $i->stock_minimo)
            ->sortBy(fn ($i) => $i->stock_minimo > 0 ? $i->stock_actual / $i->stock_minimo : 1)
            ->take(6);

        $deHoy = InventoryMovement::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->whereDate('date', today());

        // Los contadores se cuentan en la base: sobre la lista recortada a 6
        // nunca pasaban de 6 por muchos movimientos que hubiera.
        $entradasHoy = (clone $deHoy)->where('type', 'entrada')->count();
        $salidasHoy  = (clone $deHoy)->where('type', 'salida')->count();

        $movimientosHoy = (clone $deHoy)
            ->with('inventoryItem')
            ->latest('id')
            ->limit(6)
            ->get();

        return [
            'items'          => $items,
            'bajoMinimo'     => $bajoMinimo,
            'movimientosHoy' => $movimientosHoy,
            'valorTotal'     => $items->sum(fn ($i) => (float) $i->saldo_valorado),
            'entradasHoy'    => $entradasHoy,
            'salidasHoy'     => $salidasHoy,
            'totalHoy'       => $entradasHoy + $salidasHoy,
            'sinUbicar'      => $items->filter(fn ($i) => $i->stocks->isEmpty())->count(),
        ];
    }
}
