<?php

namespace App\Filament\Operaciones\Resources;

use App\Filament\Operaciones\Pages\Formulacion;
use App\Filament\Operaciones\Resources\MateriaPrimaProcesadaResource\Pages;
use App\Models\MeasurementUnit;
use App\Models\ProductDesign;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Materias primas procesadas (semielaborados): insumos que se preparan con su
 * propia receta antes de usarse en otras fórmulas (ej. azúcar pulverizada).
 *
 * Pantalla aparte del catálogo de productos a propósito: aquí no se vende nada,
 * solo se prepara. La bandera `es_semielaborado` se fija sola, para que nadie la
 * marque o desmarque por error y se crucen los catálogos.
 */
class MateriaPrimaProcesadaResource extends Resource
{
    protected static ?string $model = ProductDesign::class;
    protected static ?string $tenantRelationshipName = 'productDesigns';

    protected static ?string $navigationLabel = 'Materias primas procesadas';
    protected static ?string $navigationGroup = 'Producto';
    protected static ?int    $navigationSort  = 4;
    protected static ?string $navigationIcon  = 'heroicon-o-sparkles';
    protected static ?string $modelLabel      = 'Materia prima procesada';
    protected static ?string $pluralModelLabel = 'Materias primas procesadas';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('inventario');
    }

    /** Solo semielaborados: nunca productos de venta. */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('es_semielaborado', true);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre')
                ->placeholder('Ej. Azúcar pulverizada')
                ->required()
                ->maxLength(255)
                ->columnSpan(2),
            Forms\Components\Select::make('unidad_base_id')
                ->label('Unidad')
                ->options(fn () => MeasurementUnit::withoutGlobalScopes()
                    ->where('empresa_id', Filament::getTenant()->id)
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->pluck('nombre', 'id'))
                ->searchable()
                ->required()
                ->helperText('En qué unidad se prepara y se mide (ej. libras).')
                ->columnSpan(1),
            Forms\Components\TextInput::make('cantidad_base')
                ->label('Rinde por preparación')
                ->numeric()
                ->default(1)
                ->minValue(0.0001)
                ->helperText('Cuánto produce una preparación de su receta. El costo se reparte sobre esta cantidad.')
                ->columnSpan(1),
            Forms\Components\Toggle::make('activo')
                ->label('Activa')
                ->default(true)
                ->inline(false)
                ->columnSpan(1),
            Forms\Components\Toggle::make('solo_bajo_pedido')
                ->label('Se elabora solo cuando hay pedido')
                ->helperText('No se mantiene en stock: se prepara fresco cada vez que un pedido lo necesita. Si lo dejas apagado, se prepara solo lo que falte respecto al stock.')
                ->default(false)
                ->inline(false)
                ->columnSpan(2),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unidadBase.abreviatura')
                    ->label('Unidad')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('cantidad_base')
                    ->label('Rinde')
                    ->numeric()
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Activa')
                    ->boolean()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('receta')
                    ->label('Receta')
                    ->icon('heroicon-o-beaker')
                    ->color('gray')
                    ->url(fn (ProductDesign $record) => Formulacion::getUrl([
                        'presentacion' => $record->presentations()->value('id'),
                    ])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('Todavía no hay materias primas procesadas')
            ->emptyStateDescription('Son insumos que se preparan antes de usarse (ej. azúcar pulverizada). Crea el primero con el botón de arriba.');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMateriasPrimasProcesadas::route('/'),
            'create' => Pages\CreateMateriaPrimaProcesada::route('/create'),
            'edit'   => Pages\EditMateriaPrimaProcesada::route('/{record}/edit'),
        ];
    }
}
