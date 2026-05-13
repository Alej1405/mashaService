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

    public function getTenants(Panel $panel): Collection
    {
        $eligible = $this->eligiblePlans($panel->getId());

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
        $pathToLevel = ['app' => 1, 'pro' => 2, 'enterprise' => 3, 'logistics' => 3];
        $segment     = request()->segment(1) ?? '';
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

        $panelMap     = ['basic' => 1, 'pro' => 2, 'enterprise' => 3, 'logistics' => 3];
        $panelLevel   = $panelMap[$panelId] ?? 99;
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
        $eligible = $this->eligiblePlans($panel->getId());

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
