<?php

namespace App\Filament\Admin\Resources\EmpresaServiciosResource\Pages;

use App\Filament\Admin\Resources\EmpresaServiciosResource;
use App\Models\ServicePlan;
use App\Services\EmpresaFeaturesService;
use App\Shared\Actions\AplicarPlanAEmpresa;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEmpresaServicios extends EditRecord
{
    protected static string $resource = EmpresaServiciosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Aplicar el template del plan actual a los módulos de la empresa
            Actions\Action::make('aplicar_plan')
                ->label('Aplicar template del plan')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form(function (): array {
                    $record = $this->getRecord();
                    $planes = ServicePlan::orderBy('sort_order')
                        ->get(['id', 'key', 'nombre', 'modules_template'])
                        ->mapWithKeys(fn ($p) => [$p->id => $p->nombre])
                        ->toArray();

                    return [
                        Forms\Components\Select::make('service_plan_id')
                            ->label('Plan a aplicar')
                            ->options($planes)
                            ->default(
                                ServicePlan::where('key', $record->plan)->value('id')
                            )
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText('Los módulos de la empresa se sincronizarán con el template de este plan.'),

                        Forms\Components\Placeholder::make('preview')
                            ->label('Cambios que se aplicarán')
                            ->content(function (Forms\Get $get): string {
                                $planId = $get('service_plan_id');
                                if (! $planId) return 'Selecciona un plan para ver el preview.';

                                $plan    = ServicePlan::find($planId);
                                $preview = app(AplicarPlanAEmpresa::class)->preview($plan);

                                $activar   = implode(', ', $preview['activos'])   ?: 'ninguno';
                                $desactivar = implode(', ', $preview['inactivos']) ?: 'ninguno';

                                return "Activar: {$activar}\nDesactivar: {$desactivar}";
                            }),
                    ];
                })
                ->action(function (array $data): void {
                    $plan = ServicePlan::findOrFail($data['service_plan_id']);
                    app(AplicarPlanAEmpresa::class)->handle($this->getRecord(), $plan);

                    Notification::make()
                        ->title("Plan {$plan->nombre} aplicado")
                        ->body('Los módulos de la empresa se sincronizaron con el template del plan.')
                        ->success()
                        ->send();

                    $this->fillForm();
                }),

            // Acceso rápido a la gestión granular de sub-features
            Actions\Action::make('modulos_detallados')
                ->label('Módulos detallados')
                ->icon('heroicon-o-squares-2x2')
                ->color('gray')
                ->url(fn (): string => EmpresaServiciosResource::getUrl('features', ['record' => $this->getRecord()])),
        ];
    }

    /**
     * Inicializa features para que los toggles del form tengan binding correcto.
     * Solo añade claves faltantes; nunca sobreescribe el estado existente.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $features = $data['features'] ?? [];

        foreach (array_keys(config('erp_features', [])) as $module) {
            if (! isset($features[$module])) {
                $features[$module] = ['activo' => false];
            } elseif (! array_key_exists('activo', $features[$module])) {
                $features[$module]['activo'] = false;
            }
        }

        $data['features'] = $features;
        return $data;
    }

    /**
     * Fusiona features del form con las del registro en BD.
     * Los toggles live ya actualizaron el JSONB vía setModule(); aquí solo
     * garantizamos que el save no sobrescriba sub-features no presentes en el form.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $fresh    = $this->getRecord()->fresh()->features ?? [];
        $incoming = $data['features'] ?? [];

        // Solo actualizar .activo por módulo; preservar todas las sub-features
        foreach ($incoming as $module => $moduleData) {
            if (array_key_exists('activo', $moduleData)) {
                $fresh[$module] = array_merge($fresh[$module] ?? [], ['activo' => $moduleData['activo']]);
            }
        }

        $data['features'] = $fresh;

        // Sincronizar columnas booleanas legacy (mismo mapeo que EmpresaFeaturesService)
        $data['servicio_mailing_activo']    = (bool) data_get($fresh, 'marketing.mailing.activo', false);
        $data['servicio_cms_activo']        = (bool) data_get($fresh, 'marketing.cms.activo', false);
        $data['tipo_operacion_productos']   = (bool) data_get($fresh, 'inventario.activo', false);
        $data['tipo_operacion_servicios']   = (bool) data_get($fresh, 'produccion.diseno_servicios', false);
        $data['tipo_operacion_manufactura'] = (bool) data_get($fresh, 'produccion.activo', false);
        $data['tiene_logistica']            = (bool) data_get($fresh, 'logistica.activo', false);
        $data['tiene_comercio_exterior']    = (bool) data_get($fresh, 'logistica.comercio_exterior', false);

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Empresa actualizada correctamente';
    }
}
