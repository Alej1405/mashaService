<?php

namespace App\Filament\App\Pages\Reports;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\BankAccount;
use App\Models\CashRegister;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class InformesIndex extends Page
{
    protected static ?string $navigationGroup = 'Informes';
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Informes';
    protected static ?string $title           = 'Informes Contables';
    protected static string  $view            = 'filament.app.pages.reports.informes-index';
    protected static ?int    $navigationSort  = 1;

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
    }

    protected function getViewData(): array
    {
        $empresaId = Filament::getTenant()->id;
        $tenant    = Filament::getTenant()->slug;
        $panel     = Filament::getCurrentPanel()->getPath();

        $inicioMes = Carbon::now()->startOfMonth();
        $finMes    = Carbon::now()->endOfMonth();

        // --- Estado de Resultados: utilidad neta del mes ---
        $ingresos  = $this->sumCodigos($empresaId, '4', 'haber', $inicioMes, $finMes);
        $costos    = $this->sumCodigos($empresaId, '5', 'debe',  $inicioMes, $finMes);
        $gastos    = $this->sumCodigos($empresaId, '6', 'debe',  $inicioMes, $finMes);
        $utilidad  = $ingresos - $costos - $gastos;

        // --- Balance General: total activos ---
        $activos   = $this->sumCodigos($empresaId, '1', 'debe',  null, null);

        // --- Flujo de Caja: disponible actual ---
        $bancos    = BankAccount::where('empresa_id', $empresaId)->where('activo', true)->sum('saldo_inicial');
        $efectivo  = CashRegister::where('empresa_id', $empresaId)->where('activo', true)->sum('saldo_actual');
        $disponible = (float) $bancos + (float) $efectivo;

        // --- Balance de Comprobación: cuadrado o diferencia ---
        $debe      = JournalEntryLine::whereHas('journalEntry', fn($q) =>
                        $q->where('empresa_id', $empresaId)->where('status', 'confirmado'))
                        ->sum('debe');
        $haber     = JournalEntryLine::whereHas('journalEntry', fn($q) =>
                        $q->where('empresa_id', $empresaId)->where('status', 'confirmado'))
                        ->sum('haber');
        $diferencia = abs((float)$debe - (float)$haber);

        // --- Libro Diario: asientos del mes ---
        $asientosMes = JournalEntry::where('empresa_id', $empresaId)
            ->where('status', 'confirmado')
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->count();

        // --- Libro Mayor: última fecha de movimiento ---
        $ultimoAsiento = JournalEntry::where('empresa_id', $empresaId)
            ->where('status', 'confirmado')
            ->max('fecha');

        $base = "/{$panel}/{$tenant}";

        return [
            'informes' => [
                [
                    'titulo'      => 'Estado de Resultados',
                    'descripcion' => '¿Qué tan rentables somos?',
                    'icono'       => 'heroicon-o-arrow-trending-up',
                    'color'       => $utilidad >= 0 ? 'emerald' : 'rose',
                    'metrica'     => '$' . number_format(abs($utilidad), 2),
                    'etiqueta'    => ($utilidad >= 0 ? 'Utilidad neta' : 'Pérdida neta') . ' ' . now()->translatedFormat('M Y'),
                    'url'         => $base . '/reports/estado-resultados',
                ],
                [
                    'titulo'      => 'Balance General',
                    'descripcion' => '¿Cuánto vale la empresa?',
                    'icono'       => 'heroicon-o-scale',
                    'color'       => 'blue',
                    'metrica'     => '$' . number_format($activos, 2),
                    'etiqueta'    => 'Total activos acumulados',
                    'url'         => $base . '/reports/balance-general',
                ],
                [
                    'titulo'      => 'Flujo de Caja',
                    'descripcion' => '¿Cómo va el efectivo?',
                    'icono'       => 'heroicon-o-banknotes',
                    'color'       => 'amber',
                    'metrica'     => '$' . number_format($disponible, 2),
                    'etiqueta'    => 'Disponible en banco + caja',
                    'url'         => $base . '/reports/flujo-caja',
                ],
                [
                    'titulo'      => 'Balance de Comprobación',
                    'descripcion' => '¿Los libros cuadran?',
                    'icono'       => 'heroicon-o-check-circle',
                    'color'       => $diferencia < 0.01 ? 'emerald' : 'rose',
                    'metrica'     => $diferencia < 0.01 ? 'Cuadrado' : '$' . number_format($diferencia, 2),
                    'etiqueta'    => $diferencia < 0.01 ? 'Debe = Haber ✓' : 'Diferencia detectada',
                    'url'         => $base . '/reports/balance-comprobacion',
                ],
                [
                    'titulo'      => 'Libro Diario',
                    'descripcion' => 'Registro cronológico de asientos',
                    'icono'       => 'heroicon-o-book-open',
                    'color'       => 'purple',
                    'metrica'     => (string) $asientosMes,
                    'etiqueta'    => 'Asientos en ' . now()->translatedFormat('F Y'),
                    'url'         => $base . '/reports/libro-diario',
                ],
                [
                    'titulo'      => 'Libro Mayor',
                    'descripcion' => 'Movimientos por cuenta contable',
                    'icono'       => 'heroicon-o-document-text',
                    'color'       => 'slate',
                    'metrica'     => $ultimoAsiento ? Carbon::parse($ultimoAsiento)->translatedFormat('d M') : '—',
                    'etiqueta'    => 'Último movimiento registrado',
                    'url'         => $base . '/reports/libro-mayor',
                ],
            ],
        ];
    }

    private function sumCodigos(int $empresaId, string $prefijo, string $columna, $desde, $hasta): float
    {
        return (float) JournalEntryLine::whereHas('journalEntry', function ($q) use ($empresaId, $desde, $hasta) {
            $q->where('empresa_id', $empresaId)->where('status', 'confirmado');
            if ($desde && $hasta) {
                $q->whereBetween('fecha', [$desde, $hasta]);
            }
        })
        ->whereHas('accountPlan', fn($q) => $q->where('code', 'like', $prefijo . '.%'))
        ->sum($columna);
    }
}
