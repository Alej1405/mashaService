<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

class CartaPresentacion extends Model
{
    use HasEmpresa;

    protected $table = 'carta_presentaciones';

    protected $fillable = [
        'empresa_id',
        'asunto',
        'saludo',
        'intro',
        'servicios_titulo',
        'cierre',
        'firma_nombre',
        'firma_cargo',
        'color_primario',
        'color_acento',
        'color_texto',
        'color_fondo',
    ];
}
