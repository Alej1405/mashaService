<?php

namespace App\Filament\Operaciones\Pages;

use App\Models\InventoryItem;
use App\Models\MeasurementUnit;
use App\Models\ProductDesign;
use App\Models\ProductFormulaLine;
use App\Models\ProductPresentation;
use App\Services\ServicioFormulacion;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Armar la receta de una presentación y ver lo que cuesta.
 *
 * Una pantalla, una tarea. A la izquierda la receta, a la derecha el costo: se
 * recalcula al cambiar una línea, no al guardar, porque el número es la razón
 * por la que alguien abre esta pantalla.
 *
 * El reparto: la receta se guarda aquí (el ERP almacena) y el costo lo calcula
 * el microservicio de formulación (el servicio procesa). Si el servicio no
 * responde, la receta se sigue pudiendo editar y se dice que el costo no está
 * disponible; no se inventa un número.
 */
class Formulacion extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Formulación';
    protected static ?string $title           = 'Formulación y costo';
    protected static ?string $navigationGroup = 'Producto';
    protected static ?int    $navigationSort  = 2;
    protected static string  $view            = 'filament.operaciones.formulacion';

    public ?int $presentacionId = null;

    /** Lo que devolvió el servicio la última vez. */
    public array $costo = [];

    /** Línea que se está añadiendo. */
    public ?int $nuevoItemId = null;
    public ?float $nuevaCantidad = null;
    public ?int $nuevaUnidadId = null;
    public ?float $nuevaMerma = 0;

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('inventario');
    }

    public function mount(): void
    {
        $this->presentacionId = $this->presentaciones()->keys()->first();
        $this->calcular();
    }

    public function updatedPresentacionId(): void
    {
        $this->calcular();
    }

    /** El costo lo dice el servicio, no esta pantalla. */
    public function calcular(): void
    {
        $this->costo = [];

        if (! $this->presentacionId) {
            return;
        }

        $presentacion = ProductPresentation::find($this->presentacionId);

        if (! $presentacion) {
            return;
        }

        $this->costo = app(ServicioFormulacion::class)->costear($presentacion);
    }

    public function agregarLinea(): void
    {
        if (! $this->presentacionId || ! $this->nuevoItemId || ! $this->nuevaCantidad || ! $this->nuevaUnidadId) {
            Notification::make()->title('Falta algo')
                ->body('Una línea necesita ítem, cantidad y unidad.')->warning()->send();

            return;
        }

        ProductFormulaLine::create([
            'presentation_id'    => $this->presentacionId,
            'inventory_item_id'  => $this->nuevoItemId,
            'cantidad'           => $this->nuevaCantidad,
            'measurement_unit_id' => $this->nuevaUnidadId,
            'merma_porcentaje'   => $this->nuevaMerma ?: 0,
        ]);

        $this->nuevoItemId = null;
        $this->nuevaCantidad = null;
        $this->nuevaMerma = 0;

        $this->calcular();
    }

    public function quitarLinea(int $id): void
    {
        ProductFormulaLine::where('presentation_id', $this->presentacionId)->where('id', $id)->delete();

        $this->calcular();
    }

    /** Guarda lo calculado como costo estándar, para compararlo con el real. */
    public function guardarEstandar(): void
    {
        $presentacion = ProductPresentation::find($this->presentacionId);

        if (! $presentacion) {
            return;
        }

        $r = app(ServicioFormulacion::class)->costearYGuardar($presentacion);

        if (! ($r['ok'] ?? false)) {
            Notification::make()->title('No se pudo guardar')->body($r['error'] ?? '')->danger()->send();

            return;
        }

        $this->costo = $r;

        Notification::make()->title('Costo estándar guardado')
            ->body('$ ' . number_format($r['costo_unitario'] ?? 0, 4, ',', '.') . ' por unidad.')
            ->success()->send();
    }

    /** Presentaciones de la empresa, agrupadas por diseño. */
    public function presentaciones()
    {
        $empresa = Filament::getTenant();

        return ProductPresentation::whereHas('productDesign', fn ($q) => $q->where('empresa_id', $empresa->id))
            ->with('productDesign')
            ->get()
            ->mapWithKeys(fn (ProductPresentation $p) => [
                $p->id => ($p->productDesign?->nombre ?? '—') . ' · ' . $p->nombre,
            ]);
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $presentacion = $this->presentacionId ? ProductPresentation::with('formulaLines.inventoryItem', 'formulaLines.measurementUnit')->find($this->presentacionId) : null;

        return [
            'empresa'       => $empresa,
            'presentaciones' => $this->presentaciones(),
            'presentacion'  => $presentacion,
            'lineas'        => $presentacion?->formulaLines ?? collect(),
            'items'         => InventoryItem::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)->where('activo', true)
                ->orderBy('type')->orderBy('nombre')->get()
                ->groupBy('type')->map(fn ($g) => $g->pluck('nombre', 'id')),
            'unidades'      => MeasurementUnit::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)->where('activo', true)
                ->orderBy('tipo')->orderBy('nombre')->get()
                ->mapWithKeys(fn ($u) => [$u->id => $u->nombre . ' (' . $u->abreviatura . ')']),
            'disenos'       => ProductDesign::where('empresa_id', $empresa->id)->count(),
        ];
    }
}
