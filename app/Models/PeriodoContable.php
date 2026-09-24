<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

/**
 * Un mes contable. Cerrado, no admite movimientos: es lo que ya se presentó
 * a la Superintendencia y no puede cambiar bajo los pies del contador.
 */
class PeriodoContable extends Model
{
    use HasEmpresa;

    protected $table = 'periodos_contables';

    protected $fillable = ['empresa_id', 'anio', 'mes', 'cerrado_en', 'cerrado_por', 'declaracion_id', 'motivo'];

    protected $casts = ['anio' => 'integer', 'mes' => 'integer', 'cerrado_en' => 'datetime'];

    public function estaCerrado(): bool
    {
        return $this->cerrado_en !== null;
    }
}
