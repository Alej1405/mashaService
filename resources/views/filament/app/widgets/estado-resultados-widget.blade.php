<x-filament-widgets::widget>
    <div class="space-y-2">

        {{-- Header --}}
        <div class="d-card flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <div>
                <h3 class="text-xs font-bold" style="color:#1e293b;">Estado de Resultados</h3>
                <p class="text-[9px] mt-0.5" style="color:#94a3b8;">{{ $fechaDesde }} — {{ $fechaHasta }}</p>
            </div>
            <div class="flex gap-0.5 p-0.5 rounded-lg" style="background:#f1f5f9;border:1px solid #e2e8f0;">
                @foreach(['mes'=>'Mes','trimestre'=>'Trim.','año'=>'Año'] as $key=>$label)
                    <button wire:click="$set('periodo','{{ $key }}')" class="d-period-btn {{ $periodoActual===$key?'active':'' }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
            <div class="d-card" style="border-top:2px solid #4f46e5;">
                <p class="d-metric-lbl">Ingresos Totales</p>
                <p class="d-metric-val mt-0.5">${{ number_format($metrics['ingresos'],0) }}</p>
                @php $vc = $variacionIngresos >= 0 ? '#15803d' : '#dc2626'; @endphp
                <span class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 rounded-full text-[9px] font-bold"
                      style="background:{{ $variacionIngresos>=0?'#f0fdf4':'#fef2f2' }};color:{{ $vc }};">
                    {{ $variacionIngresos>=0?'↑':'↓' }} {{ abs(round($variacionIngresos,1)) }}%
                </span>
            </div>
            <div class="d-card" style="border-top:2px solid #dc2626;">
                <p class="d-metric-lbl">Costo de Ventas</p>
                <p class="d-metric-val mt-0.5" style="color:#b91c1c;">${{ number_format($metrics['costos'],0) }}</p>
                <p class="text-[9px] mt-1" style="color:#94a3b8;">{{ $metrics['ingresos']>0?round(($metrics['costos']/$metrics['ingresos'])*100,1):0 }}% sobre ventas</p>
            </div>
            <div class="d-card" style="border-top:2px solid #4338ca;">
                <p class="d-metric-lbl">Utilidad Bruta</p>
                <p class="d-metric-val mt-0.5" style="color:#312e81;">${{ number_format($metrics['utilidadBruta'],0) }}</p>
                <p class="text-[9px] mt-1" style="color:#94a3b8;">Margen: {{ round($salud['margenBruto']['valor'],1) }}%</p>
            </div>
            <div class="d-card" style="border-top:2px solid {{ $metrics['utilidadNeta']>=0?'#15803d':'#dc2626' }};">
                <p class="d-metric-lbl">Utilidad Neta</p>
                <p class="d-metric-val mt-0.5" style="color:{{ $metrics['utilidadNeta']>=0?'#14532d':'#991b1b' }};">
                    ${{ number_format($metrics['utilidadNeta'],0) }}
                </p>
                <p class="text-[9px] mt-1" style="color:#94a3b8;">Margen: {{ round($salud['margenNeto']['valor'],1) }}%</p>
            </div>
        </div>

        {{-- Salud + Gráfico --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-2">
            <div class="d-card">
                <h5 class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5 mb-3" style="color:#475569;">
                    <x-heroicon-o-signal class="w-3.5 h-3.5" style="color:#4f46e5;" /> Salud Financiera
                </h5>
                <div class="space-y-2.5">
                    @foreach(['Margen Bruto'=>$salud['margenBruto'],'Margen Neto'=>$salud['margenNeto'],'Carga Fiscal'=>$salud['cargaFiscal'],'Eficiencia Op.'=>$salud['eficienciaOp']] as $label=>$val)
                        @php $barColor = match($val['color']) { 'success'=>'#15803d', 'warning'=>'#d97706', 'danger'=>'#dc2626', default=>'#4f46e5' }; @endphp
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[10px] font-medium" style="color:#475569;">{{ $label }}</span>
                                <span class="text-[10px] font-bold" style="color:{{ $barColor }};">{{ round($val['valor'],1) }}%</span>
                            </div>
                            <div class="w-full rounded-full h-1.5" style="background:#f1f5f9;">
                                <div class="h-full rounded-full transition-all duration-500"
                                     style="width:{{ min(100,max(0,$val['valor'])) }}%;background:{{ $barColor }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="d-card flex flex-col">
                <h5 class="text-[10px] font-bold uppercase tracking-widest mb-3" style="color:#475569;">Composición de Resultados</h5>
                <div class="relative flex-1" style="min-height:140px;">
                    <canvas id="erChart" wire:ignore></canvas>
                </div>
            </div>
        </div>

        @if(count($interpretacion)>0)
        <div class="d-card">
            <h5 class="text-[9px] font-bold uppercase tracking-widest mb-2" style="color:#94a3b8;">Análisis Automático</h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-1.5">
                @foreach($interpretacion as $msg)
                    <div class="p-2.5 rounded-lg text-[10px]" style="background:#f8fafc;border:1px solid #e2e8f0;color:#475569;">{{ $msg }}</div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () { initERChart(); });
        document.addEventListener('livewire:updated', function () {
            const c = Chart.getChart('erChart');
            if (c) {
                c.data.datasets[0].data = [{{ $metrics['ingresos'] }},{{ $metrics['costos'] }},{{ $metrics['gastosOp'] }},{{ $metrics['utilidadNeta'] }}];
                c.update();
            } else { initERChart(); }
        });

        function initERChart() {
            const el = document.getElementById('erChart');
            if (!el) return;
            const existing = Chart.getChart('erChart');
            if (existing) existing.destroy();
            new Chart(el.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Ingresos', 'Costos', 'Gastos Op.', 'Util. Neta'],
                    datasets: [{
                        data: [{{ $metrics['ingresos'] }}, {{ $metrics['costos'] }}, {{ $metrics['gastosOp'] }}, {{ $metrics['utilidadNeta'] }}],
                        backgroundColor: ['#4f46e5', '#dc2626', '#d97706', '{{ $metrics['utilidadNeta'] >= 0 ? '#15803d' : '#b91c1c' }}'],
                        borderRadius: 5,
                        barThickness: 22
                    }]
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display:false },
                        tooltip: {
                            backgroundColor:'#1e293b', titleColor:'#f8fafc', bodyColor:'#cbd5e1',
                            callbacks: { label:(c)=>' $'+c.raw.toLocaleString('en-US',{minimumFractionDigits:2}) }
                        }
                    },
                    scales: {
                        x: { display:false },
                        y: { grid:{display:false}, border:{display:false}, ticks:{font:{size:9,weight:'600'},color:'#64748b'} }
                    }
                }
            });
        }
    </script>
</x-filament-widgets::widget>
