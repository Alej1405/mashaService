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

            <div class="flex flex-col shrink-0" style="width: 260px;">

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
                        $secundarios  = \App\Models\LogisticsPackage::ESTADOS_SECUNDARIOS[$package->estado] ?? [];
                        $secActivo    = $package->estado_secundario;
                        $secInfo      = $secActivo ? ($secundarios[$secActivo] ?? null) : null;
                        $clienteNombre = $package->storeCustomer
                            ? trim($package->storeCustomer->nombre . ' ' . ($package->storeCustomer->apellido ?? ''))
                            : '—';
                    @endphp

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700
                                cursor-grab active:cursor-grabbing select-none
                                hover:shadow-md transition-all duration-150 p-3"
                         data-id="{{ $package->id }}"
                         wire:key="pkg-{{ $package->id }}">

                        {{-- Tracking + origen --}}
                        <div class="flex items-start justify-between mb-1.5">
                            <span class="font-mono text-xs font-bold text-gray-800 dark:text-gray-100 truncate max-w-[140px]"
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
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate leading-snug">
                            {{ $clienteNombre }}
                        </p>

                        {{-- Descripción breve --}}
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

                        {{-- Estado secundario activo --}}
                        @if($secInfo)
                        <div class="mt-1.5">
                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full text-white"
                                  style="background-color: {{ $secInfo['color'] }}">
                                <span class="w-1.5 h-1.5 rounded-full bg-white opacity-80"></span>
                                {{ $secInfo['label'] }}
                            </span>
                        </div>
                        @endif

                        {{-- Selector de estados secundarios --}}
                        @if(count($secundarios) > 0)
                        <div class="mt-2 pt-1.5 border-t border-gray-50 dark:border-gray-700">
                            <div class="flex flex-wrap gap-1">
                                @foreach($secundarios as $key => $sec)
                                <button
                                    wire:click.stop="setEstadoSecundario({{ $package->id }}, '{{ $key }}')"
                                    class="text-[10px] px-1.5 py-0.5 rounded-full font-medium border transition-all cursor-pointer"
                                    style="{{ $secActivo === $key
                                        ? 'background:' . $sec['color'] . ';color:#fff;border-color:' . $sec['color'] . ';'
                                        : 'background:#f8fafc;color:#94a3b8;border-color:#e2e8f0;' }}"
                                    title="{{ $sec['label'] }}">
                                    {{ $sec['label'] }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Editar --}}
                        <div class="mt-1.5 pt-1 border-t border-gray-50 dark:border-gray-700">
                            <a href="{{ \App\Filament\Logistics\Resources\PackageResource::getUrl('edit', ['record' => $package->id], tenant: \Filament\Facades\Filament::getTenant()) }}"
                               wire:navigate
                               class="block text-center text-[11px] text-gray-400 hover:text-primary-600 transition py-0.5 rounded hover:bg-primary-50">
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
