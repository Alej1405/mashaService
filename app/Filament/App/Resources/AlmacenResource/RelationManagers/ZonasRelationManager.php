<?php

namespace App\Filament\App\Resources\AlmacenResource\RelationManagers;

use App\Models\UbicacionAlmacen;
use App\Models\ZonaAlmacen;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ZonasRelationManager extends RelationManager
{
    protected static string $relationship = 'zonas';
    protected static ?string $title = 'Zonas / Secciones';
    protected static ?string $label = 'Zona';
    protected static ?string $pluralLabel = 'Zonas';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('codigo')
                ->label('Código de Zona')
                ->required()
                ->maxLength(20),
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre')
                ->required()
                ->maxLength(150),
            Forms\Components\Select::make('tipo')
                ->label('Tipo')
                ->options(ZonaAlmacen::tiposLabels())
                ->required()
                ->default('estanteria'),
            Forms\Components\Toggle::make('activo')
                ->label('Activo')
                ->default(true),
            Forms\Components\Textarea::make('descripcion')
                ->label('Descripción')
                ->rows(2)
                ->columnSpanFull(),

            Forms\Components\Section::make('Posiciones / Ubicaciones')
                ->description('Agrega las posiciones individuales dentro de esta zona (ej: Estante A-01, Gaveta B-02).')
                ->schema([
                    Forms\Components\Repeater::make('ubicaciones')
                        ->label('')
                        ->relationship('ubicaciones')
                        ->schema([
                            Forms\Components\TextInput::make('codigo_ubicacion')
                                ->label('Código')
                                ->required()
                                ->maxLength(30)
                                ->placeholder('A-01-03'),
                            Forms\Components\TextInput::make('nombre')
                                ->label('Descripción de Posición')
                                ->required()
                                ->maxLength(150)
                                ->placeholder('Estante A, Nivel 1, Posición 3'),
                            Forms\Components\TextInput::make('capacidad_maxima')
                                ->label('Capacidad Máx.')
                                ->numeric()
                                ->nullable(),
                            Forms\Components\TextInput::make('unidad_capacidad')
                                ->label('Unidad')
                                ->maxLength(20)
                                ->placeholder('kg / u / cajas'),
                        ])
                        ->columns(4)
                        ->defaultItems(0)
                        ->addActionLabel('+ Agregar Posición')
                        ->itemLabel(fn (array $state): ?string => $state['codigo_ubicacion']
                            ? "{$state['codigo_ubicacion']} — {$state['nombre']}"
                            : null)
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            $data['empresa_id'] = Filament::getTenant()->id;
                            $data['almacen_id'] = $this->getOwnerRecord()->id;
                            return $data;
                        })
                        ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                            $data['empresa_id'] = Filament::getTenant()->id;
                            $data['almacen_id'] = $this->getOwnerRecord()->id;
                            return $data;
                        }),
                ])
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ZonaAlmacen::tiposLabels()[$state] ?? $state),
                Tables\Columns\TextColumn::make('ubicaciones_count')
                    ->label('Posiciones')
                    ->counts('ubicaciones')
                    ->badge()
                    ->color('success'),
                Tables\Columns\IconColumn::make('activo')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['empresa_id'] = Filament::getTenant()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
