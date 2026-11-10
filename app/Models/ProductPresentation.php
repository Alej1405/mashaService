<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductPresentation extends Model
{
    protected $fillable = [
        'product_design_id',
        'nombre',
        'codigo_sri',
        'capacidad',
        'graduacion_alcoholica',
        'inventory_item_id',
        'measurement_unit_id',
        'activa',
        'cantidad_minima_produccion',
        'margen_objetivo',
        'pvp_estimado',
        'costo_estandar',
        'costo_calculado_en',
    ];

    protected $casts = [
        'activa'                    => 'boolean',
        'cantidad_minima_produccion' => 'decimal:4',
        'margen_objetivo'           => 'decimal:2',
        'pvp_estimado'              => 'decimal:4',
        'costo_estandar'            => 'decimal:4',
        'capacidad'                 => 'decimal:4',
        'graduacion_alcoholica'     => 'decimal:2',
        'costo_calculado_en'        => 'datetime',
    ];

    public function productDesign(): BelongsTo
    {
        return $this->belongsTo(ProductDesign::class);
    }

    /**
     * Cada presentación se vende como un producto: con su ítem de inventario y su
     * producto de tienda, que nace sin publicar hasta que marketing lo complete.
     */
    protected static function booted(): void
    {
        // El código del SRI se genera solo si no se escribió uno (los que ya están
        // en facturas se respetan).
        static::saving(function (self $p) {
            if (blank($p->codigo_sri)) {
                $p->codigo_sri = static::generarCodigoSri($p);
            }
        });

        static::saved(fn (self $p) => app(\App\Services\PresentacionComoProducto::class)->sincronizar($p));
    }

    /**
     * Siglas del producto, capacidad y graduación: Ron Dorado 750 a 40° → RD-750-40.
     * Si ya existe en la empresa, se numera: RD-750-40-2.
     */
    public static function generarCodigoSri(self $p): string
    {
        $diseno = ProductDesign::withoutGlobalScopes()->find($p->product_design_id);
        $nombre = \Illuminate\Support\Str::ascii($diseno?->nombre ?: $p->nombre ?: 'PROD');
        $siglas = collect(preg_split('/[^A-Za-z0-9]+/', strtoupper($nombre), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($palabra) => $palabra[0])->take(4)->join('') ?: 'P';

        $entero = fn ($v) => $v === null || (float) $v == 0.0 ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $base = collect([$siglas, $entero($p->capacidad), $entero($p->graduacion_alcoholica)])->filter()->join('-');

        $codigo = $base;
        for ($n = 2; $diseno && static::codigoSriUsado($codigo, $diseno->empresa_id, $p->id); $n++) {
            $codigo = "{$base}-{$n}";
        }

        return $codigo;
    }

    /** ¿Otra presentación de la empresa ya usa este código del SRI? */
    public static function codigoSriUsado(string $codigo, int $empresaId, ?int $exceptoId = null): bool
    {
        return static::where('codigo_sri', trim($codigo))
            ->when($exceptoId, fn ($q) => $q->where('id', '!=', $exceptoId))
            ->whereHas('productDesign', fn ($q) => $q->withoutGlobalScopes()->where('empresa_id', $empresaId))
            ->exists();
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function storeProduct(): HasOne
    {
        return $this->hasOne(StoreProduct::class);
    }

    /** El envase de esta presentación; la receta es del producto. */
    public function formulaLines(): HasMany
    {
        return $this->hasMany(ProductFormulaLine::class, 'presentation_id');
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }
}
