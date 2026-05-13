<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Observer genérico para todos los modelos CMS.
 * Invalida las claves de caché afectadas cuando un registro cambia.
 */
class CmsObserver
{
    /** Mapa: clase del modelo → claves de caché a invalidar (sin el prefijo cms:{slug}:) */
    private const KEYS_MAP = [
        \App\Models\CmsHero::class         => ['hero', 'all'],
        \App\Models\CmsAbout::class        => ['about', 'all'],
        \App\Models\CmsService::class      => ['services', 'all'],
        \App\Models\CmsProduct::class      => ['products', 'all'],
        \App\Models\CmsTeamMember::class   => ['team', 'all'],
        \App\Models\CmsClientLogo::class   => ['clients', 'all'],
        \App\Models\CmsTestimonial::class  => ['testimonials', 'all'],
        \App\Models\CmsFaq::class          => ['faq', 'all'],
        \App\Models\CmsContact::class      => ['contact', 'all'],
        \App\Models\CmsTerminos::class     => ['terminos', 'all'],
        \App\Models\CmsPost::class         => ['posts', 'all'],
    ];

    public function saved(Model $model): void
    {
        $this->flush($model);
    }

    public function deleted(Model $model): void
    {
        $this->flush($model);
    }

    private function flush(Model $model): void
    {
        $slug = $this->slug($model);
        if (! $slug) {
            return;
        }

        $keys = self::KEYS_MAP[get_class($model)] ?? ['all'];

        foreach ($keys as $key) {
            Cache::forget("cms:{$slug}:{$key}");
        }

        // Para CmsPost también invalida el caché del post individual
        if ($model instanceof \App\Models\CmsPost && $model->slug) {
            Cache::forget("cms:{$slug}:post:{$model->slug}");
        }
    }

    private function slug(Model $model): ?string
    {
        $empresa = $model->empresa ?? \App\Models\Empresa::find($model->empresa_id);
        return $empresa?->slug;
    }
}
