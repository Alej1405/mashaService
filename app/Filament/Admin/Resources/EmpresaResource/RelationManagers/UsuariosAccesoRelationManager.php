<?php

namespace App\Filament\Admin\Resources\EmpresaResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class UsuariosAccesoRelationManager extends RelationManager
{
    protected static string $relationship = 'usuariosAcceso';

    protected static ?string $title            = 'Usuarios con acceso';
    protected static ?string $modelLabel       = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios con acceso';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id')
                    ->label('Usuario')
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(function (string $search): array {
                        $empresaId = $this->getOwnerRecord()->id;

                        return User::where(function (Builder $q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%");
                            })
                            ->whereDoesntHave('roles', fn (Builder $q) =>
                                $q->where('name', 'super_admin')
                            )
                            ->whereDoesntHave('empresasAcceso', fn (Builder $q) =>
                                $q->where('empresa_user_access.empresa_id', $empresaId)
                            )
                            ->limit(15)
                            ->get()
                            ->mapWithKeys(fn (User $u) => [
                                $u->id => "{$u->name} — {$u->email}",
                            ])
                            ->toArray();
                    })
                    ->helperText('Solo aparecen usuarios que aún no tienen acceso a esta empresa.'),

                Forms\Components\Select::make('rol')
                    ->label('Rol en esta empresa')
                    ->options(self::roleOptions())
                    ->required()
                    ->default('admin_empresa')
                    ->native(false)
                    ->live()
                    ->helperText('Define qué puede hacer este usuario dentro de la empresa.'),

                Forms\Components\Placeholder::make('rol_descripcion')
                    ->label('')
                    ->content(fn (Forms\Get $get): HtmlString => self::roleDescription($get('rol')))
                    ->visible(fn (Forms\Get $get): bool => filled($get('rol'))),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
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

                Tables\Columns\TextColumn::make('pivot.rol')
                    ->label('Rol en esta empresa')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::roleOptions()[$state] ?? ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'admin_empresa'     => 'warning',
                        'contador'          => 'info',
                        'inventario'        => 'success',
                        'marketing'         => 'primary',
                        'cms_editor'        => 'violet',
                        'ecommerce_manager' => 'cyan',
                        default             => 'gray',
                    }),

                Tables\Columns\TextColumn::make('empresa.name')
                    ->label('Empresa primaria')
                    ->color('gray')
                    ->badge()
                    ->tooltip('Empresa a la que pertenece originalmente este usuario'),

                Tables\Columns\IconColumn::make('es_externo')
                    ->label('Externo')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool =>
                        $record->empresa_id !== $this->getOwnerRecord()->id
                    )
                    ->trueIcon('heroicon-o-arrow-top-right-on-square')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('info')
                    ->falseColor('gray')
                    ->tooltip(fn (User $record): string =>
                        $record->empresa_id !== $this->getOwnerRecord()->id
                            ? 'Asignado desde otra empresa'
                            : 'Usuario propio de esta empresa'
                    ),
            ])
            ->headerActions([
                Tables\Actions\Action::make('asignar')
                    ->label('Asignar usuario existente')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->form(fn (Form $form) => $this->form($form))
                    ->action(function (array $data): void {
                        $empresa = $this->getOwnerRecord();
                        $user    = User::findOrFail($data['id']);

                        $user->empresasAcceso()->syncWithoutDetaching([
                            $empresa->id => ['rol' => $data['rol']],
                        ]);

                        Notification::make()
                            ->title('Acceso concedido')
                            ->body("{$user->name} ahora tiene acceso a {$empresa->name} como " . (self::roleOptions()[$data['rol']] ?? $data['rol']) . '.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('cambiar_rol')
                    ->label('Cambiar rol')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->form([
                        Forms\Components\Select::make('rol')
                            ->label('Nuevo rol en esta empresa')
                            ->options(self::roleOptions())
                            ->required()
                            ->native(false),
                    ])
                    ->fillForm(fn (User $record): array => [
                        'rol' => $record->empresasAcceso
                            ->firstWhere('id', $this->getOwnerRecord()->id)
                            ?->pivot->rol ?? 'admin_empresa',
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->empresasAcceso()->updateExistingPivot(
                            $this->getOwnerRecord()->id,
                            ['rol' => $data['rol']]
                        );

                        Notification::make()
                            ->title('Rol actualizado')
                            ->body("El rol de {$record->name} fue cambiado a " . (self::roleOptions()[$data['rol']] ?? $data['rol']) . '.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('quitar_acceso')
                    ->label('Quitar acceso')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Quitar acceso')
                    ->modalDescription(fn (User $record): string =>
                        "¿Seguro que deseas quitarle el acceso a {$record->name}? Su cuenta no será eliminada."
                    )
                    ->modalSubmitActionLabel('Sí, quitar acceso')
                    ->action(function (User $record): void {
                        $empresa = $this->getOwnerRecord();
                        $record->empresasAcceso()->detach($empresa->id);

                        Notification::make()
                            ->title('Acceso eliminado')
                            ->body("{$record->name} ya no tiene acceso a {$empresa->name}.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function roleOptions(): array
    {
        return [
            'admin_empresa'      => 'Administrador',
            'contador'           => 'Contador',
            'inventario'         => 'Encargado de inventario',
            'marketing'          => 'Marketing / Mailing',
            'cms_editor'         => 'Editor CMS',
            'ecommerce_manager'  => 'Gestor Tienda',
        ];
    }

    private static function roleDescription(?string $role): HtmlString
    {
        $data = match ($role) {
            'admin_empresa'     => ['color' => '#b45309', 'bg' => '#fffbeb', 'border' => '#fde68a', 'icon' => '🛡️',
                'titulo' => 'Administrador', 'desc' => 'Control total sobre la empresa.'],
            'contador'          => ['color' => '#1d4ed8', 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'icon' => '📊',
                'titulo' => 'Contador', 'desc' => 'Solo lectura: reportes financieros e inventario.'],
            'inventario'        => ['color' => '#065f46', 'bg' => '#f0fdf4', 'border' => '#bbf7d0', 'icon' => '📦',
                'titulo' => 'Inventario', 'desc' => 'Gestiona productos, stock y movimientos.'],
            'marketing'         => ['color' => '#6d28d9', 'bg' => '#f5f3ff', 'border' => '#ddd6fe', 'icon' => '📧',
                'titulo' => 'Marketing / Mailing', 'desc' => 'Acceso al mailing y al panel CMS.'],
            'cms_editor'        => ['color' => '#7c3aed', 'bg' => '#faf5ff', 'border' => '#e9d5ff', 'icon' => '✏️',
                'titulo' => 'Editor CMS', 'desc' => 'Edita el contenido del sitio web: hero, servicios, blog, etc.'],
            'ecommerce_manager' => ['color' => '#0891b2', 'bg' => '#ecfeff', 'border' => '#a5f3fc', 'icon' => '🛒',
                'titulo' => 'Gestor Tienda', 'desc' => 'Gestiona productos, órdenes y clientes del e-commerce.'],
            default             => null,
        };

        if (! $data) {
            return new HtmlString('');
        }

        return new HtmlString("
            <div style='background:{$data['bg']};border:1px solid {$data['border']};border-radius:8px;padding:12px 16px;display:flex;align-items:center;gap:10px;'>
                <span style='font-size:1.25rem;'>{$data['icon']}</span>
                <div>
                    <p style='margin:0;font-weight:700;font-size:0.85rem;color:{$data['color']};'>{$data['titulo']}</p>
                    <p style='margin:0;font-size:0.8rem;color:#6b7280;'>{$data['desc']}</p>
                </div>
            </div>
        ");
    }
}
