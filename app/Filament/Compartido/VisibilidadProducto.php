<?php

namespace App\Filament\Compartido;

use App\Models\Customer;
use App\Models\StoreProduct;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Columns\TextColumn;

/**
 * Quién ve un producto de la tienda: todos los clientes, o uno solo.
 * Lo usan los catálogos de Operaciones y de Ecommerce: un solo sitio.
 */
final class VisibilidadProducto
{
    /** Campos del formulario: general o exclusivo, y el cliente cuando es exclusivo. */
    public static function campos(): array
    {
        return [
            Radio::make('visibilidad')
                ->label('Quién lo ve')
                ->options(StoreProduct::VISIBILIDADES)
                ->default('general')
                ->live()
                ->required()
                ->columnSpanFull(),
            Select::make('customer_id')
                ->label('Cliente')
                ->helperText('Solo este cliente lo verá en la tienda y podrá pedirlo.')
                ->options(fn () => Customer::withoutGlobalScopes()
                    ->where('empresa_id', Filament::getTenant()->id)->where('activo', true)
                    ->get()->sortBy('nombre_completo')->pluck('nombre_completo', 'id'))
                ->searchable()
                ->visible(fn (Get $get) => $get('visibilidad') === 'exclusivo')
                ->required(fn (Get $get) => $get('visibilidad') === 'exclusivo')
                ->columnSpanFull(),
        ];
    }

    /** Columna de la tabla: «General» o el nombre del cliente. */
    public static function columna(): TextColumn
    {
        return TextColumn::make('visibilidad')
            ->label('Visibilidad')
            ->badge()
            ->formatStateUsing(fn (StoreProduct $record) => $record->visibilidad === 'exclusivo'
                ? 'Solo ' . ($record->cliente?->nombre_completo ?? 'un cliente')
                : 'General')
            ->color(fn (string $state) => $state === 'exclusivo' ? 'warning' : 'gray');
    }

    /** Acción masiva: la misma visibilidad para todos los productos seleccionados. */
    public static function accionMasiva(): BulkAction
    {
        return BulkAction::make('visibilidad')
            ->label('Cambiar quién los ve')
            ->icon('heroicon-o-eye')
            ->modalHeading('Quién ve los productos seleccionados')
            ->modalSubmitActionLabel('Aplicar')
            ->form(self::campos())
            ->action(function (Collection $records, array $data) {
                $records->each(fn (StoreProduct $p) => $p->update([
                    'visibilidad' => $data['visibilidad'],
                    'customer_id' => $data['customer_id'] ?? null,
                ]));

                $n = $records->count();
                $cliente = ($data['visibilidad'] === 'exclusivo')
                    ? Customer::withoutGlobalScopes()->find($data['customer_id'])?->nombre_completo
                    : null;

                Notification::make()
                    ->title($n === 1 ? '1 producto actualizado' : "{$n} productos actualizados")
                    ->body($cliente ? "Ahora solo los ve {$cliente}." : 'Ahora los ven todos los clientes.')
                    ->success()->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
