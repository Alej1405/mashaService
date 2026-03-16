<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasEmpresa;

class MeasurementUnit extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'abreviatura',
        'activo',
    ];
}
