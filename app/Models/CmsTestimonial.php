<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

class CmsTestimonial extends Model
{
    use HasEmpresa;

    protected $table = 'cms_testimonials';

    protected $fillable = [
        'empresa_id', 'autor_nombre', 'autor_cargo', 'autor_empresa',
        'autor_foto', 'contenido', 'estrellas', 'sort_order', 'activo',
    ];

    protected $casts = [
        'activo'     => 'boolean',
        'sort_order' => 'integer',
        'estrellas'  => 'integer',
    ];
}
