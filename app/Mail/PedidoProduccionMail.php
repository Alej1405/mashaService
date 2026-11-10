<?php

namespace App\Mail;

use App\Models\StoreOrder;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Los tres avisos al cliente de un pedido que se fabrica: receptado y en
 * planificación, inicio de producción, y terminado (con notas y lotes).
 */
class PedidoProduccionMail extends Mailable
{
    public const RECEPTADO = 'receptado';
    public const INICIO    = 'inicio';
    public const TERMINADO = 'terminado';

    public function __construct(
        public readonly StoreOrder $pedido,
        public readonly string $momento,
    ) {}

    public function envelope(): Envelope
    {
        $asunto = match ($this->momento) {
            self::RECEPTADO => "Recibimos tu pedido {$this->pedido->numero}",
            self::INICIO    => "Empezamos a producir tu pedido {$this->pedido->numero}",
            default         => "Tu pedido {$this->pedido->numero} está listo",
        };

        return new Envelope(subject: '[' . $this->pedido->empresa->name . '] ' . $asunto);
    }

    public function content(): Content
    {
        $p = $this->pedido;
        $fecha = fn ($f) => $f ? \Illuminate\Support\Carbon::parse($f)->format('d/m/Y') : null;
        $ordenes = $p->productionOrders->sortBy('id');

        return new Content(
            view: 'emails.pedido-produccion',
            with: [
                'empresa' => $p->empresa,
                'numero'  => $p->numero,
                'nombre'  => $p->customer?->nombre_completo,
                'titulo'  => match ($this->momento) {
                    self::RECEPTADO => 'Pedido recibido',
                    self::INICIO    => 'Producción iniciada',
                    default         => 'Pedido terminado',
                },
                'mensaje' => match ($this->momento) {
                    self::RECEPTADO => "Recibimos tu pedido {$p->numero} y ya está en planificación. Lo fabricamos para ti: "
                        . 'está previsto empezar el ' . $fecha($ordenes->min('fecha')) . ' y terminar el ' . $fecha($ordenes->max('fecha_fin')) . '.',
                    self::INICIO    => "Empezamos a producir tu pedido {$p->numero}. Te avisaremos cuando esté listo.",
                    default         => "Terminamos de producir tu pedido {$p->numero}. Estos son los lotes con los que se entrega:",
                },
                'lineas'  => $ordenes->map(fn ($o) => [
                    'producto' => $o->productPresentation?->nombre ?? 'Producto',
                    'cantidad' => $this->cantidad($o->cantidad_producida),
                    'lote'     => $this->momento === self::TERMINADO ? $o->lote : null,
                ])->values()->all(),
                'nota'    => $this->momento === self::TERMINADO ? $ordenes->first()?->notas_cierre : null,
                'url'     => route('portal.orders.show', [$p->empresa->slug, $p->id]),
            ],
        );
    }

    private function cantidad($valor): string
    {
        return rtrim(rtrim(number_format((float) $valor, 4, ',', '.'), '0'), ',');
    }
}
