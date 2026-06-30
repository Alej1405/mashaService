<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Panel = contenedor de visibilidad. Define qué módulos se muestran en el menú.
 *
 * Los módulos siempre funcionan en segundo plano (Observers, AccountingService);
 * el panel solo controla la visibilidad de sus opciones en la navegación.
 */
class Panel extends Model
{
    protected $table = 'panels';

    protected $fillable = [
        'key', 'name', 'path', 'color', 'icon', 'activo', 'sort',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'sort'   => 'integer',
    ];

    public function modules(): HasMany
    {
        return $this->hasMany(PanelModule::class);
    }

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class);
    }

    /** Planes que abren este panel (N:M). */
    public function servicePlans(): BelongsToMany
    {
        return $this->belongsToMany(ServicePlan::class, 'plan_panel');
    }

    /** Claves de los módulos visibles en este panel. */
    public function moduleKeys(): array
    {
        return $this->modules()->pluck('module_key')->all();
    }

    /** ¿Este panel muestra el módulo dado? */
    public function showsModule(string $moduleKey): bool
    {
        return $this->modules()->where('module_key', $moduleKey)->exists();
    }
}
