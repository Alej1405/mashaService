<x-filament-widgets::widget>
    <div class="d-card">
        <div class="d-card-header !items-end">
            <div>
                <h3 class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5 mb-0.5" style="color:#1e293b;">
                    <x-heroicon-o-presentation-chart-line class="w-3.5 h-3.5" style="color:#15803d;" />
                    Flujo de Caja Acumulado
                </h3>
                <p class="text-[9px] font-semibold" style="color:#94a3b8;">Tendencia de liquidez del período</p>
            </div>

            <div class="flex flex-wrap gap-1.5 justify-end">
                @php $totalIng=end($ingresos)?:0; $totalEgr=end($egresos)?:0; $totalNet=end($neto)?:0; @endphp
                <div class="px-2.5 py-1.5 rounded-lg" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                    <span class="text-[8px] font-bold uppercase block" style="color:#15803d;">↑ Ingresos</span>
                    <span class="text-[10px] font-bold" style="color:#14532d;">${{ number_format($totalIng,0) }}</span>
                </div>
                <div class="px-2.5 py-1.5 rounded-lg" style="background:#fef2f2;border:1px solid #fecaca;">
                    <span class="text-[8px] font-bold uppercase block" style="color:#dc2626;">↓ Egresos</span>
                    <span class="text-[10px] font-bold" style="color:#991b1b;">${{ number_format($totalEgr,0) }}</span>
                </div>
                <div class="px-2.5 py-1.5 rounded-lg" style="background:{{ $totalNet >= 0 ? '#eef2ff' : '#fef2f2' }};border:1px solid {{ $totalNet >= 0 ? '#c7d2fe' : '#fecaca' }};">
                    <span class="text-[8px] font-bold uppercase block" style="color:{{ $totalNet >= 0 ? '#4338ca' : '#dc2626' }};">= Saldo</span>
                    <span class="text-[10px] font-bold" style="color:{{ $totalNet >= 0 ? '#312e81' : '#991b1b' }};">${{ number_format($totalNet,0) }}</span>
                </div>
            </div>
        </div>

        <div class="relative mt-1" style="height:140px;">
            <canvas id="fcChart" wire:ignore></canvas>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () { initFlowChart(); });
        document.addEventListener('dashboard-periodo-updated', function () { setTimeout(initFlowChart, 400); });

        function initFlowChart() {
            const el = document.getElementById('fcChart');
            if (!el) return;
            const existing = Chart.getChart('fcChart');
            if (existing) existing.destroy();

            const ctx = el.getContext('2d');
            const gIng = ctx.createLinearGradient(0, 0, 0, 140);
            gIng.addColorStop(0, 'rgba(21,128,61,0.15)');
            gIng.addColorStop(1, 'rgba(21,128,61,0)');
            const gEgr = ctx.createLinearGradient(0, 0, 0, 140);
            gEgr.addColorStop(0, 'rgba(185,28,28,0.12)');
            gEgr.addColorStop(1, 'rgba(185,28,28,0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @js($labels),
                    datasets: [
                        { label:'Saldo Neto', data:@js($neto),     borderColor:'#4f46e5', borderWidth:2, borderDash:[5,4], pointRadius:0, fill:false, tension:0.4 },
                        { label:'Ingresos',   data:@js($ingresos), borderColor:'#15803d', borderWidth:2, backgroundColor:gIng, fill:true, pointRadius:0, tension:0.4 },
                        { label:'Egresos',    data:@js($egresos),  borderColor:'#b91c1c', borderWidth:1.5, backgroundColor:gEgr, fill:true, pointRadius:0, tension:0.4 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode:'index', intersect:false },
                    plugins: {
                        legend: { display:false },
                        tooltip: {
                            backgroundColor:'#1e293b', titleColor:'#f8fafc', bodyColor:'#cbd5e1',
                            padding:8, titleFont:{size:10,weight:'bold'}, bodyFont:{size:9},
                            callbacks: { label:(c)=>' '+c.dataset.label+': $'+c.raw.toLocaleString('en-US',{minimumFractionDigits:2}) }
                        }
                    },
                    scales: {
                        y: { beginAtZero:true, grid:{color:'#f1f5f9'}, border:{display:false}, ticks:{font:{size:8,weight:'600'},color:'#94a3b8',maxTicksLimit:4,callback:(v)=>'$'+v.toLocaleString()} },
                        x: { grid:{display:false}, border:{display:false}, ticks:{font:{size:8,weight:'600'},color:'#94a3b8',maxTicksLimit:8} }
                    }
                }
            });
        }
    </script>
</x-filament-widgets::widget>
