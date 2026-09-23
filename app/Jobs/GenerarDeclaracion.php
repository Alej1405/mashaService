<?php

namespace App\Jobs;

use App\Models\Declaracion;
use App\Notifications\DeclaracionLista;
use App\Services\AtsService;
use App\Services\Formulario104Service;
use App\Services\ServicioSri;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Genera una declaración fuera de la petición del usuario.
 *
 * El reparto es a propósito: **el ERP calcula, el microservicio escribe el
 * archivo**. El cálculo necesita el libro diario y vive donde están los datos;
 * el armado del XML no necesita la base y puede caerse sin arrastrar al ERP.
 */
class GenerarDeclaracion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    /** Espera creciente: si el servicio está reiniciándose, no insistir de golpe. */
    public array $backoff = [30, 120];

    public function __construct(public readonly int $declaracionId)
    {
    }

    public function handle(
        Formulario104Service $f104,
        AtsService $ats,
        ServicioSri $servicio,
    ): void {
        $declaracion = Declaracion::find($this->declaracionId);

        if (! $declaracion || $declaracion->estado === 'listo') {
            return;
        }

        $declaracion->update(['estado' => 'procesando']);

        // 1 · el ERP calcula, porque aquí están los asientos
        $datos = match ($declaracion->tipo) {
            'f104' => $f104->calcular($declaracion->empresa_id, $declaracion->anio, $declaracion->mes),
            'ats'  => $ats->datos($declaracion->empresa_id, $declaracion->anio, $declaracion->mes),
            default => throw new \InvalidArgumentException("No sé generar {$declaracion->tipo}."),
        };

        // 2 · el microservicio escribe el XML, sin tocar la base
        $respuesta = $declaracion->tipo === 'f104'
            ? $servicio->formulario104(
                $datos['ruc'] ?? '', $declaracion->anio, $declaracion->mes, $datos['casilleros'])
            : $servicio->ats($datos);

        $archivo = null;

        if ($respuesta['ok'] ?? false) {
            $archivo = sprintf(
                'declaraciones/%d/%s-%d-%02d-%s.xml',
                $declaracion->empresa_id, $declaracion->tipo,
                $declaracion->anio, $declaracion->mes, substr($declaracion->seguimiento, 0, 8),
            );
            Storage::disk('local')->put($archivo, $respuesta['xml']);
        }

        // Sin servicio, los casilleros ya calculados igual se guardan: el
        // contador puede verlos en pantalla y declarar a mano.
        $avisos = $datos['avisos'] ?? [];

        if (! ($respuesta['ok'] ?? false)) {
            $avisos[] = 'Los valores están calculados, pero no se pudo armar el XML: '
                . ($respuesta['error'] ?? 'sin detalle');
        }

        $declaracion->update([
            'estado'      => ($respuesta['ok'] ?? false) ? 'listo' : 'error',
            'datos'       => $datos,
            'avisos'      => $avisos,
            'archivo'     => $archivo,
            'mensaje'     => $respuesta['error'] ?? null,
            'generado_en' => now(),
        ]);

        // 3 · el aviso en el sistema, que es lo que el usuario está esperando
        $declaracion->solicitante?->notify(new DeclaracionLista($declaracion));
    }

    public function failed(Throwable $e): void
    {
        Declaracion::where('id', $this->declaracionId)->update([
            'estado'  => 'error',
            'mensaje' => str($e->getMessage())->limit(400),
        ]);
    }
}
