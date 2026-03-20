<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

class CmsClientLogo extends Model
{
    use HasEmpresa;

    protected $table = 'cms_client_logos';

    protected $fillable = [
        'empresa_id', 'nombre', 'logo', 'url', 'sort_order', 'activo',
    ];

    protected $casts = ['activo' => 'boolean', 'sort_order' => 'integer'];
}
