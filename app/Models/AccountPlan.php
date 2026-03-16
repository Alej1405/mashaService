<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountPlan extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'code',
        'name',
        'type',
        'nature',
        'parent_code',
        'level',
        'accepts_movements',
        'modulo',
        'is_active',
    ];

    public function scopeActivas(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeMovable(Builder $query): void
    {
        $query->where('accepts_movements', true);
    }

    public function scopeByModulo(Builder $query, string $modulo): void
    {
        $query->where('modulo', $modulo);
    }
}
