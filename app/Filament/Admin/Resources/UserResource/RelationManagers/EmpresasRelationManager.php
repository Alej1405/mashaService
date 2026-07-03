<?php

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Empresas a las que un usuario tiene acceso (pivote empresa_user_access, con rol).
 * Permite que un cliente con MÁS DE UNA empresa se gestione desde su ficha, sin
 * crear contraseñas por empresa: el login es un solo password; aquí solo se asigna
 * acceso + rol por empresa.
 */
class EmpresasRelationManager extends RelationManager
{
    protected static string $relationship = 'empresasAcceso';

    protected static ?string $title = 'Empresas del usuario';

    protected static ?string $recordTitleAttribute = 'name';

    /** Opciones de rol tomadas de los roles del sistema. */
    protected function rolOptions(): array
    {
        return Role::orderBy('name')->pluck('name', 'name')->all();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('rol')
                ->label('Rol en la empresa')
                ->options($this->rolOptions())
                ->searchable()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Empresa')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('plan')
                    ->label('Plan')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('pivot.rol')
                    ->label('Rol')
                    ->badge(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Agregar empresa')
                    ->modalHeading('Agregar empresa al usuario')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn ($query) => $query->where('activo', true))
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Empresa')
                            ->helperText('El usuario conserva su misma contraseña; solo se le da acceso a esta empresa.'),
                        Forms\Components\Select::make('rol')
                            ->label('Rol en la empresa')
                            ->options($this->rolOptions())
                            ->searchable()
                            ->required(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Cambiar rol'),
                Tables\Actions\DetachAction::make()
                    ->label('Quitar'),
            ])
            ->emptyStateHeading('Sin empresas adicionales')
            ->emptyStateDescription('Agrega las empresas a las que este usuario debe tener acceso.');
    }
}
