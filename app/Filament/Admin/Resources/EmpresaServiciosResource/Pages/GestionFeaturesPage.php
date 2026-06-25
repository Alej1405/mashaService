<?php

namespace App\Filament\Admin\Resources\EmpresaServiciosResource\Pages;

use App\Filament\Admin\Resources\EmpresaServiciosResource;
use App\Services\EmpresaFeaturesService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class GestionFeaturesPage extends EditRecord
{
    protected static string $resource = EmpresaServiciosResource::class;

    protected static ?string $title = 'Gestión de Módulos';

    protected static ?string $breadcrumb = 'Módulos';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('activar_todo')
                ->label('Activar todo')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Activar todos los módulos')
                ->modalDescription('Se activarán todos los módulos y sub-features de esta empresa.')
                ->action(function (): void {
                    $service  = app(EmpresaFeaturesService::class);
                    $modules  = array_keys(config('erp_features', []));
                    foreach ($modules as $module) {
                        $service->setModule($this->getRecord(), $module, true);
                    }
                    Notification::make()->title('Todos los módulos activados')->success()->send();
                    $this->fillForm();
                }),

            \Filament\Actions\Action::make('desactivar_todo')
                ->label('Desactivar todo')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Desactivar todos los módulos')
                ->modalDescription('Se desactivarán todos los módulos de esta empresa. El acceso al ERP quedará restringido.')
                ->action(function (): void {
                    $service = app(EmpresaFeaturesService::class);
                    $modules = array_keys(config('erp_features', []));
                    foreach ($modules as $module) {
                        $service->setModule($this->getRecord(), $module, false);
                    }
                    Notification::make()->title('Todos los módulos desactivados')->warning()->send();
                    $this->fillForm();
                }),
        ];
    }

    public function form(Form $form): Form
    {
        $tabs = [];

        foreach (config('erp_features', []) as $moduleKey => $moduleConfig) {
            $tabs[] = $this->buildModuleTab($moduleKey, $moduleConfig);
        }

        return $form->schema([
            Forms\Components\Tabs::make('Módulos')
                ->tabs($tabs)
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ]);
    }

    private function buildModuleTab(string $moduleKey, array $config): Forms\Components\Tabs\Tab
    {
        $features    = $config['features'] ?? [];
        $icon        = $config['icon']  ?? 'heroicon-o-squares-2x2';
        $label       = $config['label'] ?? ucfirst($moduleKey);
        $descripcion = $config['descripcion'] ?? '';

        // Separar features planas de sub-grupos (las que tienen punto son anidadas)
        $simpleFeatures = [];
        $subGroups      = [];

        foreach ($features as $featureKey => $featureLabel) {
            if (str_contains($featureKey, '.')) {
                [$group] = explode('.', $featureKey, 2);
                $subGroups[$group][$featureKey] = $featureLabel;
            } else {
                $simpleFeatures[$featureKey] = $featureLabel;
            }
        }

        $schema = [];

        // Toggle maestro del módulo completo
        $schema[] = Forms\Components\Section::make()
            ->schema([
                Forms\Components\Toggle::make("features.{$moduleKey}.activo")
                    ->label("Módulo {$label} completo")
                    ->helperText($descripcion)
                    ->onColor('success')
                    ->live()
                    ->afterStateUpdated(function (bool $state) use ($moduleKey): void {
                        app(EmpresaFeaturesService::class)
                            ->setModule($this->getRecord(), $moduleKey, $state);
                        Notification::make()
                            ->title($state ? "Módulo {$moduleKey} activado" : "Módulo {$moduleKey} desactivado")
                            ->success()
                            ->send();
                    })
                    ->columnSpanFull(),
            ])
            ->compact();

        // Features simples (sin punto)
        if (! empty($simpleFeatures)) {
            $toggles = [];
            foreach ($simpleFeatures as $fKey => $fLabel) {
                $toggles[] = Forms\Components\Toggle::make("features.{$moduleKey}.{$fKey}")
                    ->label($fLabel)
                    ->onColor('success')
                    ->live()
                    ->afterStateUpdated(function (bool $state) use ($moduleKey, $fKey): void {
                        app(EmpresaFeaturesService::class)
                            ->setFeature($this->getRecord(), "{$moduleKey}.{$fKey}", $state);
                    });
            }
            $schema[] = Forms\Components\Section::make('Sub-features')
                ->schema($toggles)
                ->columns(3)
                ->compact();
        }

        // Sub-grupos (ej: cms.*, mailing.*)
        foreach ($subGroups as $groupKey => $groupFeatures) {
            $groupLabel   = ucwords(str_replace('_', ' ', $groupKey));
            $toggles      = [];

            // Toggle maestro del sub-grupo
            $toggles[] = Forms\Components\Toggle::make("features.{$moduleKey}.{$groupKey}.activo")
                ->label("{$groupLabel} (activar todo el sub-módulo)")
                ->onColor('success')
                ->live()
                ->afterStateUpdated(function (bool $state) use ($moduleKey, $groupKey, $groupFeatures): void {
                    $service = app(EmpresaFeaturesService::class);
                    $service->setFeature($this->getRecord(), "{$moduleKey}.{$groupKey}.activo", $state);
                    foreach (array_keys($groupFeatures) as $fPath) {
                        $service->setFeature($this->getRecord(), "{$moduleKey}.{$fPath}", $state);
                    }
                    $this->syncBooleanForGroup("{$moduleKey}.{$groupKey}.activo", $state);
                })
                ->columnSpanFull();

            // Toggles individuales del sub-grupo (excepto 'activo' que ya es el maestro)
            foreach ($groupFeatures as $fPath => $fLabel) {
                $subKey = explode('.', $fPath, 2)[1] ?? $fPath;
                if ($subKey === 'activo') continue;
                $toggles[] = Forms\Components\Toggle::make("features.{$moduleKey}.{$fPath}")
                    ->label($fLabel)
                    ->onColor('success')
                    ->live()
                    ->afterStateUpdated(function (bool $state) use ($moduleKey, $fPath): void {
                        app(EmpresaFeaturesService::class)
                            ->setFeature($this->getRecord(), "{$moduleKey}.{$fPath}", $state);
                    });
            }

            $schema[] = Forms\Components\Section::make($groupLabel)
                ->schema($toggles)
                ->columns(3)
                ->compact();
        }

        return Forms\Components\Tabs\Tab::make($label)
            ->icon($icon)
            ->schema($schema);
    }

    private function syncBooleanForGroup(string $path, bool $value): void
    {
        // El service ya maneja esto, pero se invoca para asegurar sync inmediato
        app(EmpresaFeaturesService::class)->setFeature($this->getRecord(), $path, $value);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // El model cast 'array' ya deserializa features correctamente.
        // Asegurar que features no sea null (empresas antiguas sin features).
        if (empty($data['features'])) {
            $data['features'] = $this->buildDefaultFeatures();
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Preservar features que no están en el form (merge con las existentes)
        $existing = $this->getRecord()->fresh()->features ?? [];
        $incoming = $data['features'] ?? [];
        $data['features'] = array_replace_recursive($existing, $incoming);

        // Sincronizar booleanos legacy para que el EmpresaObserver funcione
        $data = array_merge($data, $this->buildSyncColumns($data['features']));

        return $data;
    }

    private function buildSyncColumns(array $features): array
    {
        return [
            'servicio_mailing_activo'    => (bool) data_get($features, 'marketing.mailing.activo', false),
            'servicio_cms_activo'        => (bool) data_get($features, 'marketing.cms.activo', false),
            'tipo_operacion_productos'   => (bool) data_get($features, 'inventario.activo', false),
            'tipo_operacion_servicios'   => (bool) data_get($features, 'produccion.diseno_servicios', false),
            'tipo_operacion_manufactura' => (bool) data_get($features, 'produccion.activo', false),
            'tiene_logistica'            => (bool) data_get($features, 'logistica.activo', false),
            'tiene_comercio_exterior'    => (bool) data_get($features, 'logistica.comercio_exterior', false),
        ];
    }

    private function buildDefaultFeatures(): array
    {
        $result = [];
        foreach (config('erp_features', []) as $moduleKey => $config) {
            $result[$moduleKey] = ['activo' => false];
            foreach (array_keys($config['features'] ?? []) as $fKey) {
                data_set($result[$moduleKey], $fKey, false);
            }
        }
        return $result;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Módulos actualizados correctamente';
    }
}
