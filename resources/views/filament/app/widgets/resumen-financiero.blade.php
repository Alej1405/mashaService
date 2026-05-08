<x-filament-widgets::widget>
    <div class="space-y-2">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">

            <div class="d-card" style="border-left:3px solid #4f46e5;">
                <p class="d-metric-lbl">Ingresos</p>
                <p class="d-metric-val mt-0.5">${{ number_format($actual['ingresos'], 0) }}</p>
                @php $varColor = $varIngresos >= 0 ? '#15803d' : '#dc2626'; @endphp
                <p class="text-[9px] font-bold mt-1" style="color:{{ $varColor }};">
                    {{ $varIngresos >= 0 ? '+' : '' }}{{ round($varIngresos, 1) }}% vs anterior
                </p>
            </div>

            <div class="d-card" style="border-left:3px solid #d97706;">
                <p class="d-metric-lbl">Costos y Gastos</p>
                <p class="d-metric-val mt-0.5" style="color:#92400e;">${{ number_format($actual['costosGastos'], 0) }}</p>
                <p class="text-[9px] font-semibold mt-1" style="color:#94a3b8;">
                    {{ $actual['ingresos'] > 0 ? round(($actual['costosGastos'] / $actual['ingresos']) * 100, 1) : 0 }}% de ingresos
                </p>
            </div>

            <div class="d-card" style="border-left:3px solid #15803d;">
                <p class="d-metric-lbl">Utilidad Neta</p>
                <p class="d-metric-val mt-0.5" style="color:{{ $actual['utilNeta'] >= 0 ? '#14532d' : '#991b1b' }};">
                    ${{ number_format($actual['utilNeta'], 0) }}
                </p>
                <p class="text-[9px] font-semibold mt-1" style="color:#94a3b8;">
                    Margen: {{ $actual['ingresos'] > 0 ? round(($actual['utilNeta'] / $actual['ingresos']) * 100, 1) : 0 }}%
                </p>
            </div>

            <div class="d-card" style="border-left:3px solid #94a3b8;">
                <p class="d-metric-lbl">Impuestos Est.</p>
                <p class="d-metric-val mt-0.5" style="color:#475569;">${{ number_format($actual['impuestos'], 0) }}</p>
                <p class="text-[9px] font-semibold mt-1" style="color:#94a3b8;">15% Trab. + 25% IR</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-1.5">
            @foreach($semáforos as $s)
                @php
                    $dotColor = match($s['color']) { 'success'=>'#15803d', 'warning'=>'#d97706', 'danger'=>'#dc2626', default=>'#4f46e5' };
                    $bgColor  = match($s['color']) { 'success'=>'#f0fdf4', 'warning'=>'#fffbeb', 'danger'=>'#fef2f2', default=>'#eef2ff' };
                    $brColor  = match($s['color']) { 'success'=>'#bbf7d0', 'warning'=>'#fde68a', 'danger'=>'#fecaca', default=>'#c7d2fe' };
                @endphp
                <div class="d-pill" style="background:{{ $bgColor }};border:1px solid {{ $brColor }};color:{{ $dotColor }};">
                    <div class="w-1.5 h-1.5 rounded-full" style="background:{{ $dotColor }};"></div>
                    {{ $s['label'] }} {{ round($s['valor'], 1) }}% — {{ $s['msg'] }}
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
