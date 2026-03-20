<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailingContact extends Model
{
    use HasFactory, HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'email',
        'telefono',
        'notas',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
