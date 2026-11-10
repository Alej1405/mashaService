<?php

namespace App\Filament\Concerns;

/**
 * Al crear o guardar, el formulario regresa a la tabla del recurso. Regla del ERP:
 * ningún formulario se queda abierto después de guardar.
 */
trait RegresaAlListado
{
    protected function getRedirectUrl(): string
    {
        $recurso = static::getResource();

        return $recurso::hasPage('index') ? $recurso::getUrl('index') : parent::getRedirectUrl();
    }
}
