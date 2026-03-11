<?php

namespace App\Observers;

use App\Models\Company;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        $this->cloneAccountsForCompany($company, ['base']);
        
        $modules = [];
        if ($company->tiene_logistica) $modules[] = 'logistica';
        if ($company->tiene_comercio_exterior) $modules[] = 'comercio_exterior';
        if ($company->tipo_operacion_productos) $modules[] = 'productos';
        if ($company->tipo_operacion_servicios) $modules[] = 'servicios';
        if ($company->tipo_operacion_manufactura) $modules[] = 'manufactura';

        if (!empty($modules)) {
            $this->cloneAccountsForCompany($company, $modules);
        }
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
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
            if ($company->wasChanged($field)) {
                if ($company->$field) {
                    $modulesToEnable[] = $modulo;
                } else {
                    $modulesToDisable[] = $modulo;
                }
            }
        }

        if (!empty($modulesToEnable)) {
            $this->cloneAccountsForCompany($company, $modulesToEnable);
        }

        if (!empty($modulesToDisable)) {
            $this->deactivateAccountsForCompany($company, $modulesToDisable);
        }
    }

    protected function cloneAccountsForCompany(Company $company, array $modules): void
    {
        $baseAccounts = \App\Models\AccountPlan::whereNull('company_id')
            ->whereIn('modulo', $modules)
            ->get();

        foreach ($baseAccounts as $account) {
            \App\Models\AccountPlan::updateOrCreate(
                ['company_id' => $company->id, 'code' => $account->code],
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

    protected function deactivateAccountsForCompany(Company $company, array $modules): void
    {
        $accounts = \App\Models\AccountPlan::where('company_id', $company->id)
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
