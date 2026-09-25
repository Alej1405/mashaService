<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\MeasurementUnit;
use App\Models\ProductFormulaLine;
use App\Models\ProductPresentation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente del microservicio de formulación.
 *
 * El reparto es el mismo que con el SRI: **el ERP almacena y consulta, el
 * servicio procesa**. Aquí se arma el catálogo —unidades, ítems con su costo
 * del kardex y las líneas de cada receta— y allá se normaliza, se explota la
 * receta hasta el fondo y se calcula el costo.
 *
 * Si el servicio no responde, el ERP sigue funcionando: solo no se puede
 * costear, y eso se dice en pantalla.
 */
class ServicioFormulacion
{
    public function __construct(
        private readonly ?string $base = null,
        private readonly ?string $token = null,
    ) {}

    private function url(): string
    {
        return rtrim($this->base ?? config('services.formulacion.url', ''), '/');
    }

    public function configurado(): bool
    {
        return $this->url() !== '';
    }

    private function peticion()
    {
        return Http::timeout(30)->acceptJson()
            ->withToken($this->token ?? config('services.formulacion.token'));
    }

    /**
     * El costo de una presentación, con su escandallo.
     *
     * @return array{ok: bool, costo_unitario?: float, lineas?: array, hallazgos?: array, error?: string}
     */
    public function costear(ProductPresentation $presentacion): array
    {
        if (! $this->configurado()) {
            return ['ok' => false, 'error' => 'El servicio de formulación no está configurado.'];
        }

        $empresaId = $presentacion->productDesign?->empresa_id;

        if (! $empresaId) {
            return ['ok' => false, 'error' => 'Esa presentación no pertenece a ninguna empresa.'];
        }

        try {
            $r = $this->peticion()->post($this->url() . '/formula/costear', [
                'presentacion_id' => $presentacion->id,
                'unidades'        => $this->unidades($empresaId),
                'items'           => $this->items($empresaId),
                'recetas'         => $this->recetas($presentacion),
            ]);

            if ($r->status() === 422 || $r->status() === 404) {
                return ['ok' => false, 'error' => $r->json('detail') ?: 'La receta no se pudo calcular.'];
            }

            if (! $r->successful()) {
                return ['ok' => false, 'error' => 'El servicio de formulación respondió ' . $r->status() . '.'];
            }

            return ['ok' => true] + $r->json();
        } catch (\Throwable $e) {
            Log::warning('Formulación: el servicio no respondió', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'El servicio de formulación no responde ahora mismo.'];
        }
    }

    /**
     * Costea y guarda el resultado como costo estándar de la presentación.
     *
     * El estándar es lo que la receta dice que debería costar. El real sale del
     * kardex cuando se produce; compararlos es lo que avisa de que la
     * producción se está yendo de precio.
     */
    public function costearYGuardar(ProductPresentation $presentacion): array
    {
        $r = $this->costear($presentacion);

        if ($r['ok'] ?? false) {
            $presentacion->update([
                'costo_estandar'     => $r['costo_unitario'] ?? 0,
                'costo_calculado_en' => now(),
            ]);
        }

        return $r;
    }

    /** Una cantidad de una unidad a otra, para la ayuda en pantalla. */
    public function convertir(int $empresaId, float $cantidad, int $desdeId, int $hastaId): array
    {
        if (! $this->configurado()) {
            return ['ok' => false, 'error' => 'El servicio de formulación no está configurado.'];
        }

        try {
            $r = $this->peticion()->post($this->url() . '/unidades/convertir', [
                'cantidad'  => $cantidad,
                'desde_id'  => $desdeId,
                'hasta_id'  => $hastaId,
                'unidades'  => $this->unidades($empresaId),
            ]);

            return $r->successful()
                ? ['ok' => true] + $r->json()
                : ['ok' => false, 'error' => $r->json('detail') ?: 'No se pudo convertir.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'El servicio de formulación no responde ahora mismo.'];
        }
    }

    // ── Lo que el ERP le pasa al servicio ────────────────────────────────────

    /** @return array<int, array<string, mixed>> */
    private function unidades(int $empresaId): array
    {
        return MeasurementUnit::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)->where('activo', true)
            ->get()
            ->map(fn (MeasurementUnit $u) => [
                'id'          => $u->id,
                'abreviatura' => $u->abreviatura,
                'tipo'        => $u->tipo,
                'factor'      => (float) $u->factor,
            ])->all();
    }

    /**
     * Los ítems con su costo del kardex.
     *
     * El costo promedio es el real; `purchase_price` queda de respaldo para el
     * ítem que todavía no tiene historia, igual que en el asiento de venta.
     */
    private function items(int $empresaId): array
    {
        return InventoryItem::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->get()
            ->map(fn (InventoryItem $i) => [
                'id'              => $i->id,
                'nombre'          => $i->nombre,
                'tipo'            => $i->type,
                'unidad_id'       => $i->measurement_unit_id,
                'costo_unitario'  => (float) ($i->costo_promedio > 0 ? $i->costo_promedio : ($i->purchase_price ?? 0)),
                'presentacion_id' => $i->product_presentation_id,
            ])->all();
    }

    /**
     * La receta pedida y todas las que cuelgan de ella.
     *
     * Se mandan todas las de la empresa: el servicio no tiene base de datos y
     * no puede ir a buscar la receta del macerado cuando la encuentre dentro
     * de la del licor.
     */
    private function recetas(ProductPresentation $presentacion): array
    {
        $empresaId = $presentacion->productDesign->empresa_id;

        return ProductPresentation::whereHas('productDesign', fn ($q) => $q->where('empresa_id', $empresaId))
            ->with('formulaLines')
            ->get()
            ->map(fn (ProductPresentation $p) => [
                'presentacion_id' => $p->id,
                'nombre'          => $p->nombre,
                'rendimiento'     => (float) ($p->cantidad_minima_produccion ?: 1),
                'unidad_id'       => $p->measurement_unit_id,
                'lineas'          => $p->formulaLines->map(fn (ProductFormulaLine $l) => [
                    'item_id'          => $l->inventory_item_id,
                    'cantidad'         => (float) $l->cantidad,
                    'unidad_id'        => $l->measurement_unit_id,
                    'merma_porcentaje' => (float) ($l->merma_porcentaje ?? 0),
                ])->values()->all(),
            ])->values()->all();
    }
}
