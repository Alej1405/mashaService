<x-filament-widgets::widget>
    <div class="d-card">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">

            {{-- Logo + Saludo + Fecha --}}
            <div class="flex items-center gap-3">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo" class="h-9 w-auto object-contain rounded" style="max-width:120px;">
                @endif
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-lg" style="background:#eef2ff;">
                        @if(now()->hour >= 6 && now()->hour < 12)
                            <x-heroicon-o-sun class="w-4 h-4" style="color:#d97706;" />
                        @elseif(now()->hour >= 12 && now()->hour < 19)
                            <x-heroicon-o-cloud class="w-4 h-4" style="color:#4f46e5;" />
                        @else
                            <x-heroicon-o-moon class="w-4 h-4" style="color:#7c3aed;" />
                        @endif
                    </div>
                    <div>
                        <h1 class="text-sm font-bold leading-tight" style="color:#1e293b;">{{ $saludo }}</h1>
                        <p class="text-[9px] font-semibold uppercase tracking-widest" style="color:#94a3b8;">{{ $fechaActual }}</p>
                    </div>
                </div>
            </div>

            {{-- Selector de periodo --}}
            <div style="display:inline-flex;align-items:center;background:#f1f5f9;border-radius:9px;padding:2px;gap:0;border:1px solid #e2e8f0;">
                @foreach(['hoy' => 'Hoy', 'semana' => 'Semana', 'mes' => 'Mes', 'año' => 'Año'] as $key => $label)
                    <button
                        wire:click="$set('periodo', '{{ $key }}')"
                        style="padding:5px 14px;border-radius:7px;border:none;font-size:0.72rem;font-weight:600;letter-spacing:0.01em;cursor:pointer;transition:all 0.18s ease;white-space:nowrap;
                        {{ $periodo === $key
                            ? 'background:#ffffff;color:#1e293b;box-shadow:0 1px 3px rgba(0,0,0,0.1);'
                            : 'background:transparent;color:#64748b;' }}"
                    >{{ $label }}</button>
                @endforeach
            </div>

            {{-- Acciones rápidas --}}
            @php $panel = filament()->getCurrentPanel()->getPath(); @endphp
            <div class="flex items-center gap-2">
                <a href="/{{ $panel }}/{{ $tenant }}/sales/create"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                    style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;"
                    onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                    <x-heroicon-o-plus class="w-3.5 h-3.5" /> Venta
                </a>
                <a href="/{{ $panel }}/{{ $tenant }}/purchases"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                    style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;"
                    onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                    <x-heroicon-o-shopping-bag class="w-3.5 h-3.5" /> Compra
                </a>
                <div class="w-px h-5 mx-1" style="background:#e2e8f0;"></div>
                <a href="/{{ $panel }}/{{ $tenant }}/estado-resultados"
                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg transition-colors"
                    style="background:#fffbeb;color:#d97706;"
                    title="Informes">
                    <x-heroicon-o-chart-bar class="w-3.5 h-3.5" />
                </a>
                <a href="/{{ $panel }}/{{ $tenant }}/cash-movements"
                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg transition-colors"
                    style="background:#f0fdf4;color:#15803d;"
                    title="Movimientos de caja">
                    <x-heroicon-o-credit-card class="w-3.5 h-3.5" />
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
