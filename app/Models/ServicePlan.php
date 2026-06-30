<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServicePlan extends Model
{
    protected $fillable = [
        'key',
        'nombre',
        'descripcion',
        'caracteristicas',
        'modules_template',
        'sort_order',
    ];

    protected $casts = [
        'caracteristicas'  => 'array',
        'modules_template' => 'array',
    ];

    /**
     * Paneles que abre este plan (N:M). El acceso a un panel se decide
     * consultando esta relación, no niveles cableados.
     */
    public function panels(): BelongsToMany
    {
        return $this->belongsToMany(Panel::class, 'plan_panel');
    }

    /** Claves de los paneles que abre este plan. */
    public function panelKeys(): array
    {
        return $this->panels()->pluck('key')->all();
    }

    /** Retorna los módulos activos del template como array de keys. */
    public function modulosActivos(): array
    {
        return array_keys(array_filter($this->modules_template ?? [], fn ($v) => $v === true));
    }

    /** True si el plan incluye el módulo indicado. O(1) por key. */
    public function incluyeModulo(string $modulo): bool
    {
        return ($this->modules_template ?? [])[$modulo] ?? false;
    }
}
