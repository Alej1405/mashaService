<?php

namespace App\Observers;

use App\Models\Empresa;

class EmpresaObserver
{
    /**
     * Handle the Empresa "created" event.
     */
    public function created(Empresa $empresa): void
    {
        $this->cloneAccountsForEmpresa($empresa, ['base']);
        
        $modules = [];
        if ($empresa->tiene_logistica) $modules[] = 'logistica';
        if ($empresa->tiene_comercio_exterior) $modules[] = 'comercio_exterior';
        if ($empresa->tipo_operacion_productos) $modules[] = 'productos';
        if ($empresa->tipo_operacion_servicios) $modules[] = 'servicios';
        if ($empresa->tipo_operacion_manufactura) $modules[] = 'manufactura';

        if (!empty($modules)) {
            $this->cloneAccountsForEmpresa($empresa, $modules);
        }

        $this->cloneAccountingMapsForEmpresa($empresa);
    }

    protected function cloneAccountingMapsForEmpresa(Empresa $empresa): void
    {
        $baseMaps = \App\Models\AccountingMap::withoutGlobalScopes()->whereNull('empresa_id')->get();

        foreach ($baseMaps as $baseMap) {
            // Ubicar la cuenta equivalente en esta empresa
            $baseAccount = \App\Models\AccountPlan::withoutGlobalScopes()->find($baseMap->account_plan_id);
            if (!$baseAccount) continue;

            $empresaAccount = \App\Models\AccountPlan::where('empresa_id', $empresa->id)
                ->where('code', $baseAccount->code)
                ->first();

            if ($empresaAccount) {
                \App\Models\AccountingMap::firstOrCreate([
                    'empresa_id' => $empresa->id,
                    'tipo_item' => $baseMap->tipo_item,
                    'tipo_movimiento' => $baseMap->tipo_movimiento,
                    'account_plan_id' => $empresaAccount->id,
                ]);
            }
        }
    }

    /**
     * Handle the Empresa "updated" event.
     */
    public function updated(Empresa $empresa): void
    {
        $modulesToEnable = [];
        $modulesToDisable = [];

        $checks = [
            'tiene_logistica' => 'logistica',
            'tiene_comercio_exterior' => 'comercio_exterior',
            'tipo_operacion_productos' => 'productos',
            'tipo_operacion_servicios' => 'servicios',
            'tipo_operacion_manufactura' => 'manufactura',
        ];

        foreach ($checks as $field => $modulo) {
            if ($empresa->wasChanged($field)) {
                if ($empresa->$field) {
                    $modulesToEnable[] = $modulo;
                } else {
                    $modulesToDisable[] = $modulo;
                }
            }
        }

        if (!empty($modulesToEnable)) {
            $this->cloneAccountsForEmpresa($empresa, $modulesToEnable);
        }

        if (!empty($modulesToDisable)) {
            $this->deactivateAccountsForEmpresa($empresa, $modulesToDisable);
        }
    }

    protected function cloneAccountsForEmpresa(Empresa $empresa, array $modules): void
    {
        $baseAccounts = \App\Models\AccountPlan::whereNull('empresa_id')
            ->whereIn('modulo', $modules)
            ->get();

        foreach ($baseAccounts as $account) {
            \App\Models\AccountPlan::updateOrCreate(
                ['empresa_id' => $empresa->id, 'code' => $account->code],
                [
                    'name' => $account->name,
                    'type' => $account->type,
                    'nature' => $account->nature,
                    'parent_code' => $account->parent_code,
                    'level' => $account->level,
                    'accepts_movements' => $account->accepts_movements,
                    'modulo' => $account->modulo,
                    'is_active' => true,
                ]
            );
        }
    }

    protected function deactivateAccountsForEmpresa(Empresa $empresa, array $modules): void
    {
        $accounts = \App\Models\AccountPlan::where('empresa_id', $empresa->id)
            ->whereIn('modulo', $modules)
            ->get();

        foreach ($accounts as $account) {
            // Lógica para prevenir desactivación si hay movimientos
            if ($this->hasMovements($account)) {
                continue;
            }
            $account->update(['is_active' => false]);
        }
    }

    protected function hasMovements($account): bool
    {
        // Por ahora retorna false ya que no hay tabla de movimientos contables implementada
        // Pero queda la estructura para futura integración
        return false;
    }
}
