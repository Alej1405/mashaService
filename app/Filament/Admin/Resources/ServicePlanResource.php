<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ServicePlanResource\Pages;
use App\Models\ServicePlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class ServicePlanResource extends Resource
{
    protected static ?string $model            = ServicePlan::class;
    protected static ?string $navigationIcon   = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel  = 'Planes';
    protected static ?string $navigationGroup  = 'Plataforma';
    protected static ?int    $navigationSort   = 1;
    protected static ?string $modelLabel       = 'Plan';
    protected static ?string $pluralModelLabel = 'Planes de Servicio';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('plan_tabs')
                ->tabs([

                    // ── Tab 1: Información ──────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Información')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Section::make()
                                ->columns(2)
                                ->schema([
                                    Forms\Components\TextInput::make('key')
                                        ->label('Clave del plan')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->regex('/^[a-z0-9_-]+$/')
                                        ->helperText('Solo minúsculas, números y guiones. Identifica el plan internamente.')
                                        ->disabled(fn ($record) => in_array($record?->key, ['basic', 'pro', 'enterprise'])),

                                    Forms\Components\TextInput::make('nombre')
                                        ->label('Nombre visible')
                                        ->required()
                                        ->maxLength(100),
                                ]),

                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\Textarea::make('descripcion')
                                        ->label('Descripción del plan')
                                        ->rows(3)
                                        ->helperText('Se muestra al cliente en su dashboard.'),

                                    Forms\Components\Repeater::make('caracteristicas')
                                        ->label('Características visibles al cliente')
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
                        ]),

                    // ── Tab 2: Paneles que abre el plan ─────────────────────
                    Forms\Components\Tabs\Tab::make('Paneles')
                        ->icon('heroicon-o-window')
                        ->schema([
                            Forms\Components\Section::make()
                                ->description('Selecciona a qué paneles podrá acceder una empresa con este plan. Los módulos visibles dentro de cada panel se configuran en la sección "Paneles".')
                                ->schema([
                                    Forms\Components\CheckboxList::make('panels')
                                        ->label('Paneles habilitados')
                                        ->relationship(
                                            name: 'panels',
                                            titleAttribute: 'name',
                                            modifyQueryUsing: fn ($query) => $query->where('activo', true)->orderBy('sort'),
                                        )
                                        ->descriptions(
                                            \App\Models\Panel::where('activo', true)->orderBy('sort')->get()
                                                ->mapWithKeys(fn ($p) => [
                                                    $p->id => 'Módulos: ' . (implode(', ', $p->moduleKeys()) ?: '—'),
                                                ])
                                                ->all()
                                        )
                                        ->columns(2)
                                        ->bulkToggleable(),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(ServicePlan::query()->orderBy('sort_order'))
            ->contentGrid(['md' => 2, 'xl' => 3])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('nombre')
                            ->weight(FontWeight::SemiBold)
                            ->grow(true),

                        Tables\Columns\TextColumn::make('key')
                            ->badge()
                            ->alignEnd()
                            ->color(fn (string $state): string => match ($state) {
                                'basic'      => 'gray',
                                'pro'        => 'info',
                                'enterprise' => 'warning',
                                default      => 'primary',
                            }),
                    ]),

                    Tables\Columns\TextColumn::make('descripcion')
                        ->size('sm')
                        ->color('gray')
                        ->wrap(),

                    Tables\Columns\TextColumn::make('paneles_html')
                        ->label('')
                        ->html()
                        ->state(function (ServicePlan $record): string {
                            $paneles = $record->panels;
                            if ($paneles->isEmpty()) {
                                return '<span class="text-xs text-slate-500">Sin paneles asignados</span>';
                            }

                            return $paneles
                                ->map(fn ($panel) => sprintf(
                                    '<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:500;background:#eef2ff;color:#4338ca;margin:2px 2px 2px 0;">%s</span>',
                                    e($panel->name)
                                ))
                                ->join('');
                        }),
                ])->space(2),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServicePlans::route('/'),
            'create' => Pages\CreateServicePlan::route('/create'),
            'edit'   => Pages\EditServicePlan::route('/{record}/edit'),
        ];
    }
}
