<x-filament-widgets::widget>
    <div class="d-card h-full">
        <div class="d-card-header">
            <h3 class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5" style="color:#f1f5f9;">
                <x-heroicon-o-presentation-chart-bar class="w-3.5 h-3.5" style="color:#6366f1;" />
                Ventas vs Compras
            </h3>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full" style="background:#6366f1;"></span><span class="text-[8px] font-bold uppercase" style="color:#64748b;">Ventas</span></div>
                <div class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full" style="background:#f59e0b;"></span><span class="text-[8px] font-bold uppercase" style="color:#64748b;">Compras</span></div>
            </div>
        </div>

        <div class="relative w-full" style="height:160px;">
            <canvas id="vcChart" wire:ignore></canvas>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () { initVCChart(); });

        function initVCChart() {
            const ctx = document.getElementById('vcChart');
            if (!ctx) return;
            const existing = Chart.getChart('vcChart');
            if (existing) existing.destroy();

            new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @js($labels),
                    datasets: [
                        { label:'Ventas', data:@js($ventas), backgroundColor:'rgba(99,102,241,0.7)', borderRadius:4, barPercentage:0.65 },
                        { label:'Compras', data:@js($compras), backgroundColor:'rgba(245,158,11,0.6)', borderRadius:4, barPercentage:0.65 }
                    ]
                },
                options: {
                    responsive:true, maintainAspectRatio:false,
                    plugins: {
                        legend:{display:false},
                        tooltip:{backgroundColor:'rgba(15,23,42,0.95)',titleColor:'#f1f5f9',bodyColor:'#94a3b8',padding:8,titleFont:{size:10,weight:'bold'},bodyFont:{size:9},callbacks:{label:(c)=>' '+c.dataset.label+': $'+c.raw.toLocaleString('en-US',{minimumFractionDigits:2})}}
                    },
                    scales: {
                        y:{beginAtZero:true,grid:{color:'rgba(148,163,184,0.06)'},border:{display:false},ticks:{font:{size:8,weight:'700'},color:'#64748b',callback:(v)=>'$'+v.toLocaleString()}},
                        x:{grid:{display:false},border:{display:false},ticks:{font:{size:8,weight:'700'},color:'#64748b'}}
                    }
                }
            });
        }
    </script>
</x-filament-widgets::widget>
