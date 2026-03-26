<?php

namespace App\Filament\App\Pages;

use App\Models\Debt;
use App\Models\DebtPayment;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DebtReportPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Resumen de Deudas';

    protected static ?string $title = 'Informe Global de Deudas';

    protected static ?string $navigationGroup = 'Financiamiento';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.app.pages.debt-report';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
    }

    public function getViewData(): array
    {
        $empresaId = Filament::getTenant()->id;

        // Queries con empresa_id explícito para evitar problemas de scope
        $base = Debt::where('empresa_id', $empresaId);

        $stats = [
            'total_deudas'    => (clone $base)->count(),
            'monto_total'     => (clone $base)->whereIn('estado', ['activa', 'parcial', 'vencida'])->sum('monto_original'),
            'saldo_pendiente' => (clone $base)->whereIn('estado', ['activa', 'parcial', 'vencida'])->sum('saldo_pendiente'),
            'total_pagado'    => (clone $base)->sum('total_pagado'),
            'deudas_vencidas' => (clone $base)->where('estado', 'vencida')->count(),
            'deudas_activas'  => (clone $base)->whereIn('estado', ['activa', 'parcial'])->count(),
            'deudas_pagadas'  => (clone $base)->where('estado', 'pagada')->count(),
            'proximas_cuotas' => \App\Models\DebtAmortizationLine::whereHas('debt', fn ($q) => $q->where('empresa_id', $empresaId)->whereIn('estado', ['activa', 'parcial']))
                ->where('estado', 'pendiente')
                ->where('fecha_vencimiento', '<=', now()->addDays(30)->toDateString())
                ->count(),
        ];

        $proximas = \App\Models\DebtAmortizationLine::whereHas('debt', fn ($q) => $q->where('empresa_id', $empresaId)->whereIn('estado', ['activa', 'parcial']))
            ->where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<=', now()->addDays(30)->toDateString())
            ->with('debt')
            ->orderBy('fecha_vencimiento')
            ->get();

        return compact('stats', 'proximas');
    }

    public function table(Table $table): Table
    {
        $empresaId = Filament::getTenant()->id;

        return $table
            ->query(Debt::where('empresa_id', $empresaId)->with(['payments']))
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('N°')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('acreedor')
                    ->label('Acreedor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'prestamo_bancario'    => 'Bancario',
                        'tarjeta_credito'      => 'Tarjeta',
                        'prestamo_personal'    => 'Personal',
                        'prestamo_empresarial' => 'Empresarial',
                        default                => 'Otro',
                    }),

                Tables\Columns\TextColumn::make('monto_original')
                    ->label('Monto Original')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('total_pagado')
                    ->label('Pagado')
                    ->money('USD')
                    ->color('success'),

                Tables\Columns\TextColumn::make('saldo_pendiente')
                    ->label('Pendiente')
                    ->money('USD')
                    ->weight('bold')
                    ->color(fn ($record) => $record->saldo_pendiente > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('porcentaje_pagado')
                    ->label('% Pagado')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->badge()
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state > 0 ? 'warning' : 'gray')),

                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->color(fn ($record) => $record->fecha_vencimiento < now()->toDateString() && $record->estado !== 'pagada' ? 'danger' : null),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'activa'       => 'Activa',
                        'parcial'      => 'Parcial',
                        'vencida'      => 'Vencida',
                        'pagada'       => 'Pagada',
                        'borrador'     => 'Borrador',
                        'refinanciada' => 'Refinanciada',
                    ]),
            ])
            ->defaultSort('estado')
            ->striped();
    }
}
