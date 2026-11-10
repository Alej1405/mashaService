<?php

namespace App\Modules\Produccion\Actions;

use App\Models\Empresa;
use App\Models\StoreOrder;
use App\Models\User;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Str;

/**
 * El módulo /produccion del bot de Telegram. La conversación vive aquí; n8n solo
 * transporta: manda el texto y el estado (md) y entrega la respuesta.
 *
 * Pedido → iniciar jornada (¿cuántas personas?) → cerrar jornada → ¿terminó?
 * → notas → un lote por producto → pedido producido.
 *
 * @return array{reply:string, md:array, exit:bool}
 */
#[Documentado(
    grupo: 'Producción',
    descripcion: 'Conversación del bot para registrar jornadas, cierre, notas y lotes de un pedido.',
    tipo: 'action',
)]
final class ConversacionProduccion
{
    public function __construct(private readonly ProduccionDePedido $produccion) {}

    public function responder(User $usuario, Empresa $empresa, string $texto, array $md): array
    {
        $texto = trim($texto);
        $paso = $md['sub'] ?? 'lista';

        if ($paso === 'lista' || str_starts_with(mb_strtolower($texto), '/produccion') || $texto === '0') {
            return $this->lista($empresa);
        }

        $pedido = isset($md['id'])
            ? StoreOrder::withoutGlobalScopes()->where('empresa_id', $empresa->id)->find($md['id'])
            : null;

        try {
            return match ($paso) {
                'elegir'   => $this->elegir($empresa, $texto, $md),
                'pedido'   => $this->accion($pedido, $texto, $usuario),
                'personas' => $this->personas($pedido, $texto, $usuario),
                'termino'  => $this->termino($pedido, $texto),
                'notas'    => $this->notas($pedido, $texto),
                'lote'     => $this->lote($pedido, $texto, $md, $usuario),
                default    => $this->lista($empresa),
            };
        } catch (\RuntimeException $e) {
            return $this->r('⚠️ ' . $e->getMessage() . "\n\n0 para volver a la lista.", $md);
        }
    }

    private function lista(Empresa $empresa): array
    {
        $pedidos = StoreOrder::withoutGlobalScopes()->where('empresa_id', $empresa->id)
            ->whereIn('estado', ['pendiente', 'receptado', 'procesando'])
            ->whereHas('productionOrders')
            ->with('customer', 'jornadas')->orderBy('created_at')->limit(15)->get();

        if ($pedidos->isEmpty()) {
            return $this->r('No hay pedidos por producir.', ['sub' => 'lista'], true);
        }

        $lineas = $pedidos->values()->map(fn ($p, $i) => ($i + 1) . '. ' . $p->numero . ' · ' . ($p->customer?->nombre_completo ?? 'Cliente')
            . ' · ' . $this->estado($p) . ($this->produccion->jornadaAbierta($p) ? ' · jornada abierta' : ''))->implode("\n");

        return $this->r("🏭 Pedidos por producir:\n{$lineas}\n\nResponde el número del pedido.", ['sub' => 'elegir', 'ids' => $pedidos->pluck('id')->all()]);
    }

    private function elegir(Empresa $empresa, string $texto, array $md): array
    {
        $id = $md['ids'][((int) $texto) - 1] ?? null;
        $pedido = $id ? StoreOrder::withoutGlobalScopes()->where('empresa_id', $empresa->id)->find($id) : null;

        if (! $pedido) {
            return $this->r('Número inválido. Responde uno de la lista o 0 para verla otra vez.', $md);
        }

        return $this->menuPedido($pedido);
    }

    private function menuPedido(StoreOrder $pedido): array
    {
        $pedido->loadMissing('customer', 'orderItems', 'productionOrders');
        $abierta = $this->produccion->jornadaAbierta($pedido);
        $mo = $this->produccion->manoDeObra($pedido);
        $plan = $pedido->productionOrders->min('fecha');

        $texto = "📦 {$pedido->numero} · " . ($pedido->customer?->nombre_completo ?? 'Cliente') . "\n"
            . $pedido->orderItems->map(fn ($i) => '• ' . Str::limit($i->nombre_snapshot, 40) . ' × ' . $this->num($i->cantidad))->implode("\n") . "\n"
            . 'Estado: ' . $this->estado($pedido)
            . ($plan ? ' · inicio previsto ' . \Illuminate\Support\Carbon::parse($plan)->format('d/m/Y') : '') . "\n"
            . "Jornadas cerradas: {$mo['jornadas']} · {$this->num($mo['horas_persona'])} horas-persona · $" . number_format($mo['costo'], 2) . "\n\n"
            . ($abierta
                ? "Jornada {$abierta->numero} abierta desde las {$abierta->inicio->timezone('America/Guayaquil')->format('H:i')} con {$abierta->personas} personas.\n1. Terminar la jornada"
                : '1. Iniciar jornada' . ($mo['jornadas'] ? ' ' . ($mo['jornadas'] + 1) : ' (inicio de producción)'))
            . "\n0. Volver";

        return $this->r($texto, ['sub' => 'pedido', 'id' => $pedido->id]);
    }

    private function accion(?StoreOrder $pedido, string $texto, User $usuario): array
    {
        if (! $pedido || $texto !== '1') {
            return $pedido ? $this->menuPedido($pedido) : $this->r('Pedido no encontrado.', ['sub' => 'lista'], true);
        }

        if ($this->produccion->jornadaAbierta($pedido)) {
            $j = $this->produccion->cerrarJornada($pedido, $usuario);

            return $this->r("✅ Jornada {$j->numero} cerrada: {$this->num($j->horas)} h × {$j->personas} personas = $" . number_format((float) $j->costo_mano_obra, 2)
                . " de mano de obra.\n\n¿Se terminó todo el pedido?\n1. Sí, terminado\n2. No, continúa en otra jornada", ['sub' => 'termino', 'id' => $pedido->id]);
        }

        return $this->r('¿Cuántas personas van a trabajar en esta jornada? Responde con un número.', ['sub' => 'personas', 'id' => $pedido->id]);
    }

    private function personas(?StoreOrder $pedido, string $texto, User $usuario): array
    {
        if (! ctype_digit($texto) || (int) $texto < 1) {
            return $this->r('Responde solo con un número de personas (1 o más).', ['sub' => 'personas', 'id' => $pedido?->id]);
        }

        $j = $this->produccion->iniciarJornada($pedido, (int) $texto, $usuario);

        return $this->r("▶️ Jornada {$j->numero} de {$pedido->numero} iniciada a las {$j->inicio->timezone('America/Guayaquil')->format('H:i')} con {$j->personas} personas."
            . ($j->numero === 1 ? "\nSe avisó al cliente que empezó la producción." : '')
            . "\n\nPara cerrarla: /produccion y elige el pedido.", ['sub' => 'lista'], true);
    }

    private function termino(?StoreOrder $pedido, string $texto): array
    {
        if ($texto === '2') {
            return $this->r("Listo. {$pedido->numero} sigue en producción; la próxima jornada se inicia desde /produccion.", ['sub' => 'lista'], true);
        }
        if ($texto !== '1') {
            return $this->r("Responde 1 si se terminó el pedido o 2 si continúa.", ['sub' => 'termino', 'id' => $pedido->id]);
        }

        return $this->r("¿Alguna nota para el cliente? Por ejemplo: «Pedido con retraso por imprevistos».\nEscribe la nota o «no».", ['sub' => 'notas', 'id' => $pedido->id]);
    }

    private function notas(?StoreOrder $pedido, string $texto): array
    {
        $notas = in_array(mb_strtolower($texto), ['no', 'ninguna', 'sin notas'], true) ? null : Str::limit($texto, 1000, '');
        $pendientes = $pedido->productionOrders()->whereNotIn('estado', ['terminado', 'completado'])->pluck('id')->all();

        return $this->pedirLote($pedido, ['sub' => 'lote', 'id' => $pedido->id, 'notas' => $notas, 'pendientes' => $pendientes, 'lotes' => []]);
    }

    private function lote(?StoreOrder $pedido, string $texto, array $md, User $usuario): array
    {
        if ($texto === '' || mb_strlen($texto) > 60) {
            return $this->pedirLote($pedido, $md, 'El lote debe tener entre 1 y 60 caracteres.');
        }

        $actual = array_shift($md['pendientes']);
        $md['lotes'][$actual] = $texto;

        if ($md['pendientes']) {
            return $this->pedirLote($pedido, $md);
        }

        $this->produccion->terminar($pedido, $md['notas'] ?? null, $md['lotes'], $usuario);
        $pedido->load('productionOrders.productPresentation');

        return $this->r("🎉 {$pedido->numero} terminado.\n"
            . $pedido->productionOrders->map(fn ($o) => '• ' . ($o->productPresentation?->nombre ?? $o->referencia) . ': lote ' . $o->lote)->implode("\n")
            . "\nSe avisó al cliente por correo.", ['sub' => 'lista'], true);
    }

    private function pedirLote(StoreOrder $pedido, array $md, string $aviso = ''): array
    {
        $orden = $pedido->productionOrders()->with('productPresentation')->find($md['pendientes'][0] ?? 0);

        return $this->r(($aviso ? "⚠️ {$aviso}\n" : '') . '🏷️ Lote de entrega de ' . ($orden?->productPresentation?->nombre ?? $orden?->referencia)
            . ' × ' . $this->num($orden?->cantidad_producida) . ':', $md);
    }

    private function estado(StoreOrder $p): string
    {
        return match ($p->estado) {
            'pendiente'  => 'por planificar',
            'receptado'  => 'planificado',
            'procesando' => 'en producción',
            default      => $p->estado,
        };
    }

    private function num($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    }

    private function r(string $reply, array $md, bool $exit = false): array
    {
        return ['reply' => $reply, 'md' => $md, 'exit' => $exit];
    }
}
