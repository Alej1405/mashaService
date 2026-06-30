<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PanelResource\Pages;
use App\Models\Panel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class PanelResource extends Resource
{
    protected static ?string $model            = Panel::class;
    protected static ?string $navigationIcon   = 'heroicon-o-window';
    protected static ?string $navigationLabel  = 'Paneles';
    protected static ?string $navigationGroup  = 'Plataforma';
    protected static ?int    $navigationSort   = 2;
    protected static ?string $modelLabel       = 'Panel';
    protected static ?string $pluralModelLabel = 'Paneles';

    /** Paneles base del sistema: su clave y ruta no se editan (cablean providers Filament). */
    public const PANELES_BASE = ['basic', 'pro', 'enterprise', 'logistics', 'cms', 'ecommerce'];

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        $catalogo = config('erp_features', []);

        return $form->schema([
            Forms\Components\Section::make('Información')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre visible')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('key')
                        ->label('Clave')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->regex('/^[a-z0-9_-]+$/')
                        ->helperText('Identificador interno. Debe coincidir con el id del panel Filament.')
                        ->disabled(fn ($record) => in_array($record?->key, self::PANELES_BASE, true)),

                    Forms\Components\TextInput::make('path')
                        ->label('Ruta (path)')
                        ->required()
                        ->helperText('Segmento de URL del panel, ej. "app", "pro", "store".')
                        ->disabled(fn ($record) => in_array($record?->key, self::PANELES_BASE, true)),

                    Forms\Components\TextInput::make('sort')
                        ->label('Orden')
                        ->numeric()
                        ->default(50),
                ]),

            Forms\Components\Section::make('Apariencia')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('color')
                        ->label('Color')
                        ->options([
                            'slate' => 'Slate', 'gray' => 'Gris', 'indigo' => 'Índigo',
                            'amber' => 'Ámbar', 'cyan' => 'Cian', 'violet' => 'Violeta',
                            'emerald' => 'Esmeralda', 'rose' => 'Rosa', 'sky' => 'Cielo',
                        ])
                        ->default('slate'),

                    Forms\Components\TextInput::make('icon')
                        ->label('Ícono (Heroicon)')
                        ->placeholder('heroicon-o-squares-2x2')
                        ->default('heroicon-o-squares-2x2'),

                    Forms\Components\Toggle::make('activo')
                        ->label('Activo')
                        ->default(true)
                        ->inline(false),
                ]),

            Forms\Components\Section::make('Módulos del panel')
                ->description('Módulos visibles en este panel. Sus opciones aparecen en el menú; agregar o quitar un módulo solo cambia la visibilidad, nunca apaga su lógica.')
                ->schema([
                    Forms\Components\CheckboxList::make('module_keys')
                        ->label('')
                        ->options(collect($catalogo)->map(fn (array $m) => $m['label'])->all())
                        ->descriptions(collect($catalogo)->map(fn (array $m) => $m['descripcion'] ?? '')->all())
                        ->columns(2)
                        ->bulkToggleable()
                        ->afterStateHydrated(fn (Forms\Components\CheckboxList $component, ?Panel $record) =>
                            $component->state($record?->moduleKeys() ?? [])
                        ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $catalogo = config('erp_features', []);

        return $table
            ->query(Panel::query()->orderBy('sort'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Panel')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Panel $r): string => '/' . $r->path),

                Tables\Columns\TextColumn::make('key')
                    ->label('Clave')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('modulos_html')
                    ->label('Módulos')
                    ->html()
                    ->state(function (Panel $record) use ($catalogo): string {
                        $keys = $record->moduleKeys();
                        if (empty($keys)) {
                            return '<span class="text-xs text-slate-500">Sin módulos</span>';
                        }

                        return collect($keys)
                            ->map(fn ($k) => sprintf(
                                '<span style="display:inline-flex;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:500;background:#f1f5f9;color:#475569;margin:2px 2px 2px 0;">%s</span>',
                                e($catalogo[$k]['label'] ?? $k)
                            ))
                            ->join('');
                    }),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
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
            'index'  => Pages\ListPanels::route('/'),
            'create' => Pages\CreatePanel::route('/create'),
            'edit'   => Pages\EditPanel::route('/{record}/edit'),
        ];
    }
}
