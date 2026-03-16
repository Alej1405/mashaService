<x-filament-widgets::widget>
    <div class="d-card h-full flex flex-col">
        <div class="d-card-header">
            <h3 class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5" style="color:#f1f5f9;">
                <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5" style="color:#f87171;" />
                Alertas de Stock
            </h3>
            @if(count($productos) > 0)
                <span class="px-1.5 py-0.5 rounded text-[9px] font-black" style="background:rgba(239,68,68,0.15);color:#f87171;">{{ count($productos) }}</span>
            @endif
        </div>

        <div class="flex-grow space-y-1 overflow-y-auto pr-1" style="scrollbar-width:thin;scrollbar-color:rgba(148,163,184,0.15) transparent;">
            @forelse($productos as $p)
                @php
                    $badge = match($p['estado']) {
                        'agotado'=>['bg'=>'rgba(239,68,68,0.15)','color'=>'#f87171','border'=>'rgba(239,68,68,0.2)'],
                        'critico'=>['bg'=>'rgba(249,115,22,0.15)','color'=>'#fb923c','border'=>'rgba(249,115,22,0.2)'],
                        'bajo'   =>['bg'=>'rgba(245,158,11,0.15)','color'=>'#fbbf24','border'=>'rgba(245,158,11,0.2)'],
                        default  =>['bg'=>'rgba(148,163,184,0.1)','color'=>'#94a3b8','border'=>'rgba(148,163,184,0.15)'],
                    };
                @endphp
                <div class="flex items-center justify-between px-2 py-1.5 rounded-lg" style="background:rgba(255,255,255,0.02);">
                    <div class="flex-1 min-w-0 pr-2">
                        <p class="text-[10px] font-semibold truncate" style="color:#e2e8f0;">{{ $p['name'] }}</p>
                        <p class="text-[8px] font-medium" style="color:#475569;">Stock: {{ number_format($p['stock'],0) }} / Mín: {{ number_format($p['min'],0) }}</p>
                    </div>
                    <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider whitespace-nowrap"
                          style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};border:1px solid {{ $badge['border'] }};">
                        {{ $p['estado'] }}
                    </span>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-6 text-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center mb-2" style="background:rgba(16,185,129,0.1);">
                        <x-heroicon-o-check-badge class="w-4 h-4" style="color:#34d399;" />
                    </div>
                    <p class="text-[10px] font-bold uppercase tracking-widest" style="color:#64748b;">Inventario Saludable</p>
                </div>
            @endforelse
        </div>

        @if(count($productos) > 0)
            <div class="mt-2 pt-2" style="border-top:1px solid rgba(148,163,184,0.08);">
                <a href="/app/{{ $tenant }}/inventory-items" class="flex items-center justify-center gap-1 text-[9px] font-bold uppercase tracking-widest" style="color:#818cf8;">
                    Ver inventario <x-heroicon-s-chevron-right class="w-2.5 h-2.5" />
                </a>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
