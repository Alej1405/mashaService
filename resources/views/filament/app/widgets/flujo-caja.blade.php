<x-filament-widgets::widget>
    <div class="d-card">
        <div class="d-card-header !items-end">
            <div>
                <h3 class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5 mb-0.5" style="color:#f1f5f9;">
                    <x-heroicon-o-presentation-chart-line class="w-3.5 h-3.5" style="color:#34d399;" />
                    Flujo de Caja Acumulado
                </h3>
                <p class="text-[8px] font-bold uppercase tracking-widest" style="color:#475569;">Tendencia de Liquidez</p>
            </div>

            <div class="flex flex-wrap gap-1.5 justify-end">
                @php $totalIng=end($ingresos)?:0; $totalEgr=end($egresos)?:0; $totalNet=end($neto)?:0; @endphp
                <div class="px-2 py-1 rounded-lg" style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.15);">
                    <span class="text-[7px] font-black uppercase block" style="color:#34d399;">↑ Ing.</span>
                    <span class="text-[10px] font-bold" style="color:#6ee7b7;">${{ number_format($totalIng,0) }}</span>
                </div>
                <div class="px-2 py-1 rounded-lg" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.15);">
                    <span class="text-[7px] font-black uppercase block" style="color:#f87171;">↓ Egr.</span>
                    <span class="text-[10px] font-bold" style="color:#fca5a5;">${{ number_format($totalEgr,0) }}</span>
                </div>
                <div class="px-2 py-1 rounded-lg" style="background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.15);">
                    <span class="text-[7px] font-black uppercase block" style="color:#818cf8;">= Saldo</span>
                    <span class="text-[10px] font-bold" style="color:#a5b4fc;">${{ number_format($totalNet,0) }}</span>
                </div>
            </div>
        </div>

        <div class="relative mt-1" style="height:130px;">
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
            const gIng = ctx.createLinearGradient(0,0,0,130);
            gIng.addColorStop(0,'rgba(16,185,129,0.12)'); gIng.addColorStop(1,'rgba(16,185,129,0)');
            const gEgr = ctx.createLinearGradient(0,0,0,130);
            gEgr.addColorStop(0,'rgba(239,68,68,0.1)'); gEgr.addColorStop(1,'rgba(239,68,68,0)');

            new Chart(ctx, {
                type:'line',
                data:{
                    labels:@js($labels),
                    datasets:[
                        {label:'Saldo Neto',data:@js($neto),borderColor:'#6366f1',borderWidth:2,borderDash:[4,4],pointRadius:0,fill:false,tension:0.4},
                        {label:'Ingresos',data:@js($ingresos),borderColor:'#10b981',borderWidth:2,backgroundColor:gIng,fill:true,pointRadius:0,tension:0.4},
                        {label:'Egresos',data:@js($egresos),borderColor:'#ef4444',borderWidth:1.5,backgroundColor:gEgr,fill:true,pointRadius:0,tension:0.4}
                    ]
                },
                options:{
                    responsive:true,maintainAspectRatio:false,
                    interaction:{mode:'index',intersect:false},
                    plugins:{legend:{display:false},tooltip:{backgroundColor:'rgba(15,23,42,0.95)',titleColor:'#f1f5f9',bodyColor:'#94a3b8',padding:8,titleFont:{size:10,weight:'bold'},bodyFont:{size:9},callbacks:{label:(c)=>' '+c.dataset.label+': $'+c.raw.toLocaleString('en-US',{minimumFractionDigits:2})}}},
                    scales:{
                        y:{beginAtZero:true,grid:{color:'rgba(148,163,184,0.06)'},border:{display:false},ticks:{font:{size:7,weight:'700'},color:'#64748b',maxTicksLimit:4,callback:(v)=>'$'+v.toLocaleString()}},
                        x:{grid:{display:false},border:{display:false},ticks:{font:{size:7,weight:'700'},color:'#64748b',maxTicksLimit:8}}
                    }
                }
            });
        }
    </script>
</x-filament-widgets::widget>
