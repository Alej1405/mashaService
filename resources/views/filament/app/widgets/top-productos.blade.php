<x-filament-widgets::widget>
    <div class="d-card h-full">
        <div class="d-card-header">
            <h3 class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5" style="color:#f1f5f9;">
                <x-heroicon-o-trophy class="w-3.5 h-3.5" style="color:#fbbf24;" />
                Top Ventas
            </h3>
            <span class="text-[8px] font-bold uppercase tracking-widest" style="color:#475569;">Por Unidades</span>
        </div>

        <div class="space-y-3 mt-1">
            @forelse($productos as $index => $p)
                <div>
                    <div class="flex items-center justify-between mb-1 px-0.5">
                        <div class="flex items-center gap-1.5 min-w-0 pr-2">
                            <span class="text-[10px] w-4 text-center flex-shrink-0">
                                @if($index===0)🥇@elseif($index===1)🥈@elseif($index===2)🥉
                                @else<span class="text-[9px] font-bold" style="color:#475569;">#{{ $index+1 }}</span>@endif
                            </span>
                            <span class="text-[10px] font-semibold truncate" style="color:#cbd5e1;">{{ $p['name'] }}</span>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-[10px] font-bold" style="color:#f1f5f9;">${{ number_format($p['revenue'],0) }}</p>
                            <p class="text-[8px]" style="color:#475569;">{{ number_format($p['qty'],0) }} uds</p>
                        </div>
                    </div>
                    <div class="w-full rounded-full h-1" style="background:rgba(255,255,255,0.05);">
                        <div class="h-full rounded-full transition-all duration-700" style="width:{{ $p['percent'] }}%;background:linear-gradient(90deg,#6366f1,#8b5cf6);"></div>
                    </div>
                </div>
            @empty
                <div class="py-6 text-center">
                    <p class="text-[10px] font-bold uppercase tracking-widest" style="color:#475569;">Sin ventas en este periodo</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-widgets::widget>
