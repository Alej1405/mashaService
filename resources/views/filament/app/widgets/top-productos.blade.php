<x-filament-widgets::widget>
    <div class="d-card h-full">
        <div class="d-card-header">
            <h3 class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5" style="color:#1e293b;">
                <x-heroicon-o-trophy class="w-3.5 h-3.5" style="color:#d97706;" />
                Top Ventas
            </h3>
            <span class="text-[9px] font-semibold" style="color:#94a3b8;">Por Unidades</span>
        </div>

        <div class="space-y-2.5 mt-1">
            @forelse($productos as $index => $p)
                @php
                    $barColor = match($index) {
                        0 => '#4f46e5',
                        1 => '#0369a1',
                        2 => '#15803d',
                        3 => '#d97706',
                        default => '#7c3aed',
                    };
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-1 px-0.5">
                        <div class="flex items-center gap-1.5 min-w-0 pr-2">
                            <span class="text-[10px] w-4 text-center flex-shrink-0 font-bold" style="color:#94a3b8;">
                                @if($index===0)🥇@elseif($index===1)🥈@elseif($index===2)🥉
                                @else<span>#{{ $index+1 }}</span>@endif
                            </span>
                            <span class="text-[10px] font-semibold truncate" style="color:#1e293b;">{{ $p['name'] }}</span>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-[10px] font-bold" style="color:#1e293b;">${{ number_format($p['revenue'],0) }}</p>
                            <p class="text-[9px]" style="color:#94a3b8;">{{ number_format($p['qty'],0) }} uds</p>
                        </div>
                    </div>
                    <div class="w-full rounded-full h-1.5" style="background:#f1f5f9;">
                        <div class="h-full rounded-full transition-all duration-700"
                             style="width:{{ $p['percent'] }}%;background:{{ $barColor }};"></div>
                    </div>
                </div>
            @empty
                <div class="py-6 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-widest" style="color:#94a3b8;">Sin ventas en este periodo</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-widgets::widget>
