<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * La traducción del ERP a un código del SRI.
 *
 * Una fila dice: "la forma de pago 'transferencia' de un gasto es el código 20
 * de la tabla 13 del ATS". Con empresa_id nulo es regla del sistema; con
 * empresa_id, el ajuste de esa empresa, que manda sobre la del sistema.
 */
class MapaSri extends Model
{
    protected $table = 'mapa_sri';

    protected $fillable = ['empresa_id', 'entidad', 'clave', 'anexo', 'tabla', 'codigo'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
