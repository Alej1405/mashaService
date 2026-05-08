<?php

namespace App\Filament\App\Widgets;

use App\Models\BankAccount;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\ProductionOrder;
use App\Models\Purchase;
use App\Models\Sale;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class SaldosCuentasWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.saldos-cuentas';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
    }

    protected function getViewData(): array
    {
        $empresaId = Filament::getTenant()->id;
        $panelPath = filament()->getCurrentPanel()->getPath();
        $tenant    = Filament::getTenant()->slug;

        $cajas = CashRegister::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->get(['nombre', 'tipo', 'saldo_actual']);

        $totalEfectivo = $cajas->sum('saldo_actual');

        $bancos = BankAccount::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->with('bank')
            ->get(['id', 'bank_id', 'numero_cuenta', 'tipo_cuenta', 'nombre_titular', 'saldo_inicial']);

        $ventasBorrador = Sale::where('empresa_id', $empresaId)
            ->where('estado', 'borrador')
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total')
            ->first();

        $comprasBorrador = Purchase::where('empresa_id', $empresaId)
            ->where('status', 'borrador')
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total')
            ->first();

        $clientesNuevosMes = Customer::where('empresa_id', $empresaId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $produccionActiva = ProductionOrder::where('empresa_id', $empresaId)
            ->where('estado', 'borrador')
            ->count();

        return [
            'cajas'              => $cajas,
            'totalEfectivo'      => (float) $totalEfectivo,
            'bancos'             => $bancos,
            'totalBancos'        => $bancos->count(),
            'ventasBorrador'     => $ventasBorrador,
            'comprasBorrador'    => $comprasBorrador,
            'clientesNuevosMes'  => $clientesNuevosMes,
            'produccionActiva'   => $produccionActiva,
            'panelPath'          => $panelPath,
            'tenant'             => $tenant,
        ];
    }
}
