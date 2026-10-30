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
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $modelLabel = 'socio';
    protected static ?string $pluralModelLabel = 'Socios y accionistas';
    protected static ?int    $navigationSort = 3;
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

            // Lo que el REBEFICS pide de la persona natural que es beneficiaria final.
            Forms\Components\Section::make('Beneficiario final · REBEFICS')
                ->description('Persona natural con participación: el SRI pide su nacimiento, su país y su domicilio con los códigos de sus tablas.')
                ->columns(2)->collapsible()
                ->schema([
                    Forms\Components\DatePicker::make('fecha_nacimiento')->label('Fecha de nacimiento')->maxDate(now()),
                    Forms\Components\Select::make('pais_nacionalidad')->label('Nacionalidad')->searchable()
                        ->options(fn () => self::catalogo('5'))->default('593'),
                    Forms\Components\Select::make('residencia_fiscal')->label('Residencia fiscal')->searchable()
                        ->options(fn () => self::catalogo('5'))->default('593')->live(),
                    Forms\Components\Select::make('provincia')->label('Provincia')->searchable()
                        ->options(fn () => self::catalogo('adm_provincia'))->live()
                        ->afterStateUpdated(function (Forms\Set $set) {
                            $set('canton', null);
                            $set('parroquia', null);
                        })
                        ->visible(fn (Forms\Get $get) => $get('residencia_fiscal') === '593'),
                    Forms\Components\Select::make('canton')->label('Cantón')->searchable()
                        ->options(fn (Forms\Get $get) => self::catalogo('adm_canton', $get('provincia')))->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('parroquia', null))
                        ->visible(fn (Forms\Get $get) => $get('residencia_fiscal') === '593'),
                    Forms\Components\Select::make('parroquia')->label('Parroquia')->searchable()
                        ->options(fn (Forms\Get $get) => self::catalogo('adm_parroquia', $get('canton')))
                        ->visible(fn (Forms\Get $get) => $get('residencia_fiscal') === '593'),
                    Forms\Components\TextInput::make('calle')->label('Calle')->maxLength(100),
                    Forms\Components\TextInput::make('numero')->label('Número')->maxLength(10)
                        ->helperText('Si no tiene, SN'),
                    Forms\Components\TextInput::make('interseccion')->label('Intersección')->maxLength(100),
                    Forms\Components\TextInput::make('codigo_postal')->label('Código postal')->numeric()->maxLength(10),
                    Forms\Components\TextInput::make('referencia')->label('Referencia')->maxLength(100)->columnSpanFull(),
                ]),
        ]);
    }

    /** Códigos de una tabla del REBEFICS; con `$grupo`, solo los hijos de ese código (cantones de una provincia…). */
    private static function catalogo(string $tabla, ?string $grupo = null): array
    {
        return \App\Models\CatalogoSri::de('rebefics', $tabla)
            ->when(func_num_args() > 1, fn ($q) => $q->where('grupo', $grupo ?? '-'))
            ->orderBy('descripcion')->pluck('descripcion', 'codigo')->all();
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
