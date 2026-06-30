<x-filament-panels::page>

<style>
/*
 * Sistema de motion para la página de documentación.
 * Emil: custom cubic-bezier, no Tailwind generics.
 * impeccable: prefers-reduced-motion es obligatorio.
 */
.tab-panel-enter {
    transition:
        opacity 180ms cubic-bezier(0.23, 1, 0.32, 1),
        transform 180ms cubic-bezier(0.23, 1, 0.32, 1);
}
.tab-panel-from {
    opacity: 0;
    transform: translateY(5px) scale(0.99);
}
.tab-panel-to {
    opacity: 1;
    transform: translateY(0) scale(1);
}
.tab-panel-leave {
    transition: opacity 90ms ease;
}
.tab-panel-leave-from { opacity: 1; }
.tab-panel-leave-to   { opacity: 0; }

/* Stagger en items de lista — primer render del panel */
.doc-list > li {
    opacity: 0;
    transform: translateY(4px);
    animation: docItemIn 220ms cubic-bezier(0.23, 1, 0.32, 1) both;
}
.doc-list > li:nth-child(1) { animation-delay: 40ms; }
.doc-list > li:nth-child(2) { animation-delay: 75ms; }
.doc-list > li:nth-child(3) { animation-delay: 110ms; }
.doc-list > li:nth-child(4) { animation-delay: 145ms; }
.doc-list > li:nth-child(5) { animation-delay: 180ms; }
.doc-list > li:nth-child(6) { animation-delay: 215ms; }

@keyframes docItemIn {
    to { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
    .tab-panel-enter,
    .tab-panel-leave { transition: none; }
    .tab-panel-from,
    .tab-panel-to { opacity: 1; transform: none; }
    .doc-list > li {
        animation: none;
        opacity: 1;
        transform: none;
    }
}

[x-cloak] { display: none !important; }
</style>

@php
/* Mapa de colores por módulo — inline styles (Tailwind purge no escanea variables Blade) */
$palette = [
    'violet'  => ['bg' => '#f5f3ff', 'color' => '#7c3aed', 'border' => '#ede9fe'],
    'emerald' => ['bg' => '#ecfdf5', 'color' => '#059669', 'border' => '#d1fae5'],
    'amber'   => ['bg' => '#fffbeb', 'color' => '#d97706', 'border' => '#fde68a'],
    'blue'    => ['bg' => '#eff6ff', 'color' => '#2563eb', 'border' => '#dbeafe'],
    'green'   => ['bg' => '#f0fdf4', 'color' => '#16a34a', 'border' => '#bbf7d0'],
    'orange'  => ['bg' => '#fff7ed', 'color' => '#ea580c', 'border' => '#fed7aa'],
    'pink'    => ['bg' => '#fdf2f8', 'color' => '#db2777', 'border' => '#fce7f3'],
    'cyan'    => ['bg' => '#ecfeff', 'color' => '#0891b2', 'border' => '#cffafe'],
    'slate'   => ['bg' => '#f8fafc', 'color' => '#64748b', 'border' => '#e2e8f0'],
];

$modulosData = $this->getModulesData();
$actionsData = $this->getActionsData();
$primerKey   = array_key_first($modulosData) ?? '';
@endphp


{{-- ══════════════════════════════════════════════════════
     SECCIÓN 1 — Módulos documentados
     Tabs: cada módulo es una pestaña, contenido full-width.
     Rompe el patrón "identical card grid" (impeccable ban).
══════════════════════════════════════════════════════ --}}

@if (!empty($modulosData))
<div x-data="{ tab: @js($primerKey) }" class="space-y-0">

    {{-- Encabezado de sección --}}
    <div class="mb-5">
        <h2 class="text-sm font-semibold text-slate-900">Módulos documentados</h2>
        <p class="mt-0.5 text-xs text-slate-500">
            {{ count($modulosData) }} de {{ count(config('erp_features', [])) }} módulos con documentación en esta iteración.
        </p>
    </div>

    {{-- Strip de tabs --}}
    <div class="flex items-end gap-0.5 border-b border-slate-200">
        @foreach ($modulosData as $key => $modulo)
        @php $c = $palette[$modulo['color']] ?? $palette['slate']; @endphp
        <button
            type="button"
            @click="tab = @js($key)"
            class="relative flex items-center gap-2 px-3.5 py-2.5 rounded-t-lg text-sm font-medium
                   transition-[color,background-color] duration-[120ms] ease
                   active:scale-[0.97] active:transition-none
                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-1"
            :class="tab === @js($key)
                ? 'bg-white border border-slate-200 border-b-white -mb-px z-10 text-slate-900'
                : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50/70'"
        >
            <span
                class="w-4 h-4 flex-shrink-0"
                :style="tab === @js($key)
                    ? 'color: {{ $c['color'] }}'
                    : 'color: #94a3b8'"
            >
                <x-dynamic-component :component="$modulo['icon']" class="w-4 h-4" />
            </span>
            {{ $modulo['label'] }}
        </button>
        @endforeach
    </div>

    {{-- Paneles de contenido — uno por módulo --}}
    @foreach ($modulosData as $key => $modulo)
    @php $c = $palette[$modulo['color']] ?? $palette['slate']; @endphp
    <div
        x-show="tab === @js($key)"
        x-cloak
        x-transition:enter="tab-panel-enter"
        x-transition:enter-start="tab-panel-from"
        x-transition:enter-end="tab-panel-to"
        x-transition:leave="tab-panel-leave"
        x-transition:leave-start="tab-panel-leave-from"
        x-transition:leave-end="tab-panel-leave-to"
        class="pt-6"
    >
        {{-- Cabecera del módulo: ícono + nombre + descripción corta --}}
        <div class="flex items-center gap-4 mb-6">
            <div
                class="w-12 h-12 flex-shrink-0 rounded-xl flex items-center justify-center"
                style="background-color: {{ $c['bg'] }}; border: 1.5px solid {{ $c['border'] }}"
            >
                <x-dynamic-component
                    :component="$modulo['icon']"
                    class="w-6 h-6"
                    style="color: {{ $c['color'] }}" />
            </div>
            <div>
                <h3 class="text-base font-semibold text-slate-900 leading-none">
                    {{ $modulo['label'] }}
                </h3>
                <p class="mt-1 text-sm text-slate-500 leading-snug max-w-[60ch]">
                    {{ $modulo['descripcion'] }}
                </p>
            </div>
        </div>

        {{-- Contenido en 3 columnas — diferencia cada módulo del anterior --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-x-8 gap-y-6">

            {{-- Columna 1: Descripción + Casos de uso --}}
            <div class="space-y-6">
                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">Descripción</p>
                    <p class="text-sm text-slate-700 leading-relaxed max-w-[65ch]">
                        {{ $modulo['descripcion_larga'] }}
                    </p>
                </div>

                @if (!empty($modulo['casos_uso']))
                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">Casos de uso</p>
                    <ul class="doc-list space-y-2">
                        @foreach ($modulo['casos_uso'] as $caso)
                        <li class="flex items-start gap-2 text-sm text-slate-600">
                            <x-heroicon-o-arrow-right
                                class="w-3.5 h-3.5 mt-0.5 flex-shrink-0 text-slate-400" />
                            {{ $caso }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>

            {{-- Columna 2: Alcance --}}
            <div class="space-y-6">
                @if (!empty($modulo['alcance']['incluye']))
                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">Incluye</p>
                    <ul class="doc-list space-y-2">
                        @foreach ($modulo['alcance']['incluye'] as $item)
                        <li class="flex items-start gap-2 text-sm text-slate-700">
                            <span
                                class="mt-0.5 flex-shrink-0 w-4 h-4 rounded-full flex items-center justify-center"
                                style="background-color: {{ $c['bg'] }}"
                            >
                                <x-heroicon-s-check class="w-2.5 h-2.5" style="color: {{ $c['color'] }}" />
                            </span>
                            {{ $item }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if (!empty($modulo['alcance']['no_incluye']))
                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">No incluye</p>
                    <ul class="space-y-1.5">
                        @foreach ($modulo['alcance']['no_incluye'] as $item)
                        <li class="flex items-start gap-2 text-sm text-slate-500">
                            <span class="mt-1.5 flex-shrink-0 w-1 h-1 rounded-full bg-slate-300"></span>
                            {{ $item }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>

            {{-- Columna 3: Referencias técnicas --}}
            <div class="space-y-6">
                @if (!empty($modulo['services']))
                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">Services</p>
                    <div class="space-y-2">
                        @foreach ($modulo['services'] as $clase => $descripcion)
                        <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2.5">
                            <code
                                class="text-xs font-mono break-all block"
                                style="color: {{ $c['color'] }}"
                            >{{ $clase }}</code>
                            <p class="text-xs text-slate-500 mt-1 leading-snug">{{ $descripcion }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if (!empty($modulo['queries_principales']))
                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">Queries Eloquent</p>
                    <div class="space-y-2">
                        @foreach ($modulo['queries_principales'] as $query => $descripcion)
                        <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2.5">
                            <code class="text-xs font-mono text-slate-700 break-all block leading-relaxed">
                                {{ $query }}
                            </code>
                            <p class="text-xs text-slate-500 mt-1 leading-snug">{{ $descripcion }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if (!empty($modulo['algoritmos']))
                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">Algoritmos</p>
                    <div class="space-y-2">
                        @foreach ($modulo['algoritmos'] as $tipo => $descripcion)
                        {{-- Amber: color firma del sistema para señales de alto valor --}}
                        <div class="rounded-lg border border-amber-100 bg-amber-50 px-3 py-2.5">
                            <p class="text-xs font-semibold text-amber-800">{{ $tipo }}</p>
                            <p class="text-xs text-amber-700 mt-0.5 leading-snug">{{ $descripcion }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

        </div>
    </div>
    @endforeach

</div>
@else
{{-- Estado vacío — impeccable: always design the empty state --}}
<div class="flex flex-col items-center justify-center py-16 text-center">
    <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center mb-3">
        <x-heroicon-o-book-open class="w-6 h-6 text-slate-400" />
    </div>
    <p class="text-sm font-semibold text-slate-700">Sin módulos documentados</p>
    <p class="mt-1 text-xs text-slate-400 max-w-xs leading-relaxed">
        Agrega <code class="font-mono bg-slate-100 px-1 rounded">descripcion_larga</code>
        a un módulo en <code class="font-mono bg-slate-100 px-1 rounded">config/erp_features.php</code>
        para que aparezca aquí.
    </p>
</div>
@endif


{{-- ══════════════════════════════════════════════════════
     SECCIÓN 2 — Clases con #[Documentado]
     Filas en lugar de cards anidadas.
══════════════════════════════════════════════════════ --}}

@if (!empty($actionsData))
<div class="mt-10 space-y-3">

    <div>
        <h2 class="text-sm font-semibold text-slate-900">Clases con #[Documentado]</h2>
        <p class="mt-0.5 text-xs text-slate-500">
            Escaneado desde
            <code class="font-mono bg-slate-100 px-1 py-0.5 rounded text-xs">app/Shared/Actions/</code>
            y
            <code class="font-mono bg-slate-100 px-1 py-0.5 rounded text-xs">app/Shared/Queries/</code>
        </p>
    </div>

    @foreach ($actionsData as $grupo => $clases)
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">

        {{-- Encabezado del grupo --}}
        <div class="flex items-center gap-2 px-4 py-2.5 bg-slate-50 border-b border-slate-100">
            <x-heroicon-o-folder class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
            <p class="text-xs font-semibold text-slate-600">{{ $grupo }}</p>
            <span class="ml-auto text-xs text-slate-400">
                {{ count($clases) }} {{ count($clases) === 1 ? 'clase' : 'clases' }}
            </span>
        </div>

        {{-- Filas de clases — sin clase doc-list, el stagger es solo para listas de módulos --}}
        <div class="divide-y divide-slate-50">
            @foreach ($clases as $item)
            <div class="flex items-center gap-3 px-4 py-3
                        transition-[background-color] duration-[120ms] ease
                        hover:bg-slate-50">

                {{-- Tipo: amber para actions (firma del sistema), emerald para queries --}}
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold
                             tracking-wide uppercase flex-shrink-0 leading-none
                    {{ $item['tipo'] === 'action'
                        ? 'bg-amber-50 text-amber-700 border border-amber-100'
                        : 'bg-emerald-50 text-emerald-700 border border-emerald-100' }}">
                    {{ $item['tipo'] }}
                </span>

                <div class="min-w-0 flex-1 flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-semibold text-slate-800">{{ $item['clase'] }}</span>
                    <span class="text-slate-300 select-none">·</span>
                    <span class="text-xs text-slate-500">{{ $item['descripcion'] }}</span>
                </div>

                <code class="text-[10px] font-mono text-slate-400 flex-shrink-0 hidden lg:block whitespace-nowrap">
                    {{ $item['archivo'] }}
                </code>
            </div>
            @endforeach
        </div>

    </div>
    @endforeach

</div>
@endif

</x-filament-panels::page>
