<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $modelLabel = 'Empresa';
    protected static ?string $pluralModelLabel = 'Empresas';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Empresa')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])->columns(2),

                Forms\Components\Section::make('Configuración de Módulos Operativos')
                    ->description('Active los módulos que correspondan al tipo de operación de la empresa.')
                    ->schema([
                        Forms\Components\Toggle::make('tipo_operacion_productos')
                            ->label('Operación Productos')
                            ->default(true),
                        Forms\Components\Toggle::make('tipo_operacion_servicios')
                            ->label('Operación Servicios'),
                        Forms\Components\Toggle::make('tipo_operacion_manufactura')
                            ->label('Operación Manufactura'),
                        Forms\Components\Toggle::make('tiene_logistica')
                            ->label('Módulo Logística'),
                        Forms\Components\Toggle::make('tiene_comercio_exterior')
                            ->label('Comercio Exterior'),
                    ])->columns(3),
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
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('tipo_operacion_productos')
                    ->label('Prod.')
                    ->boolean(),
                Tables\Columns\IconColumn::make('tipo_operacion_servicios')
                    ->label('Serv.')
                    ->boolean(),
                Tables\Columns\IconColumn::make('tipo_operacion_manufactura')
                    ->label('Man.')
                    ->boolean(),
                Tables\Columns\IconColumn::make('tiene_logistica')
                    ->label('Log.')
                    ->boolean(),
                Tables\Columns\IconColumn::make('tiene_comercio_exterior')
                    ->label('ComExt.')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('tipo_operacion_productos')
                    ->label('Productos'),
                Tables\Filters\TernaryFilter::make('tiene_logistica')
                    ->label('Logística'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
