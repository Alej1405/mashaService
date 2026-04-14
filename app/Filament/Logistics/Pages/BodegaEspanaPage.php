<?php

namespace App\Filament\Logistics\Pages;

use App\Models\LogisticsBodega;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BodegaEspanaPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-flag';
    protected static ?string $navigationLabel = 'España';
    protected static ?string $navigationGroup = 'Bodegas';
    protected static ?string $title           = 'Bodega — España';
    protected static ?int    $navigationSort  = 2;
    protected static string  $view            = 'filament.logistics.pages.bodega-form';

    public ?array $data = [];

    public function mount(): void
    {
        $bodega = $this->getBodega();
        $this->form->fill($bodega ? $bodega->toArray() : ['pais' => 'ESPANA', 'nombre' => 'Bodega España']);
    }

    private function getBodega(): ?LogisticsBodega
    {
        return LogisticsBodega::withoutGlobalScopes()
            ->where('empresa_id', Filament::getTenant()->id)
            ->where('pais', 'ESPANA')
            ->first();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de la bodega')
                    ->description('Dirección física donde se reciben los paquetes en España.')
                    ->schema([
                        TextInput::make('nombre')
                            ->label('Nombre de la bodega')
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('direccion_origen')
                            ->label('Dirección de la bodega (calle, número, piso)')
                            ->rows(2)
                            ->columnSpanFull(),
                        Grid::make(3)->schema([
                            TextInput::make('ciudad')->label('Ciudad'),
                            TextInput::make('estado_provincia')->label('Provincia / Comunidad'),
                            TextInput::make('codigo_postal')->label('Código postal'),
                        ]),
                        TextInput::make('empresa_aliada')
                            ->label('Empresa aliada / operador')
                            ->helperText('Nombre del courier o empresa de consolidación en origen.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Contacto en origen')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('contacto_nombre')->label('Nombre de contacto'),
                            TextInput::make('contacto_email')->label('Email')->email(),
                            TextInput::make('contacto_telefono')->label('Teléfono'),
                        ]),
                    ]),

                Section::make('Notas adicionales')
                    ->collapsed()
                    ->schema([
                        Textarea::make('notas')->label('Notas')->rows(3)->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('guardar')
                ->label('Guardar configuración')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data['empresa_id'] = Filament::getTenant()->id;
        $data['pais']       = 'ESPANA';

        LogisticsBodega::withoutGlobalScopes()->updateOrCreate(
            ['empresa_id' => $data['empresa_id'], 'pais' => 'ESPANA'],
            $data
        );

        Notification::make()->title('Bodega España guardada correctamente.')->success()->send();
    }
}
