<x-filament-widgets::widget>
    <div class="d-card h-full flex flex-col">
        <div class="d-card-header">
            <h3 class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5" style="color:#1e293b;">
                <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5" style="color:#dc2626;" />
                Alertas de Stock
            </h3>
            @if(count($productos) > 0)
                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">{{ count($productos) }}</span>
            @endif
        </div>

        <div class="flex-grow space-y-1 overflow-y-auto pr-1" style="scrollbar-width:thin;scrollbar-color:#e2e8f0 transparent;">
            @forelse($productos as $p)
                @php
                    $badge = match($p['estado']) {
                        'agotado' => ['bg'=>'#fef2f2', 'color'=>'#b91c1c', 'border'=>'#fecaca'],
                        'critico' => ['bg'=>'#fff7ed', 'color'=>'#c2410c', 'border'=>'#fed7aa'],
                        'bajo'    => ['bg'=>'#fffbeb', 'color'=>'#b45309', 'border'=>'#fde68a'],
                        default   => ['bg'=>'#f8fafc', 'color'=>'#64748b', 'border'=>'#e2e8f0'],
                    };
                @endphp
                <div class="flex items-center justify-between px-2.5 py-2 rounded-lg" style="background:#f8fafc;border:1px solid #f1f5f9;">
                    <div class="flex-1 min-w-0 pr-2">
                        <p class="text-[10px] font-semibold truncate" style="color:#1e293b;">{{ $p['name'] }}</p>
                        <p class="text-[9px]" style="color:#94a3b8;">Stock: {{ number_format($p['stock'],0) }} / Mín: {{ number_format($p['min'],0) }}</p>
                    </div>
                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide whitespace-nowrap"
                          style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};border:1px solid {{ $badge['border'] }};">
                        {{ $p['estado'] }}
                    </span>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-6 text-center">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center mb-2" style="background:#f0fdf4;">
                        <x-heroicon-o-check-badge class="w-5 h-5" style="color:#15803d;" />
                    </div>
                    <p class="text-[10px] font-bold uppercase tracking-widest" style="color:#94a3b8;">Inventario Saludable</p>
                </div>
            @endforelse
        </div>

        @if(count($productos) > 0)
            <div class="mt-2 pt-2" style="border-top:1px solid #f1f5f9;">
                <a href="/{{ $panelPath }}/{{ $tenant }}/inventory-items"
                   class="flex items-center justify-center gap-1 text-[9px] font-bold uppercase tracking-widest" style="color:#4f46e5;">
                    Ver inventario <x-heroicon-s-chevron-right class="w-2.5 h-2.5" />
                </a>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
