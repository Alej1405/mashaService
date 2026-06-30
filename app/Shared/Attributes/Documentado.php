<?php

namespace App\Shared\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Documentado
{
    /**
     * @param string $grupo       Agrupador visible en la página de documentación (ej. "Planes", "Tienda").
     * @param string $descripcion Qué hace la clase, en una frase. En español.
     * @param string $tipo        "query" | "action"
     */
    public function __construct(
        public readonly string $grupo,
        public readonly string $descripcion,
        public readonly string $tipo = 'query',
    ) {}
}
