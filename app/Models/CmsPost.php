<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CmsPost extends Model
{
    use HasEmpresa;

    protected $table = 'cms_posts';

    protected $fillable = [
        'empresa_id', 'titulo', 'slug', 'contenido', 'imagen', 'publicado_en', 'activo',
    ];

    protected $casts = [
        'activo'        => 'boolean',
        'publicado_en'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->titulo);
            }
        });
    }
}
