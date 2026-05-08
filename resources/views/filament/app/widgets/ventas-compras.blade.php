<x-filament-widgets::widget>
    <div class="d-card h-full">
        <div class="d-card-header">
            <h3 class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1.5" style="color:#1e293b;">
                <x-heroicon-o-presentation-chart-bar class="w-3.5 h-3.5" style="color:#4f46e5;" />
                Ventas vs Compras
            </h3>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full" style="background:#4f46e5;"></span>
                    <span class="text-[9px] font-semibold" style="color:#475569;">Ventas</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full" style="background:#d97706;"></span>
                    <span class="text-[9px] font-semibold" style="color:#475569;">Compras</span>
                </div>
            </div>
        </div>

        <div class="relative w-full" style="height:160px;">
            <canvas id="vcChart" wire:ignore></canvas>
        </div>
    </div>

    <script>
        (function () {
            const LABELS  = @js($labels);
            const VENTAS  = @js($ventas);
            const COMPRAS = @js($compras);

            function buildVCChart() {
                const ctx = document.getElementById('vcChart');
                if (!ctx) return;
                const existing = Chart.getChart('vcChart');
                if (existing) existing.destroy();

                new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: LABELS,
                        datasets: [
                            { label:'Ventas',  data:VENTAS,  backgroundColor:'#4f46e5', borderRadius:4, barPercentage:0.65 },
                            { label:'Compras', data:COMPRAS, backgroundColor:'#d97706', borderRadius:4, barPercentage:0.65 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1e293b', titleColor: '#f8fafc', bodyColor: '#cbd5e1',
                                padding: 8, titleFont:{size:10,weight:'bold'}, bodyFont:{size:9},
                                callbacks: { label: (c) => ' ' + c.dataset.label + ': $' + c.raw.toLocaleString('en-US', {minimumFractionDigits:2}) }
                            }
                        },
                        scales: {
                            y: { beginAtZero:true, grid:{color:'#f1f5f9'}, border:{display:false}, ticks:{font:{size:8,weight:'600'},color:'#94a3b8',callback:(v)=>'$'+v.toLocaleString()} },
                            x: { grid:{display:false}, border:{display:false}, ticks:{font:{size:8,weight:'600'},color:'#94a3b8'} }
                        }
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', buildVCChart);
            document.addEventListener('livewire:navigated', buildVCChart);
        })();
    </script>
</x-filament-widgets::widget>
