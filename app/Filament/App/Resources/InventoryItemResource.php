<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\InventoryItemResource\Pages;
use App\Filament\App\Resources\InventoryItemResource\RelationManagers;
use App\Filament\App\Resources\SupplierResource;
use App\Models\InventoryItem;
use App\Models\MeasurementUnit;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;
    protected static ?string $tenantRelationshipName = 'inventoryItems';

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $modelLabel = 'Ítem de Inventario';
    protected static ?string $pluralModelLabel = 'Inventario / Ítems';
    protected static ?string $tenantOwnershipRelationshipName = 'empresa';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Información General')
                            ->schema([
                                Forms\Components\TextInput::make('codigo')
                                    ->label('Código')
                                    ->default(fn () => 'INV-' . strtoupper(uniqid()))
                                    ->readOnly()
                                    ->required(),
                                Forms\Components\TextInput::make('nombre')
                                    ->label('Nombre del Ítem')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->label('Tipo de Ítem')
                                    ->options([
                                        'insumo' => 'Insumo',
                                        'materia_prima' => 'Materia Prima',
                                        'producto_terminado' => 'Producto Terminado',
                                        'activo_fijo' => 'Activo Fijo',
                                        'servicio' => 'Servicio',
                                    ])
                                    ->reactive()
                                    ->required(),
                                Forms\Components\Select::make('measurement_unit_id')
                                    ->label('Unidad de Medida')
                                    ->relationship('measurementUnit', 'nombre', fn ($query) => $query->where('activo', true))
                                    ->searchable()
                                    ->preload()
                                    ->createOptionModalHeading('Nueva Unidad de Medida')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nombre')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('abreviatura')
                                            ->label('Abreviatura')
                                            ->maxLength(10),
                                    ])
                                    ->createOptionUsing(fn (array $data): int =>
                                        MeasurementUnit::create([...$data, 'empresa_id' => Filament::getTenant()->id])->getKey()),
                                Forms\Components\RichEditor::make('descripcion')
                                    ->label('Descripción')
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Precios y Stock')
                            ->schema([
                                Forms\Components\TextInput::make('purchase_price')
                                    ->label('Precio de Compra')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0),
                                Forms\Components\TextInput::make('sale_price')
                                    ->label('Precio de Venta')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('stock_actual')
                                    ->label('Stock Actual')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\TextInput::make('stock_minimo')
                                    ->label('Stock Mínimo (Alerta)')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\Section::make('Conversión de Unidades')
                                    ->description('Configura si compras en una unidad diferente a la que controlas en stock (ej: compras en kg pero el stock se lleva en gramos).')
                                    ->schema([
                                        Forms\Components\Select::make('purchase_unit_id')
                                            ->label('Unidad de Compra')
                                            ->relationship('purchaseUnit', 'nombre', fn ($query) => $query->where('activo', true))
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nombre} ({$record->abreviatura})")
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->live()
                                            ->helperText('Deja en blanco si compras y usas la misma unidad.')
                                            ->createOptionModalHeading('Nueva Unidad de Medida')
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('nombre')->label('Nombre')->required()->maxLength(100),
                                                Forms\Components\TextInput::make('abreviatura')->label('Abreviatura')->maxLength(10),
                                            ])
                                            ->createOptionUsing(fn (array $data): int =>
                                                MeasurementUnit::create([...$data, 'empresa_id' => Filament::getTenant()->id])->getKey()),
                                        Forms\Components\TextInput::make('conversion_factor')
                                            ->label('Factor de Conversión')
                                            ->numeric()
                                            ->default(1)
                                            ->visible(fn (Forms\Get $get) => !empty($get('purchase_unit_id')))
                                            ->helperText(function (Forms\Get $get) {
                                                $pu = MeasurementUnit::find($get('purchase_unit_id'));
                                                $su = MeasurementUnit::find($get('measurement_unit_id'));
                                                $puLabel = $pu?->abreviatura ?? 'unidad compra';
                                                $suLabel = $su?->abreviatura ?? 'unidad stock';
                                                return "1 {$puLabel} = ? {$suLabel}  (ej: kg→g pondrías 1000)";
                                            }),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Lote y Caducidad')
                            ->schema([
                                Forms\Components\TextInput::make('lote')
                                    ->label('Lote o Nro. de Serie')
                                    ->maxLength(255),
                                Forms\Components\DatePicker::make('fecha_caducidad')
                                    ->label('Fecha de Caducidad'),
                            ])
                            ->columns(2)
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['insumo', 'materia_prima'])),

                        Forms\Components\Tabs\Tab::make('Contabilidad y Proveedor')
                            ->schema([
                                Forms\Components\Select::make('supplier_id')
                                    ->label('Proveedor Predeterminado')
                                    ->relationship('supplier', 'nombre')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionModalHeading('Nuevo Proveedor')
                                    ->createOptionForm(fn () => SupplierResource::getQuickCreateFormSchema())
                                    ->createOptionUsing(function (array $data): int {
                                        return \App\Models\Supplier::create([
                                            ...$data,
                                            'empresa_id' => \Filament\Facades\Filament::getTenant()->id,
                                        ])->getKey();
                                    }),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Archivos y Fichas (PDFs)')
                            ->schema([
                                Forms\Components\Repeater::make('files')
                                    ->label('Documentos PDF Relacionados')
                                    ->relationship('files')
                                    ->schema([
                                        Forms\Components\TextInput::make('nombre_archivo')
                                            ->label('Nombre Mnemónico')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('tipo')
                                            ->label('Tipo de Documento')
                                            ->options([
                                                'ficha_tecnica' => 'Ficha Técnica',
                                                'certificado' => 'Certificado de Calidad',
                                                'hoja_seguridad' => 'Hoja de Seguridad',
                                                'otro' => 'Otro',
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('descripcion')
                                            ->label('Descripción Breve')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Forms\Components\FileUpload::make('path')
                                            ->label('Archivo PDF')
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->disk('fichas')
                                            ->visibility('private')
                                            ->maxSize(5120) // 5MB max
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->maxItems(10)
                                    ->itemLabel(fn (array $state): ?string => $state['nombre_archivo'] ?? null),
                            ]),
                    ])
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('measurementUnit.abreviatura')
                    ->label('U.M.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_actual')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($record) => $record->stock_bajo ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('P. Compra')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('activo')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Filtrar por Tipo')
                    ->options([
                        'insumo' => 'Insumo',
                        'materia_prima' => 'Materia Prima',
                        'producto_terminado' => 'Producto Terminado',
                        'activo_fijo' => 'Activo Fijo',
                        'servicio' => 'Servicio',
                    ]),
                Tables\Filters\Filter::make('stock_bajo')
                    ->label('Alerta de Stock Bajo')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stock_actual', '<=', 'stock_minimo')),
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
            'index' => Pages\ListInventoryItems::route('/'),
            'create' => Pages\CreateInventoryItem::route('/create'),
            'edit' => Pages\EditInventoryItem::route('/{record}/edit'),
        ];
    }

    public static function getQuickCreateFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre del Ítem')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('type')
                ->label('Tipo de Ítem')
                ->options([
                    'insumo' => 'Insumo',
                    'materia_prima' => 'Materia Prima',
                    'producto_terminado' => 'Producto Terminado',
                    'activo_fijo' => 'Activo Fijo',
                    'servicio' => 'Servicio',
                ])
                ->required(),
            Forms\Components\Select::make('measurement_unit_id')
                ->label('Unidad de Medida')
                ->relationship('measurementUnit', 'nombre', fn ($query) => $query->where('activo', true))
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('purchase_price')
                ->label('Precio de Compra')
                ->numeric()
                ->prefix('$')
                ->default(0),
        ];
    }
}
