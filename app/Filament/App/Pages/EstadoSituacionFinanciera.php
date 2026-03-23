<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use App\Models\AccountPlan;
use Filament\Facades\Filament;
use Barryvdh\DomPDF\Facade\Pdf;

class EstadoSituacionFinanciera extends Page
{
    protected static ?string $navigationIcon       = 'heroicon-o-document-currency-dollar';
    protected static ?string $navigationGroup      = 'Contabilidad General';
    protected static ?string $navigationParentItem = 'Informes';
    protected static ?string $navigationLabel      = 'Estado de Resultados (SUPERCIAS)';
    protected static ?string $title = 'Estado de Resultados Integrales (SUPERCIAS)';

    protected static string $view = 'filament.app.pages.estado-situacion-financiera';

    public static function canAccess(): bool
    {
        return true;
    }

    public function getViewData(): array
    {
        return [
            'ingresos' => $this->getAccountsByType('ingreso'),
            'costos' => $this->getAccountsByType('costo'),
            'gastos' => $this->getAccountsByType('gasto'),
            'empresa' => Filament::getTenant(),
        ];
    }

    private function getAccountsByType(string $type)
    {
        $tenantId = Filament::getTenant()->id;
        
        return AccountPlan::where('empresa_id', $tenantId)
            ->where('type', $type)
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                $account->saldo = 0.00; // Simulated for now
                return $account;
            });
    }

    public function downloadPdf()
    {
        $data = $this->getViewData();
        $pdf = Pdf::loadView('reports.estado-resultados', $data);
        return response()->streamDownload(fn () => print($pdf->output()), 'estado-resultados-' . date('Ymd') . '.pdf');
    }
}

