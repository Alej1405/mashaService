<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Almacen extends Model
{
    use HasEmpresa;

    protected $table = 'almacenes';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'tipo',
        'descripcion',
        'direccion',
        'responsable',
        'activo',
    ];

    public function zonas(): HasMany
    {
        return $this->hasMany(ZonaAlmacen::class);
    }

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(UbicacionAlmacen::class);
    }

    public static function tiposLabels(): array
    {
        return [
            'bodega_propia'     => 'Bodega Propia',
            'deposito_externo'  => 'Depósito Externo',
            'area_produccion'   => 'Área de Producción',
            'punto_venta'       => 'Punto de Venta',
            'transito'          => 'Tránsito',
        ];
    }
}
