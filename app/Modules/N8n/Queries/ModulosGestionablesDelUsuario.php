<?php

namespace App\Modules\N8n\Queries;

use App\Models\Empresa;
use App\Models\Panel;
use App\Models\Role;
use App\Models\User;
use App\Shared\Attributes\Documentado;

/**
 * Módulos que un usuario puede gestionar en una empresa dada = intersección
 * Plan (plan_panel) ∩ Rol (role_module), incluyendo los paneles role-based
 * (cms, ecommerce). Réplica de la lógica de PanelAccess pero con usuario/empresa
 * explícitos (la API n8n no tiene contexto de Filament::getTenant()/auth()).
 */
#[Documentado(
    grupo: 'Integración n8n',
    descripcion: 'Calcula los módulos que un usuario puede gestionar en una empresa (plan ∩ rol).',
    tipo: 'query',
)]
final class ModulosGestionablesDelUsuario
{
    /** @return list<string> claves de módulo (config/erp_features) */
    public function handle(User $user, Empresa $empresa): array
    {
        $isSuper = $user->hasRole('super_admin');

        $rolName = $user->empresasAcceso()
            ->where('empresas.id', $empresa->id)
            ->first()?->pivot->rol
            ?: $user->getRoleNames()->first();

        $rolModules = $isSuper ? null : (Role::where('name', $rolName)->first()?->moduleKeys() ?? []);

        $candidatos = collect($empresa->servicePlan?->panels()->where('activo', true)->get() ?? collect());

        $roleBased = User::roleBasedPanels();
        foreach ($roleBased as $key => $roles) {
            if ($isSuper || in_array($rolName, $roles, true)) {
                $panel = Panel::where('key', $key)->where('activo', true)->first();
                if ($panel && ! $candidatos->contains('id', $panel->id)) {
                    $candidatos->push($panel);
                }
            }
        }

        $keys = [];
        foreach ($candidatos as $panel) {
            $modulosPanel = $panel->moduleKeys();
            $esRoleBased = isset($roleBased[$panel->key]);

            $visibles = ($rolModules === null || $esRoleBased)
                ? $modulosPanel
                : array_values(array_intersect($modulosPanel, $rolModules));

            if (empty($visibles) && ! $esRoleBased && ! $isSuper) {
                continue;
            }

            $keys = array_merge($keys, $visibles);
        }

        return array_values(array_unique($keys));
    }
}
