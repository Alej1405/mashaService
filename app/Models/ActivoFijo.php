<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Maquinaria y equipo: no se consume, se deprecia. */
class ActivoFijo extends Model
{
    use HasEmpresa;

    protected $table = 'activos_fijos';

    protected $fillable = [
        'empresa_id','codigo','nombre','categoria','ubicacion','fecha_compra','costo','valor_residual',
        'vida_util_meses','depreciacion_acumulada','cuenta_activo_id','cuenta_depreciacion_id','cuenta_gasto_id','activo',
    ];

    protected $casts = [
        'fecha_compra' => 'date', 'costo' => 'decimal:2', 'valor_residual' => 'decimal:2',
        'depreciacion_acumulada' => 'decimal:2', 'activo' => 'boolean',
    ];

    public function depreciaciones(): HasMany { return $this->hasMany(Depreciacion::class); }
    public function cuentaGasto(): BelongsTo { return $this->belongsTo(AccountPlan::class, 'cuenta_gasto_id'); }

    /** Línea recta sobre el costo menos el residual. */
    public function getCuotaMensualAttribute(): float
    {
        if ($this->vida_util_meses <= 0) {
            return 0.0;
        }

        return round(((float) $this->costo - (float) $this->valor_residual) / $this->vida_util_meses, 2);
    }

    public function getValorLibrosAttribute(): float
    {
        return round((float) $this->costo - (float) $this->depreciacion_acumulada, 2);
    }

    public function getDepreciadoCompletoAttribute(): bool
    {
        return $this->valor_libros <= (float) $this->valor_residual + 0.01;
    }
}
