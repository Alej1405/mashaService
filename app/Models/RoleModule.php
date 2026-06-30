<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asociación rol ↔ módulo. Cada fila = un módulo visible para un rol.
 * module_key referencia una clave del catálogo config('erp_features').
 */
class RoleModule extends Model
{
    protected $table = 'role_module';

    protected $fillable = ['role_id', 'module_key'];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
