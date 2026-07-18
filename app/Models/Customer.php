<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasEmpresa;

    public const TIPOS = [
        'natural'  => 'Persona natural',
        'juridica' => 'Empresa / RUC',
    ];

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'apellido',
        'razon_social',
        'tipo_persona',
        'tipo_identificacion',
        'numero_identificacion',
        'email',
        'password',
        'email_verified_at',
        'telefono',
        'direccion',
        'es_exportador',
        'pais_destino',
        'cuenta_contable_id',
        'activo',
        'is_super_admin',
        // Punto de venta / landing pública (el cliente es el punto de venta)
        'publicado',
        'menu_activo',
        'slug',
        'descripcion_web',
        'horario',
        'logo',
        'banner',
        'latitud',
        'longitud',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'es_exportador'     => 'boolean',
        'activo'            => 'boolean',
        'is_super_admin'    => 'boolean',
        'publicado'         => 'boolean',
        'menu_activo'       => 'boolean',
        'latitud'           => 'decimal:7',
        'longitud'          => 'decimal:7',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->codigo)) {
                $year = now()->year;
                $last = self::where('empresa_id', $model->empresa_id)->latest('id')->first();
                $next = $last ? ((int) substr($last->codigo, -5)) + 1 : 1;
                $model->codigo = "CLI-{$year}-" . str_pad($next, 5, '0', STR_PAD_LEFT);
            }

            if (empty($model->cuenta_contable_id)) {
                try {
                    $empresaId = $model->empresa_id
                        ?? (function_exists('filament') ? \Filament\Facades\Filament::getTenant()?->id : null);
                    if ($empresaId) {
                        $cuenta = \App\Services\AccountingService::getMapeo($empresaId, 'global', 'venta_credito');
                        $model->cuenta_contable_id = $cuenta->id;
                    }
                } catch (\Exception) {
                    \Illuminate\Support\Facades\Log::warning("Customer accounting map failed: {$model->codigo}");
                }
            }
        });

        // Al publicar web o menú el cliente necesita un slug para su URL pública/QR.
        static::saving(function ($model) {
            self::asegurarSlugPublico($model);
        });
    }

    /** Genera un slug único por empresa si el cliente es público (web o menú) y no tiene. */
    protected static function asegurarSlugPublico($model): void
    {
        if (! empty($model->slug)) {
            return;
        }
        if (! $model->publicado && ! $model->menu_activo) {
            return;
        }

        $base = \Illuminate\Support\Str::slug($model->nombre_completo ?: ('cliente-' . ($model->codigo ?? '')));
        $base = $base !== '' ? $base : 'punto-venta';

        $slug = $base;
        $i = 2;
        while (self::withoutGlobalScopes()
                ->where('empresa_id', $model->empresa_id)
                ->where('slug', $slug)
                ->when($model->id, fn ($q) => $q->where('id', '!=', $model->id))
                ->exists()) {
            $slug = $base . '-' . $i++;
        }

        $model->slug = $slug;
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        if ($this->tipo_persona === 'juridica' && $this->razon_social) {
            return $this->razon_social;
        }
        return trim($this->nombre . ' ' . ($this->apellido ?? ''));
    }

    // ── Relaciones ERP ────────────────────────────────────────────────────────

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function cuentaContable(): BelongsTo
    {
        return $this->belongsTo(AccountPlan::class, 'cuenta_contable_id');
    }

    // ── Relaciones Portal ─────────────────────────────────────────────────────

    public function addresses(): HasMany
    {
        return $this->hasMany(StoreAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class);
    }

    public function serviceContracts(): HasMany
    {
        return $this->hasMany(ServiceContract::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(StoreCustomerCompany::class);
    }

    /** Ítems del menú del punto de venta (tabla independiente customer_menu_items). */
    public function menuItems(): HasMany
    {
        return $this->hasMany(CustomerMenuItem::class);
    }

    // ── Módulos normalizados del cliente (1:1) ─────────────────────────────────

    /** Parte web/landing pública. No todos los clientes la tienen. */
    public function web(): HasOne
    {
        return $this->hasOne(CustomerWeb::class);
    }

    /** Parte financiera (cuenta contable, saldo, crédito). */
    public function finance(): HasOne
    {
        return $this->hasOne(CustomerFinance::class);
    }

    /**
     * URL pública de la landing del punto de venta. La base sale de FRONTEND_URL
     * (config app.frontend_url) y cae a app.url si no está definida; el front la
     * resuelve como /clientes/{slug}.
     */
    public function landingUrl(): string
    {
        $base = rtrim((string) (config('app.frontend_url') ?: config('app.url')), '/');

        return $this->slug ? "{$base}/clientes/{$this->slug}" : $base;
    }

    /** QR (SVG inline) que apunta a la landing del punto de venta. */
    public function qrSvg(int $size = 220): string
    {
        return \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size($size)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($this->landingUrl());
    }
}
