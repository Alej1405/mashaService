<x-filament-panels::page>

    {{-- Barra superior --}}
    <div class="flex flex-wrap items-center gap-3 mb-4">
        @php $totales = $this->totales; @endphp
        <div class="flex gap-2 text-sm">
            <span class="px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium">
                Total: <strong>{{ $totales['total'] }}</strong>
            </span>
            <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 font-medium">
                En curso: <strong>{{ $totales['en_curso'] }}</strong>
            </span>
            <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 font-medium">
                Entregados: <strong>{{ $totales['entregados'] }}</strong>
            </span>
        </div>
        <div class="ml-auto">
            <a href="{{ \App\Filament\Logistics\Resources\PackageResource::getUrl('create', tenant: \Filament\Facades\Filament::getTenant()) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                      bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo paquete
            </a>
        </div>
    </div>

    {{-- Tablero Kanban --}}
    <div class="overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
        <div class="flex gap-3 pb-4" style="min-width: max-content; align-items: flex-start;">

            @php
                $columns         = $this->columns;
                $packagesByState = $this->packagesByState;
            @endphp

            @foreach($columns as $estado => $info)
            @php $cards = $packagesByState[$estado] ?? []; @endphp

            <div class="flex flex-col shrink-0" style="width: 272px;">

                {{-- Cabecera columna --}}
                <div class="rounded-t-xl px-3 py-2.5 flex items-center justify-between"
                     style="background-color: {{ $info['color'] }}22; border-left: 3px solid {{ $info['color'] }}">
                    <p class="text-xs font-bold leading-tight" style="color: {{ $info['color'] }}">
                        {{ $info['label'] }}
                    </p>
                    <span class="text-[11px] font-bold rounded-full w-5 h-5 flex items-center justify-center text-white"
                          style="background-color: {{ $info['color'] }}">
                        {{ count($cards) }}
                    </span>
                </div>

                {{-- Columna droppable --}}
                <div x-data
                     x-init="
                         $nextTick(() => {
                             if (typeof Sortable === 'undefined') return;
                             if ($el._sortable) $el._sortable.destroy();
                             $el._sortable = Sortable.create($el, {
                                 group: 'kanban',
                                 animation: 200,
                                 ghostClass: 'opacity-30',
                                 onEnd(evt) {
                                     const id     = parseInt(evt.item.dataset.id);
                                     const estado = evt.to.dataset.estado;
                                     if (!id || !estado) return;
                                     $wire.moverPaquete(id, estado);
                                 }
                             });
                         })
                     "
                     data-estado="{{ $estado }}"
                     class="rounded-b-xl p-2 space-y-2"
                     style="min-height: 400px;
                            max-height: calc(100vh - 280px);
                            overflow-y: auto;
                            background-color: {{ $info['color'] }}08;
                            border: 1px solid {{ $info['color'] }}25;
                            border-top: none;">

                    @foreach($cards as $package)
                    @php
                        $secundarios   = \App\Models\LogisticsPackage::ESTADOS_SECUNDARIOS[$package->estado] ?? [];
                        $secActivo     = $package->estado_secundario;
                        $clienteNombre = $package->storeCustomer
                            ? trim($package->storeCustomer->nombre . ' ' . ($package->storeCustomer->apellido ?? ''))
                            : '—';
                        $shipment   = $package->shipments->first();
                        $shipEstado = $shipment ? (\App\Models\LogisticsShipment::ESTADOS[$shipment->estado] ?? null) : null;
                    @endphp

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm
                                border border-gray-100 dark:border-gray-700
                                cursor-grab active:cursor-grabbing select-none
                                hover:shadow-md transition-all duration-150"
                         data-id="{{ $package->id }}"
                         wire:key="pkg-{{ $package->id }}">

                        {{-- ── Cabecera de la tarjeta: embarque prominente ── --}}
                        @if($shipment)
                        <div class="px-3 pt-2.5 pb-2 border-b border-gray-100 dark:border-gray-700">
                            <a href="{{ \App\Filament\Logistics\Resources\ShipmentResource::getUrl('edit', ['record' => $shipment->id], tenant: \Filament\Facades\Filament::getTenant()) }}"
                               wire:navigate.stop
                               class="flex items-center justify-between gap-2 group">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <svg class="w-3.5 h-3.5 shrink-0 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                    </svg>
                                    <span class="font-mono text-sm font-bold text-primary-600 dark:text-primary-400 truncate group-hover:underline">
                                        {{ $shipment->numero_embarque }}
                                    </span>
                                </div>
                                @if($shipEstado)
                                <span class="shrink-0 text-[10px] font-semibold px-2 py-0.5 rounded-full text-white whitespace-nowrap"
                                      style="background-color: {{ $shipEstado['color'] }}">
                                    {{ $shipEstado['label'] }}
                                </span>
                                @endif
                            </a>
                        </div>
                        @endif

                        {{-- ── Cuerpo ── --}}
                        <div class="px-3 pt-2 pb-1">

                            {{-- Tracking + origen --}}
                            <div class="flex items-center justify-between gap-1 mb-1">
                                <span class="font-mono text-[11px] font-semibold text-gray-500 dark:text-gray-400 truncate"
                                      title="{{ $package->numero_tracking }}">
                                    {{ $package->numero_tracking ?? 'Sin tracking' }}
                                </span>
                                @if($package->bodega)
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-blue-50 text-blue-600 shrink-0">
                                    {{ $package->bodega->pais ?? '—' }}
                                </span>
                                @endif
                            </div>

                            {{-- Cliente --}}
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate leading-snug">
                                {{ $clienteNombre }}
                            </p>

                            {{-- Descripción --}}
                            @if($package->descripcion)
                            <p class="text-[11px] text-gray-400 truncate mt-0.5">{{ $package->descripcion }}</p>
                            @endif

                            {{-- Métricas --}}
                            <div class="mt-1.5 flex flex-wrap gap-x-2 gap-y-0.5 text-[11px] text-gray-500">
                                @if($package->peso_kg)
                                    <span>⚖️ {{ $package->peso_kg }} kg</span>
                                @endif
                                @if($package->valor_declarado)
                                    <span>💵 ${{ number_format($package->valor_declarado, 0) }}</span>
                                @endif
                                @if($package->monto_cobro)
                                    <span class="text-orange-500 font-semibold">💰 ${{ number_format($package->monto_cobro, 2) }}</span>
                                @endif
                            </div>

                        </div>

                        {{-- ── Estado secundario (select) ── --}}
                        @if(count($secundarios) > 0)
                        <div class="px-3 pb-2">
                            <select
                                wire:change.stop="setEstadoSecundario({{ $package->id }}, $event.target.value)"
                                class="w-full text-[11px] rounded-lg border px-2 py-1.5 font-medium
                                       focus:outline-none focus:ring-2 focus:ring-primary-400 transition
                                       bg-gray-50 dark:bg-gray-700 dark:border-gray-600
                                       border-gray-200 text-gray-600 dark:text-gray-300 cursor-pointer">
                                <option value="">— sin subestado —</option>
                                @foreach($secundarios as $key => $sec)
                                <option value="{{ $key }}" {{ $secActivo === $key ? 'selected' : '' }}>
                                    {{ $sec['label'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- ── Enlace editar ── --}}
                        <div class="px-3 pb-2.5">
                            <a href="{{ \App\Filament\Logistics\Resources\PackageResource::getUrl('edit', ['record' => $package->id], tenant: \Filament\Facades\Filament::getTenant()) }}"
                               wire:navigate
                               class="block text-center text-[11px] text-gray-400 hover:text-primary-600 transition
                                      py-1 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 border border-transparent hover:border-primary-100">
                                Ver / editar →
                            </a>
                        </div>

                    </div>
                    @endforeach

                </div>
            </div>
            @endforeach

        </div>
    </div>

</x-filament-panels::page>
