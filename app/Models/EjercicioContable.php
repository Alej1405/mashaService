<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

/** Ejercicio anual. Una vez cerrado, no admite asientos. */
class EjercicioContable extends Model
{
    use HasEmpresa;

    protected $table = 'ejercicios_contables';

    protected $fillable = ['empresa_id','anio','cerrado_en','cerrado_por','asiento_cierre_id','resultado'];

    protected $casts = ['cerrado_en' => 'datetime', 'resultado' => 'decimal:2'];

    public function getCerradoAttribute(): bool
    {
        return $this->cerrado_en !== null;
    }
}
