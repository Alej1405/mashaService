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
        'telefono',
        'direccion',
        'activo',
        // Punto de venta: núcleo + toggles/handle. El CONTENIDO de la landing
        // (descripción, horario, logo, banner, ubicación) vive en customer_web.
        'publicado',
        'menu_activo',
        'slug',
        // NOTA: contabilidad (cuenta_contable_id → customer_finance), comercio exterior
        // (es_exportador/pais_destino → customer_export) y acceso al portal (password/
        // email_verified_at/is_super_admin → customer_access) viven en su contexto.
        // Se leen aquí vía accessors-puente; se escriben en sus tablas.
    ];

    protected $casts = [
        'activo'      => 'boolean',
        'publicado'   => 'boolean',
        'menu_activo' => 'boolean',
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

        });

        // La cuenta contable vive en customer_finance (contexto). Al crear el cliente
        // se resuelve el mapeo y se asegura su fila de finanzas con la cuenta por defecto.
        static::created(function ($model) {
            try {
                $empresaId = $model->empresa_id
                    ?? (function_exists('filament') ? \Filament\Facades\Filament::getTenant()?->id : null);
                if (! $empresaId) {
                    return;
                }
                $cuenta = \App\Services\AccountingService::getMapeo($empresaId, 'global', 'venta_credito');
                $model->finance()->firstOrCreate(
                    ['customer_id' => $model->id],
                    ['empresa_id' => $empresaId, 'cuenta_contable_id' => $cuenta->id, 'saldo' => 0, 'limite_credito' => 0],
                );
            } catch (\Exception) {
                \Illuminate\Support\Facades\Log::warning("Customer accounting map failed: {$model->codigo}");
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

    /** Cuenta contable del cliente, ahora en customer_finance (contexto). */
    public function cuentaContable(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            AccountPlan::class,
            CustomerFinance::class,
            'customer_id',        // FK en customer_finance → customers
            'id',                 // PK en account_plans
            'id',                 // PK en customers
            'cuenta_contable_id', // FK en customer_finance → account_plans
        );
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

    /** Galería de imágenes de la landing (tabla propia, máx. 5, opcional). */
    public function webImages(): HasMany
    {
        return $this->hasMany(CustomerWebImage::class)->orderBy('orden')->orderBy('id');
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

    /** Comercio exterior (aduana). Solo quien exporta la tiene. */
    public function export(): HasOne
    {
        return $this->hasOne(CustomerExport::class);
    }

    /** Acceso al portal (password, verificación, rol super admin). */
    public function access(): HasOne
    {
        return $this->hasOne(CustomerAccess::class);
    }

    // ── Accessors-puente: exponen datos de contexto como si fueran del cliente ──
    // Reads. Las escrituras van a la tabla de contexto correspondiente.

    public function getCuentaContableIdAttribute(): ?int
    {
        return $this->finance?->cuenta_contable_id;
    }

    public function getEsExportadorAttribute(): bool
    {
        return (bool) ($this->export?->es_exportador ?? false);
    }

    public function getPaisDestinoAttribute(): ?string
    {
        return $this->export?->pais_destino;
    }

    public function getPasswordAttribute(): ?string
    {
        return $this->access?->password;
    }

    public function getEmailVerifiedAtAttribute()
    {
        return $this->access?->email_verified_at;
    }

    public function getIsSuperAdminAttribute(): bool
    {
        return (bool) ($this->access?->is_super_admin ?? false);
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
