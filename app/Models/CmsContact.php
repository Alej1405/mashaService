<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

class CmsContact extends Model
{
    use HasEmpresa;

    protected $table = 'cms_contacts';

    protected $fillable = [
        'empresa_id', 'direccion', 'telefono', 'email', 'whatsapp',
        'mapa_embed', 'facebook', 'instagram', 'linkedin', 'youtube', 'tiktok', 'activo',
    ];

    protected $casts = ['activo' => 'boolean'];
}
