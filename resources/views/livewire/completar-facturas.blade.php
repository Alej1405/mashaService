<div class="ct">
@include('filament.contabilidad._estilos')
@php $money = fn ($n) => '$ ' . number_format((float) $n, 2, ',', '.'); @endphp

    {{-- 1 · Subir: se procesa apenas se eligen los archivos. --}}
    <section class="ct-panel">
        <header>
            <div>
                <h2 class="ct-tit">Subir facturas en XML</h2>
                <p class="ct-pie2">Varios a la vez o un .zip. Las que el ERP reconoce se registran solas; las demás quedan abajo para completarlas.</p>
            </div>
        </header>
        <div class="ct-fila" style="flex-wrap:wrap; gap:10px">
            <label class="ct-fila-s" style="display:flex; flex-direction:column; gap:4px; flex:1 1 220px">
                Cómo se pagaron
                <select wire:model="pago" class="ct-select">
                    @foreach ($medios as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </label>
            {{-- El servidor acepta 20 archivos por petición (max_file_uploads): se mandan en grupos de 15. --}}
            <label class="ct-subir" style="flex:2 1 320px; position:relative"
                   x-data="{ total: 0, enviados: 0 }"
                   x-on:change="
                       const archivos = Array.from($event.target.files);
                       if (! archivos.length) return;
                       total = archivos.length; enviados = 0;
                       await $wire.empezarSubida();
                       for (let i = 0; i < archivos.length; i += 15) {
                           const grupo = archivos.slice(i, i + 15);
                           await new Promise((listo, fallo) => $wire.uploadMultiple('xmls', grupo, listo, fallo));
                           enviados += grupo.length;
                       }
                       total = 0; $event.target.value = '';
                   ">
                <input type="file" multiple accept=".xml,.zip" style="position:absolute; width:1px; height:1px; opacity:0">
                <span x-show="! total"><strong>Elegir XML o .zip</strong> · se registran al soltarlos</span>
                <span x-show="total" x-cloak>Leyendo y registrando… <span x-text="enviados"></span> de <span x-text="total"></span></span>
            </label>
        </div>
        @error('xmls.*') <p class="ct-fila-s" style="color:var(--da); padding:0 18px 12px">{{ $message }}</p> @enderror

        @if ($resultado)
            <div class="ct-fila" style="flex-wrap:wrap; gap:8px">
                @if (! empty($resultado['texto']))
                    <span class="ct-chip ct-chip-ok">{{ $resultado['texto'] }}</span>
                @else
                    <span class="ct-chip ct-chip-ok">{{ $resultado['registradas'] }} registradas</span>
                    @if ($resultado['esperan']) <span class="ct-chip ct-chip-wa">{{ $resultado['esperan'] }} por completar abajo</span> @endif
                    @if (count($resultado['fallos'])) <span class="ct-chip ct-chip-da">{{ count($resultado['fallos']) }} no se pudieron</span> @endif
                    @if ($resultado['resto']) <span class="ct-chip ct-chip-n">{{ $resultado['resto'] }} sin procesar: súbelas en otra tanda</span> @endif
                @endif
                @foreach (array_slice($resultado['fallos'], 0, 5) as $f)
                    <p class="ct-fila-s" style="width:100%; margin:0">{{ $f }}</p>
                @endforeach
            </div>
        @endif
    </section>

    {{-- 2 · Completar: cada factura con sus productos; lo que falta, en la misma línea. --}}
    <section class="ct-panel">
        <header>
            <div>
                <h2 class="ct-tit">Facturas por completar</h2>
                <p class="ct-pie2">El ERP propone qué es cada producto nuevo y por qué. Confirma o cambia: se dice una sola vez y las próximas facturas entran solas.</p>
            </div>
            @if ($compras->count() > 1)
                <button type="button" class="ct-btn-descarga" style="border:0; cursor:pointer"
                        wire:click="registrarTodas" wire:loading.attr="disabled" wire:target="registrarTodas">
                    Registrar todas
                </button>
            @endif
        </header>

        @forelse ($compras as $c)
            <div class="ct-fila" style="flex-direction:column; align-items:stretch; gap:10px" wire:key="cf-{{ $c->id }}">
                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap">
                    <div>
                        <p class="ct-fila-t">{{ $c->supplier?->nombre ?? 'Proveedor' }}</p>
                        <p class="ct-fila-s">{{ $c->numero_factura }} · {{ $c->date?->format('d/m/Y') }} · <strong>{{ $money($c->total) }}</strong></p>
                    </div>
                    <span class="ct-chip ct-chip-ok" style="align-self:flex-start">Con detalle · {{ $c->items->count() }} {{ $c->items->count() === 1 ? 'producto' : 'productos' }}</span>
                </div>

                <ul class="ct-lineas" style="margin:0">
                    @foreach ($c->items as $l)
                        @php $p = $l->productoProveedor; $nuevo = $p && ! $p->estaConfigurado(); @endphp
                        <li style="flex-wrap:wrap; align-items:center">
                            <span style="flex:1 1 220px">
                                {{ $l->descripcion ?: $l->inventoryItem?->nombre }} · {{ rtrim(rtrim(number_format((float) $l->quantity, 2, ',', '.'), '0'), ',') }}
                                @if (! $nuevo && ($l->inventoryItem || $p?->tipo_gasto_id))
                                    <span class="ct-fila-s"> → {{ $l->inventoryItem?->nombre ?? 'gasto' }}</span>
                                @endif
                            </span>
                            @if ($nuevo)
                                @php $prop = $propuestas[$p->id] ?? null; @endphp
                                <span style="display:flex; flex-direction:column; gap:4px; flex:1 1 240px">
                                <select wire:model.live="producto.{{ $p->id }}" class="ct-select" aria-label="Qué es {{ $p->descripcion }}">
                                    <option value="">— producto nuevo: qué es —</option>
                                    <optgroup label="Crear en el inventario">
                                        <option value="nuevo:insumo">Crear insumo nuevo</option>
                                        <option value="nuevo:materia_prima">Crear materia prima nueva</option>
                                    </optgroup>
                                    @foreach ($items as $tipo => $grupo)
                                        <optgroup label="{{ ucfirst(str_replace('_', ' ', $tipo)) }}">
                                            @foreach ($grupo as $id => $nombre)
                                                <option value="item:{{ $id }}">{{ $nombre }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                    <optgroup label="Gasto">
                                        @foreach ($tiposGasto as $id => $nombre)
                                            <option value="gasto:{{ $id }}">{{ $nombre }}</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                @if ($prop && $prop['valor'] !== '' && ($producto[$p->id] ?? '') === $prop['valor'])
                                    <span class="ct-fila-s" style="margin:0">Propuesta del ERP: {{ $prop['motivo'] }}</span>
                                @elseif ($prop && $prop['valor'] === '')
                                    <span class="ct-fila-s" style="margin:0; color:var(--wa)">{{ ucfirst($prop['motivo']) }}: elige el tipo de gasto</span>
                                @endif
                                </span>
                            @endif
                            <span>{{ $money($l->total_item) }}</span>
                        </li>
                    @endforeach
                </ul>

                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:flex-end">
                    @if ($c->requiere_forma_pago)
                        <select wire:model="pagoCompra.{{ $c->id }}" class="ct-select" style="flex:0 1 260px" aria-label="Cómo se pagó">
                            @foreach ($medios as $valor => $etiqueta)
                                <option value="{{ $valor }}">{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    @endif
                    <button type="button" class="ct-btn-descarga" style="border:0; cursor:pointer"
                            wire:click="registrar({{ $c->id }})" wire:loading.attr="disabled" wire:target="registrar({{ $c->id }})">
                        Registrar
                    </button>
                </div>
                @error("compra.{$c->id}") <p class="ct-fila-s" style="color:var(--da); margin:0">{{ $message }}</p> @enderror
            </div>
        @empty
            <p class="ct-fila-s" style="padding:18px">No hay facturas por completar. Todo lo que llegó está registrado con su asiento.</p>
        @endforelse
    </section>
</div>
