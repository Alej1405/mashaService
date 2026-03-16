<x-filament-widgets::widget>
    <div class="d-card">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">

            {{-- Saludo + Fecha --}}
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
                <div>
                    <h1 class="text-sm font-bold leading-tight" style="color: #f1f5f9;">{{ $saludo }}</h1>
                    <p class="text-[9px] font-semibold uppercase tracking-widest" style="color: #64748b;">{{ $fechaActual }}</p>
                </div>
            </div>

            {{-- Selector de periodo --}}
            <div class="flex items-center gap-1 p-1 rounded-full" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(148,163,184,0.1);">
                @foreach(['hoy' => 'Hoy', 'semana' => 'Sem', 'mes' => 'Mes', 'año' => 'Año'] as $key => $label)
                    <button
                        wire:click="$set('periodo', '{{ $key }}')"
                        class="d-period-btn {{ $periodo === $key ? 'active' : '' }}"
                    >{{ $label }}</button>
                @endforeach
            </div>

            {{-- Acciones rápidas --}}
            <div class="flex items-center gap-2">
                <a href="/app/{{ $tenant }}/sales/create" class="d-action-primary">
                    <x-heroicon-o-plus class="w-3.5 h-3.5" /> Venta
                </a>
                <a href="/app/{{ $tenant }}/purchases/create" class="d-action-secondary">
                    <x-heroicon-o-shopping-bag class="w-3.5 h-3.5" /> Compra
                </a>
                <div class="w-px h-5 mx-1" style="background: rgba(148,163,184,0.15);"></div>
                <a href="/app/{{ $tenant }}/reports/estado-resultados"
                   class="d-action-icon" title="Informes"
                   style="background: rgba(245,158,11,0.1); color: #fbbf24;">
                    <x-heroicon-o-chart-bar class="w-3.5 h-3.5" />
                </a>
                <a href="/app/{{ $tenant }}/cash-movements"
                   class="d-action-icon" title="Registrar Pago"
                   style="background: rgba(16,185,129,0.1); color: #34d399;">
                    <x-heroicon-o-credit-card class="w-3.5 h-3.5" />
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
