<?php

namespace App\Filament\App\Pages\Reports;

use App\Models\AccountPlan;
use App\Models\JournalEntryLine;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\HtmlString;

class BalanceGeneral extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationGroup = 'Informes Financieros';
    protected static ?string $navigationIcon  = 'heroicon-o-scale';
    protected static ?string $title           = 'Balance General';
    protected static string  $view            = 'filament.app.pages.reports.balance-general';

    public $fecha_corte;

    // Colecciones vacías para que el Blade no falle antes del primer render
    public Collection $activos;
    public Collection $pasivos;
    public Collection $patrimonio;

    public function mount(): void
    {
        $this->fecha_corte = now()->toDateString();
        $this->activos     = collect();
        $this->pasivos     = collect();
        $this->patrimonio  = collect();

        $this->form->fill(['fecha_corte' => $this->fecha_corte]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('print')
                ->label('Imprimir')
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print()']),
            \Filament\Actions\Action::make('exportarExcelAction')
                ->label('Exportar Excel')
                ->icon('heroicon-m-table-cells')
                ->color('success')
                ->action('exportarExcel'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Período de reporte')
                    ->schema([
                        DatePicker::make('fecha_corte')
                            ->label('Fecha de corte')
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(fn($state) => $this->fecha_corte = $state),
                    ])
                    ->columns(1)
                    ->extraAttributes(['class' => 'no-print']),

                Placeholder::make('report_view')
                    ->label('')
                    ->content(fn() => new HtmlString($this->renderReport())),
            ]);
    }

    // -------------------------------------------------------------------------
    // Carga de saldos
    // -------------------------------------------------------------------------

    protected function getSaldos(): Collection
    {
        $empresaId  = Filament::getTenant()->id;
        $fechaCorte = $this->fecha_corte;

        return AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->whereIn('type', ['activo', 'pasivo', 'patrimonio'])
            ->where('accepts_movements', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(function ($cuenta) use ($empresaId, $fechaCorte) {
                $baseQ = fn() => JournalEntryLine::where('account_plan_id', $cuenta->id)
                    ->whereHas('journalEntry', fn($q) => $q
                        ->withoutGlobalScopes()
                        ->where('empresa_id', $empresaId)
                        ->where('status', 'confirmado')
                        ->where('esta_cuadrado', true)
                        ->when($fechaCorte, fn($q) => $q->whereDate('fecha', '<=', $fechaCorte))
                    );

                $debe  = (float) $baseQ()->sum('debe');
                $haber = (float) $baseQ()->sum('haber');

                $cuenta->saldo = $cuenta->nature === 'deudora'
                    ? $debe - $haber
                    : $haber - $debe;

                return $cuenta;
            })
            ->filter(fn($c) => round($c->saldo, 2) != 0);
    }

    // -------------------------------------------------------------------------
    // Render HTML (formato Supercias)
    // -------------------------------------------------------------------------

    public function renderReport(): string
    {
        $empresa  = Filament::getTenant();
        $cuentas  = $this->getSaldos();

        // Agrupaciones según CUC Supercias
        $activosCte    = $cuentas->filter(fn($c) => str_starts_with($c->code, '1.1'));
        $activosNoCte  = $cuentas->filter(fn($c) => str_starts_with($c->code, '1.2'));
        $pasivosCte    = $cuentas->filter(fn($c) => str_starts_with($c->code, '2.1'));
        $pasivosNoCte  = $cuentas->filter(fn($c) => str_starts_with($c->code, '2.2'));
        $patrimonioAll = $cuentas->filter(fn($c) => $c->type === 'patrimonio');

        $tActCte   = $activosCte->sum('saldo');
        $tActNoCte = $activosNoCte->sum('saldo');
        $tActivos  = $tActCte + $tActNoCte;

        $tPasCte   = $pasivosCte->sum('saldo');
        $tPasNoCte = $pasivosNoCte->sum('saldo');
        $tPasivos  = $tPasCte + $tPasNoCte;

        $tPat      = $patrimonioAll->sum('saldo');
        $tPasPat   = $tPasivos + $tPat;

        $cuadra    = abs($tActivos - $tPasPat) < 0.01;

        $fmt = fn($val) => $val < 0
            ? '<span style="color:#dc2626;">(' . number_format(abs($val), 2) . ')</span>'
            : number_format($val, 2);

        $rows = fn(Collection $col) => $col->map(fn($c) => "
            <tr>
                <td style='font-family:monospace;color:#6b7280;white-space:nowrap;padding:3px 8px;'>{$c->code}</td>
                <td style='padding:3px 8px 3px " . (($c->level - 1) * 16 + 8) . "px;'>{$c->name}</td>
                <td style='text-align:right;padding:3px 8px;'>" . $fmt($c->saldo) . "</td>
            </tr>")->implode('');

        $balanceIndicator = $cuadra
            ? "<div style='background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:8px 16px;border-radius:6px;font-weight:bold;font-size:0.85rem;'>✔ Balance cuadrado</div>"
            : "<div style='background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:8px 16px;border-radius:6px;font-weight:bold;font-size:0.85rem;'>⚠ DESCUADRADO — diferencia: " . $fmt($tActivos - $tPasPat) . "</div>";

        $html = "
        <div style='background:#fff;padding:2rem;max-width:900px;margin:0 auto;font-family:Arial,sans-serif;font-size:0.875rem;' id='print-area'>
        <style>
            @media print {
                .no-print { display:none !important; }
                body { background:white !important; }
                .fi-main { padding:0 !important; }
                .fi-topbar { display:none !important; }
                .fi-sidebar { display:none !important; }
            }
            .bg-table { width:100%; border-collapse:collapse; }
            .bg-table td { vertical-align:top; }
            .section-title {
                font-weight:bold; font-size:0.8rem; letter-spacing:.05em;
                text-transform:uppercase; padding:6px 8px;
                background:#f3f4f6; border-bottom:2px solid #d1d5db;
            }
            .subtotal-row td { font-weight:bold; border-top:1px solid #9ca3af; padding:4px 8px; }
            .total-row td { font-weight:bold; border-top:2px solid #374151; border-bottom:4px double #374151; padding:5px 8px; font-size:0.95rem; }
            .grand-total td { font-weight:bold; background:#111827; color:#fff; padding:8px; font-size:1rem; }
        </style>

        <!-- Encabezado Supercias -->
        <div style='text-align:center;margin-bottom:1.5rem;'>
            <p style='font-size:0.8rem;color:#6b7280;'>SUPERINTENDENCIA DE COMPAÑÍAS, VALORES Y SEGUROS</p>
            <h1 style='font-size:1.3rem;font-weight:800;text-transform:uppercase;margin:4px 0;'>{$empresa->name}</h1>
            <h2 style='font-size:1.1rem;font-weight:700;margin:4px 0;'>ESTADO DE SITUACIÓN FINANCIERA</h2>
            <p style='color:#374151;'>Al {$this->fecha_corte}</p>
            <p style='font-size:0.8rem;font-style:italic;color:#6b7280;'>Expresado en dólares de los Estados Unidos de América</p>
        </div>

        <!-- Cuerpo: dos columnas -->
        <table class='bg-table'>
        <tr>

        <!-- ========== ACTIVOS ========== -->
        <td style='width:50%;padding-right:1rem;vertical-align:top;'>
            <table style='width:100%;border-collapse:collapse;'>
                <tr><td colspan='3' style='font-weight:900;font-size:1rem;text-transform:uppercase;
                    padding:6px 8px;background:#1e3a5f;color:#fff;'>ACTIVOS</td></tr>

                <tr><td colspan='3' class='section-title'>1. Activos Corrientes</td></tr>
                {$rows($activosCte)}
                <tr class='subtotal-row'>
                    <td colspan='2' style='text-align:right;'>Total Activos Corrientes</td>
                    <td style='text-align:right;'>" . $fmt($tActCte) . "</td>
                </tr>

                <tr><td colspan='3' class='section-title'>2. Activos No Corrientes</td></tr>
                {$rows($activosNoCte)}
                <tr class='subtotal-row'>
                    <td colspan='2' style='text-align:right;'>Total Activos No Corrientes</td>
                    <td style='text-align:right;'>" . $fmt($tActNoCte) . "</td>
                </tr>

                <tr class='total-row'>
                    <td colspan='2' style='text-align:right;text-transform:uppercase;'>TOTAL ACTIVOS</td>
                    <td style='text-align:right;'>" . $fmt($tActivos) . "</td>
                </tr>
            </table>
        </td>

        <!-- ========== PASIVOS + PATRIMONIO ========== -->
        <td style='width:50%;padding-left:1rem;vertical-align:top;border-left:2px solid #e5e7eb;'>
            <table style='width:100%;border-collapse:collapse;'>
                <tr><td colspan='3' style='font-weight:900;font-size:1rem;text-transform:uppercase;
                    padding:6px 8px;background:#7f1d1d;color:#fff;'>PASIVOS</td></tr>

                <tr><td colspan='3' class='section-title'>1. Pasivos Corrientes</td></tr>
                {$rows($pasivosCte)}
                <tr class='subtotal-row'>
                    <td colspan='2' style='text-align:right;'>Total Pasivos Corrientes</td>
                    <td style='text-align:right;'>" . $fmt($tPasCte) . "</td>
                </tr>

                <tr><td colspan='3' class='section-title'>2. Pasivos No Corrientes</td></tr>
                {$rows($pasivosNoCte)}
                <tr class='subtotal-row'>
                    <td colspan='2' style='text-align:right;'>Total Pasivos No Corrientes</td>
                    <td style='text-align:right;'>" . $fmt($tPasNoCte) . "</td>
                </tr>

                <tr class='total-row'>
                    <td colspan='2' style='text-align:right;text-transform:uppercase;'>TOTAL PASIVOS</td>
                    <td style='text-align:right;'>" . $fmt($tPasivos) . "</td>
                </tr>

                <tr style='height:8px;'><td colspan='3'></td></tr>

                <tr><td colspan='3' style='font-weight:900;font-size:1rem;text-transform:uppercase;
                    padding:6px 8px;background:#14532d;color:#fff;'>PATRIMONIO NETO</td></tr>
                {$rows($patrimonioAll)}
                <tr class='total-row'>
                    <td colspan='2' style='text-align:right;text-transform:uppercase;'>TOTAL PATRIMONIO</td>
                    <td style='text-align:right;'>" . $fmt($tPat) . "</td>
                </tr>

                <tr class='grand-total'>
                    <td colspan='2' style='text-align:right;text-transform:uppercase;letter-spacing:.05em;'>
                        TOTAL PASIVOS + PATRIMONIO
                    </td>
                    <td style='text-align:right;'>" . $fmt($tPasPat) . "</td>
                </tr>
            </table>
        </td>

        </tr>
        </table>

        <!-- Estado de cuadratura -->
        <div style='margin-top:1.5rem;display:flex;justify-content:flex-end;'>
            {$balanceIndicator}
        </div>

        <!-- Firmas -->
        <div style='margin-top:3rem;display:flex;justify-content:space-around;text-align:center;' class='no-print'>
            <div style='border-top:1px solid #374151;width:180px;padding-top:6px;'>
                <p style='font-weight:bold;font-size:0.75rem;text-transform:uppercase;'>Representante Legal</p>
            </div>
            <div style='border-top:1px solid #374151;width:180px;padding-top:6px;'>
                <p style='font-weight:bold;font-size:0.75rem;text-transform:uppercase;'>Contador General</p>
            </div>
        </div>
        </div>";

        return $html;
    }

    // -------------------------------------------------------------------------
    // Exportar Excel (formato Supercias)
    // -------------------------------------------------------------------------

    public function exportarExcel()
    {
        $tenant = Filament::getTenant();

        $export = new \App\Exports\BalanceGeneralExport(
            empresaId:      $tenant->id,
            fechaCorte:     $this->fecha_corte,
            nombreEmpresa:  $tenant->name,
        );

        return $export->download(
            'BalanceGeneral_' . $this->fecha_corte . '.xlsx'
        );
    }
}
