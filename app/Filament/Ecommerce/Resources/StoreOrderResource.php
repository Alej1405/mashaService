<?php

namespace App\Filament\Ecommerce\Resources;

use App\Filament\Ecommerce\Resources\StoreOrderResource\Pages;
use App\Models\Customer;
use App\Models\StoreOrder;
use App\Models\StoreProduct;
use App\Services\StoreOrderService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StoreOrderResource extends Resource
{
    protected static ?string $model = StoreOrder::class;

    protected static ?string $tenantRelationshipName = 'storeOrders';
    protected static ?string $navigationIcon         = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel        = 'Órdenes';
    protected static ?string $navigationGroup        = 'Ventas';
    protected static ?string $modelLabel             = 'Orden';
    protected static ?string $pluralModelLabel       = 'Órdenes';
    protected static ?int    $navigationSort         = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ── INGRESAR pedido (solo al crear) ─────────────────────────────
            Select::make('customer_id')
                ->label('Cliente')
                ->options(fn () => Customer::query()->orderBy('nombre')
                    ->get()->mapWithKeys(fn ($c) => [$c->id => trim(($c->nombre ?? '') . ' ' . ($c->apellido ?? ''))]))
                ->searchable()
                ->required()
                ->visible(fn (string $operation) => $operation === 'create')
                ->columnSpanFull(),
            Repeater::make('items')
                ->label('Productos del pedido')
                ->schema([
                    Select::make('store_product_id')
                        ->label('Producto')
                        ->options(fn () => StoreProduct::query()->where('publicado', true)->orderBy('nombre')->pluck('nombre', 'id'))
                        ->searchable()
                        ->required()
                        ->columnSpan(2),
                    TextInput::make('cantidad')
                        ->label('Cantidad')
                        ->numeric()
                        ->minValue(0.0001)
                        ->default(1)
                        ->required()
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->minItems(1)
                ->addActionLabel('Agregar producto')
                ->dehydrated(false)
                ->visible(fn (string $operation) => $operation === 'create')
                ->columnSpanFull(),

            // ── GESTIÓN (solo al editar) ────────────────────────────────────
            Select::make('estado')->label('Estado')
                ->options(['pendiente'=>'Pendiente','pagado'=>'Pagado','receptado'=>'Receptado','procesando'=>'Procesando','enviado'=>'Enviado','entregado'=>'Entregado','cancelado'=>'Cancelado'])
                ->required()->native(false)
                ->visible(fn (string $operation) => $operation === 'edit'),
            Select::make('estado_pago')->label('Estado de Pago')
                ->options(['pendiente'=>'Pendiente','aprobado'=>'Aprobado','fallido'=>'Fallido','reembolsado'=>'Reembolsado'])
                ->required()->native(false)
                ->visible(fn (string $operation) => $operation === 'edit'),
            Textarea::make('notas_cliente')->label('Notas')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero')->label('Orden')->searchable()->sortable()->weight('bold'),
                TextColumn::make('customer.nombre')->label('Cliente')->searchable()
                    ->formatStateUsing(fn ($record) => trim(($record->customer?->nombre ?? '') . ' ' . ($record->customer?->apellido ?? ''))),
                TextColumn::make('total')->label('Total')->money('USD')->sortable(),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->color(fn ($state) => match ($state) {
                        'pendiente'  => 'warning', 'pagado' => 'info', 'receptado' => 'primary', 'procesando' => 'primary',
                        'enviado'    => 'info',    'entregado' => 'success', 'cancelado' => 'danger', default => 'gray',
                    }),
                TextColumn::make('estado_pago')->label('Pago')->badge()
                    ->color(fn ($state) => match ($state) {
                        'aprobado'    => 'success', 'pendiente' => 'warning',
                        'fallido'     => 'danger',  'reembolsado' => 'gray', default => 'gray',
                    }),
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estado')->options(['pendiente'=>'Pendiente','pagado'=>'Pagado','receptado'=>'Receptado','procesando'=>'Procesando','enviado'=>'Enviado','entregado'=>'Entregado','cancelado'=>'Cancelado'])->native(false),
                SelectFilter::make('estado_pago')->options(['pendiente'=>'Pendiente','aprobado'=>'Aprobado','fallido'=>'Fallido','reembolsado'=>'Reembolsado'])->native(false),
            ])
            ->actions([
                Action::make('confirmar_pago')
                    ->label('Confirmar pago')->icon('heroicon-o-check-badge')->color('success')
                    ->visible(fn (StoreOrder $r) => !$r->sale_id && $r->estado_pago !== 'aprobado')
                    ->requiresConfirmation()
                    ->modalHeading('¿Confirmar pago?')
                    ->modalDescription('Se creará una Venta en el ERP y se actualizará el inventario.')
                    ->action(function (StoreOrder $record) {
                        try {
                            app(StoreOrderService::class)->confirmOrder($record);
                            Notification::make()->title('Pago confirmado')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('aceptar')
                    ->label('Aceptar pedido')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (StoreOrder $r) => in_array($r->estado, ['pendiente', 'pagado'], true))
                    ->requiresConfirmation()
                    ->modalHeading('¿Aceptar el pedido?')
                    ->modalDescription('Pasa a «receptado». El inventario disponible se descuenta automáticamente; lo que falte se mapea a producción.')
                    ->action(function (StoreOrder $record) {
                        try {
                            $record->update(['estado' => 'receptado']);
                            Notification::make()->title('Pedido receptado')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),
                EditAction::make()->label('Estado'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStoreOrders::route('/'),
            'create' => Pages\CreateStoreOrder::route('/create'),
            'edit'   => Pages\EditStoreOrder::route('/{record}/edit'),
        ];
    }
}
