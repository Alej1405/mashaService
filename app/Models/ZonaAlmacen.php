<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZonaAlmacen extends Model
{
    use HasEmpresa;

    protected $table = 'zonas_almacen';

    protected $fillable = [
        'empresa_id',
        'almacen_id',
        'codigo',
        'nombre',
        'tipo',
        'descripcion',
        'activo',
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(UbicacionAlmacen::class, 'zona_id');
    }

    public static function tiposLabels(): array
    {
        return [
            'pasillo'          => 'Pasillo',
            'estanteria'       => 'Estantería',
            'anaquel'          => 'Anaquel',
            'area_refrigerada' => 'Área Refrigerada',
            'camara_fria'      => 'Cámara Fría',
            'area_cuarentena'  => 'Área de Cuarentena',
            'area_despacho'    => 'Área de Despacho',
            'area_recepcion'   => 'Área de Recepción',
            'piso'             => 'Piso',
            'otro'             => 'Otro',
        ];
    }
}
