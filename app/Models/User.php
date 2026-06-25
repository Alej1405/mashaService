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

    /** Niveles de plan reutilizados en los métodos de tenant. */
    private const PLAN_LEVELS = ['basic' => 1, 'pro' => 2, 'enterprise' => 3];

    /** Planes elegibles para un panel (plan del tenant >= nivel del panel). */
    private function eligiblePlans(string $panelId): array
    {
        $min = self::PLAN_LEVELS[$panelId] ?? 1;
        return array_keys(array_filter(self::PLAN_LEVELS, fn($l) => $l >= $min));
    }

    /** Paneles que usan roles de acceso en lugar de niveles de plan. */
    private const ROLE_BASED_PANELS = [
        'cms'        => ['admin_empresa', 'marketing', 'cms_editor'],
        'ecommerce'  => ['admin_empresa', 'ecommerce_manager'],
    ];

    public function getTenants(Panel $panel): Collection
    {
        $panelId = $panel->getId();

        // Paneles basados en roles: retornar todas las empresas activas a las que el usuario tenga acceso
        if (isset(self::ROLE_BASED_PANELS[$panelId])) {
            if ($this->hasRole('super_admin')) {
                return Empresa::where('activo', true)->get();
            }
            return $this->empresasAcceso()->where('activo', true)->get();
        }

        $eligible = $this->eligiblePlans($panelId);

        if ($this->hasRole('super_admin')) {
            return Empresa::where('activo', true)->whereIn('plan', $eligible)->get();
        }

        return $this->empresasAcceso()
            ->where('activo', true)
            ->whereIn('plan', $eligible)
            ->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        $segment = request()->segment(1) ?? '';

        // Paneles basados en roles (cms, store)
        if (isset(self::ROLE_BASED_PANELS[$segment]) || $segment === 'store') {
            if (! $tenant->activo) return false;
            if ($this->hasRole('super_admin')) return true;
            return $this->empresasAcceso()->where('empresas.id', $tenant->id)->exists();
        }

        $pathToLevel = ['app' => 1, 'pro' => 2, 'enterprise' => 3, 'logistics' => 3];
        $panelLevel  = $pathToLevel[$segment] ?? 1;
        $tenantLevel = self::PLAN_LEVELS[$tenant->plan ?? 'basic'] ?? 1;

        if ($tenantLevel < $panelLevel) {
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

        $panelMap      = ['basic' => 1, 'pro' => 2, 'enterprise' => 3, 'logistics' => 3];
        $panelLevel    = $panelMap[$panelId] ?? 99;
        $eligiblePlans = array_keys(array_filter(
            self::PLAN_LEVELS,
            fn ($l) => $l >= $panelLevel
        ));

        return $this->empresasAcceso()
            ->where('activo', true)
            ->whereIn('plan', $eligiblePlans)
            ->exists();
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

        $eligible = $this->eligiblePlans($panelId);

        if ($this->hasRole('super_admin')) {
            return $this->empresa
                ?? Empresa::where('activo', true)->whereIn('plan', $eligible)->first();
        }

        // Preferir la empresa primaria si califica
        if ($this->empresa && in_array($this->empresa->plan ?? 'basic', $eligible)) {
            return $this->empresa;
        }

        return $this->empresasAcceso()
            ->where('activo', true)
            ->whereIn('plan', $eligible)
            ->first();
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
