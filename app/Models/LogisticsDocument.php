<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LogisticsDocument extends Model
{
    use HasEmpresa;

    protected $table = 'logistics_documents';

    protected $fillable = [
        'empresa_id',
        'documentable_type',
        'documentable_id',
        'tipo',
        'nombre',
        'archivo_path',
        'notas',
    ];

    public const TIPOS = [
        'declaracion_aduana' => 'Declaración de Aduana',
        'factura_producto'   => 'Factura de Producto',
        'factura_servicio'   => 'Factura de Servicio',
        'foto'               => 'Fotografía',
        'otro'               => 'Otro',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
