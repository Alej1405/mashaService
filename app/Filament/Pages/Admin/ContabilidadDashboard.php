<?php

namespace App\Filament\Pages\Admin;

use App\Models\Empresa;
use App\Models\ServiceInvoice;
use Filament\Pages\Page;

class ContabilidadDashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $navigationGroup = 'Contabilidad';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.pages.admin.contabilidad-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function getStats(): array
    {
        $ingresosMes = ServiceInvoice::where('estado', 'pagado')
            ->whereMonth('fecha_pago', now()->month)
            ->whereYear('fecha_pago', now()->year)
            ->sum('monto');

        $ingresosTotales = ServiceInvoice::where('estado', 'pagado')->sum('monto');

        $pendientes = ServiceInvoice::where('estado', 'pendiente')->get();
        $montoPendiente = $pendientes->sum('monto');

        $vencidos = ServiceInvoice::where('estado', 'vencido')
            ->orWhere(fn ($q) => $q->where('estado', 'pendiente')->where('fecha_vencimiento', '<', now()))
            ->count();

        $montoVencido = ServiceInvoice::where('estado', 'vencido')
            ->orWhere(fn ($q) => $q->where('estado', 'pendiente')->where('fecha_vencimiento', '<', now()))
            ->sum('monto');

        $porPlan = ServiceInvoice::where('estado', 'pagado')
            ->selectRaw('plan, SUM(monto) as total')
            ->groupBy('plan')
            ->pluck('total', 'plan')
            ->toArray();

        $totalEmpresas = Empresa::where('activo', true)->count();
        $empresasSinFactura = Empresa::where('activo', true)
            ->whereDoesntHave('serviceInvoices', fn($q) => $q->whereMonth('created_at', now()->month))
            ->count();

        return compact(
            'ingresosMes', 'ingresosTotales', 'montoPendiente',
            'vencidos', 'montoVencido', 'porPlan',
            'totalEmpresas', 'empresasSinFactura'
        );
    }

    public function getUltimasFacturas()
    {
        return ServiceInvoice::with('empresa')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();
    }

    public function getVencidasProximas()
    {
        return ServiceInvoice::with('empresa')
            ->where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<=', now()->addDays(7))
            ->orderBy('fecha_vencimiento')
            ->get();
    }
}
