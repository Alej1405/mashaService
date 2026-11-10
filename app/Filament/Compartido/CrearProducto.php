<?php

namespace App\Filament\Compartido;

use App\Filament\Operaciones\Resources\ProductDesignResource;
use App\Models\ProductDesign;
use App\Models\StoreCategory;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Crear un producto con sus presentaciones, igual que en Diseño de producto.
 * Cada presentación se vende como un producto: al guardarla nacen su ítem de
 * inventario y su producto de tienda (sin publicar). Lo usan el catálogo de
 * Operaciones y el de la tienda.
 */
final class CrearProducto
{
    public static function accion(): Action
    {
        return Action::make('crearProducto')
            ->label('Crear producto')
            ->icon('heroicon-o-plus')
            ->modalHeading('Nuevo producto')
            ->modalDescription('Cada presentación se vende como un producto de la tienda, con su código del SRI.')
            ->modalSubmitActionLabel('Crear producto')
            ->modalWidth('5xl')
            ->form([
                TextInput::make('nombre')
                    ->label('Nombre del producto')
                    ->placeholder('Ron Dorado')
                    ->required()
                    ->maxLength(150),
                Select::make('store_category_id')
                    ->label('Categoría de tienda')
                    ->options(fn () => StoreCategory::where('empresa_id', Filament::getTenant()->id)
                        ->where('publicado', true)->pluck('nombre', 'id'))
                    ->searchable(),
                Repeater::make('presentaciones')
                    ->label('Presentaciones')
                    ->schema([
                        ...ProductDesignResource::camposPresentacion(),
                        TextInput::make('pvp_estimado')
                            ->label('PVP')
                            ->numeric()
                            ->prefix('$')
                            ->columnSpan(1),
                    ])
                    ->columns(7)
                    ->minItems(1)
                    ->defaultItems(1)
                    ->addActionLabel('+ Agregar presentación'),
            ])
            ->action(function (array $data) {
                $diseno = DB::transaction(function () use ($data) {
                    $diseno = ProductDesign::create([
                        'empresa_id'                     => Filament::getTenant()->id,
                        'nombre'                         => $data['nombre'],
                        'store_category_id'              => $data['store_category_id'] ?? null,
                        'tiene_multiples_presentaciones' => count($data['presentaciones']) > 1,
                    ]);

                    foreach ($data['presentaciones'] as $p) {
                        $diseno->presentations()->create($p + ['activa' => true, 'cantidad_minima_produccion' => 1]);
                    }

                    return $diseno;
                });

                $n = count($data['presentaciones']);
                Notification::make()
                    ->title("{$diseno->nombre} creado")
                    ->body(($n === 1 ? '1 presentación' : "{$n} presentaciones")
                        . ' en el catálogo, sin publicar. Su fórmula se arma en Formulación.')
                    ->success()->send();
            });
    }
}
