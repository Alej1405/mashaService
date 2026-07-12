<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StoreProduct extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'store_category_id',
        'nombre',
        'slug',
        'sku',
        'descripcion',
        'precio_venta',
        'precio_distribuidor',
        'cantidad_minima_distribuidor',
        'imagen_principal',
        'galeria',
        'caracteristicas',
        'unidad_precio',
        'meta_titulo',
        'meta_descripcion',
        'publicado',
        'destacado',
        'orden',
    ];

    protected $casts = [
        'precio_venta'                 => 'decimal:4',
        'precio_distribuidor'          => 'decimal:4',
        'cantidad_minima_distribuidor' => 'integer',
        'caracteristicas'              => 'array',
        'publicado'                    => 'boolean',
        'destacado'                    => 'boolean',
        'orden'                        => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = static::uniqueSlug($model->empresa_id, Str::slug($model->nombre));
            }
        });
    }

    private static function uniqueSlug(int $empresaId, string $base): string
    {
        $slug = $base;
        $i    = 1;
        while (static::withoutGlobalScopes()->where('empresa_id', $empresaId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

    public function storeCategory(): BelongsTo
    {
        return $this->belongsTo(StoreCategory::class);
    }

    /** Imágenes del producto (tabla normalizada product_images), ordenadas. */
    public function imagenes(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('orden');
    }

    // ── Imágenes: imagen_principal/galeria son VIRTUALES sobre product_images ──
    // (las columnas físicas se retiraron; una sola fuente = product_images).
    private array $imagenesPendientes = [];
    private bool $sincronizarImagenesPendiente = false;

    public function getImagenPrincipalAttribute(): ?string
    {
        return optional($this->imagenes->firstWhere('es_principal', true) ?? $this->imagenes->first())->path;
    }

    public function getGaleriaAttribute(): array
    {
        return $this->imagenes->where('es_principal', false)->sortBy('orden')->pluck('path')->values()->all();
    }

    public function setImagenPrincipalAttribute($value): void
    {
        $this->imagenesPendientes['principal'] = $value ?: null;
        $this->sincronizarImagenesPendiente = true;
    }

    public function setGaleriaAttribute($value): void
    {
        $this->imagenesPendientes['galeria'] = is_array($value) ? array_values(array_filter($value)) : [];
        $this->sincronizarImagenesPendiente = true;
    }

    protected static function booted(): void
    {
        // Al guardar, reconstruye product_images desde lo asignado a
        // imagen_principal / galeria (portada primero, luego galería).
        static::saved(function (self $m) {
            if (! $m->sincronizarImagenesPendiente) {
                return;
            }
            $m->sincronizarImagenesPendiente = false;

            $principal = array_key_exists('principal', $m->imagenesPendientes)
                ? $m->imagenesPendientes['principal']
                : $m->getImagenPrincipalAttribute();
            $galeria = array_key_exists('galeria', $m->imagenesPendientes)
                ? $m->imagenesPendientes['galeria']
                : $m->getGaleriaAttribute();
            $m->imagenesPendientes = [];

            $m->imagenes()->delete();
            $orden = 0;
            if ($principal) {
                $m->imagenes()->create(['empresa_id' => $m->empresa_id, 'path' => $principal, 'es_principal' => true, 'orden' => $orden++]);
            }
            foreach ($galeria as $p) {
                if ($p === $principal) {
                    continue;
                }
                $m->imagenes()->create(['empresa_id' => $m->empresa_id, 'path' => $p, 'es_principal' => false, 'orden' => $orden++]);
            }
            $m->unsetRelation('imagenes');
        });
    }

    /** Insumos / materia prima que componen el producto (pivote product_materials). */
    public function materiales(): HasMany
    {
        return $this->hasMany(ProductMaterial::class);
    }

    /** Origen(es) de stock: items de inventario terminado que respaldan al producto. */
    public function stockItems(): HasMany
    {
        return $this->hasMany(StoreProductStock::class);
    }

    // ── Stock VIRTUAL: se lee del inventario real, no se guarda en store_products ──
    // Reemplaza a las columnas físicas stock / stock_minimo / gestionar_stock, que
    // se retiraron (una sola fuente de verdad = inventory_items.stock_actual).

    /**
     * ¿El producto controla stock? Sí cuando tiene al menos un item de inventario
     * enlazado. Sin enlace = no se valida stock (se trata como bajo pedido).
     */
    public function getGestionarStockAttribute(): bool
    {
        // Usa la relación ya cargada si existe (evita N+1 en listados con eager-load).
        return $this->relationLoaded('stockItems')
            ? $this->stockItems->isNotEmpty()
            : $this->stockItems()->exists();
    }

    /** Alias legible del anterior. */
    public function getGestionaStockAttribute(): bool
    {
        return $this->getGestionarStockAttribute();
    }

    /**
     * Unidades vendibles disponibles = el mínimo, entre todos los items enlazados,
     * de floor(stock_actual / cantidad). Null cuando el producto no gestiona stock.
     */
    public function getStockDisponibleAttribute(): ?int
    {
        $items = $this->relationLoaded('stockItems')
            ? $this->stockItems
            : $this->stockItems()->with('inventoryItem')->get();

        if ($items->isEmpty()) {
            return null;
        }

        return (int) $items->min(function (StoreProductStock $si): int {
            $porUnidad = (float) $si->cantidad ?: 1.0;
            $existencia = (float) ($si->inventoryItem?->stock_actual ?? 0);

            return (int) floor($existencia / $porUnidad);
        });
    }

    /** Compatibilidad: lectura de `stock` como número (0 si no gestiona stock). */
    public function getStockAttribute(): int
    {
        return $this->getStockDisponibleAttribute() ?? 0;
    }
}
