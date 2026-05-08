<?php

namespace App\Filament\App\Widgets;

use App\Models\BankAccount;
use App\Models\CashRegister;
use App\Models\JournalEntryLine;
use App\Models\Purchase;
use App\Models\Sale;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class KpiFinancieroWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.kpi-financiero';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
    }

    protected function getViewData(): array
    {
        $empresaId = Filament::getTenant()->id;

        // Por cobrar: ventas a crédito confirmadas
        $cobranza = Sale::where('empresa_id', $empresaId)
            ->where('forma_pago', 'credito')
            ->where('estado', 'confirmado')
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total')
            ->first();

        // Por pagar: compras a crédito confirmadas
        $pagadero = Purchase::where('empresa_id', $empresaId)
            ->where('forma_pago', 'credito')
            ->where('status', 'confirmado')
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total')
            ->first();

        // Efectivo: saldo real de cajas activas
        $efectivo = CashRegister::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(saldo_actual), 0) as total')
            ->first();

        // Bancos: saldo inicial + movimientos contables confirmados para cuentas bancarias
        $bankAccounts   = BankAccount::where('empresa_id', $empresaId)->where('activo', true)->get();
        $saldoInicialBancos = $bankAccounts->sum('saldo_inicial');
        $accountPlanIds = $bankAccounts->pluck('account_plan_id')->filter();

        $movimientosBancos = 0;
        if ($accountPlanIds->isNotEmpty()) {
            $movimientosBancos = JournalEntryLine::whereIn('account_plan_id', $accountPlanIds)
                ->whereHas('journalEntry', fn ($q) => $q
                    ->where('empresa_id', $empresaId)
                    ->where('status', 'confirmado')
                )
                ->selectRaw('COALESCE(SUM(debe - haber), 0) as saldo')
                ->value('saldo') ?? 0;
        }

        $totalBancos    = (float) $saldoInicialBancos + (float) $movimientosBancos;
        $totalEfectivo  = (float) $efectivo->total;
        $porCobrar      = (float) $cobranza->total;
        $porPagar       = (float) $pagadero->total;

        // Índice de liquidez: activos líquidos disponibles vs obligaciones
        $activosLiquidos = $totalBancos + $totalEfectivo;
        $liquidez        = $porPagar > 0 ? round($activosLiquidos / $porPagar, 2) : null;

        // Alertas de errores contables
        $erroresVentas   = Sale::where('empresa_id', $empresaId)->where('error_contable', true)->count();
        $erroresCompras  = Purchase::where('empresa_id', $empresaId)->where('error_contable', true)->count();
        $sinAsientoVentas = Sale::where('empresa_id', $empresaId)
            ->where('estado', 'confirmado')
            ->whereNull('journal_entry_id')
            ->count();

        return [
            'porCobrar'       => $porCobrar,
            'porCobrarQty'    => (int) $cobranza->cantidad,
            'porPagar'        => $porPagar,
            'porPagarQty'     => (int) $pagadero->cantidad,
            'totalBancos'     => $totalBancos,
            'bancosQty'       => $bankAccounts->count(),
            'totalEfectivo'   => $totalEfectivo,
            'cajasQty'        => (int) $efectivo->cantidad,
            'liquidez'        => $liquidez,
            'activosLiquidos' => $activosLiquidos,
            'alertaContable'  => $erroresVentas + $erroresCompras + $sinAsientoVentas,
            'tenant'          => Filament::getTenant()->slug,
        ];
    }
}
