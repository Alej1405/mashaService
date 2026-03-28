<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CostoFijoResource\Pages;
use App\Models\CostoFijo;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CostoFijoResource extends Resource
{
    protected static ?string $model = CostoFijo::class;

    protected static ?string $tenantRelationshipName = 'costosFijos';

    protected static ?string $navigationIcon   = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel  = 'Costos Fijos';
    protected static ?string $navigationGroup  = 'Planificación';
    protected static ?string $modelLabel       = 'Costo Fijo';
    protected static ?string $pluralModelLabel = 'Costos Fijos';
    protected static ?int    $navigationSort   = 10;

    public static function canAccess(): bool
    {
        $panel = Filament::getCurrentPanel()?->getId();

        return in_array($panel, ['pro', 'enterprise'])
            && \App\Helpers\PlanHelper::can('pro');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('nombre')
                ->label('Nombre')
                ->required()
                ->maxLength(255)
                ->placeholder('Ej. Arriendo local, Energía eléctrica…')
                ->columnSpan(2),

            Select::make('categoria')
                ->label('Categoría')
                ->options([
                    'Instalaciones'  => 'Instalaciones',
                    'Servicios'      => 'Servicios básicos',
                    'Personal'       => 'Personal administrativo',
                    'Tecnología'     => 'Tecnología y software',
                    'Marketing'      => 'Marketing y publicidad',
                    'Financiero'     => 'Financiero y bancario',
                    'Operativo'      => 'Operativo (otros)',
                ])
                ->required()
                ->default('Operativo')
                ->columnSpan(1),

            TextInput::make('monto')
                ->label('Monto')
                ->numeric()
                ->required()
                ->prefix('$')
                ->minValue(0)
                ->columnSpan(1),

            Select::make('frecuencia')
                ->label('Frecuencia de pago')
                ->options([
                    'mensual'     => 'Mensual',
                    'trimestral'  => 'Trimestral',
                    'semestral'   => 'Semestral',
                    'anual'       => 'Anual',
                ])
                ->required()
                ->default('mensual')
                ->helperText('El costo mensual equivalente se calcula automáticamente.')
                ->columnSpan(1),

            Toggle::make('activo')
                ->label('Activo')
                ->default(true)
                ->helperText('Los costos inactivos no se incluyen en los cálculos de planificación.')
                ->columnSpan(1),

            Textarea::make('descripcion')
                ->label('Descripción / Notas')
                ->rows(2)
                ->nullable()
                ->columnSpanFull(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Concepto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('categoria')
                    ->label('Categoría')
                    ->badge()
                    ->color('info'),

                TextColumn::make('monto')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('frecuencia')
                    ->label('Frecuencia')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'mensual'    => 'success',
                        'trimestral' => 'warning',
                        'semestral'  => 'info',
                        'anual'      => 'gray',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'mensual'    => 'Mensual',
                        'trimestral' => 'Trimestral',
                        'semestral'  => 'Semestral',
                        'anual'      => 'Anual',
                        default      => $state,
                    }),

                TextColumn::make('monto_mensual')
                    ->label('Equiv. mensual')
                    ->money('USD')
                    ->getStateUsing(fn (CostoFijo $record): float => $record->monto_mensual)
                    ->color('primary')
                    ->weight('bold'),

                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('categoria')
            ->filters([
                SelectFilter::make('categoria')
                    ->options([
                        'Instalaciones'  => 'Instalaciones',
                        'Servicios'      => 'Servicios básicos',
                        'Personal'       => 'Personal administrativo',
                        'Tecnología'     => 'Tecnología y software',
                        'Marketing'      => 'Marketing y publicidad',
                        'Financiero'     => 'Financiero y bancario',
                        'Operativo'      => 'Operativo (otros)',
                    ]),
                TernaryFilter::make('activo'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([])
            ->emptyStateHeading('Sin costos fijos registrados')
            ->emptyStateDescription('Agrega los gastos fijos de tu empresa: arriendo, luz, agua, sueldos administrativos, etc.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCostosFijos::route('/'),
            'create' => Pages\CreateCostoFijo::route('/create'),
            'edit'   => Pages\EditCostoFijo::route('/{record}/edit'),
        ];
    }
}
