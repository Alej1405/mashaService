<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Un código de un catálogo del SRI.
 *
 * El código por sí solo no identifica nada: el "02" de la tabla 2 del ATS es
 * una cédula en una compra, y el "02" de la tabla 1 del REBEFICS es una
 * sociedad que cotiza en bolsa. Siempre se busca por anexo + tabla.
 */
class CatalogoSri extends Model
{
    protected $table = 'catalogos_sri';

    protected $fillable = [
        'anexo', 'tabla', 'nombre_tabla', 'codigo', 'descripcion',
        'porcentaje', 'grupo', 'compatible_con', 'formula',
        'vigente_desde', 'vigente_hasta',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
    ];

    /** Lo que regía en esa fecha: el anexo de julio usa el código de julio. */
    public function scopeVigente(Builder $q, ?string $fecha = null): Builder
    {
        $fecha ??= now()->toDateString();

        return $q->whereDate('vigente_desde', '<=', $fecha)
            ->where(fn ($s) => $s->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $fecha));
    }

    public function scopeDe(Builder $q, string $anexo, string $tabla): Builder
    {
        return $q->where('anexo', $anexo)->where('tabla', $tabla);
    }

    /** Los códigos de otra tabla que este admite, como lista. */
    public function compatibles(): array
    {
        if (! $this->compatible_con) {
            return [];
        }

        return array_values(array_filter(array_map(
            'trim',
            preg_split('/[,·]/', $this->compatible_con),
        ), fn ($v) => $v !== '' && $v !== '-'));
    }
}
