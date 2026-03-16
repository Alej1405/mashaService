<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class EmpresaScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $empresaId = null;

        if (function_exists('filament') && filament()->getTenant()) {
            $empresaId = filament()->getTenant()->id;
        } elseif (auth()->check()) {
            $empresaId = auth()->user()->empresa_id;
        }

        if ($empresaId) {
            $builder->where('empresa_id', $empresaId);
        }
    }
}
