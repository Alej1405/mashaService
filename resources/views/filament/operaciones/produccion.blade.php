<x-filament-panels::page>

@php
    $estadoConfig = [
        'en_proyecto' => ['border' => 'border-blue-200',   'badge_bg' => 'bg-blue-50',   'badge_text' => 'text-blue-700',    'dot' => 'bg-blue-400',   'label' => 'En Proyecto'],
        'en_proceso'  => ['border' => 'border-indigo-200', 'badge_bg' => 'bg-indigo-50', 'badge_text' => 'text-indigo-700',  'dot' => 'bg-indigo-500', 'label' => 'En Proceso'],
        'sin_stock'   => ['border' => 'border-amber-200',  'badge_bg' => 'bg-amber-50',  'badge_text' => 'text-amber-700',   'dot' => 'bg-amber-400',  'label' => 'Sin Stock'],
        'finalizado'  => ['border' => 'border-emerald-200','badge_bg' => 'bg-emerald-50','badge_text' => 'text-emerald-700', 'dot' => 'bg-emerald-400','label' => 'Finalizado'],
        'despachado'  => ['border' => 'border-gray-200',   'badge_bg' => 'bg-gray-50',   'badge_text' => 'text-gray-600',    'dot' => 'bg-gray-400',   'label' => 'Despachado'],
    ];

    $grupos      = $planes->groupBy('estado');
    $ordenGrupos = ['en_proceso', 'sin_stock', 'en_proyecto', 'finalizado', 'despachado'];
@endphp

@if($planes->isEmpty())
    <div class="flex flex-col items-center justify-center py-24 text-center">
        <x-heroicon-o-cog-6-tooth class="w-14 h-14 text-gray-300 mb-4" />
        <p class="text-base font-semibold text-gray-600">Sin planes de producción</p>
        <p class="text-sm text-gray-400 mt-1">
            Planifica simulaciones en <strong>Planificación</strong> para que aparezcan aquí.
        </p>
    </div>
@else

    @foreach($ordenGrupos as $estadoKey)
        @if($grupos->has($estadoKey))
        @php $cfg = $estadoConfig[$estadoKey]; @endphp

        <div class="mb-8">
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 rounded-full {{ $cfg['dot'] }}"></span>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-500">
                    {{ $cfg['label'] }}
                    <span class="ml-1 font-normal text-gray-400">({{ $grupos[$estadoKey]->count() }})</span>
                </h2>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($grupos[$estadoKey] as $plan)
                @php
                    $sim               = $plan->simulation;
                    $design            = $sim?->productDesign;
                    $orders            = $plan->productionOrders;
                    $numOrd              = $orders->count();
                    $completadas         = $orders->where('estado', 'completado')->count();
                    $pct                 = $numOrd > 0 ? round(($completadas / $numOrd) * 100) : 0;
                    $totalSim            = (float) ($sim?->cantidad ?? 0);
                    $unidadesPlanif      = $orders->whereNotIn('estado', ['anulado'])->sum('cantidad_producida');
                    $unidadesFaltantes   = max(0, $totalSim - $unidadesPlanif);
                    $tieneAbastecimiento = $orders->where('estado', 'abastecimiento')->count() > 0;
                @endphp

                <div class="flex flex-col rounded-xl border {{ $cfg['border'] }} bg-white shadow-sm overflow-hidden">

                    {{-- Header --}}
                    <div class="px-4 pt-4 pb-3 border-b border-gray-100">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <p class="font-semibold text-gray-800 text-sm leading-snug line-clamp-2">
                                {{ $sim?->nombre ?? '—' }}
                            </p>
                            <span class="flex-shrink-0 text-xs font-medium px-2 py-0.5 rounded-full {{ $cfg['badge_bg'] }} {{ $cfg['badge_text'] }}">
                                {{ $cfg['label'] }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 truncate">
                            {{ $design?->nombre ?? '—' }}
                            @if($sim?->presentation_nombre)
                                · <em>{{ $sim->presentation_nombre }}</em>
                            @endif
                        </p>
                    </div>

                    {{-- Datos --}}
                    <div class="px-4 py-3 space-y-1.5 flex-1">

                        <div class="flex items-center gap-1.5 text-xs text-gray-500">
                            <x-heroicon-o-calendar-days class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                            <span>{{ $plan->fecha_inicio?->format('d/m/Y') }} — {{ $plan->fecha_fin?->format('d/m/Y') }}</span>
                        </div>

                        <div class="flex items-center gap-1.5 text-xs text-gray-500">
                            <x-heroicon-o-cube class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                            <span><strong class="text-gray-700">{{ number_format($totalSim, 0) }}</strong> u. total · <strong class="text-red-500">${{ number_format((float)($sim?->costo_total ?? 0), 2) }}</strong></span>
                        </div>

                        {{-- Cobertura de etapas --}}
                        @if($numOrd > 0 || $estadoKey !== 'en_proyecto')
                        <div class="flex items-center justify-between text-xs mt-0.5">
                            <span class="text-gray-500">Planificado: <strong class="text-gray-700">{{ number_format($unidadesPlanif, 0) }}</strong> u.</span>
                            @if($unidadesFaltantes > 0)
                                <span class="font-semibold text-amber-600">Faltan {{ number_format($unidadesFaltantes, 0) }} u.</span>
                            @else
                                <span class="font-semibold text-emerald-600">✓ Cubierto</span>
                            @endif
                        </div>
                        @endif

                        {{-- Progreso --}}
                        @if($numOrd > 0)
                        <div class="pt-1">
                            <div class="flex justify-between text-xs text-gray-400 mb-1">
                                <span>{{ $completadas }}/{{ $numOrd }} etapas</span>
                                <span>{{ $pct }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                <div class="bg-indigo-500 h-1.5 rounded-full" style="width:{{ $pct }}%"></div>
                            </div>
                            <div class="mt-1.5 space-y-0.5">
                                @foreach($orders->sortBy('fecha')->take(4) as $ord)
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex flex-col">
                                        <span class="text-gray-500 font-mono text-[11px]">{{ $ord->referencia }}</span>
                                        @if($ord->fecha)
                                            <span class="text-gray-400 text-[10px]">
                                                {{ $ord->fecha->format('d/m') }}{{ $ord->fecha_fin ? ' – '.$ord->fecha_fin->format('d/m') : '' }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="text-gray-400">{{ number_format((float)$ord->cantidad_producida, 0) }} u.</span>
                                        @if($ord->estado === 'completado')
                                            <span class="text-emerald-500">✓</span>
                                        @elseif($ord->estado === 'anulado')
                                            <span class="text-red-400">✗</span>
                                        @elseif($ord->estado === 'abastecimiento')
                                            <span class="inline-flex items-center gap-0.5 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700">⚠ abast.</span>
                                        @else
                                            <span class="text-indigo-400">●</span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                                @if($orders->count() > 4)
                                    <p class="text-[11px] text-gray-400">+{{ $orders->count() - 4 }} más...</p>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Acciones --}}
                    <div class="px-4 pb-4 pt-3 border-t border-gray-100 flex flex-col gap-2">

                        @if($estadoKey === 'en_proyecto')
                            <button
                                wire:click="mountAction('configurarProduccion', {{ \Illuminate\Support\Js::from(['plan_id' => $plan->id, 'nombre' => $sim?->nombre]) }})"
                                class="w-full flex items-center justify-center gap-1.5 text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 transition-colors">
                                <x-heroicon-o-cog-6-tooth class="w-3.5 h-3.5" />
                                Configurar producción
                            </button>
                        @endif

                        @if($estadoKey === 'en_proceso')
                            @if($tieneAbastecimiento)
                                <div class="w-full flex items-center gap-1.5 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                    <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5 flex-shrink-0" />
                                    <span>Hay etapas pendientes de abastecimiento.</span>
                                </div>
                            @else
                                <button
                                    wire:click="mountAction('cambiarEstado', {{ \Illuminate\Support\Js::from(['plan_id' => $plan->id, 'nuevo_estado' => 'finalizado']) }})"
                                    class="w-full flex items-center justify-center gap-1.5 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-3 py-2 transition-colors">
                                    <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
                                    Marcar finalizado
                                </button>
                            @endif
                            <button
                                wire:click="mountAction('cambiarEstado', {{ \Illuminate\Support\Js::from(['plan_id' => $plan->id, 'nuevo_estado' => 'sin_stock']) }})"
                                class="w-full flex items-center justify-center gap-1.5 text-xs font-medium border border-amber-300 text-amber-700 hover:bg-amber-50 rounded-lg px-3 py-1.5 transition-colors">
                                Pausar por falta de stock
                            </button>
                        @endif

                        @if($estadoKey === 'sin_stock')
                            <button
                                wire:click="mountAction('cambiarEstado', {{ \Illuminate\Support\Js::from(['plan_id' => $plan->id, 'nuevo_estado' => 'en_proceso']) }})"
                                class="w-full flex items-center justify-center gap-1.5 text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 transition-colors">
                                <x-heroicon-o-play class="w-3.5 h-3.5" />
                                Reanudar producción
                            </button>
                        @endif

                        @if($estadoKey === 'finalizado')
                            <button
                                wire:click="mountAction('cambiarEstado', {{ \Illuminate\Support\Js::from(['plan_id' => $plan->id, 'nuevo_estado' => 'despachado']) }})"
                                class="w-full flex items-center justify-center gap-1.5 text-xs font-semibold bg-gray-700 hover:bg-gray-800 text-white rounded-lg px-3 py-2 transition-colors">
                                <x-heroicon-o-truck class="w-3.5 h-3.5" />
                                Marcar despachado
                            </button>
                        @endif

                        @if($estadoKey === 'despachado')
                            <p class="text-xs text-gray-400 text-center py-1 italic">Proceso completado</p>
                        @endif

                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach

@endif

<x-filament-actions::modals />

</x-filament-panels::page>
