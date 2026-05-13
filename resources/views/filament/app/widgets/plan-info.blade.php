<x-filament-widgets::widget>
    <div class="d-card">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <div class="p-1.5 rounded-lg" style="background:#eef2ff;">
                    <x-heroicon-o-credit-card class="w-4 h-4" style="color:#4f46e5;" />
                </div>
                <span class="text-xs font-bold uppercase tracking-widest" style="color:#64748b;">Tu Plan</span>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                  style="background:{{ $badgeBg }};color:{{ $badgeColor }};border:1px solid {{ $badgeColor }}22;">
                {{ $planLabel }}
            </span>
        </div>

        {{-- Features grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
            @foreach($features as $feature)
                <div class="flex items-center gap-2 px-3 py-2 rounded-lg" style="background:#f8fafc;border:1px solid #e2e8f0;">
                    <div class="flex-shrink-0 p-1 rounded-md" style="background:#ecfdf5;">
                        <x-heroicon-o-check-circle class="w-3.5 h-3.5" style="color:#16a34a;" />
                    </div>
                    <span class="text-[11px] font-medium leading-tight" style="color:#475569;">{{ $feature }}</span>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
