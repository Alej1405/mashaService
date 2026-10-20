<?php

namespace App\Jobs;

use App\Models\ComprobanteSri;
use App\Models\User;
use App\Notifications\RegistroDelPortalTerminado;
use App\Services\ClasificadorComprobantesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Registra con su detalle todas las facturas del portal que esperan en la bandeja.
 *
 * Un año son cientos de facturas y cada una es una consulta al SRI: dentro de la
 * petición web se cortaría a los 30 segundos. Aquí corre en la cola, deja el
 * avance en caché para que la bandeja lo muestre y avisa en la campana al final.
 *
 * Primero las más recientes: son las que el SRI todavía devuelve con su detalle.
 * Es idempotente: lo ya registrado sale de la bandeja y no se vuelve a tocar.
 */
class RegistrarComprobantesDelPortal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3500;

    /** @param array<int, string> $pagos comprobante_id => pago elegido en la fila (formato MedioPago) */
    public function __construct(
        public readonly int $empresaId,
        public readonly ?int $usuarioId,
        public readonly array $pagos = [],
        public readonly ?int $anio = null,
    ) {
    }

    public static function claveAvance(int $empresaId): string
    {
        return "portal-sri:avance:{$empresaId}";
    }

    public function handle(ClasificadorComprobantesService $servicio): void
    {
        $pendientes = ComprobanteSri::where('empresa_id', $this->empresaId)
            ->sinClasificar()
            ->when($this->anio, fn ($q) => $q->whereYear('fecha_emision', $this->anio))
            ->where(fn ($q) => $q->whereNull('desglose')->orWhereNotIn('desglose', ClasificadorComprobantesService::SIN_DETALLE))
            ->orderByDesc('fecha_emision')
            ->get();

        $cuenta = ['total' => $pendientes->count(), 'hechos' => 0, 'registradas' => 0, 'pendientes' => 0,
                   'manuales' => 0, 'errores' => 0, 'corriendo' => true, 'detalle_errores' => []];
        $this->avance($cuenta);

        foreach ($pendientes as $c) {
            $pago = $this->pagos[$c->id] ?? $servicio->proponer($c)['pago'] ?? 'credito';
            $r = $servicio->registrarConDetalle($c, $pago, $this->usuarioId);

            if ($r['ok'] ?? false) {
                $cuenta[($r['estado'] ?? '') === 'pendiente' ? 'pendientes' : 'registradas']++;
            } elseif ($r['manual'] ?? false) {
                $cuenta['manuales']++;
            } else {
                $cuenta['errores']++;
                $cuenta['detalle_errores'][] = "{$c->numero}: {$r['error']}";
            }

            $cuenta['hechos']++;
            $this->avance($cuenta);
        }

        $cuenta['corriendo'] = false;
        $this->avance($cuenta);

        User::find($this->usuarioId)?->notify(new RegistroDelPortalTerminado($cuenta));
    }

    public function failed(Throwable $e): void
    {
        $avance = Cache::get(self::claveAvance($this->empresaId), []);
        $this->avance(['corriendo' => false, 'falla' => str($e->getMessage())->limit(200)->toString()] + $avance);
    }

    private function avance(array $cuenta): void
    {
        Cache::put(self::claveAvance($this->empresaId), $cuenta, now()->addHours(6));
    }
}
