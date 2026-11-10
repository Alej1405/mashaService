<?php

namespace App\Modules\Produccion\Actions;

use App\Mail\PedidoProduccionMail;
use App\Models\ProduccionJornada;
use App\Models\StoreOrder;
use App\Models\User;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * La producción de un pedido, de la planificación a la entrega del lote.
 *
 * - planificar: fechas previstas; el pedido queda receptado y se avisa al cliente.
 * - iniciarJornada: con cuántas personas; la primera inicia la producción y avisa.
 * - cerrarJornada: horas trabajadas y su mano de obra a la tarifa de la jornada.
 * - terminar: notas y un lote por producto; el pedido queda producido y se avisa.
 *
 * Trazabilidad (alimentos): cada paso guarda quién y cuándo. Las órdenes quedan
 * «terminadas» con su lote; el cierre contable (kardex y asiento) llega cuando
 * el producto tenga fórmula y costo.
 */
#[Documentado(
    grupo: 'Producción',
    descripcion: 'Planifica, registra jornadas y termina la producción de un pedido, con lotes y avisos al cliente.',
    tipo: 'action',
)]
final class ProduccionDePedido
{
    public function planificar(StoreOrder $pedido, string $inicio, string $fin): void
    {
        if (Carbon::parse($fin)->lt(Carbon::parse($inicio))) {
            throw new \RuntimeException('La fecha de fin no puede ser anterior a la de inicio.');
        }

        $primeraVez = $pedido->estado === 'pendiente';

        DB::transaction(function () use ($pedido, $inicio, $fin) {
            $pedido->productionOrders()->where('estado', 'borrador')->update(['fecha' => $inicio, 'fecha_fin' => $fin, 'estado' => 'planificado']);
            $pedido->productionOrders()->where('estado', 'planificado')->update(['fecha' => $inicio, 'fecha_fin' => $fin]);

            if ($pedido->estado === 'pendiente') {
                $pedido->update(['estado' => 'receptado']);
            }
        });

        if ($primeraVez) {
            $this->avisar($pedido, PedidoProduccionMail::RECEPTADO);
        }
    }

    public function jornadaAbierta(StoreOrder $pedido): ?ProduccionJornada
    {
        return $pedido->jornadas()->whereNull('fin')->first();
    }

    public function iniciarJornada(StoreOrder $pedido, int $personas, User $usuario): ProduccionJornada
    {
        if ($personas < 1) {
            throw new \RuntimeException('Indica cuántas personas van a trabajar (1 o más).');
        }
        if ($this->jornadaAbierta($pedido)) {
            throw new \RuntimeException('Ya hay una jornada abierta en este pedido: ciérrala primero.');
        }
        if (in_array($pedido->estado, ['producido', 'enviado', 'entregado', 'cancelado'], true)) {
            throw new \RuntimeException("El pedido está {$pedido->estado}: ya no admite jornadas.");
        }

        $tarifa = (float) $pedido->empresa->costo_hora_mano_obra;
        if ($tarifa <= 0) {
            throw new \RuntimeException('Falta el valor de la mano de obra por hora en Contabilidad › Configuración.');
        }

        $primera = ! $pedido->jornadas()->exists();

        $jornada = DB::transaction(function () use ($pedido, $personas, $usuario, $tarifa, $primera) {
            $jornada = $pedido->jornadas()->create([
                'empresa_id'   => $pedido->empresa_id,
                'numero'       => ((int) $pedido->jornadas()->max('numero')) + 1,
                'inicio'       => now(),
                'personas'     => $personas,
                'tarifa_hora'  => $tarifa,
                'iniciada_por' => $usuario->id,
            ]);

            if ($primera) {
                $pedido->productionOrders()->whereIn('estado', ['borrador', 'planificado'])
                    ->update(['estado' => 'en_proceso', 'inicio_real' => now()]);
                $pedido->update(['estado' => 'procesando']);
            }

            return $jornada;
        });

        if ($primera) {
            $this->avisar($pedido, PedidoProduccionMail::INICIO);
        }

        return $jornada;
    }

    /** Cierra la jornada abierta a la hora $fin (cuando se pidió cerrar) con su motivo. */
    public function cerrarJornada(StoreOrder $pedido, User $usuario, string $motivo, ?Carbon $fin = null): ProduccionJornada
    {
        $jornada = $this->jornadaAbierta($pedido)
            ?? throw new \RuntimeException('Este pedido no tiene una jornada abierta.');

        $fin ??= now();
        $horas = round($jornada->inicio->diffInMinutes($fin) / 60, 2);

        $jornada->update([
            'fin'             => $fin,
            'motivo_cierre'   => \Illuminate\Support\Str::limit(trim($motivo), 255, ''),
            'horas'           => $horas,
            // Dinero con aritmética decimal y redondeo a la mitad hacia arriba: 9,045 → 9,05.
            'costo_mano_obra' => bcadd(bcmul(bcmul(number_format($horas, 2, '.', ''), (string) $jornada->personas, 4), (string) $jornada->tarifa_hora, 4), '0.005', 2),
            'cerrada_por'     => $usuario->id,
        ]);

        return $jornada->fresh();
    }

    /** @param array<int,string> $lotes production_order_linea_id => lote */
    public function terminar(StoreOrder $pedido, ?string $notas, array $lotes, User $usuario): void
    {
        if ($this->jornadaAbierta($pedido)) {
            throw new \RuntimeException('Cierra la jornada abierta antes de terminar el pedido.');
        }

        $ordenes = $pedido->productionOrders()->whereNotIn('estado', ['terminado', 'completado'])->with('lineas')->get();
        foreach ($ordenes->flatMap->lineas as $l) {
            if (blank($lotes[$l->id] ?? null)) {
                throw new \RuntimeException("Falta el lote de {$l->descripcion}.");
            }
        }

        DB::transaction(function () use ($pedido, $notas, $lotes, $usuario, $ordenes) {
            foreach ($ordenes as $o) {
                foreach ($o->lineas as $l) {
                    $l->update(['lote' => trim($lotes[$l->id])]);
                }
                $o->update([
                    'estado'        => 'terminado',
                    'fin_real'      => now(),
                    'notas_cierre'  => $notas,
                    'terminado_por' => $usuario->id,
                ]);
            }
            $pedido->update(['estado' => 'producido']);
        });

        $this->avisar($pedido, PedidoProduccionMail::TERMINADO);
    }

    /** Mano de obra acumulada del pedido: horas-persona y costo. */
    public function manoDeObra(StoreOrder $pedido): array
    {
        $j = $pedido->jornadas()->whereNotNull('fin')->get();

        return [
            'jornadas'      => $j->count(),
            'horas_persona' => round($j->sum(fn ($x) => (float) $x->horas * $x->personas), 2),
            'costo'         => round((float) $j->sum('costo_mano_obra'), 2),
        ];
    }

    private function avisar(StoreOrder $pedido, string $momento): void
    {
        $pedido->loadMissing('empresa', 'customer', 'productionOrders.lineas');
        $correo = $pedido->customer?->email;

        if (! $correo) {
            return;
        }

        try {
            Mail::to($correo)->send(new PedidoProduccionMail($pedido->fresh(['empresa', 'customer', 'productionOrders.lineas']), $momento));
        } catch (\Throwable $e) {
            // El registro de producción no falla porque el correo no salga.
            Log::error('No se pudo avisar al cliente del pedido', ['pedido' => $pedido->numero, 'momento' => $momento, 'error' => $e->getMessage()]);
        }
    }
}
