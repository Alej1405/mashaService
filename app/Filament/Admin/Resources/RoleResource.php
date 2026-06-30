<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model            = Role::class;
    protected static ?string $navigationIcon   = 'heroicon-o-identification';
    protected static ?string $navigationLabel  = 'Roles';
    protected static ?string $navigationGroup  = 'Plataforma';
    protected static ?int    $navigationSort   = 3;
    protected static ?string $modelLabel       = 'Rol';
    protected static ?string $pluralModelLabel = 'Roles';

    /** Roles del sistema: su nombre está cableado en código; no se renombra ni elimina. */
    public const ROLES_BASE = [
        'super_admin', 'admin_empresa', 'contador',
        'inventario', 'marketing', 'cms_editor', 'ecommerce_manager',
    ];

    /** Etiquetas legibles de los roles del sistema. */
    public const ETIQUETAS = [
        'super_admin'       => 'Super administrador',
        'admin_empresa'     => 'Administrador de empresa',
        'contador'          => 'Contador',
        'inventario'        => 'Encargado de inventario',
        'marketing'         => 'Marketing',
        'cms_editor'        => 'Editor de contenido',
        'ecommerce_manager' => 'Gestor de tienda',
    ];

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        $catalogo = config('erp_features', []);

        return $form->schema([
            Forms\Components\Section::make('Identidad del rol')
                ->description('El nombre identifica el rol en todo el sistema. Los roles del sistema no se renombran.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre del rol')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->regex('/^[a-z0-9_-]+$/')
                        ->helperText('Solo minúsculas, números y guiones bajos. Ej: jefe_ventas.')
                        ->disabled(fn ($record) => in_array($record?->name, self::ROLES_BASE, true))
                        ->dehydrated(),

                    Forms\Components\Hidden::make('guard_name')
                        ->default('web'),
                ]),

            Forms\Components\Section::make('Módulos que ve este rol')
                ->description('Marca los módulos visibles para los usuarios con este rol. Quitar un módulo solo lo oculta del menú; su lógica sigue funcionando en segundo plano.')
                ->schema([
                    Forms\Components\CheckboxList::make('module_keys')
                        ->label('')
                        ->options(collect($catalogo)->map(fn (array $m) => $m['label'])->all())
                        ->descriptions(collect($catalogo)->map(fn (array $m) => $m['descripcion'] ?? '')->all())
                        ->columns(2)
                        ->bulkToggleable()
                        ->afterStateHydrated(fn (Forms\Components\CheckboxList $component, ?Role $record) =>
                            $component->state($record?->moduleKeys() ?? [])
                        ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $catalogo = config('erp_features', []);

        return $table
            ->query(Role::query()->orderBy('name'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Rol')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn (string $state): string => self::ETIQUETAS[$state] ?? $state)
                    ->description(fn (Role $r): string => $r->name),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->state(fn (Role $r): string => in_array($r->name, self::ROLES_BASE, true) ? 'Sistema' : 'Personalizado')
                    ->color(fn (string $state): string => $state === 'Sistema' ? 'gray' : 'info'),

                Tables\Columns\TextColumn::make('modulos_html')
                    ->label('Módulos')
                    ->html()
                    ->state(function (Role $record) use ($catalogo): string {
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
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square'),

                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->visible(fn (Role $record): bool => ! in_array($record->name, self::ROLES_BASE, true)),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
