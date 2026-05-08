<x-filament-panels::page>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">

        @foreach($informes as $informe)
            @php
                $colorMap = [
                    'emerald' => ['bg' => 'bg-emerald-50 dark:bg-emerald-950', 'border' => 'border-emerald-200 dark:border-emerald-800', 'icon' => 'text-emerald-600 dark:text-emerald-400', 'metric' => 'text-emerald-700 dark:text-emerald-300'],
                    'rose'    => ['bg' => 'bg-rose-50 dark:bg-rose-950',       'border' => 'border-rose-200 dark:border-rose-800',       'icon' => 'text-rose-600 dark:text-rose-400',       'metric' => 'text-rose-700 dark:text-rose-300'],
                    'blue'    => ['bg' => 'bg-blue-50 dark:bg-blue-950',       'border' => 'border-blue-200 dark:border-blue-800',       'icon' => 'text-blue-600 dark:text-blue-400',       'metric' => 'text-blue-700 dark:text-blue-300'],
                    'amber'   => ['bg' => 'bg-amber-50 dark:bg-amber-950',     'border' => 'border-amber-200 dark:border-amber-800',     'icon' => 'text-amber-600 dark:text-amber-400',     'metric' => 'text-amber-700 dark:text-amber-300'],
                    'purple'  => ['bg' => 'bg-purple-50 dark:bg-purple-950',   'border' => 'border-purple-200 dark:border-purple-800',   'icon' => 'text-purple-600 dark:text-purple-400',   'metric' => 'text-purple-700 dark:text-purple-300'],
                    'slate'   => ['bg' => 'bg-slate-50 dark:bg-slate-800',     'border' => 'border-slate-200 dark:border-slate-700',     'icon' => 'text-slate-500 dark:text-slate-400',     'metric' => 'text-slate-700 dark:text-slate-300'],
                ];
                $c = $colorMap[$informe['color']] ?? $colorMap['slate'];
            @endphp

            <a href="{{ $informe['url'] }}"
               class="group block rounded-2xl border {{ $c['border'] }} {{ $c['bg'] }} p-6 transition hover:shadow-lg hover:scale-[1.01] cursor-pointer">

                <div class="flex items-start justify-between mb-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-1">
                            {{ $informe['titulo'] }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $informe['descripcion'] }}
                        </p>
                    </div>
                    <x-dynamic-component
                        :component="$informe['icono']"
                        class="w-8 h-8 {{ $c['icon'] }} shrink-0 ml-3" />
                </div>

                <p class="text-3xl font-bold {{ $c['metric'] }} mb-1">
                    {{ $informe['metrica'] }}
                </p>
                <p class="text-xs text-gray-400">
                    {{ $informe['etiqueta'] }}
                </p>

                <div class="mt-4 flex items-center gap-1 text-xs font-medium {{ $c['icon'] }} opacity-0 group-hover:opacity-100 transition">
                    Ver informe completo
                    <x-heroicon-o-arrow-right class="w-3 h-3" />
                </div>

            </a>
        @endforeach

    </div>

</x-filament-panels::page>
