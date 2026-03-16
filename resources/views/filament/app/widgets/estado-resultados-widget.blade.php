<x-filament-widgets::widget>
    <div class="space-y-3">

        {{-- Header --}}
        <div class="d-card flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <div>
                <h3 class="text-xs font-bold" style="color:#f1f5f9;">Estado de Resultados</h3>
                <p class="text-[9px] mt-0.5" style="color:#475569;">{{ $fechaDesde }} — {{ $fechaHasta }}</p>
            </div>
            <div class="flex gap-1 p-0.5 rounded-full" style="background:rgba(255,255,255,0.04);border:1px solid rgba(148,163,184,0.1);">
                @foreach(['mes'=>'Mes','trimestre'=>'Trimestre','año'=>'Año'] as $key=>$label)
                    <button wire:click="$set('periodo','{{ $key }}')" class="d-period-btn {{ $periodoActual===$key?'active':'' }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
            <div class="d-card" style="border-top:2px solid #6366f1;">
                <p class="d-metric-lbl">Ingresos Totales</p>
                <p class="d-metric-val mt-0.5">${{ number_format($metrics['ingresos'],0) }}</p>
                @php $vc=$variacionIngresos>=0?'#34d399':'#f87171'; @endphp
                <span class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 rounded-full text-[8px] font-bold"
                      style="background:{{ $variacionIngresos>=0?'rgba(16,185,129,0.12)':'rgba(239,68,68,0.12)' }};color:{{ $vc }};">
                    {{ $variacionIngresos>=0?'↑':'↓' }} {{ abs(round($variacionIngresos,1)) }}%
                </span>
            </div>
            <div class="d-card" style="border-top:2px solid #ef4444;">
                <p class="d-metric-lbl">Costo de Ventas</p>
                <p class="d-metric-val mt-0.5" style="color:#f87171;">${{ number_format($metrics['costos'],0) }}</p>
                <p class="text-[9px] mt-1" style="color:#475569;">{{ $metrics['ingresos']>0?round(($metrics['costos']/$metrics['ingresos'])*100,1):0 }}% sobre ventas</p>
            </div>
            <div class="d-card" style="border-top:2px solid #818cf8;">
                <p class="d-metric-lbl">Utilidad Bruta</p>
                <p class="d-metric-val mt-0.5" style="color:#a5b4fc;">${{ number_format($metrics['utilidadBruta'],0) }}</p>
                <p class="text-[9px] mt-1" style="color:#475569;">Margen: {{ round($salud['margenBruto']['valor'],1) }}%</p>
            </div>
            <div class="d-card" style="border-top:2px solid {{ $metrics['utilidadNeta']>=0?'#10b981':'#ef4444' }};">
                <p class="d-metric-lbl">Utilidad Neta</p>
                <p class="d-metric-val mt-0.5" style="color:{{ $metrics['utilidadNeta']>=0?'#34d399':'#f87171' }};">
                    ${{ number_format($metrics['utilidadNeta'],0) }}
                </p>
                <p class="text-[9px] mt-1" style="color:#475569;">Margen: {{ round($salud['margenNeto']['valor'],1) }}%</p>
            </div>
        </div>

        {{-- Semáforo + Gráfico --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <div class="d-card">
                <h5 class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5 mb-3" style="color:#94a3b8;">
                    <x-heroicon-o-signal class="w-3.5 h-3.5" style="color:#6366f1;" /> Salud Financiera
                </h5>
                <div class="space-y-3">
                    @foreach(['Margen Bruto'=>$salud['margenBruto'],'Margen Neto'=>$salud['margenNeto'],'Carga Fiscal'=>$salud['cargaFiscal'],'Eficiencia Op.'=>$salud['eficienciaOp']] as $label=>$val)
                        @php $barColor=match($val['color']){'success'=>'#10b981','warning'=>'#f59e0b','danger'=>'#ef4444',default=>'#6366f1'}; @endphp
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[10px] font-medium" style="color:#94a3b8;">{{ $label }}</span>
                                <span class="text-[10px] font-bold" style="color:{{ $barColor }};">{{ round($val['valor'],1) }}%</span>
                            </div>
                            <div class="w-full rounded-full h-1" style="background:rgba(255,255,255,0.06);">
                                <div class="h-full rounded-full transition-all duration-500" style="width:{{ min(100,max(0,$val['valor'])) }}%;background:{{ $barColor }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="d-card flex flex-col">
                <h5 class="text-[10px] font-bold uppercase tracking-widest mb-3" style="color:#94a3b8;">Composición de Resultados</h5>
                <div class="relative flex-1" style="min-height:140px;">
                    <canvas id="erChart" wire:ignore></canvas>
                </div>
            </div>
        </div>

        @if(count($interpretacion)>0)
        <div class="d-card">
            <h5 class="text-[9px] font-bold uppercase tracking-widest mb-2" style="color:#475569;">Análisis Automático</h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-1.5">
                @foreach($interpretacion as $msg)
                    <div class="p-2 rounded-lg text-[10px]" style="background:rgba(255,255,255,0.03);border:1px solid rgba(148,163,184,0.08);color:#94a3b8;">{{ $msg }}</div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () { initERChart(); });
        document.addEventListener('livewire:updated', function () {
            const c = Chart.getChart('erChart');
            if (c) { c.data.datasets[0].data=[{{ $metrics['ingresos'] }},{{ $metrics['costos'] }},{{ $metrics['gastosOp'] }},{{ $metrics['utilidadNeta'] }}]; c.update(); }
            else { initERChart(); }
        });

        function initERChart() {
            const el = document.getElementById('erChart');
            if (!el) return;
            const existing = Chart.getChart('erChart');
            if (existing) existing.destroy();
            new Chart(el.getContext('2d'), {
                type:'bar',
                data:{
                    labels:['Ingresos','Costos','Gastos Op.','Util. Neta'],
                    datasets:[{data:[{{ $metrics['ingresos'] }},{{ $metrics['costos'] }},{{ $metrics['gastosOp'] }},{{ $metrics['utilidadNeta'] }}],backgroundColor:['rgba(99,102,241,0.7)','rgba(239,68,68,0.7)','rgba(245,158,11,0.7)','rgba(16,185,129,0.7)'],borderRadius:5,barThickness:22}]
                },
                options:{
                    indexAxis:'y',responsive:true,maintainAspectRatio:false,
                    plugins:{legend:{display:false},tooltip:{backgroundColor:'rgba(15,23,42,0.95)',titleColor:'#f1f5f9',bodyColor:'#94a3b8',callbacks:{label:(c)=>' $'+c.raw.toLocaleString('en-US',{minimumFractionDigits:2})}}},
                    scales:{x:{display:false},y:{grid:{display:false},border:{display:false},ticks:{font:{size:9,weight:'600'},color:'#64748b'}}}
                }
            });
        }
    </script>
</x-filament-widgets::widget>
