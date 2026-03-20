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
        'mostrar_servicios',
        'mostrar_equipo',
        'mostrar_contacto',
        'cierre',
        'firma_nombre',
        'firma_cargo',
        'color_primario',
        'color_acento',
        'color_texto',
        'color_fondo',
        'template',
    ];

    protected $casts = [
        'mostrar_servicios' => 'boolean',
        'mostrar_equipo'    => 'boolean',
        'mostrar_contacto'  => 'boolean',
    ];
}
