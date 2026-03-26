<?php

namespace App\Filament\App\Resources\DebtResource\Pages;

use App\Filament\App\Resources\DebtResource;
use App\Models\Debt;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewDebt extends ViewRecord
{
    protected static string $resource = DebtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('activar')
                ->label('Activar Deuda')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Activar Deuda')
                ->modalDescription('Se generará el asiento contable y la tabla de amortización. Esta acción no se puede deshacer fácilmente.')
                ->visible(fn () => $this->record->estado === 'borrador')
                ->action(function () {
                    try {
                        $this->record->update(['estado' => 'activa']);
                        $this->record->refresh();
                        \Filament\Notifications\Notification::make()
                            ->title('Deuda activada')
                            ->body("Asiento contable y tabla de amortización generados correctamente.")
                            ->success()
                            ->send();
                        $this->refreshFormData(['estado', 'journal_entry_id', 'saldo_pendiente']);
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error al activar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('imprimir_historial')
                ->label('Imprimir Historial')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('debt.payments.print', [
                    'empresa' => \Filament\Facades\Filament::getTenant()->slug,
                    'debt'    => $this->record->id,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Información del Préstamo')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('numero')
                        ->label('N° Deuda')
                        ->copyable()
                        ->weight('bold'),

                    Infolists\Components\TextEntry::make('tipo')
                        ->label('Tipo')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'prestamo_bancario'    => 'Préstamo Bancario',
                            'tarjeta_credito'      => 'Tarjeta de Crédito',
                            'prestamo_personal'    => 'Préstamo Personal',
                            'prestamo_empresarial' => 'Préstamo Empresarial',
                            default                => 'Otro',
                        })
                        ->color(fn ($state) => match ($state) {
                            'prestamo_bancario'    => 'info',
                            'tarjeta_credito'      => 'warning',
                            'prestamo_personal'    => 'primary',
                            'prestamo_empresarial' => 'success',
                            default                => 'gray',
                        }),

                    Infolists\Components\TextEntry::make('estado')
                        ->label('Estado')
                        ->badge()
                        ->formatStateUsing(fn ($state) => ucfirst($state))
                        ->color(fn ($state) => match ($state) {
                            'borrador'     => 'gray',
                            'activa'       => 'info',
                            'parcial'      => 'warning',
                            'pagada'       => 'success',
                            'vencida'      => 'danger',
                            'refinanciada' => 'primary',
                            default        => 'gray',
                        }),

                    Infolists\Components\TextEntry::make('acreedor')
                        ->label('Acreedor'),

                    Infolists\Components\TextEntry::make('clasificacion')
                        ->label('Clasificación')
                        ->formatStateUsing(fn ($state) => $state === 'corriente' ? 'Corriente (≤ 12 meses)' : 'No Corriente (> 12 meses)'),

                    Infolists\Components\TextEntry::make('descripcion')
                        ->label('Propósito')
                        ->columnSpanFull(),
                ]),

            Infolists\Components\Section::make('Condiciones Financieras')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('monto_original')
                        ->label('Monto Original')
                        ->money('USD')
                        ->weight('bold'),

                    Infolists\Components\TextEntry::make('saldo_pendiente')
                        ->label('Saldo Pendiente')
                        ->money('USD')
                        ->weight('bold')
                        ->color(fn ($record) => $record->saldo_pendiente > 0 ? 'danger' : 'success'),

                    Infolists\Components\TextEntry::make('total_pagado')
                        ->label('Total Pagado')
                        ->money('USD')
                        ->color('success'),

                    Infolists\Components\TextEntry::make('tasa_interes')
                        ->label('Tasa de Interés')
                        ->formatStateUsing(fn ($state, $record) => number_format($state, 2) . '% ' . ucfirst($record->frecuencia_tasa) . ' (' . ucfirst($record->tipo_tasa) . ')'),

                    Infolists\Components\TextEntry::make('fecha_inicio')
                        ->label('Fecha Inicio')
                        ->date('d/m/Y'),

                    Infolists\Components\TextEntry::make('fecha_vencimiento')
                        ->label('Fecha Vencimiento')
                        ->date('d/m/Y')
                        ->color(fn ($record) => $record->fecha_vencimiento < now()->toDateString() && $record->estado !== 'pagada' ? 'danger' : null),

                    Infolists\Components\TextEntry::make('plazo_meses')
                        ->label('Plazo')
                        ->formatStateUsing(fn ($state) => $state ? "{$state} meses" : '—'),

                    Infolists\Components\TextEntry::make('numero_cuotas')
                        ->label('Cuotas')
                        ->formatStateUsing(fn ($state) => $state ?? 'Pago único'),

                    Infolists\Components\TextEntry::make('porcentaje_pagado')
                        ->label('% Pagado')
                        ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                        ->badge()
                        ->color(fn ($state) => $state >= 100 ? 'success' : ($state > 0 ? 'warning' : 'gray')),
                ]),

            Infolists\Components\Section::make('Datos del Acreedor')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Infolists\Components\TextEntry::make('cuenta_pago_acreedor')
                        ->label('Cuenta del Acreedor')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('banco_acreedor')
                        ->label('Banco del Acreedor')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('bankAccount.nombre_completo')
                        ->label('Nuestra Cuenta Bancaria')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('journalEntry.numero')
                        ->label('Asiento Contable')
                        ->placeholder('Pendiente')
                        ->badge()
                        ->color('info'),
                ]),

            Infolists\Components\Section::make('Notas')
                ->collapsed()
                ->schema([
                    Infolists\Components\TextEntry::make('notas')
                        ->label('')
                        ->placeholder('Sin notas')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
