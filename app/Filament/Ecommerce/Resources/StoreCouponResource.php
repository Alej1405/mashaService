<?php

namespace App\Filament\Ecommerce\Resources;

use App\Filament\Ecommerce\Resources\StoreCouponResource\Pages;
use App\Models\StoreCoupon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class StoreCouponResource extends Resource
{
    protected static ?string $model = StoreCoupon::class;

    protected static ?string $tenantRelationshipName = 'storeCoupons';
    protected static ?string $navigationIcon         = 'heroicon-o-ticket';
    protected static ?string $navigationLabel        = 'Cupones';
    protected static ?string $navigationGroup        = 'Promociones';
    protected static ?string $modelLabel             = 'Cupón';
    protected static ?string $pluralModelLabel       = 'Cupones';
    protected static ?int    $navigationSort         = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('codigo')->label('Código')->required()->maxLength(50)
                ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                ->dehydrateStateUsing(fn ($state) => strtoupper($state))->columnSpan(1),
            Select::make('tipo')->label('Tipo')
                ->options(['porcentaje'=>'Porcentaje (%)','monto_fijo'=>'Monto Fijo ($)'])
                ->required()->native(false)->columnSpan(1),
            TextInput::make('valor')->label('Valor')->numeric()->required()->columnSpan(1),
            TextInput::make('minimo_compra')->label('Mínimo de Compra')->numeric()->nullable()->prefix('$')->columnSpan(1),
            TextInput::make('maximo_usos')->label('Máximo de Usos')->numeric()->nullable()->helperText('Vacío = ilimitado')->columnSpan(1),
            Toggle::make('activo')->label('Activo')->default(true)->columnSpan(1),
            DatePicker::make('fecha_inicio')->label('Válido desde')->nullable()->native(false)->columnSpan(1),
            DatePicker::make('fecha_fin')->label('Válido hasta')->nullable()->native(false)->columnSpan(1),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->weight('bold')
                    ->badge()->color('primary'),
                TextColumn::make('tipo')->label('Tipo')
                    ->formatStateUsing(fn ($state) => $state === 'porcentaje' ? 'Porcentaje' : 'Monto fijo')
                    ->badge()->color(fn ($state) => $state === 'porcentaje' ? 'info' : 'success'),
                TextColumn::make('valor')->label('Valor')
                    ->formatStateUsing(fn ($state, $record) => $record->tipo === 'porcentaje' ? "{$state}%" : "\${$state}"),
                TextColumn::make('usos_actuales')->label('Usos')->color('gray'),
                TextColumn::make('fecha_fin')->label('Vence')->date('d/m/Y')->color('gray'),
                IconColumn::make('activo')->label('Activo')->boolean(),
            ])
            ->filters([TernaryFilter::make('activo')->label('Activo')])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([\Filament\Tables\Actions\BulkActionGroup::make([\Filament\Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStoreCoupons::route('/'),
            'create' => Pages\CreateStoreCoupon::route('/create'),
            'edit'   => Pages\EditStoreCoupon::route('/{record}/edit'),
        ];
    }
}
