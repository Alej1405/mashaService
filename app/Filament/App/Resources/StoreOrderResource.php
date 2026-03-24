<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\StoreOrderResource\Pages;
use App\Models\StoreOrder;
use App\Services\StoreOrderService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StoreOrderResource extends Resource
{
    protected static ?string $model = StoreOrder::class;

    protected static ?string $tenantRelationshipName = 'storeOrders';

    protected static ?string $navigationIcon   = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel  = 'Órdenes';
    protected static ?string $navigationGroup  = 'E-Commerce';
    protected static ?string $modelLabel       = 'Orden';
    protected static ?string $pluralModelLabel = 'Órdenes';
    protected static ?int    $navigationSort   = 3;

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'enterprise'
            && \App\Helpers\PlanHelper::can('enterprise');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('estado')
                ->label('Estado')
                ->options([
                    'pendiente'  => 'Pendiente',
                    'pagado'     => 'Pagado',
                    'procesando' => 'Procesando',
                    'enviado'    => 'Enviado',
                    'entregado'  => 'Entregado',
                    'cancelado'  => 'Cancelado',
                ])
                ->required(),
            Select::make('estado_pago')
                ->label('Estado de Pago')
                ->options([
                    'pendiente'   => 'Pendiente',
                    'aprobado'    => 'Aprobado',
                    'fallido'     => 'Fallido',
                    'reembolsado' => 'Reembolsado',
                ])
                ->required(),
            Textarea::make('notas_cliente')
                ->label('Notas')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero')
                    ->label('Orden')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('customer.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->formatStateUsing(fn ($record) =>
                        trim(($record->customer?->nombre ?? '') . ' ' . ($record->customer?->apellido ?? ''))),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pendiente'  => 'warning',
                        'pagado'     => 'info',
                        'procesando' => 'primary',
                        'enviado'    => 'info',
                        'entregado'  => 'success',
                        'cancelado'  => 'danger',
                        default      => 'gray',
                    }),
                TextColumn::make('estado_pago')
                    ->label('Pago')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'aprobado'    => 'success',
                        'pendiente'   => 'warning',
                        'fallido'     => 'danger',
                        'reembolsado' => 'gray',
                        default       => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estado')
                    ->options([
                        'pendiente'  => 'Pendiente',
                        'pagado'     => 'Pagado',
                        'procesando' => 'Procesando',
                        'enviado'    => 'Enviado',
                        'entregado'  => 'Entregado',
                        'cancelado'  => 'Cancelado',
                    ]),
                SelectFilter::make('estado_pago')
                    ->options([
                        'pendiente'   => 'Pendiente',
                        'aprobado'    => 'Aprobado',
                        'fallido'     => 'Fallido',
                        'reembolsado' => 'Reembolsado',
                    ]),
            ])
            ->actions([
                Action::make('confirmar_pago')
                    ->label('Confirmar pago')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (StoreOrder $record) =>
                        !$record->sale_id && $record->estado_pago !== 'aprobado')
                    ->requiresConfirmation()
                    ->modalHeading('¿Confirmar pago?')
                    ->modalDescription('Se creará una Venta en el ERP y se actualizará el stock e inventario contable.')
                    ->action(function (StoreOrder $record) {
                        try {
                            app(StoreOrderService::class)->confirmOrder($record);
                            Notification::make()->title('Pago confirmado')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                        }
                    }),
                EditAction::make()->label('Estado'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStoreOrders::route('/'),
            'edit'  => Pages\EditStoreOrder::route('/{record}/edit'),
        ];
    }
}
