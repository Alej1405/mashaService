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
                $this->tarjeta104($empresa),
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

    /**
     * El 104 que toca declarar, con su estado real: el mes en curso todavía no se
     * declara, así que la tarjeta mira el primero que falta presentar.
     */
    private function tarjeta104(\App\Models\Empresa $empresa): array
    {
        $alerta = app(\App\Services\GestionSriService::class)->alertaIva($empresa);
        $url = Cumplimiento::getUrl();

        if (! $alerta) {
            return ['codigo' => 'FORMULARIO 104', 'nombre' => 'IVA mensual', 'cifra' => 'Al día',
                    'pie' => 'todos los meses presentados', 'estado' => 'Presentado', 'tono' => 'ok', 'url' => $url];
        }

        $d = \App\Models\Declaracion::where('empresa_id', $empresa->id)->where('tipo', 'f104')
            ->where('anio', $alerta['anio'])->where('mes', $alerta['mes'])->latest('id')->first();
        $listo = $d?->estado === 'listo';

        return [
            'codigo' => 'FORMULARIO 104',
            'nombre' => 'IVA de ' . $alerta['etiqueta'],
            'cifra'  => $listo ? '$ ' . number_format($d->casillero('999'), 2, ',', '.') : '—',
            'pie'    => ($listo ? 'a pagar · ' : '') . ($alerta['vence'] ? 'vence el ' . $alerta['vence']->format('d/m/Y') : 'sin fecha de vencimiento'),
            'estado' => $listo ? 'Falta el comprobante' : 'Por generar',
            'tono'   => $alerta['urgente'] ? 'da' : 'wa',
            'url'    => $url,
        ];
    }

    /**
     * Switch del modo «Cuadre de gestión anterior». Solo los administradores: abre
     * a cambios los meses anteriores al inicio de gestión, aunque estén cerrados.
     */
    protected function getHeaderActions(): array
    {
        $empresa = Filament::getTenant();
        $encendido = $empresa?->modo_cuadre_en !== null;
        $corte = $empresa?->inicio_gestion_erp?->translatedFormat('F \\d\\e Y') ?? 'el inicio de gestión';

        return [
            \Filament\Actions\Action::make('modo_cuadre')
                ->label($encendido ? 'Apagar modo cuadre' : 'Modo cuadre de gestión anterior')
                ->icon($encendido ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                ->color($encendido ? 'warning' : 'gray')
                ->visible(fn () => auth()->user()?->hasAnyRole(['admin_empresa', 'super_admin']))
                ->requiresConfirmation()
                ->modalHeading($encendido ? 'Apagar el modo cuadre' : 'Encender el modo cuadre')
                ->modalDescription($encendido
                    ? "Los meses anteriores a {$corte} vuelven a quedar protegidos: los que están cerrados ya no admiten cambios."
                    : "Mientras esté encendido, los meses anteriores a {$corte} admiten registros y correcciones aunque estén "
                        . "cerrados, para cuadrar la gestión de antes del ERP. Desde {$corte} nada cambia: lo declarado sigue cerrado.")
                ->modalSubmitActionLabel($encendido ? 'Apagar' : 'Encender')
                ->action(function () use ($empresa, $encendido) {
                    $empresa->update($encendido
                        ? ['modo_cuadre_en' => null, 'modo_cuadre_por' => null]
                        : ['modo_cuadre_en' => now(), 'modo_cuadre_por' => auth()->id()]);

                    \Filament\Notifications\Notification::make()
                        ->title($encendido ? 'Modo cuadre apagado' : 'Modo cuadre encendido')
                        ->success()->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }
}
