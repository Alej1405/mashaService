<x-filament-panels::page>
@include('filament.contabilidad._estilos')
@php $money = fn ($n) => '$ ' . number_format((float) $n, 2, ',', '.'); @endphp

<div class="ct">
    @if ($compras->isEmpty())
        <section class="ct-panel">
            <header><h2 class="ct-tit">Nada por completar</h2></header>
            <p class="ct-vacio">
                Las facturas que llegaron por correo o Telegram están registradas, con su asiento y su entrada al inventario.
            </p>
        </section>
    @else
        <div class="ct-aviso">
            <p>
                <strong>{{ $compras->count() }} {{ $compras->count() === 1 ? 'factura recibida' : 'facturas recibidas' }} sin registrar.</strong>
                Mientras no se diga cómo se pagaron no tienen asiento: no suman al inventario ni a cuentas por pagar.
            </p>
        </div>

        <section class="ct-panel">
            <header>
                <h2 class="ct-tit">Facturas recibidas</h2>
                <span class="ct-pie2">la forma de pago del XML no dice de qué caja o banco salió el dinero</span>
            </header>

            @foreach ($compras as $c)
                @php $pago = (string) ($eleccion[$c->id]['pago'] ?? ''); @endphp
                <div class="ct-fila" style="align-items:flex-start; flex-wrap:wrap; gap:10px">
                    <div style="min-width:230px; flex:1">
                        <p class="ct-fila-t">{{ $c->supplier?->nombre }}</p>
                        <p class="ct-fila-s">
                            {{ $c->numero_factura }} · {{ $c->date->format('d/m/Y') }} ·
                            base {{ $money($c->subtotal) }} · IVA {{ $money($c->iva) }} ·
                            <strong>{{ $money($c->total) }}</strong> · llegó por {{ $c->origen }}
                        </p>
                        @if ($c->items_por_configurar_count > 0)
                            <p class="ct-fila-s" style="margin-top:4px">
                                <span class="ct-chip ct-chip-wa">{{ $c->items_por_configurar_count }} {{ $c->items_por_configurar_count === 1 ? 'producto' : 'productos' }} por configurar</span>
                                <a href="{{ $urlProductos }}" style="color:var(--ac); font-weight:600">Configurar en Inventario</a>
                            </p>
                        @endif
                        @if ($c->error_contable_msg)
                            <p class="ct-fila-s" style="margin-top:4px"><span class="ct-chip ct-chip-da">No generó asiento</span> {{ $c->error_contable_msg }}</p>
                        @endif
                    </div>

                    <div style="display:flex; gap:8px; align-items:center; flex:1 1 420px; flex-wrap:wrap">
                        @if ($c->requiere_forma_pago)
                            <select wire:model.live="eleccion.{{ $c->id }}.pago" class="ct-select" style="flex:2 1 260px" aria-label="Cómo se pagó">
                                <option value="">— cómo se pagó —</option>
                                @foreach ($opciones as $valor => $etiqueta)
                                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                            @if (str_starts_with($pago, 'credito'))
                                <input type="number" min="1" wire:model="eleccion.{{ $c->id }}.plazo"
                                       class="ct-select" style="width:96px" aria-label="Días de plazo" placeholder="días">
                            @endif
                        @else
                            <span class="ct-chip ct-chip-ok">pago: {{ $c->forma_pago }}</span>
                        @endif

                        <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                wire:click="registrar({{ $c->id }})" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="registrar({{ $c->id }})">Registrar</span>
                            <span wire:loading wire:target="registrar({{ $c->id }})">Registrando…</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </section>
    @endif
</div>
</x-filament-panels::page>
