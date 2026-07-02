<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\HasDefaultTenant;
use Spatie\Permission\Traits\HasRoles;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements FilamentUser, HasTenants, HasDefaultTenant
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /** Paneles que usan roles de acceso en lugar del plan. Se mantienen igual. */
    private const ROLE_BASED_PANELS = [
        'cms'        => ['admin_empresa', 'marketing', 'cms_editor'],
        'ecommerce'  => ['admin_empresa', 'ecommerce_manager'],
    ];

    /** Mapa de paneles basados en rol (lectura pública para el hub de inicio). */
    public static function roleBasedPanels(): array
    {
        return self::ROLE_BASED_PANELS;
    }

    /**
     * Claves de los planes que abren un panel dado (su id Filament = panels.key).
     * Fuente de verdad del acceso: relación plan_panel (configurable desde el admin).
     *
     * @return array<int,string>
     */
    private function plansThatOpenPanel(string $panelId): array
    {
        return \App\Models\Panel::where('key', $panelId)->first()
            ?->servicePlans()->pluck('key')->all() ?? [];
    }

    public function getTenants(Panel $panel): Collection
    {
        $panelId = $panel->getId();

        // Paneles basados en roles: todas las empresas activas accesibles.
        if (isset(self::ROLE_BASED_PANELS[$panelId])) {
            if ($this->hasRole('super_admin')) {
                return Empresa::where('activo', true)->get();
            }
            return $this->empresasAcceso()->where('activo', true)->get();
        }

        // Paneles por plan: empresas cuyo plan abre este panel.
        $planKeys = $this->plansThatOpenPanel($panelId);

        if ($this->hasRole('super_admin')) {
            return Empresa::where('activo', true)->whereIn('plan', $planKeys)->get();
        }

        return $this->empresasAcceso()->where('activo', true)->whereIn('plan', $planKeys)->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if (! $tenant->activo) {
            return false;
        }

        // Resolver el panel desde Filament (NO desde el segmento de la URL): en la ruta
        // global /livewire/update el primer segmento es "livewire", no el path del panel,
        // y usar request()->segment(1) hacía fallar canAccessTenant → 404 en cada update.
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? '';

        // Paneles basados en roles (cms, ecommerce)
        if (isset(self::ROLE_BASED_PANELS[$panelId])) {
            if ($this->hasRole('super_admin')) return true;
            return $this->empresasAcceso()->where('empresas.id', $tenant->id)->exists();
        }

        // Paneles por plan: el plan del tenant debe abrir este panel.
        $planKeys = $this->plansThatOpenPanel($panelId);

        if (! in_array($tenant->plan, $planKeys, true)) {
            return false;
        }

        if ($this->hasRole('super_admin')) {
            return true;
        }

        return $this->empresasAcceso()
            ->where('empresas.id', $tenant->id)
            ->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $panelId = $panel->getId();

        if ($panelId === 'admin') {
            return $this->hasRole('super_admin');
        }

        if ($this->hasRole('super_admin')) {
            return true;
        }

        // Paneles cms y ecommerce: basado en roles del pivot
        if (isset(self::ROLE_BASED_PANELS[$panelId])) {
            $allowedRoles = self::ROLE_BASED_PANELS[$panelId];
            return $this->empresasAcceso()
                ->where('activo', true)
                ->wherePivotIn('rol', $allowedRoles)
                ->exists()
                || $this->empresa()->where('activo', true)->exists();
        }

        // Paneles por plan: ¿alguna empresa accesible tiene un plan que abre este panel?
        $planKeys = $this->plansThatOpenPanel($panelId);
        if (empty($planKeys)) {
            return false;
        }

        return $this->empresasAcceso()->where('activo', true)->whereIn('plan', $planKeys)->exists()
            || $this->empresa()->where('activo', true)->whereIn('plan', $planKeys)->exists();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        $panelId = $panel->getId();

        // Paneles basados en roles
        if (isset(self::ROLE_BASED_PANELS[$panelId])) {
            if ($this->hasRole('super_admin')) {
                return $this->empresa ?? Empresa::where('activo', true)->first();
            }
            return $this->empresa?->activo ? $this->empresa
                : $this->empresasAcceso()->where('activo', true)->first();
        }

        // Paneles por plan: preferir la empresa primaria si su plan abre el panel.
        $planKeys = $this->plansThatOpenPanel($panelId);

        if ($this->empresa && $this->empresa->activo && in_array($this->empresa->plan, $planKeys, true)) {
            return $this->empresa;
        }

        if ($this->hasRole('super_admin')) {
            return Empresa::where('activo', true)->whereIn('plan', $planKeys)->first();
        }

        return $this->empresasAcceso()->where('activo', true)->whereIn('plan', $planKeys)->first();
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function empresasAcceso(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class, 'empresa_user_access', 'user_id', 'empresa_id')
            ->withPivot('rol')
            ->withTimestamps();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'empresa_id',
        'name',
        'email',
        'password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function isOnline(): bool
    {
        return \DB::table('sessions')
            ->where('user_id', $this->id)
            ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
            ->exists();
    }

    public function supportChats(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupportChat::class);
    }
}
