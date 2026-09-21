<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

/**
 * Item de navegacion. 'ubicacion' incluye 'inferior', que es la barra de
 * la parte baja del movil: la marca de la casa, no un extra.
 */
class SitioNavegacion extends Model
{
    use HasEmpresa;

    protected $table = 'sitio_navegacion';

    protected $fillable = [
        'empresa_id', 'etiqueta', 'ruta', 'icono',
        'ubicacion', 'dispositivo', 'sort_order', 'activo',
    ];

    protected $casts = ['sort_order' => 'integer', 'activo' => 'boolean'];
}
