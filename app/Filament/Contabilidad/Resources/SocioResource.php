<?php

namespace App\Filament\Contabilidad\Resources;

use App\Filament\Contabilidad\Resources\SocioResource\Pages;
use App\Models\Socio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Nómina de socios: anexo obligatorio con los estados financieros. */
class SocioResource extends Resource
{
    protected static ?string $model = Socio::class;
    protected static ?string $slug = 'socios';
    protected static ?string $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Socios';
    protected static ?string $modelLabel = 'socio';
    protected static ?string $pluralModelLabel = 'Socios y accionistas';
    protected static ?int    $navigationSort = 6;
    protected static ?string $tenantOwnershipRelationshipName = 'empresa';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\Select::make('tipo_identificacion')->label('Tipo de identificación')
                ->options(['cedula' => 'Cédula', 'ruc' => 'RUC', 'pasaporte' => 'Pasaporte'])->default('cedula')->required(),
            Forms\Components\TextInput::make('identificacion')->label('Identificación')->required()->maxLength(20),
            Forms\Components\TextInput::make('nombre')->label('Nombre completo')->required()->maxLength(160)->columnSpanFull(),
            Forms\Components\TextInput::make('nacionalidad')->label('Nacionalidad')->default('Ecuatoriana')->maxLength(60),
            Forms\Components\TextInput::make('participacion')->label('Participación')->numeric()->suffix('%')->default(0),
            Forms\Components\TextInput::make('capital')->label('Capital suscrito')->numeric()->prefix('$')->default(0),
            Forms\Components\Toggle::make('activo')->label('Vigente')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('identificacion')->label('Identificación')->searchable(),
                Tables\Columns\TextColumn::make('nombre')->label('Nombre')->searchable()
                    ->description(fn ($record) => $record->nacionalidad),
                Tables\Columns\TextColumn::make('participacion')->label('Participación')->alignEnd()
                    ->formatStateUsing(fn ($state) => rtrim(rtrim(number_format((float) $state, 2, ',', '.'), '0'), ',') . ' %')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')
                        ->formatStateUsing(fn ($state) => rtrim(rtrim(number_format((float) $state, 2, ',', '.'), '0'), ',') . ' %')),
                Tables\Columns\TextColumn::make('capital')->label('Capital')->alignEnd()
                    ->formatStateUsing(fn ($state) => '$ ' . number_format((float) $state, 2, ',', '.'))
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Capital suscrito')
                        ->formatStateUsing(fn ($state) => '$ ' . number_format((float) $state, 2, ',', '.'))),
                Tables\Columns\IconColumn::make('activo')->label('Vigente')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make()->slideOver()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSocios::route('/')];
    }
}
