<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AccountPlanResource\Pages;
use App\Models\AccountPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccountPlanResource extends Resource
{
    protected static ?string $model = AccountPlan::class;
    protected static ?string $tenantRelationshipName = 'accountPlans';

    protected static ?string $tenantOwnershipRelationshipName = 'empresa';

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $modelLabel = 'Plan de Cuentas';
    protected static ?string $pluralModelLabel = 'Planes de Cuentas';
    protected static ?string $navigationGroup = 'Contabilidad';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles de la Cuenta')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Cuenta')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Cuenta')
                            ->options([
                                'activo' => 'Activo',
                                'pasivo' => 'Pasivo',
                                'patrimonio' => 'Patrimonio',
                                'ingreso' => 'Ingreso',
                                'costo' => 'Costo',
                                'gasto' => 'Gasto',
                            ])
                            ->required(),
                        Forms\Components\Select::make('nature')
                            ->label('Naturaleza')
                            ->options([
                                'deudora' => 'Deudora',
                                'acreedora' => 'Acreedora',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('parent_code')
                            ->label('Código Padre'),
                        Forms\Components\TextInput::make('level')
                            ->label('Nivel')
                            ->numeric()
                            ->required(),
                        Forms\Components\Toggle::make('accepts_movements')
                            ->label('Acepta Movimientos')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Cuenta Activa')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'activo' => 'success',
                        'pasivo' => 'danger',
                        'patrimonio' => 'warning',
                        'ingreso' => 'info',
                        'costo' => 'gray',
                        'gasto' => 'orange',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('nature')
                    ->label('Naturaleza')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\IconColumn::make('accepts_movements')
                    ->label('Mov.')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('level')
                    ->label('Nivel')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'activo' => 'Activo',
                        'pasivo' => 'Pasivo',
                        'patrimonio' => 'Patrimonio',
                        'ingreso' => 'Ingreso',
                        'costo' => 'Costo',
                        'gasto' => 'Gasto',
                    ]),
                Tables\Filters\Filter::make('solo_movimiento')
                    ->label('Acepta Movimientos')
                    ->query(fn (Builder $query): Builder => $query->where('accepts_movements', true)),
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
            'index' => Pages\ListAccountPlans::route('/'),
            'create' => Pages\CreateAccountPlan::route('/create'),
            'edit' => Pages\EditAccountPlan::route('/{record}/edit'),
        ];
    }
}
