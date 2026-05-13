<?php

namespace App\Traits;

use App\Models\Empresa;
use App\Models\Scopes\EmpresaScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasEmpresa
{
    public static function bootHasEmpresa(): void
    {
        static::addGlobalScope(new EmpresaScope);
        
        static::creating(function ($model) {
            if (empty($model->empresa_id)) {
                $tenantId = function_exists('filament') && filament()->getTenant()
                    ? filament()->getTenant()->id
                    : (auth()->check() ? auth()->user()->empresa_id : null);

                if ($tenantId) {
                    $model->empresa_id = $tenantId;
                }
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
