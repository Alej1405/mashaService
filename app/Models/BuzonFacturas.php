<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

/**
 * El correo al que llegan las facturas electrónicas de la empresa (el que tiene
 * registrado en el SRI). n8n lo lee con un flujo y una credencial IMAP propios.
 * La contraseña se guarda cifrada y nunca se devuelve en una respuesta.
 */
class BuzonFacturas extends Model
{
    use HasEmpresa;

    protected $table = 'buzones_facturas';

    protected $fillable = [
        'empresa_id', 'host', 'puerto', 'usuario', 'password', 'activo', 'autorizado_por', 'autorizado_en',
        'n8n_credencial_id', 'n8n_flujo_id', 'sincronizado_en', 'ultimo_error',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password'        => 'encrypted',
        'activo'          => 'boolean',
        'puerto'          => 'integer',
        'sincronizado_en' => 'datetime',
        'autorizado_en'   => 'datetime',
    ];
}
