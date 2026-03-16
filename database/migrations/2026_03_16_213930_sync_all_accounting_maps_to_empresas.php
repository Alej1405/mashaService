<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\AccountPlan;
use App\Models\AccountingMap;
use App\Models\Empresa;

return new class extends Migration
{
    /**
     * Sincroniza TODOS los mapeos base (empresa_id=null) con cada empresa existente.
     * Para cada mapeo base, clona la cuenta destino a la empresa si no existe,
     * y luego crea el mapeo si no estaba presente.
     */
    public function up(): void
    {
        $mapasBase = AccountingMap::withoutGlobalScopes()
            ->whereNull('empresa_id')
            ->get();

        $empresas = Empresa::withoutGlobalScopes()->get();

        foreach ($empresas as $empresa) {
            foreach ($mapasBase as $mapaBase) {
                // Verificar si ya existe el mapeo en esta empresa
                $existe = AccountingMap::withoutGlobalScopes()
                    ->where('empresa_id', $empresa->id)
                    ->where('tipo_item', $mapaBase->tipo_item)
                    ->where('tipo_movimiento', $mapaBase->tipo_movimiento)
                    ->exists();

                if ($existe) {
                    continue;
                }

                // Obtener la cuenta base
                $cuentaBase = AccountPlan::withoutGlobalScopes()
                    ->whereNull('empresa_id')
                    ->where('id', $mapaBase->account_plan_id)
                    ->first();

                if (! $cuentaBase) {
                    continue;
                }

                // Buscar la cuenta equivalente en la empresa
                $cuentaEmpresa = AccountPlan::withoutGlobalScopes()
                    ->where('empresa_id', $empresa->id)
                    ->where('code', $cuentaBase->code)
                    ->first();

                // Si no existe, clonarla desde la base
                if (! $cuentaEmpresa) {
                    $cuentaEmpresa = AccountPlan::withoutGlobalScopes()->create([
                        'empresa_id'        => $empresa->id,
                        'code'              => $cuentaBase->code,
                        'name'              => $cuentaBase->name,
                        'type'              => $cuentaBase->type,
                        'nature'            => $cuentaBase->nature,
                        'parent_code'       => $cuentaBase->parent_code,
                        'level'             => $cuentaBase->level,
                        'accepts_movements' => $cuentaBase->accepts_movements,
                        'modulo'            => $cuentaBase->modulo,
                        'is_active'         => true,
                    ]);
                }

                // Crear el mapeo para esta empresa
                AccountingMap::withoutGlobalScopes()->create([
                    'empresa_id'      => $empresa->id,
                    'tipo_item'       => $mapaBase->tipo_item,
                    'tipo_movimiento' => $mapaBase->tipo_movimiento,
                    'account_plan_id' => $cuentaEmpresa->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        // No se revierten los mapeos sincronizados (operación segura)
    }
};
