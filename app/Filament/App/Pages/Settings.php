<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Facades\Filament;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.app.pages.settings';

    protected static ?string $navigationLabel = 'Configuración';

    protected static ?string $title = 'Configuración de la Empresa';

    protected static ?string $navigationGroup = 'Administración';

    public ?array $data = [];

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        $this->form->fill($tenant->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Módulos Habilitados')
                    ->description('Estado de los módulos operativos de su empresa (Gestionados por el Administrador Central).')
                    ->schema([
                        Forms\Components\Toggle::make('tipo_operacion_productos')
                            ->label('Operación Productos')
                            ->disabled(),
                        Forms\Components\Toggle::make('tipo_operacion_servicios')
                            ->label('Operación Servicios')
                            ->disabled(),
                        Forms\Components\Toggle::make('tipo_operacion_manufactura')
                            ->label('Operación Manufactura')
                            ->disabled(),
                        Forms\Components\Toggle::make('tiene_logistica')
                            ->label('Módulo Logística')
                            ->disabled(),
                        Forms\Components\Toggle::make('tiene_comercio_exterior')
                            ->label('Comercio Exterior')
                            ->disabled(),
                    ])->columns(3),
            ])
            ->statePath('data');
    }
}
