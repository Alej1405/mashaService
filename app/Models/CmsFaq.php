<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

class CmsFaq extends Model
{
    use HasEmpresa;

    protected $table = 'cms_faqs';

    protected $fillable = [
        'empresa_id', 'pregunta', 'respuesta', 'sort_order', 'activo',
    ];

    protected $casts = ['activo' => 'boolean', 'sort_order' => 'integer'];
}
