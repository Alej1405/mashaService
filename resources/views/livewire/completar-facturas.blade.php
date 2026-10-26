<div class="ct">
@include('filament.contabilidad._estilos')
@php $money = fn ($n) => '$ ' . number_format((float) $n, 2, ',', '.'); @endphp

    {{-- 1 · Subir: se procesa apenas se eligen los archivos. --}}
    <section class="ct-panel">
        <header>
            <div>
                <h2 class="ct-tit">Subir facturas en XML</h2>
                <p class="ct-pie2">Varios a la vez o un .zip. Nada se registra al subir: cada factura queda {{ $conLista ? 'abajo' : 'en «Listas para registrar»' }} con lo que dice su XML, para auditarla.</p>
            </div>
        </header>
        <div class="ct-fila" style="flex-wrap:wrap; gap:10px">
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
                    @if ($resultado['registradas']) <span class="ct-chip ct-chip-ok">{{ $resultado['registradas'] }} ya estaban registradas</span> @endif
                    @if ($resultado['esperan']) <span class="ct-chip ct-chip-wa">{{ $resultado['esperan'] }} {{ $conLista ? 'por auditar abajo' : 'pasaron a «Listas para registrar»' }}</span> @endif
                    @if (count($resultado['fallos'])) <span class="ct-chip ct-chip-da">{{ count($resultado['fallos']) }} no se pudieron</span> @endif
                    @if ($resultado['resto']) <span class="ct-chip ct-chip-n">{{ $resultado['resto'] }} sin procesar: súbelas en otra tanda</span> @endif
                @endif
                @foreach (array_slice($resultado['fallos'], 0, 5) as $f)
                    <p class="ct-fila-s" style="width:100%; margin:0">{{ $f }}</p>
                @endforeach
            </div>
        @endif
    </section>

    @if ($conLista)
    {{-- 2 · Completar: cada factura con sus productos; lo que falta, en la misma línea. --}}
    <section class="ct-panel">
        <header>
            <div>
                <h2 class="ct-tit">Facturas por auditar</h2>
                <p class="ct-pie2">Cada factura muestra cómo dice su XML que se pagó y qué propone el ERP para cada producto. Regístralas una por una, cambiando lo que haga falta, o todas juntas tal como dicen sus XML.</p>
            </div>
            @if ($compras->count() > 1)
                <button type="button" class="ct-btn-descarga" style="border:0; cursor:pointer"
                        wire:click="registrarTodas" wire:loading.attr="disabled" wire:target="registrarTodas">
                    {{ $proveedor ? 'Registrar las ' . $compras->count() . ' de este proveedor' : 'Registrar todas según su XML' }}
                </button>
            @endif
        </header>

        @if ($proveedor && $compras->isNotEmpty())
            {{-- Un solo pago para todo el proveedor; cada fila lo toma y se puede cambiar. --}}
            <div class="ct-fila" style="flex-wrap:wrap; gap:10px">
                <p class="ct-fila-t" style="flex:1; min-width:200px">Cómo se pagaron todas</p>
                <select wire:model.live="pagoTodas" class="ct-select" style="flex:1 1 260px" aria-label="Cómo se pagaron todas">
                    <option value="">Como diga el XML de cada una</option>
                    @foreach ($medios as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        {{-- El XML dice el tipo de pago, no de qué caja o banco salió: se elige una vez por tipo. --}}
        @if (count($cuentasPorElegir))
            <div class="ct-fila" style="flex-wrap:wrap; gap:10px">
                @foreach ($cuentasPorElegir as $forma => $info)
                    <label class="ct-fila-s" style="display:flex; flex-direction:column; gap:4px; flex:1 1 240px">
                        {{ $info['etiqueta'] }} ({{ $info['cuantas'] }} {{ $info['cuantas'] === 1 ? 'factura' : 'facturas' }}): ¿de cuál salió?
                        <select wire:model.live="cuentaPor.{{ $forma }}" class="ct-select">
                            <option value="">— elegir —</option>
                            @foreach ($info['opciones'] as $valor => $etiqueta)
                                <option value="{{ $valor }}">{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach
            </div>
        @endif

        @forelse ($compras as $c)
            <div class="ct-fila" style="flex-direction:column; align-items:stretch; gap:10px" wire:key="cf-{{ $c->id }}">
                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap">
                    <div>
                        <p class="ct-fila-t">{{ $c->supplier?->nombre ?? 'Proveedor' }}</p>
                        <p class="ct-fila-s">{{ $c->numero_factura }} · {{ $c->date?->format('d/m/Y') }} · <strong>{{ $money($c->total) }}</strong></p>
                    </div>
                    <span style="display:flex; gap:6px; flex-wrap:wrap; align-self:flex-start">
                        <span class="ct-chip ct-chip-n">XML: {{ $c->forma_pago_sri ? ($formasSri[$c->forma_pago_sri] ?? 'código ' . $c->forma_pago_sri) : 'no dice cómo se pagó' }}</span>
                        <span class="ct-chip ct-chip-ok">Con detalle · {{ $c->items->count() }} {{ $c->items->count() === 1 ? 'producto' : 'productos' }}</span>
                    </span>
                </div>

                {{-- Una factura que es un solo gasto (p. ej. médica): un tipo para todas sus líneas. --}}
                @if ($c->items->contains(fn ($l) => $l->productoProveedor && ! $l->productoProveedor->estaConfigurado()))
                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
                        <span class="ct-fila-s" style="margin:0">Toda la factura a un tipo de gasto:</span>
                        <select wire:model.live="gastoTodas.{{ $c->id }}" class="ct-select" style="flex:0 1 320px" aria-label="Toda la factura a un tipo de gasto">
                            <option value="">— línea por línea —</option>
                            @foreach ($tiposGasto as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

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
                    @php $sugerida = $sugeridas[$c->id] ?? null; @endphp
                    <select wire:model.live="pagoCompra.{{ $c->id }}" class="ct-select" style="flex:0 1 300px" aria-label="Cómo se pagó">
                        <option value="">{{ $sugerida ? 'Según el XML · ' . ($medios[$sugerida] ?? $sugerida) : '— cómo se pagó —' }}</option>
                        @foreach ($medios as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    @if ($c->origen === \App\Models\Purchase::ORIGEN_PORTAL)
                        <button type="button" class="ct-pestana" wire:click="noEsDeLaEmpresa({{ $c->id }})"
                                wire:confirm="¿Es una compra personal? No se registra como gasto ni mueve dinero de la empresa: solo su IVA suma al 104.">No es de la empresa</button>
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
    @endif
</div>
