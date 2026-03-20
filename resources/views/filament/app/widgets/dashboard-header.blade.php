<x-filament-widgets::widget>
    <div class="d-card">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">

            {{-- Logo + Saludo + Fecha --}}
            <div class="flex items-center gap-3">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo" class="h-9 w-auto object-contain rounded" style="max-width:120px;">
                @endif
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-lg" style="background: rgba(99,102,241,0.12);">
                        @if(now()->hour >= 6 && now()->hour < 12)
                            <x-heroicon-o-sun class="w-4 h-4" style="color: #fbbf24;" />
                        @elseif(now()->hour >= 12 && now()->hour < 19)
                            <x-heroicon-o-cloud class="w-4 h-4" style="color: #818cf8;" />
                        @else
                            <x-heroicon-o-moon class="w-4 h-4" style="color: #a78bfa;" />
                        @endif
                    </div>
                    <div class="p-2">
                        <h1 class="text-sm font-bold leading-tight" style="color: #2f4e6e;">{{ $saludo }}</h1>
                        <p class="text-[9px] font-semibold uppercase tracking-widest" style="color: #64748b;">{{ $fechaActual }}</p>
                    </div>
                </div>
            </div>

            {{-- Selector de periodo — estilo segmented control Apple --}}
            <div style="
                display: inline-flex;
                align-items: center;
                background: rgba(120,120,128,0.12);
                border-radius: 9px;
                padding: 2px;
                gap: 0;
            ">
                @foreach(['hoy' => 'Hoy', 'semana' => 'Semana', 'mes' => 'Mes', 'año' => 'Año'] as $key => $label)
                    <button
                        wire:click="$set('periodo', '{{ $key }}')"
                        style="
                            padding: 5px 14px;
                            border-radius: 7px;
                            border: none;
                            font-size: 0.72rem;
                            font-weight: 600;
                            letter-spacing: 0.01em;
                            cursor: pointer;
                            transition: all 0.18s ease;
                            white-space: nowrap;
                            {{ $periodo === $key
                                ? 'background:#ffffff; color:#1c1c1e; box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.08);'
                                : 'background:transparent; color:#6b7280;' }}
                        "
                    >{{ $label }}</button>
                @endforeach
            </div>

            {{-- Acciones rápidas --}}
            @php $panel = filament()->getCurrentPanel()->getPath(); @endphp
            <div class="flex items-center gap-2">
                <a href="/{{ $panel }}/{{ $tenant }}/sales/create"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 transition-colors">
                    <x-heroicon-o-plus class="w-3.5 h-3.5" /> Venta
                </a>
                <a href="/{{ $panel }}/{{ $tenant }}/purchases"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 transition-colors">
                    <x-heroicon-o-shopping-bag class="w-3.5 h-3.5" /> Compra
                </a>
                <div class="w-px h-5 mx-1 bg-slate-200 dark:bg-slate-700"></div>
                <a href="/{{ $panel }}/{{ $tenant }}/estado-resultados"
                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-amber-50 text-amber-500 hover:bg-amber-100 dark:bg-amber-900/20 dark:hover:bg-amber-900/40 transition-colors"
                    title="Informes">
                    <x-heroicon-o-chart-bar class="w-3.5 h-3.5" />
                </a>
                <a href="/{{ $panel }}/{{ $tenant }}/cash-movements"
                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40 transition-colors"
                    title="Movimientos de caja">
                    <x-heroicon-o-credit-card class="w-3.5 h-3.5" />
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
