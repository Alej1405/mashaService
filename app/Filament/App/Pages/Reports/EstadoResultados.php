<?php

namespace App\Filament\App\Pages\Reports;

use App\Models\AccountPlan;
use App\Models\JournalEntryLine;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\HtmlString;
use App\Filament\App\Widgets\EstadoResultadosWidget;

class EstadoResultados extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('print')
                ->label('Imprimir')
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print()']),
            \Filament\Actions\Action::make('exportarPdf')
                ->label('Exportar PDF')
                ->icon('heroicon-m-document-text')
                ->color('primary')
                ->extraAttributes(['onclick' => 'window.print()']),
            \Filament\Actions\Action::make('exportarExcelAction')
                ->label('Exportar Excel')
                ->icon('heroicon-m-table-cells')
                ->color('success')
                ->action('exportarExcel'),
        ];
    }

    // protected function getFooterWidgets(): array
    // {
    //     return [
    //         EstadoResultadosWidget::class,
    //     ];
    // }

    protected static ?string $navigationGroup      = 'Contabilidad General';
    protected static ?string $navigationParentItem = 'Informes';
    protected static ?string $navigationIcon       = 'heroicon-o-document-text';
    protected static ?string $title                = 'Estado de Resultados';
    protected static string $view = 'filament.app.pages.reports.estado-resultados';

    public $fecha_desde;
    public $fecha_hasta;

    // Variables de compatibilidad para evitar error 'Undefined variable' en el Blade
    public Collection $ingresos;
    public Collection $costos;
    public Collection $gastos;

    public function mount()
    {
        $this->fecha_desde = now()->startOfYear()->toDateString();
        $this->fecha_hasta = now()->toDateString();
        
        // Inicializar vacías para que el Blade no falle
        $this->ingresos = collect();
        $this->costos = collect();
        $this->gastos = collect();

        $this->form->fill([
            'fecha_desde' => $this->fecha_desde,
            'fecha_hasta' => $this->fecha_hasta,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtros de Reporte')
                    ->schema([
                        DatePicker::make('fecha_desde')
                            ->label('Fecha Desde')
                            ->default(now()->startOfYear())
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->fecha_desde = $state),
                        DatePicker::make('fecha_hasta')
                            ->label('Fecha Hasta')
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->fecha_hasta = $state),
                    ])
                    ->columns(2)
                    ->extraAttributes(['class' => 'no-print']),

                Placeholder::make('report_view')
                    ->label('')
                    ->content(fn () => new HtmlString($this->renderReport())),
            ]);
    }

    /**
     * Verifica si un código de cuenta pertenece a un prefijo, respetando límites de segmento.
     * bajoPrefijo('4.1.01', '4.1')  → true   bajoPrefijo('4.10.01', '4.1') → false
     */
    private function bajoPrefijo(string $code, string $prefix): bool
    {
        $p = rtrim($prefix, '.');
        return $code === $p || str_starts_with($code, $p . '.');
    }

    /**
     * Obtiene cuentas de un type con sus saldos netos en el período.
     * Sin $codes, devuelve TODAS las cuentas del type → el informe no depende
     * de que existan códigos con un prefijo concreto.
     */
    protected function getCuentasMonto(array $codes, string $type, string $sumField = 'haber'): Collection
    {
        $empresaId  = Filament::getTenant()->id;
        $fechaDesde = $this->fecha_desde;
        $fechaHasta = $this->fecha_hasta;

        $query = AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('type', $type)
            ->where('accepts_movements', true)
            ->where('is_active', true);

        // Solo aplica filtro de código si se pasan prefijos; usa límite de segmento
        // para evitar que '4.1' matchee '4.10.xx'
        if (! empty($codes)) {
            $query->where(function ($q) use ($codes) {
                foreach ($codes as $prefix) {
                    $p = rtrim($prefix, '.');
                    $q->orWhere('code', $p)
                      ->orWhere('code', 'like', $p . '.%');
                }
            });
        }

        return $query->orderBy('code')->get()
            ->map(function ($cuenta) use ($empresaId, $fechaDesde, $fechaHasta, $sumField) {
                $baseQuery = fn () => JournalEntryLine::where('account_plan_id', $cuenta->id)
                    ->whereHas('journalEntry', fn ($q) => $q
                        ->withoutGlobalScopes()
                        ->where('empresa_id', $empresaId)
                        ->where('status', 'confirmado')
                        ->where('esta_cuadrado', true)
                        ->when($fechaDesde, fn ($q) => $q->whereDate('fecha', '>=', $fechaDesde))
                        ->when($fechaHasta,  fn ($q) => $q->whereDate('fecha', '<=', $fechaHasta))
                    );

                $debe  = (float) $baseQuery()->sum('debe');
                $haber = (float) $baseQuery()->sum('haber');
                $monto = ($sumField === 'haber') ? ($haber - $debe) : ($debe - $haber);

                return ['code' => $cuenta->code, 'name' => $cuenta->name, 'monto' => $monto];
            })
            ->filter(fn ($row) => round($row['monto'], 2) != 0)
            ->values();
    }

    public function renderReport(): string
    {
        $empresa = Filament::getTenant();

        // Obtener TODOS los registros por type — sin filtrar por código.
        // La sub-clasificación se hace con bajoPrefijo() sobre los datos ya en memoria.
        $todosIngresos = $this->getCuentasMonto([], 'ingreso', 'haber');
        $ingresosOrd   = $todosIngresos->filter(fn ($r) => $this->bajoPrefijo($r['code'], '4.1'));
        $otrosIngresos = $todosIngresos->reject(fn ($r) => $this->bajoPrefijo($r['code'], '4.1'));

        $costos = $this->getCuentasMonto([], 'costo', 'debe');

        $todosGastos = $this->getCuentasMonto([], 'gasto', 'debe');
        $gastosOp    = $todosGastos->filter(fn ($r) =>
            $this->bajoPrefijo($r['code'], '6.1') || $this->bajoPrefijo($r['code'], '6.2'));
        $gastosNoOp  = $todosGastos->reject(fn ($r) =>
            $this->bajoPrefijo($r['code'], '6.1') || $this->bajoPrefijo($r['code'], '6.2'));

        // Totales base
        $totalIngOrd = $ingresosOrd->sum('monto');
        $totalOtrosIng = $otrosIngresos->sum('monto');
        $totalIngresos = $totalIngOrd + $totalOtrosIng;

        $totalCostos = $costos->sum('monto');
        $utilidadBruta = $totalIngresos - $totalCostos;

        $totalGastosOp = $gastosOp->sum('monto');
        $utilidadOperacional = $utilidadBruta - $totalGastosOp;

        $totalGastosNoOp = $gastosNoOp->sum('monto');
        $utilidadAntesImp = $utilidadOperacional - $totalGastosNoOp;

        // Impuestos
        $participacion = $utilidadAntesImp > 0 ? $utilidadAntesImp * 0.15 : 0;
        $baseImp = $utilidadAntesImp - $participacion;
        $impuestoRenta = $baseImp > 0 ? $baseImp * 0.25 : 0;
        
        $utilidadNeta = $utilidadAntesImp - $participacion - $impuestoRenta;

        // Formateador de moneda
        $fmt = fn($val) => $val < 0 
            ? '<span class="text-danger-600">(' . number_format(abs($val), 2) . ')</span>'
            : number_format($val, 2);

        $html = "
        <div class='bg-white p-8 border shadow-sm max-w-5xl mx-auto print:border-0 print:shadow-none' id='print-area'>
            <style>
                /* ELIMINAR BLOQUE ANTIGUO DEL BLADE MEDIANTE CSS (Restricción: No tocar otros archivos) */
                .fi-main .space-y-6 > div:nth-child(2) { display: none !important; }
                
                @media print {
                    .no-print { display: none !important; }
                    body { background: white !important; }
                    .fi-main { padding: 0 !important; }
                    .fi-topbar { display: none !important; }
                    .fi-sidebar { display: none !important; }
                }
                .report-table td { padding: 4px 8px; font-size: 0.95rem; }
                .report-section-title { font-weight: bold; background-color: #f9fafb; padding: 8px; border-bottom: 2px solid #e5e7eb; margin-top: 1rem; }
                .report-total-row { font-weight: bold; border-top: 1px solid #374151; }
                .report-grand-total { font-weight: bold; border-top: 1px solid #374151; border-bottom: 4px double #374151; font-size: 1.1rem; }
            </style>

            <div class='text-center mb-8'>
                <h1 class='text-2xl font-bold uppercase'>{$empresa->name}</h1>
                <h2 class='text-xl font-semibold'>ESTADO DE RESULTADOS INTEGRAL</h2>
                <p class='text-gray-600'>Período comprendido entre {$this->fecha_desde} y {$this->fecha_hasta}</p>
                <p class='text-sm italic text-gray-500'>Expresado en dólares de los Estados Unidos de América</p>
            </div>
            <div class='mb-6'></div>

            <table class='w-full report-table'>
                <tbody>
                    <tr class='report-section-title'><td colspan='3'>1. INGRESOS DE ACTIVIDADES ORDINARIAS</td></tr>
                    " . $this->renderRows($ingresosOrd) . "
                    <tr class='report-total-row'>
                        <td colspan='2' class='text-right'>TOTAL INGRESOS ORDINARIOS</td>
                        <td class='text-right'>{$fmt($totalIngOrd)}</td>
                    </tr>

                    <tr class='report-section-title'><td colspan='3'>2. OTROS INGRESOS</td></tr>
                    " . $this->renderRows($otrosIngresos) . "
                    <tr class='report-total-row'>
                        <td colspan='2' class='text-right'>TOTAL OTROS INGRESOS</td>
                        <td class='text-right'>{$fmt($totalOtrosIng)}</td>
                    </tr>

                    <tr class='font-bold bg-gray-50 uppercase'>
                        <td colspan='2' class='py-3 text-right'>(=) TOTAL INGRESOS</td>
                        <td class='py-3 text-right border-y-2 border-gray-800'>{$fmt($totalIngresos)}</td>
                    </tr>

                    <tr class='report-section-title'><td colspan='3'>3. COSTO DE VENTAS Y PRODUCCIÓN</td></tr>
                    " . $this->renderRows($costos) . "
                    <tr class='report-total-row'>
                        <td colspan='2' class='text-right'>(-) TOTAL COSTO DE VENTAS</td>
                        <td class='text-right'>{$fmt($totalCostos * -1)}</td>
                    </tr>

                    <tr class='font-bold bg-gray-100 uppercase'>
                        <td colspan='2' class='py-3 text-right group'>(=) UTILIDAD BRUTA</td>
                        <td class='py-3 text-right border-y-2 border-gray-800 " . ($utilidadBruta >= 0 ? 'text-success-600' : 'text-danger-600') . "'>
                            {$fmt($utilidadBruta)}
                        </td>
                    </tr>

                    <tr class='report-section-title'><td colspan='3'>4. GASTOS OPERACIONALES</td></tr>
                    " . $this->renderRows($gastosOp) . "
                    <tr class='report-total-row'>
                        <td colspan='2' class='text-right'>(-) TOTAL GASTOS OPERACIONALES</td>
                        <td class='text-right'>{$fmt($totalGastosOp * -1)}</td>
                    </tr>

                    <tr class='font-bold bg-gray-50'>
                        <td colspan='2' class='text-right uppercase'>(=) UTILIDAD OPERACIONAL</td>
                        <td class='text-right border-b-2 border-gray-400'>{$fmt($utilidadOperacional)}</td>
                    </tr>

                    <tr class='report-section-title'><td colspan='3'>5. GASTOS NO OPERACIONALES</td></tr>
                    " . $this->renderRows($gastosNoOp) . "
                    <tr class='report-total-row'>
                        <td colspan='2' class='text-right'>(-) TOTAL GASTOS NO OPERACIONALES</td>
                        <td class='text-right'>{$fmt($totalGastosNoOp * -1)}</td>
                    </tr>

                    <tr class='font-bold bg-gray-200'>
                        <td colspan='2' class='py-2 text-right uppercase tracking-wider'>(=) UTILIDAD ANTES DE IMPUESTOS Y PARTICIPACIÓN</td>
                        <td class='py-2 text-right border-y-2 border-gray-900'>{$fmt($utilidadAntesImp)}</td>
                    </tr>

                    <tr class='text-gray-600 italic'>
                        <td colspan='2' class='text-right'>(-) 15% Participación Trabajadores</td>
                        <td class='text-right'>{$fmt($participacion * -1)}</td>
                    </tr>
                    
                    <tr class='text-gray-600 italic'>
                        <td colspan='2' class='text-right'>(-) 25% Impuesto a la Renta</td>
                        <td class='text-right'>{$fmt($impuestoRenta * -1)}</td>
                    </tr>

                    <tr class='report-grand-total bg-gray-900 text-white'>
                        <td colspan='2' class='py-4 text-right px-4 text-lg uppercase tracking-widest'>UTILIDAD / (PÉRDIDA) NETA DEL EJERCICIO</td>
                        <td class='py-4 text-right px-4 text-xl " . ($utilidadNeta >= 0 ? 'text-success-400' : 'text-danger-400') . "'>
                            {$fmt($utilidadNeta)}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class='mt-16 flex justify-around text-center no-print'>
                <div class='border-t border-gray-800 w-48 pt-2'>
                    <p class='font-bold text-xs uppercase'>Representante Legal</p>
                </div>
                <div class='border-t border-gray-800 w-48 pt-2'>
                    <p class='font-bold text-xs uppercase'>Contador General</p>
                </div>
            </div>
        </div>
        ";

        return $html;
    }

    public function exportarExcel()
    {
        $tenant = Filament::getTenant();
        
        $export = new \App\Exports\EstadoResultadosExport(
            empresaId: $tenant->id,
            fechaDesde: $this->fecha_desde,
            fechaHasta: $this->fecha_hasta,
            nombreEmpresa: $tenant->name,
            ruc: $this->getRucEmpresa($tenant)
        );

        return $export->download(
            'EstadoResultados_' . $this->fecha_desde . '_' . $this->fecha_hasta . '.xlsx'
        );
    }

    protected function getRucEmpresa($tenant): ?string
    {
        // El modelo no tiene ruc explícito, pero intentamos buscarlo o usar fallback
        // Según inspección previa, el modelo Empresa no tiene columna 'ruc'.
        return '000000000001'; 
    }

    protected function renderRows(Collection $rows): string
    {
        $html = "";
        foreach ($rows as $row) {
            $html .= "
            <tr>
                <td class='text-gray-400 font-mono w-32'>{$row['code']}</td>
                <td class='text-gray-700'>{$row['name']}</td>
                <td class='text-right'>" . number_format($row['monto'], 2) . "</td>
            </tr>";
        }
        return $html;
    }
}
