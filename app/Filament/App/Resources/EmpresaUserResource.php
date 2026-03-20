<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\EmpresaUserResource\Pages;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class EmpresaUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon   = 'heroicon-o-users';
    protected static ?string $navigationLabel  = 'Usuarios';
    protected static ?string $navigationGroup  = 'Empresa';
    protected static ?int    $navigationSort   = 10;
    protected static ?string $modelLabel       = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios de la empresa';

    /** Solo el administrador de la empresa (y super_admin) puede gestionar usuarios. */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['admin_empresa', 'super_admin']) ?? false;
    }

    /** Scoped a la empresa activa, ocultando super_admins. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('empresa_id', Filament::getTenant()?->id)
            ->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'super_admin'));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del usuario')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre completo')
                            ->placeholder('María González')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->placeholder('usuario@empresa.com')
                            ->required()
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->maxLength(255)
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->helperText(fn (string $operation) => $operation === 'edit'
                                ? 'Deja en blanco para no cambiar la contraseña actual.'
                                : null)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Acceso y permisos')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Rol del usuario')
                            ->options([
                                'admin_empresa' => 'Administrador',
                                'contador'      => 'Contador',
                                'inventario'    => 'Encargado de inventario',
                                'marketing'     => 'Marketing',
                            ])
                            ->required()
                            ->live()
                            ->helperText('El rol determina a qué módulos puede acceder este usuario.')
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('role_description')
                            ->label('')
                            ->content(fn (Get $get): HtmlString => self::roleDescription($get('role')))
                            ->visible(fn (Get $get): bool => filled($get('role')))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin_empresa' => 'Administrador',
                        'contador'      => 'Contador',
                        'inventario'    => 'Inventario',
                        'marketing'     => 'Marketing',
                        default         => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'admin_empresa' => 'warning',
                        'contador'      => 'info',
                        'inventario'    => 'success',
                        'marketing'     => 'primary',
                        default         => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->since()
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (User $record): bool => $record->id !== auth()->id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmpresaUsers::route('/'),
            'create' => Pages\CreateEmpresaUser::route('/create'),
            'edit'   => Pages\EditEmpresaUser::route('/{record}/edit'),
        ];
    }

    // ── Descripción de cada rol en lenguaje claro ──────────────────────────

    private static function roleDescription(?string $role): HtmlString
    {
        if (! $role) {
            return new HtmlString('');
        }

        $data = match ($role) {
            'admin_empresa' => [
                'color'  => '#b45309',
                'bg'     => '#fffbeb',
                'border' => '#fde68a',
                'icon'   => '🛡️',
                'titulo' => 'Administrador de la empresa',
                'desc'   => 'Tiene control total sobre la cuenta de la empresa.',
                'puede'  => [
                    'Registrar, editar y eliminar otros usuarios',
                    'Ver y exportar todos los reportes financieros',
                    'Gestionar ventas, compras, inventario y manufactura',
                    'Administrar proveedores y clientes',
                    'Configurar el perfil y ajustes de la empresa',
                    'Usar el módulo de mailing completo',
                ],
                'nopuede' => [],
            ],

            'contador' => [
                'color'  => '#1d4ed8',
                'bg'     => '#eff6ff',
                'border' => '#bfdbfe',
                'icon'   => '📊',
                'titulo' => 'Contador',
                'desc'   => 'Perfil orientado al análisis financiero y contable. Solo puede consultar información, no modificarla.',
                'puede'  => [
                    'Ver y exportar todos los reportes financieros',
                    'Consultar el inventario y la lista de proveedores',
                ],
                'nopuede' => [
                    'Crear o modificar ventas, compras ni inventario',
                    'Registrar otros usuarios',
                    'Cambiar la configuración de la empresa',
                    'Acceder al módulo de mailing',
                ],
            ],

            'inventario' => [
                'color'  => '#065f46',
                'bg'     => '#f0fdf4',
                'border' => '#bbf7d0',
                'icon'   => '📦',
                'titulo' => 'Encargado de inventario',
                'desc'   => 'Gestiona el stock y los productos de la empresa.',
                'puede'  => [
                    'Ver, crear y editar productos en el inventario',
                    'Registrar movimientos de stock (entradas y salidas)',
                    'Consultar la lista de proveedores',
                ],
                'nopuede' => [
                    'Ver reportes financieros ni estados de resultados',
                    'Acceder a ventas ni a compras',
                    'Registrar otros usuarios',
                    'Cambiar la configuración de la empresa',
                    'Acceder al módulo de mailing',
                ],
            ],

            'marketing' => [
                'color'  => '#6d28d9',
                'bg'     => '#f5f3ff',
                'border' => '#ddd6fe',
                'icon'   => '📧',
                'titulo' => 'Marketing',
                'desc'   => 'Acceso exclusivo al módulo de mailing. Ideal para el equipo que gestiona comunicaciones con clientes.',
                'puede'  => [
                    'Crear y editar plantillas de correo',
                    'Importar y administrar la lista de contactos',
                    'Crear y enviar campañas de correo masivo',
                    'Ver estadísticas de envíos',
                ],
                'nopuede' => [
                    'Ver ventas, compras, inventario ni finanzas',
                    'Acceder a reportes financieros',
                    'Ver o modificar datos de proveedores',
                    'Registrar otros usuarios ni cambiar configuraciones',
                    'Acceder al panel ERP (solo el panel de mailing)',
                ],
            ],

            default => null,
        };

        if (! $data) {
            return new HtmlString('');
        }

        $puedeItems  = implode('', array_map(
            fn ($item) => "<li style='margin:4px 0;'>✅ {$item}</li>",
            $data['puede']
        ));

        $nopuedeItems = implode('', array_map(
            fn ($item) => "<li style='margin:4px 0;'>❌ {$item}</li>",
            $data['nopuede']
        ));

        $nopuedeSection = $nopuedeItems
            ? "<div style='margin-top:12px;'><p style='margin:0 0 6px;font-weight:600;font-size:0.8rem;color:{$data['color']};'>No puede:</p><ul style='margin:0;padding-left:4px;list-style:none;color:#374151;font-size:0.85rem;'>{$nopuedeItems}</ul></div>"
            : '';

        return new HtmlString("
            <div style='background:{$data['bg']};border:1px solid {$data['border']};border-radius:10px;padding:16px 20px;'>
                <div style='display:flex;align-items:center;gap:8px;margin-bottom:8px;'>
                    <span style='font-size:1.25rem;'>{$data['icon']}</span>
                    <div>
                        <p style='margin:0;font-weight:700;font-size:0.9rem;color:{$data['color']};'>{$data['titulo']}</p>
                        <p style='margin:0;font-size:0.8rem;color:#6b7280;'>{$data['desc']}</p>
                    </div>
                </div>
                <p style='margin:0 0 6px;font-weight:600;font-size:0.8rem;color:{$data['color']};'>Puede:</p>
                <ul style='margin:0;padding-left:4px;list-style:none;color:#374151;font-size:0.85rem;'>
                    {$puedeItems}
                </ul>
                {$nopuedeSection}
            </div>
        ");
    }
}
