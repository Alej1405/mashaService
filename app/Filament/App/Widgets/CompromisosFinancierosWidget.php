<?php

namespace App\Filament\App\Widgets;

use App\Models\CostoFijo;
use App\Models\Debt;
use App\Models\DebtAmortizationLine;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class CompromisosFinancierosWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.compromisos-financieros';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 7;

    public static function canView(): bool
    {
        return \App\Helpers\PlanHelper::can('pro');
    }

    protected function getViewData(): array
    {
        $empresaId = Filament::getTenant()->id;

        // Costos fijos operativos mensuales
        $costosFijosMensual = CostoFijo::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->get()
            ->sum(fn ($c) => $c->monto_mensual);

        // Cuotas del mes actual no pagadas
        $cuotasMesActual = DebtAmortizationLine::whereHas('debt', fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereMonth('fecha_vencimiento', now()->month)
            ->whereYear('fecha_vencimiento', now()->year)
            ->where('estado', '!=', 'pagada')
            ->sum('total_cuota');

        // Saldo total deudas activas
        $saldoTotalDeudas = Debt::where('empresa_id', $empresaId)
            ->whereIn('estado', ['activa', 'parcial', 'vencida'])
            ->sum('saldo_pendiente');

        // Cuotas morosas (vencidas sin pagar)
        $cuotasMorosas = DebtAmortizationLine::whereHas('debt', fn ($q) => $q->where('empresa_id', $empresaId))
            ->where('estado', 'vencida')
            ->with('debt')
            ->orderBy('fecha_vencimiento')
            ->get();

        $totalMoroso = $cuotasMorosas->sum('total_cuota');

        // Próximas cuotas (60 días), excluyendo vencidas (ya en morosas)
        $proximasCuotas = DebtAmortizationLine::whereHas('debt', fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereBetween('fecha_vencimiento', [now()->toDateString(), now()->addDays(60)->toDateString()])
            ->where('estado', 'pendiente')
            ->with('debt')
            ->orderBy('fecha_vencimiento')
            ->get();

        return [
            'costosFijosMensual' => round($costosFijosMensual, 2),
            'cuotasMesActual'    => round((float) $cuotasMesActual, 2),
            'totalMensual'       => round($costosFijosMensual + (float) $cuotasMesActual, 2),
            'saldoTotalDeudas'   => round((float) $saldoTotalDeudas, 2),
            'cuotasMorosas'      => $cuotasMorosas,
            'totalMoroso'        => round((float) $totalMoroso, 2),
            'proximasCuotas'     => $proximasCuotas,
            'mesNombre'          => ucfirst(now()->translatedFormat('F Y')),
        ];
    }
}
