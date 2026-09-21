<?php

namespace App\Observers;

use App\Models\SitioCasoImagen;
use App\Models\Empresa;
use App\Support\SitioPropio;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer unico para los modelos del sitio. No invalida clave por clave:
 * renueva el sello de la empresa y con eso cae todo el cache de ese sitio.
 */
class SitioObserver
{
    public function saved(Model $model): void
    {
        $this->renovar($model);
    }

    public function deleted(Model $model): void
    {
        $this->renovar($model);
    }

    private function renovar(Model $model): void
    {
        $slug = $this->slug($model);

        if ($slug) {
            SitioPropio::renovarSello($slug);
        }
    }

    private function slug(Model $model): ?string
    {
        // La imagen de galeria no tiene empresa_id: cuelga de su caso.
        if ($model instanceof SitioCasoImagen) {
            $model->loadMissing('caso.empresa');
            return $model->caso?->empresa?->slug;
        }

        $empresa = $model->empresa ?? Empresa::find($model->empresa_id);

        return $empresa?->slug;
    }
}
