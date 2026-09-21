<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Concerns\DelSitioPropio;
use App\Filament\Admin\Resources\SitioPlanResource\Pages;
use App\Models\SitioPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SitioPlanResource extends Resource
{
    use DelSitioPropio;

    protected static ?string $model = SitioPlan::class;

    protected static ?string $tenantRelationshipName = null;
    protected static ?string $navigationIcon         = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel        = 'Precios';
    protected static ?string $navigationGroup        = 'Sitio MashaCorp';
    protected static ?int    $navigationSort         = 3;
    protected static ?string $modelLabel             = 'Precio';
    protected static ?string $pluralModelLabel       = 'Precios';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Select::make('pagina_slug')
                    ->label('Sección')
                    ->options(fn (): array => array_combine(config('sitio.paginas'), config('sitio.paginas')))
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('nombre')->label('Nombre')->required()->maxLength(120),
                Forms\Components\Textarea::make('descripcion')->label('Descripción')->rows(2)->columnSpanFull(),
                Forms\Components\TextInput::make('precio_desde')
                    ->label('Precio desde')
                    ->helperText('Vacío = "a convenir". El sitio muestra el precio sin pedir cotización.')
                    ->numeric()->prefix('$'),
                Forms\Components\Select::make('periodicidad')
                    ->label('Periodicidad')
                    ->options(['unico' => 'Pago único', 'mensual' => 'Mensual', 'anual' => 'Anual'])
                    ->default('unico')
                    ->native(false),
                Forms\Components\Repeater::make('incluye')
                    ->label('Qué incluye')
                    ->schema([Forms\Components\TextInput::make('texto')->label('Ítem')->required()->maxLength(200)])
                    ->addActionLabel('Agregar ítem')
                    ->defaultItems(0)
                    ->collapsible()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('nota')->label('Nota al pie')->maxLength(200)->columnSpanFull(),
                Forms\Components\Toggle::make('destacado')->label('Destacado'),
                Forms\Components\Toggle::make('activo')->label('Visible')->default(true),
                Forms\Components\TextInput::make('sort_order')->label('Orden')->numeric()->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('pagina_slug')->label('Sección')->badge(),
                Tables\Columns\TextColumn::make('nombre')->label('Plan')->weight('semibold')->searchable(),
                Tables\Columns\TextColumn::make('precio_desde')->label('Desde')->money('USD')->placeholder('A convenir'),
                Tables\Columns\TextColumn::make('periodicidad')->label('Periodicidad')->color('gray'),
                Tables\Columns\ToggleColumn::make('activo')->label('Visible'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSitioPlanes::route('/')];
    }
}
