<?php

namespace App\Filament\Contabilidad\Resources;

use App\Filament\Contabilidad\Resources\AdministradorResource\Pages;
use App\Models\Administrador;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Gerente, representante legal y demás administradores: el REBEFICS y la Superintendencia los piden. */
class AdministradorResource extends Resource
{
    protected static ?string $model = Administrador::class;
    protected static ?string $slug = 'administradores';
    protected static ?string $navigationIcon  = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Administradores';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $modelLabel = 'administrador';
    protected static ?string $pluralModelLabel = 'Administradores';
    protected static ?int    $navigationSort = 4;
    protected static ?string $tenantOwnershipRelationshipName = 'empresa';
    protected static ?string $tenantRelationshipName = 'administradores';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\TextInput::make('identificacion')->label('Cédula o RUC')->required()->maxLength(20),
            Forms\Components\TextInput::make('nombre')->label('Nombre completo')->required()->maxLength(160),
            Forms\Components\TextInput::make('cargo')->label('Cargo')->default('Gerente General')->required()->maxLength(80),
            Forms\Components\DatePicker::make('desde')->label('Nombrado desde')->required(),
            Forms\Components\DatePicker::make('hasta')->label('Hasta')->helperText('Vacío mientras siga en el cargo'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->label('Nombre')->searchable()
                    ->description(fn ($record) => $record->identificacion),
                Tables\Columns\TextColumn::make('cargo')->label('Cargo'),
                Tables\Columns\TextColumn::make('desde')->label('Desde')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('hasta')->label('Hasta')->date('d/m/Y')->placeholder('En funciones'),
            ])
            ->actions([Tables\Actions\EditAction::make()->slideOver()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAdministradores::route('/')];
    }
}
