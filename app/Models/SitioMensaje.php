<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

/** Mensaje del formulario de un solo campo. Es la unica escritura del sitio. */
class SitioMensaje extends Model
{
    use HasEmpresa;

    protected $table = 'sitio_mensajes';

    protected $fillable = [
        'empresa_id', 'contacto', 'mensaje', 'origen',
        'ip_hash', 'user_agent', 'atendido', 'atendido_en',
    ];

    protected $casts = ['atendido' => 'boolean', 'atendido_en' => 'datetime'];
}
