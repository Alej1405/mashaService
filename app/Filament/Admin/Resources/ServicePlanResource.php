<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ServicePlanResource\Pages;
use App\Models\ServicePlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServicePlanResource extends Resource
{
    protected static ?string $model             = ServicePlan::class;
    protected static ?string $navigationIcon    = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel   = 'Planes';
    protected static ?string $navigationGroup   = 'Operaciones';
    protected static ?int    $navigationSort    = 2;
    protected static ?string $modelLabel        = 'Plan';
    protected static ?string $pluralModelLabel  = 'Planes de Servicio';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificación')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('key')
                        ->label('Clave del plan')
                        ->disabled()
                        ->helperText('La clave identifica el plan internamente y no puede cambiarse.'),

                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre visible')
                        ->required()
                        ->maxLength(100),
                ]),

            Forms\Components\Section::make('Descripción')
                ->schema([
                    Forms\Components\Textarea::make('descripcion')
                        ->label('Descripción del plan')
                        ->rows(3)
                        ->helperText('Explica brevemente qué incluye este plan. Se muestra al cliente en su dashboard.'),
                ]),

            Forms\Components\Section::make('Características incluidas')
                ->description('Lista de funcionalidades que incluye el plan. Cada línea es un ítem independiente.')
                ->schema([
                    Forms\Components\Repeater::make('caracteristicas')
                        ->label('')
                        ->simple(
                            Forms\Components\TextInput::make('value')
                                ->label('Característica')
                                ->required()
                                ->placeholder('Ej: Acceso a módulo de Ventas')
                        )
                        ->addActionLabel('Agregar característica')
                        ->reorderable()
                        ->collapsible(false)
                        ->defaultItems(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(ServicePlan::query()->orderBy('sort_order'))
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Clave')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'basic'      => 'gray',
                        'pro'        => 'info',
                        'enterprise' => 'warning',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Plan')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(80)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('caracteristicas')
                    ->label('Características')
                    ->state(fn (ServicePlan $r): string => count($r->caracteristicas ?? []) . ' ítems')
                    ->badge()
                    ->color('gray'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServicePlans::route('/'),
            'edit'  => Pages\EditServicePlan::route('/{record}/edit'),
        ];
    }
}
