<?php

namespace App\Filament\App\Pages\Reports;

use App\Models\JournalEntryLine;
use App\Models\AccountPlan;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;

class FlujoCaja extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationGroup      = 'Contabilidad General';
    protected static ?string $navigationParentItem = 'Informes';
    protected static ?string $navigationIcon       = 'heroicon-o-document-text';
    protected static ?string $title                = 'Flujo de Caja';
    protected static string $view = 'filament.app.pages.reports.flujo-caja';

    public $fecha_desde;
    public $fecha_hasta;
    public Collection $cuentasData;
    public float $total_saldo_inicial = 0;
    public float $total_entradas = 0;
    public float $total_salidas = 0;
    public float $total_saldo_final = 0;

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

    public function exportarExcel()
    {
        $tenant = Filament::getTenant();

        $export = new \App\Exports\FlujoCajaExport(
            empresaId:     $tenant->id,
            fechaDesde:    $this->fecha_desde,
            fechaHasta:    $this->fecha_hasta,
            nombreEmpresa: $tenant->name,
        );

        return $export->download('FlujoCaja_' . $this->fecha_desde . '_' . $this->fecha_hasta . '.xlsx');
    }

    public function mount()
    {
        $this->fecha_desde = now()->startOfYear()->toDateString();
        $this->fecha_hasta = now()->toDateString();
        $this->form->fill([
            'fecha_desde' => $this->fecha_desde,
            'fecha_hasta' => $this->fecha_hasta,
        ]);
        $this->loadData();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('fecha_desde')
                    ->label('Desde')
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->fecha_desde = $state;
                        $this->loadData();
                    }),
                DatePicker::make('fecha_hasta')
                    ->label('Hasta')
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->fecha_hasta = $state;
                        $this->loadData();
                    }),
            ])
            ->columns(2);
    }

    protected function loadData()
    {
        $empresaId = Filament::getTenant()->id;

        $cuentasEfectivo = AccountPlan::where('empresa_id', $empresaId)
            ->where('code', 'like', '1.1.01.%')
            ->where('is_active', true)
            ->get();

        $this->cuentasData = collect();
        $this->total_saldo_inicial = 0;
        $this->total_entradas = 0;
        $this->total_salidas = 0;

        foreach ($cuentasEfectivo as $cuenta) {
            $entradas = (float) JournalEntryLine::where('account_plan_id', $cuenta->id)
                ->whereHas('journalEntry', fn($q) => $q
                    ->withoutGlobalScopes()
                    ->where('empresa_id', $empresaId)
                    ->where('status', 'confirmado')
                    ->where('esta_cuadrado', true)
                    ->whereBetween('fecha', [$this->fecha_desde, $this->fecha_hasta])
                )->sum('debe');

            $salidas = (float) JournalEntryLine::where('account_plan_id', $cuenta->id)
                ->whereHas('journalEntry', fn($q) => $q
                    ->withoutGlobalScopes()
                    ->where('empresa_id', $empresaId)
                    ->where('status', 'confirmado')
                    ->where('esta_cuadrado', true)
                    ->whereBetween('fecha', [$this->fecha_desde, $this->fecha_hasta])
                )->sum('haber');

            $saldoInicial = (float) JournalEntryLine::where('account_plan_id', $cuenta->id)
                ->whereHas('journalEntry', fn($q) => $q
                    ->withoutGlobalScopes()
                    ->where('empresa_id', $empresaId)
                    ->where('status', 'confirmado')
                    ->where('esta_cuadrado', true)
                    ->whereDate('fecha', '<', $this->fecha_desde)
                )->selectRaw('SUM(debe) - SUM(haber) as saldo')
                ->value('saldo') ?? 0;

            $saldoFinal = $saldoInicial + $entradas - $salidas;

            $this->cuentasData->push((object) [
                'name' => $cuenta->name,
                'code' => $cuenta->code,
                'saldo_inicial' => $saldoInicial,
                'entradas' => $entradas,
                'salidas' => $salidas,
                'saldo_final' => $saldoFinal,
            ]);

            $this->total_saldo_inicial += $saldoInicial;
            $this->total_entradas += $entradas;
            $this->total_salidas += $salidas;
        }

        $this->total_saldo_final = $this->total_saldo_inicial + $this->total_entradas - $this->total_salidas;
    }
}
