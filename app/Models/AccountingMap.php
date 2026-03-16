<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingMap extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'tipo_item',
        'tipo_movimiento',
        'account_plan_id',
    ];

    public function accountPlan(): BelongsTo
    {
        return $this->belongsTo(AccountPlan::class);
    }

    /**
     * Retorna la cuenta contable mapeada.
     */
    public static function getCuenta(?int $empresaId, string $tipoItem, string $tipoMovimiento): ?AccountPlan
    {
        // Buscar mapeo para la empresa específica
        $map = self::where('empresa_id', $empresaId)
            ->where('tipo_item', $tipoItem)
            ->where('tipo_movimiento', $tipoMovimiento)
            ->first();

        // Si no lo encuentra para la empresa actual, busca el base (empresa_id = null) opcional.
        // Como copiamos esto al crearse la empresa, debe encontrarlo en la misma.
        if (!$map && $empresaId) {
            $map = self::whereNull('empresa_id')
                ->where('tipo_item', $tipoItem)
                ->where('tipo_movimiento', $tipoMovimiento)
                ->first();
        }

        return $map ? $map->accountPlan : null;
    }
}
