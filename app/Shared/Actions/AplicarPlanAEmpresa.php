<?php

namespace App\Shared\Actions;

use App\Models\Empresa;
use App\Models\ServicePlan;
use App\Services\EmpresaFeaturesService;
use App\Shared\Attributes\Documentado;

#[Documentado(
    grupo: 'Planes',
    descripcion: 'Aplica el template de módulos de un ServicePlan a las features JSONB de una empresa y actualiza su columna plan.',
    tipo: 'action',
)]
final class AplicarPlanAEmpresa
{
    public function __construct(
        private readonly EmpresaFeaturesService $featuresService,
    ) {}

    /**
     * Itera el modules_template del plan (array key→bool) y delega a
     * EmpresaFeaturesService::setModule() para cada módulo.
     * Complejidad: O(M) donde M = cantidad de módulos en el template (≤9).
     */
    public function handle(Empresa $empresa, ServicePlan $plan): void
    {
        $template = $plan->modules_template ?? [];

        foreach ($template as $modulo => $activo) {
            $this->featuresService->setModule($empresa, $modulo, (bool) $activo);
        }

        $empresa->update(['plan' => $plan->key]);
    }

    /**
     * Retorna un resumen de qué módulos se activarán/desactivarán,
     * útil para mostrar en el modal de confirmación antes de aplicar.
     * Usa array_filter con O(1) por key — sin iteraciones lineales de búsqueda.
     *
     * @return array{ activos: string[], inactivos: string[] }
     */
    public function preview(ServicePlan $plan): array
    {
        $template  = $plan->modules_template ?? [];
        $catalogo  = config('erp_features', []);

        $activos   = [];
        $inactivos = [];

        foreach ($template as $key => $valor) {
            $label = $catalogo[$key]['label'] ?? $key;
            $valor ? ($activos[] = $label) : ($inactivos[] = $label);
        }

        return compact('activos', 'inactivos');
    }
}
