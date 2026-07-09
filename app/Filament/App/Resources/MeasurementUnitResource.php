<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MeasurementUnitResource\Pages;
use App\Filament\App\Resources\MeasurementUnitResource\RelationManagers;
use App\Models\MeasurementUnit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MeasurementUnitResource extends Resource
{
    protected static ?string $model = MeasurementUnit::class;
    protected static ?string $tenantRelationshipName = 'measurementUnits';

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $modelLabel = 'Unidad de Medida';
    protected static ?string $pluralModelLabel = 'Unidades de Medida';
    protected static ?string $tenantOwnershipRelationshipName = 'empresa';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('inventario');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre de Unidad')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                Forms\Components\TextInput::make('abreviatura')
                    ->label('Abreviatura')
                    ->maxLength(255)
                    ->columnSpan(1),
                Forms\Components\Select::make('tipo')
                    ->label('Familia')
                    ->options([
                        'conteo'   => 'Conteo',
                        'longitud' => 'Longitud',
                        'masa'     => 'Masa',
                        'volumen'  => 'Volumen',
                    ])
                    ->native(false)
                    ->helperText('A qué magnitud pertenece. Solo se convierte entre unidades de la misma familia.')
                    ->columnSpan(1),
                Forms\Components\TextInput::make('factor')
                    ->label('Equivale a (en la unidad base)')
                    ->numeric()
                    ->default(1)
                    ->minValue(0.00000001)
                    ->helperText('Cuánto vale en la base de su familia. Bases: mL, metro, gramo, unidad. Ej: 1 Litro = 1000 (base mL); 1 kg = 1000 (base g).')
                    ->columnSpan(2),
                Forms\Components\Toggle::make('activo')
                    ->label('Activo')
                    ->default(true)
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('abreviatura')
                    ->label('Abreviatura')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Familia')
                    ->badge()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('factor')
                    ->label('Factor (base)')
                    ->numeric(),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListMeasurementUnits::route('/'),
            'create' => Pages\CreateMeasurementUnit::route('/create'),
            'edit' => Pages\EditMeasurementUnit::route('/{record}/edit'),
        ];
    }
}
