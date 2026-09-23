<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

/** Accionista o socio. Su nómina es anexo obligatorio ante la SCVS. */
class Socio extends Model
{
    use HasEmpresa;

    protected $fillable = ['empresa_id','tipo_identificacion','identificacion','nombre','nacionalidad','participacion','capital','activo'];

    protected $casts = ['participacion' => 'decimal:2', 'capital' => 'decimal:2', 'activo' => 'boolean'];
}
