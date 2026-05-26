<x-filament-panels::page>

    @php
        $labelCls = 'text-xs font-medium text-gray-400 uppercase tracking-wide mb-0.5';
        $valueCls = 'text-sm font-bold text-gray-800';

        $estadoColor = [
            'borrador'    => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600',  'label' => 'Borrador'],
            'en_proyecto' => ['bg' => 'bg-blue-100',  'text' => 'text-blue-700',  'label' => 'En Proyecto'],
            'aprobado'    => ['bg' => 'bg-emerald-100','text' => 'text-emerald-700','label' => 'Aprobado'],
            'archivado'   => ['bg' => 'bg-amber-100',  'text' => 'text-amber-700', 'label' => 'Archivado'],
        ];
    @endphp

    @if($simulations->isNotEmpty())

        <div class="mb-6">
            <p class="text-sm text-gray-500">Selecciona una simulación para planificar su producción y asignarle fechas.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($simulations as $sim)
            @php
                $estado    = $estadoColor[$sim->estado] ?? $estadoColor['borrador'];
                $margen    = (float) ($sim->margen_bruto ?? 0);
                $roi       = (float) ($sim->roi ?? 0);
                $margenClr = $margen >= 30 ? 'text-emerald-600' : ($margen >= 15 ? 'text-amber-600' : 'text-red-500');
                $roiClr    = $roi >= 20 ? 'text-emerald-600' : ($roi >= 0 ? 'text-amber-600' : 'text-red-500');

                // Verificar si ya tiene plan asignado
                $yaPlaneado = \App\Models\ProductionPlan::where('product_simulation_id', $sim->id)->exists();
            @endphp
            <div
                wire:click="mountAction('planificar', {{ \Illuminate\Support\Js::from([
                    'simulation_id' => $sim->id,
                    'nombre'        => $sim->nombre,
                ]) }})"
                class="group cursor-pointer rounded-xl border bg-white shadow-sm p-5 transition-all duration-200
                       {{ $yaPlaneado ? 'border-emerald-300 hover:border-emerald-400 hover:bg-emerald-50/30' : 'border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/30 hover:shadow-md' }}"
            >
                {{-- Header --}}
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="flex-shrink-0 w-8 h-8 rounded-lg {{ $yaPlaneado ? 'bg-emerald-100' : 'bg-indigo-100' }} flex items-center justify-center">
                            @if($yaPlaneado)
                                <x-heroicon-o-check-circle class="w-4 h-4 text-emerald-600" />
                            @else
                                <x-heroicon-o-beaker class="w-4 h-4 text-indigo-600" />
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-800 text-sm leading-tight truncate">{{ $sim->nombre }}</p>
                            <p class="text-xs text-gray-400 mt-0.5 truncate">
                                {{ $sim->productDesign?->nombre ?? '—' }}
                                @if($sim->presentation_nombre)
                                    · <span class="text-gray-500">{{ $sim->presentation_nombre }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <span class="flex-shrink-0 text-xs font-medium px-2 py-0.5 rounded-full {{ $estado['bg'] }} {{ $estado['text'] }}">
                        {{ $estado['label'] }}
                    </span>
                </div>

                {{-- Cantidad --}}
                <div class="flex items-center gap-1 mb-3">
                    <x-heroicon-o-cube class="w-3.5 h-3.5 text-gray-400" />
                    <span class="text-xs text-gray-500">Lote: <strong class="text-gray-700">{{ number_format((float)$sim->cantidad, 0) }} u.</strong></span>
                </div>

                <div class="border-t border-gray-100 mb-3"></div>

                {{-- KPIs financieros --}}
                <div class="grid grid-cols-2 gap-x-3 gap-y-2">
                    <div>
                        <p class="{{ $labelCls }}">Costo total</p>
                        <p class="text-sm font-bold text-red-500">${{ number_format((float)$sim->costo_total, 2) }}</p>
                    </div>
                    <div>
                        <p class="{{ $labelCls }}">Ingreso neto</p>
                        <p class="text-sm font-bold text-emerald-600">${{ number_format((float)$sim->ingreso_neto, 2) }}</p>
                    </div>
                    <div>
                        <p class="{{ $labelCls }}">Margen bruto</p>
                        <p class="text-sm font-bold {{ $margenClr }}">{{ number_format($margen, 1) }}%</p>
                    </div>
                    <div>
                        <p class="{{ $labelCls }}">ROI</p>
                        <p class="text-sm font-bold {{ $roiClr }}">{{ number_format($roi, 1) }}%</p>
                    </div>
                </div>

                {{-- CTA --}}
                <div class="mt-4 flex items-center justify-center gap-1 text-xs font-medium transition-opacity
                    {{ $yaPlaneado
                        ? 'text-emerald-600 opacity-100'
                        : 'text-indigo-500 opacity-0 group-hover:opacity-100' }}">
                    @if($yaPlaneado)
                        <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
                        Ya planificado — volver a planificar
                    @else
                        <x-heroicon-o-plus-circle class="w-3.5 h-3.5" />
                        Asignar fechas de producción
                    @endif
                </div>
            </div>
            @endforeach
        </div>

    @else
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <x-heroicon-o-beaker class="w-16 h-16 text-gray-300 mb-4" />
            <p class="text-lg font-semibold text-gray-500">Sin simulaciones guardadas</p>
            <p class="text-sm text-gray-400 mt-1">
                Crea y guarda simulaciones en la sección <strong>Diseño de Productos</strong> para poder planificar su producción.
            </p>
        </div>
    @endif

    <x-filament-actions::modals />

</x-filament-panels::page>
