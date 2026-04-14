<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsConsignatario extends Model
{
    use HasEmpresa;

    protected $table = 'logistics_consignatarios';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'cedula_pasaporte',
        'email',
        'telefono',
        'direccion_destino',
        'valor_declarado_acumulado',
        'total_embarques',
        'notas',
    ];

    protected $casts = [
        'valor_declarado_acumulado' => 'decimal:2',
        'total_embarques'           => 'integer',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(LogisticsShipment::class, 'consignatario_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(LogisticsPackage::class, 'consignatario_id');
    }

    /**
     * Recalcula el acumulado sumando todos los embarques entregados.
     */
    public function recalcularAcumulado(): void
    {
        $total = $this->shipments()
            ->whereIn('estado', ['entregada', 'autorizado_salida', 'pagada', 'liquidada'])
            ->sum('valor_total_declarado');

        $count = $this->shipments()->count();

        $this->update([
            'valor_declarado_acumulado' => $total,
            'total_embarques'           => $count,
        ]);
    }
}
