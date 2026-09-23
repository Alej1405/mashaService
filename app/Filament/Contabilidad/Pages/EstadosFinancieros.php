<?php

namespace App\Filament\Contabilidad\Pages;

use App\Filament\Contabilidad\Resources\PlanDeCuentasResource;
use App\Models\JournalEntryLine;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

/**
 * Los estados financieros que recibe la Superintendencia.
 *
 * Se arman con la línea que cada cuenta declara en el plan, no adivinando por
 * el prefijo del código, y siempre con el comparativo del ejercicio anterior:
 * bajo NIIF la columna del año pasado es parte del estado.
 */
class EstadosFinancieros extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Estados financieros';
    protected static ?string $title           = 'Estados financieros';
    protected static ?int    $navigationSort  = 4;
    protected static string  $view            = 'filament.contabilidad.estados-financieros';

    public ?int $anio = null;
    public string $estado = 'Estado de situación financiera';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    public function mount(): void
    {
        $this->anio = (int) request()->integer('anio', now()->year);
        $this->estado = request()->string('estado')->toString() ?: 'Estado de situación financiera';
    }

    /** Saldo por línea del estado en un ejercicio. */
    private function porLinea(int $empresaId, int $anio): array
    {
        return JournalEntryLine::query()
            ->join('journal_entries as a', 'a.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('account_plans as c', 'c.id', '=', 'journal_entry_lines.account_plan_id')
            ->where('a.empresa_id', $empresaId)
            ->where('a.status', 'confirmado')
            ->whereYear('a.fecha', $anio)
            ->whereNotNull('c.linea_estado')
            ->groupBy('c.linea_estado', 'c.nature')
            ->selectRaw('c.linea_estado, c.nature, sum(journal_entry_lines.debe) as debe, sum(journal_entry_lines.haber) as haber')
            ->get()
            ->mapWithKeys(function ($f) {
                $valor = $f->nature === 'deudora'
                    ? (float) $f->debe - (float) $f->haber
                    : (float) $f->haber - (float) $f->debe;

                return [$f->linea_estado => round($valor, 2)];
            })
            ->all();
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $anio    = $this->anio ?? now()->year;

        $actual   = $this->porLinea($empresa->id, $anio);
        $anterior = $this->porLinea($empresa->id, $anio - 1);

        $estructura = PlanDeCuentasResource::LINEAS[$this->estado] ?? [];

        $filas = [];
        foreach ($estructura as $clave => $etiqueta) {
            $filas[] = [
                'etiqueta' => $etiqueta,
                'actual'   => $actual[$clave] ?? 0.0,
                'anterior' => $anterior[$clave] ?? 0.0,
            ];
        }

        $sinMapear = app(\App\Services\ContabilidadService::class)->cuentasSinMapear($empresa->id);

        return [
            'empresa'    => $empresa,
            'anio'       => $anio,
            'estado'     => $this->estado,
            'estados'    => array_keys(PlanDeCuentasResource::LINEAS),
            'filas'      => $filas,
            'totalA'     => array_sum(array_column($filas, 'actual')),
            'totalB'     => array_sum(array_column($filas, 'anterior')),
            'sinMapear'  => $sinMapear,
        ];
    }
}
