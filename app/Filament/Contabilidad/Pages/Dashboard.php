<?php

namespace App\Filament\Contabilidad\Pages;

use App\Models\EjercicioContable;
use App\Models\JournalEntry;
use App\Services\ContabilidadService;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Inicio de Contabilidad.
 *
 * Los KPI no son adornos: son las obligaciones que se presentan mes a mes al
 * SRI y el estado de situación que recibe la Superintendencia cada abril.
 */
class Dashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Inicio';
    protected static ?string $title           = 'Contabilidad';
    protected static ?int    $navigationSort  = -2;
    protected static string  $view            = 'filament.contabilidad.dashboard';

    public static function getRoutePath(): string
    {
        return '/';
    }

    public function getHeading(): string
    {
        return 'Contabilidad';
    }

    public function getSubheading(): ?string
    {
        $empresa = Filament::getTenant();
        $marco = app(ContabilidadService::class)->clasificacion($empresa->id)['marco'];

        return collect([$empresa->name, 'ejercicio ' . now()->year, $marco])->join(' · ');
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $conta   = app(ContabilidadService::class);
        $anio    = now()->year;

        $saldos        = $conta->saldos($empresa->id);
        $clasificacion = $conta->clasificacion($empresa->id);
        $sinMapear     = app(\App\Services\MapeoSuperciasService::class)->porRevisar($empresa->id);

        $mes = now()->startOfMonth();
        $asientosDelMes = JournalEntry::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->whereBetween('fecha', [$mes->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();

        // Lo que se declara cada mes. El valor del IVA sale de los asientos;
        // el vencimiento depende del noveno dígito del RUC, que aún no está.
        $ivaPorPagar = (float) \App\Models\JournalEntryLine::query()
            ->join('journal_entries as a', 'a.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('account_plans as c', 'c.id', '=', 'journal_entry_lines.account_plan_id')
            ->where('a.empresa_id', $empresa->id)
            ->where('a.status', 'confirmado')
            ->whereBetween('a.fecha', [$mes->toDateString(), now()->endOfMonth()->toDateString()])
            ->where('c.code', 'like', '2.1.04%')
            ->sum(DB::raw('journal_entry_lines.haber - journal_entry_lines.debe'));

        $retenido = (float) \App\Models\Retencion::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->whereBetween('fecha', [$mes->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('total_retenido');

        $ejercicios = EjercicioContable::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('anio')
            ->limit(3)
            ->get();

        return [
            'empresa'       => $empresa,
            'saldos'        => $saldos,
            'clasificacion' => $clasificacion,
            'sinMapear'     => $sinMapear,
            'asientosDelMes'=> $asientosDelMes,
            'periodo'       => $mes->translatedFormat('F \d\e Y'),
            'declaraciones' => [
                ['codigo' => 'FORMULARIO 104', 'nombre' => 'IVA mensual',
                 'cifra' => '$ ' . number_format(max($ivaPorPagar, 0), 2, ',', '.'),
                 'pie' => 'a pagar · ' . $mes->translatedFormat('F'), 'estado' => 'Por generar'],
                ['codigo' => 'FORMULARIO 103', 'nombre' => 'Retenciones en la fuente',
                 'cifra' => '$ ' . number_format($retenido, 2, ',', '.'),
                 'pie' => $retenido > 0 ? 'retenido en el mes' : 'sin retenciones registradas', 'estado' => 'Por generar'],
                ['codigo' => 'ANEXO ATS', 'nombre' => 'Transaccional simplificado',
                 'cifra' => (string) $asientosDelMes,
                 'pie' => 'asientos del periodo', 'estado' => 'Por generar'],
            ],
            'obligaciones'  => $conta->obligacionesScvs($empresa->id, $anio),
            'societario'    => $conta->resumenSocietario($empresa->id),
            'ejercicios'    => $ejercicios,
            'tarifaIva'     => $conta->tarifaIvaVigente($empresa->id),
        ];
    }
}
