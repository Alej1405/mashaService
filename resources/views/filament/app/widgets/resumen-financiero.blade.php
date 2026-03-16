<x-filament-widgets::widget>
    <div class="space-y-2">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">

            <div class="d-card" style="border-left: 3px solid #6366f1;">
                <p class="d-metric-lbl">Ingresos</p>
                <p class="d-metric-val mt-0.5">${{ number_format($actual['ingresos'], 0) }}</p>
                @php $varColor = $varIngresos >= 0 ? '#34d399' : '#f87171'; @endphp
                <p class="text-[9px] font-bold mt-1" style="color:{{ $varColor }};">
                    {{ $varIngresos >= 0 ? '+' : '' }}{{ round($varIngresos, 1) }}% vs anterior
                </p>
            </div>

            <div class="d-card" style="border-left: 3px solid #f59e0b;">
                <p class="d-metric-lbl">Costos y Gastos</p>
                <p class="d-metric-val mt-0.5" style="color:#fbbf24;">${{ number_format($actual['costosGastos'], 0) }}</p>
                <p class="text-[9px] font-bold mt-1" style="color:#64748b;">
                    {{ $actual['ingresos'] > 0 ? round(($actual['costosGastos'] / $actual['ingresos']) * 100, 1) : 0 }}% de ingresos
                </p>
            </div>

            <div class="d-card" style="border-left: 3px solid #10b981;">
                <p class="d-metric-lbl">Utilidad Neta</p>
                <p class="d-metric-val mt-0.5" style="color:#34d399;">${{ number_format($actual['utilNeta'], 0) }}</p>
                <p class="text-[9px] font-bold mt-1" style="color:#64748b;">
                    Margen: {{ $actual['ingresos'] > 0 ? round(($actual['utilNeta'] / $actual['ingresos']) * 100, 1) : 0 }}%
                </p>
            </div>

            <div class="d-card" style="border-left: 3px solid #64748b;">
                <p class="d-metric-lbl">Impuestos Est.</p>
                <p class="d-metric-val mt-0.5" style="color:#94a3b8;">${{ number_format($actual['impuestos'], 0) }}</p>
                <p class="text-[9px] font-bold mt-1" style="color:#64748b;">15% Trab. + 25% IR</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-1.5">
            @foreach($semáforos as $s)
                @php
                    $dotColor = match($s['color']) { 'success'=>'#10b981','warning'=>'#f59e0b','danger'=>'#ef4444',default=>'#6366f1' };
                    $bgColor  = match($s['color']) { 'success'=>'rgba(16,185,129,0.1)','warning'=>'rgba(245,158,11,0.1)','danger'=>'rgba(239,68,68,0.1)',default=>'rgba(99,102,241,0.1)' };
                    $brColor  = match($s['color']) { 'success'=>'rgba(16,185,129,0.2)','warning'=>'rgba(245,158,11,0.2)','danger'=>'rgba(239,68,68,0.2)',default=>'rgba(99,102,241,0.2)' };
                @endphp
                <div class="d-pill" style="background:{{ $bgColor }};border:1px solid {{ $brColor }};color:{{ $dotColor }};">
                    <div class="w-1.5 h-1.5 rounded-full" style="background:{{ $dotColor }};"></div>
                    {{ $s['label'] }} {{ round($s['valor'], 1) }}%
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
