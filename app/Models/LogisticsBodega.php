<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsBodega extends Model
{
    use HasEmpresa;

    protected $table = 'logistics_bodegas';

    protected $fillable = [
        'empresa_id',
        'pais',
        'nombre',
        'direccion_origen',
        'ciudad',
        'estado_provincia',
        'codigo_postal',
        'empresa_aliada',
        'contacto_nombre',
        'contacto_email',
        'contacto_telefono',
        'notas',
    ];

    public const PAISES = [
        'EEUU'   => 'Estados Unidos (EEUU)',
        'ESPANA' => 'España',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(LogisticsPackage::class, 'bodega_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(LogisticsShipment::class, 'bodega_id');
    }
}
