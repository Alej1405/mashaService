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
 * Trae del SRI el detalle de las facturas recibidas del portal que esperan en la bandeja.
 *
 * Corre solo al subir el archivo del portal (y con «Traer el detalle»). Cada factura
 * termina en uno de dos grupos: con su detalle, lista para elegir el pago y
 * registrarla; o sin él, porque el SRI ya no la entrega, esperando su XML.
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
        public readonly ?string $proveedor = null,
    ) {
    }

    public static function claveAvance(int $empresaId): string
    {
        return "portal-sri:avance:{$empresaId}";
    }

    /** Lo pone en la cola si no hay otro corriendo para esa empresa. false = ya había uno. */
    public static function encolar(int $empresaId, ?int $usuarioId, array $pagos = [], ?int $anio = null, ?string $proveedor = null): bool
    {
        if (Cache::get(self::claveAvance($empresaId))['corriendo'] ?? false) {
            return false;
        }

        Cache::put(self::claveAvance($empresaId), ['corriendo' => true, 'total' => 0, 'hechos' => 0], now()->addHours(6));
        self::dispatch($empresaId, $usuarioId, $pagos, $anio, $proveedor);

        return true;
    }

    public function handle(ClasificadorComprobantesService $servicio): void
    {
        // Solo recibidas: una emitida es una venta y se registra en su fila.
        $pendientes = ComprobanteSri::where('empresa_id', $this->empresaId)->where('origen', 'recibido')
            ->sinClasificar()
            ->when($this->anio, fn ($q) => $q->whereYear('fecha_emision', $this->anio))
            ->when($this->proveedor, fn ($q) => $q->where('identificacion', $this->proveedor))
            ->where(fn ($q) => $q->whereNull('desglose')->orWhereNotIn('desglose', ClasificadorComprobantesService::SIN_DETALLE))
            ->orderByDesc('fecha_emision')
            ->get();

        $cuenta = ['total' => $pendientes->count(), 'hechos' => 0, 'registradas' => 0, 'pendientes' => 0,
                   'manuales' => 0, 'errores' => 0, 'corriendo' => true, 'detalle_errores' => []];
        $this->avance($cuenta);

        foreach ($pendientes as $c) {
            // Sin forma elegida en la fila, la compra queda para auditar con lo que dice su XML.
            $pago = $this->pagos[$c->id] ?? null;
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
