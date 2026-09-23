<?php

namespace App\Filament\Contabilidad\Pages;

use App\Services\SuperciasService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Los archivos que se suben al portal de la Superintendencia.
 *
 * Se descargan como .txt uno por uno, o los cuatro en un zip. El contenido se
 * arma del mapeo de cada cuenta al catálogo de la SCVS.
 */
class InformesSupercias extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = 'Informes Supercías';
    protected static ?string $title           = 'Informes para la Superintendencia';
    protected static ?int    $navigationSort  = 6;
    protected static string  $view            = 'filament.contabilidad.informes-supercias';

    public int $anio;

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
    }

    public function mount(): void
    {
        $this->anio = (int) request()->integer('anio', now()->year - 1);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('descargar_todo')
                ->label('Descargar los cuatro')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->action(fn () => $this->descargarJuego()),
        ];
    }

    public function descargar(string $estado): StreamedResponse
    {
        $empresa = Filament::getTenant();
        $nombre = \App\Models\CatalogoSupercias::ARCHIVOS[$estado];
        $contenido = app(SuperciasService::class)->archivo($empresa->id, $this->anio, $estado);

        return response()->streamDownload(
            fn () => print($contenido),
            $this->anio . '_' . $nombre,
            ['Content-Type' => 'text/plain; charset=utf-8'],
        );
    }

    public function descargarJuego(): StreamedResponse
    {
        $empresa = Filament::getTenant();
        $archivos = app(SuperciasService::class)->juegoCompleto($empresa->id, $this->anio);
        $anio = $this->anio;
        $ruc = $empresa->numero_identificacion ?: $empresa->slug;

        return response()->streamDownload(function () use ($archivos, $anio, $ruc) {
            $tmp = tempnam(sys_get_temp_dir(), 'scvs') . '.zip';
            $zip = new \ZipArchive();
            $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            foreach ($archivos as $nombre => $contenido) {
                $zip->addFromString($anio . '_' . $nombre, $contenido);
            }

            $zip->close();
            echo file_get_contents($tmp);
            @unlink($tmp);
        }, "supercias_{$ruc}_{$anio}.zip", ['Content-Type' => 'application/zip']);
    }

    protected function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $scvs = app(SuperciasService::class);

        return [
            'empresa'     => $empresa,
            'anio'        => $this->anio,
            'resumen'     => $scvs->resumen($empresa->id, $this->anio),
            'sinCodigo'   => $scvs->cuentasSinCodigo($empresa->id),
            'muestra'     => collect(explode("\n", $scvs->archivo($empresa->id, $this->anio, 'situacion_financiera')))
                                ->filter()->take(12)->all(),
        ];
    }
}
