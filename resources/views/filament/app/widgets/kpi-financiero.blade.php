<x-filament-widgets::widget>
    <x-filament::section>

        {{-- Cuatro KPIs --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- Por cobrar --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Por cobrar</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    ${{ number_format($porCobrar, 2) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ $porCobrarQty }} {{ $porCobrarQty === 1 ? 'factura pendiente' : 'facturas pendientes' }}
                </p>
                <div class="mt-3 h-1 rounded-full bg-blue-100 dark:bg-blue-900">
                    <div class="h-1 rounded-full bg-blue-500" style="width: 100%"></div>
                </div>
            </div>

            {{-- Por pagar --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Por pagar</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    ${{ number_format($porPagar, 2) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ $porPagarQty }} {{ $porPagarQty === 1 ? 'factura pendiente' : 'facturas pendientes' }}
                </p>
                <div class="mt-3 h-1 rounded-full bg-rose-100 dark:bg-rose-900">
                    <div class="h-1 rounded-full bg-rose-500" style="width: 100%"></div>
                </div>
            </div>

            {{-- En banco --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">En banco</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    ${{ number_format($totalBancos, 2) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ $bancosQty }} {{ $bancosQty === 1 ? 'cuenta activa' : 'cuentas activas' }}
                </p>
                <div class="mt-3 h-1 rounded-full bg-emerald-100 dark:bg-emerald-900">
                    <div class="h-1 rounded-full bg-emerald-500" style="width: 100%"></div>
                </div>
            </div>

            {{-- En efectivo --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Efectivo</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    ${{ number_format($totalEfectivo, 2) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ $cajasQty }} {{ $cajasQty === 1 ? 'caja activa' : 'cajas activas' }}
                </p>
                <div class="mt-3 h-1 rounded-full bg-amber-100 dark:bg-amber-900">
                    <div class="h-1 rounded-full bg-amber-500" style="width: 100%"></div>
                </div>
            </div>

        </div>

        {{-- Índice de liquidez --}}
        <div class="mt-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            @php
                if ($liquidez === null) {
                    $liqTexto      = 'Sin obligaciones registradas';
                    $liqTextClass  = 'text-gray-500 dark:text-gray-400';
                    $liqBarClass   = 'bg-gray-400';
                } elseif ($liquidez >= 2) {
                    $liqTexto      = 'Posición sólida';
                    $liqTextClass  = 'text-emerald-600 dark:text-emerald-400';
                    $liqBarClass   = 'bg-emerald-500';
                } elseif ($liquidez >= 1) {
                    $liqTexto      = 'Posición ajustada';
                    $liqTextClass  = 'text-amber-600 dark:text-amber-400';
                    $liqBarClass   = 'bg-amber-500';
                } else {
                    $liqTexto      = 'Atención: liquidez crítica';
                    $liqTextClass  = 'text-rose-600 dark:text-rose-400';
                    $liqBarClass   = 'bg-rose-500';
                }
                $barWidth = $liquidez !== null ? min(100, ($liquidez / 3) * 100) : 0;
            @endphp

            <div class="flex items-center gap-6">
                <div class="shrink-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Índice de liquidez</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        @if($liquidez !== null)
                            {{ number_format($liquidez, 1) }}x
                        @else
                            —
                        @endif
                    </p>
                </div>

                <div class="flex-1">
                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                        <span>Activos líquidos: ${{ number_format($activosLiquidos, 2) }}</span>
                        <span class="font-semibold {{ $liqTextClass }}">{{ $liqTexto }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800">
                        <div class="h-2 rounded-full {{ $liqBarClass }} transition-all duration-500"
                             style="width: {{ $barWidth }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        (Banco + Efectivo) ÷ Por pagar
                    </p>
                </div>
            </div>
        </div>

        {{-- Alerta contable --}}
        @if($alertaContable > 0)
            <div class="mt-4 flex items-center gap-3 bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" />
                <div>
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                        {{ $alertaContable }} transacción{{ $alertaContable !== 1 ? 'es' : '' }} con error contable
                    </p>
                    <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
                        Hay registros confirmados sin asiento generado. Revisa Asientos Contables o ejecuta
                        <code class="font-mono bg-amber-100 dark:bg-amber-900 px-1 rounded">php artisan accounting:check-maps --fix</code>
                    </p>
                </div>
            </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>
