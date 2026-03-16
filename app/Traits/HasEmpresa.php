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
            if (auth()->check() && empty($model->empresa_id)) {
                $model->empresa_id = auth()->user()->empresa_id;
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
