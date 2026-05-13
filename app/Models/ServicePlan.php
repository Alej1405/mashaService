<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePlan extends Model
{
    protected $fillable = [
        'key',
        'nombre',
        'descripcion',
        'caracteristicas',
        'sort_order',
    ];

    protected $casts = [
        'caracteristicas' => 'array',
    ];
}
