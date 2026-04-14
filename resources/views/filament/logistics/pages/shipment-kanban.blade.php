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
                Entregados: <strong>{{ $totales['entregada'] }}</strong>
            </span>
        </div>

        <div class="ml-auto flex items-center gap-2">
            <select wire:model.live="filtroTipo"
                    class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200
                           shadow-sm focus:ring-primary-500 focus:border-primary-500 py-1.5 pl-3 pr-8">
                <option value="">Todos los tipos</option>
                @foreach(\App\Models\LogisticsShipment::TIPOS as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>

            <a href="{{ \App\Filament\Logistics\Resources\ShipmentResource::getUrl('create', tenant: \Filament\Facades\Filament::getTenant()) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                      bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo embarque
            </a>
        </div>
    </div>

    {{-- Tablero Kanban --}}
    <div class="overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
        <div class="flex gap-3 pb-4" style="min-width: max-content; align-items: flex-start;">

            @php
                $columns          = $this->columns;
                $shipmentsByState = $this->shipmentsByState;
            @endphp

            @foreach($columns as $estado => $info)
            @php $cards = $shipmentsByState[$estado] ?? []; @endphp

            <div class="flex flex-col shrink-0" style="width: 255px;">

                {{-- Cabecera --}}
                <div class="rounded-t-xl px-3 py-2 flex items-center justify-between"
                     style="background-color: {{ $info['color'] }}22; border-left: 3px solid {{ $info['color'] }}">
                    <div>
                        <p class="text-xs font-bold leading-tight" style="color: {{ $info['color'] }}">
                            {{ $info['label'] }}
                        </p>
                        <p class="text-[10px] text-gray-400">{{ $info['grupo'] }}</p>
                    </div>
                    <span class="text-[11px] font-bold rounded-full w-5 h-5 flex items-center justify-center text-white"
                          style="background-color: {{ $info['color'] }}">
                        {{ count($cards) }}
                    </span>
                </div>

                {{-- Cuerpo droppable — x-init inicializa SortableJS cuando el nodo está en el DOM --}}
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
                                     $wire.moverEmbarque(id, estado);
                                 }
                             });
                         })
                     "
                     data-estado="{{ $estado }}"
                     class="rounded-b-xl p-2 space-y-2"
                     style="min-height: 380px;
                            max-height: calc(100vh - 290px);
                            overflow-y: auto;
                            background-color: {{ $info['color'] }}08;
                            border: 1px solid {{ $info['color'] }}25;
                            border-top: none;">

                    @foreach($cards as $shipment)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700
                                cursor-grab active:cursor-grabbing select-none
                                hover:shadow-md hover:border-gray-200 transition-all duration-150 p-3"
                         data-id="{{ $shipment->id }}"
                         wire:key="card-{{ $shipment->id }}">

                        {{-- Número + tipo --}}
                        <div class="flex items-start justify-between mb-1.5">
                            <span class="font-mono text-xs font-bold text-gray-800 dark:text-gray-100">
                                {{ $shipment->numero_embarque }}
                            </span>
                            @php
                                $tipoStyles = match($shipment->tipo) {
                                    'consolidado' => 'background:#f3e8ff;color:#7c3aed',
                                    'fraccionado' => 'background:#fff7ed;color:#c2410c',
                                    default       => 'background:#eff6ff;color:#1d4ed8',
                                };
                            @endphp
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full"
                                  style="{{ $tipoStyles }}">
                                {{ ucfirst($shipment->tipo) }}
                            </span>
                        </div>

                        {{-- Consignatario --}}
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate leading-snug">
                            {{ $shipment->consignatario?->nombre ?? '—' }}
                        </p>

                        {{-- Meta --}}
                        <div class="mt-2 flex flex-wrap gap-x-2 gap-y-0.5 text-[11px] text-gray-500">
                            <span>🌍 {{ $shipment->bodega?->pais ?? '—' }}</span>
                            <span>💵 ${{ number_format($shipment->valor_total_declarado, 0) }}</span>
                            <span>📦 {{ $shipment->packages_count ?? 0 }}</span>
                            @if($shipment->fecha_embarque)
                                <span>📅 {{ $shipment->fecha_embarque->format('d/m/y') }}</span>
                            @endif
                        </div>

                        {{-- Alerta SENAE > $400 --}}
                        @if($shipment->valor_total_declarado > 400)
                        <div class="mt-1.5 text-[10px] text-amber-600 font-medium flex items-center gap-1">
                            <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Supera $400 — sujeto a impuesto
                        </div>
                        @endif

                        {{-- Editar --}}
                        <div class="mt-2 pt-1.5 border-t border-gray-50 dark:border-gray-700">
                            <a href="{{ \App\Filament\Logistics\Resources\ShipmentResource::getUrl('edit', ['record' => $shipment->id], tenant: \Filament\Facades\Filament::getTenant()) }}"
                               class="block text-center text-[11px] text-gray-400 hover:text-primary-600 transition py-0.5 rounded hover:bg-primary-50 dark:hover:bg-primary-900/30">
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
