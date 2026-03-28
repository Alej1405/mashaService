<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Compromisos Financieros — {{ $mesNombre }}</x-slot>
        <x-slot name="description">Costos fijos operativos + servicio de deuda del mes</x-slot>

        {{-- ── Cards de resumen ──────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Costos Fijos Operativos</p>
                <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-gray-100">${{ number_format($costosFijosMensual, 2) }}</p>
                <p class="text-xs text-gray-400 mt-1">equivalente mensual</p>
            </div>

            <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950 p-4">
                <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">Cuotas de Deuda este mes</p>
                <p class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">${{ number_format($cuotasMesActual, 2) }}</p>
                <p class="text-xs text-blue-400 mt-1">servicio de deuda</p>
            </div>

            <div class="rounded-xl border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-950 p-4">
                <p class="text-xs font-medium text-violet-600 dark:text-violet-400 uppercase tracking-wide">Total Mensual Requerido</p>
                <p class="mt-1 text-2xl font-bold text-violet-700 dark:text-violet-300">${{ number_format($totalMensual, 2) }}</p>
                <p class="text-xs text-violet-400 mt-1">operativo + deudas</p>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Saldo Total Deudas</p>
                <p class="mt-1 text-2xl font-bold {{ $saldoTotalDeudas > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    ${{ number_format($saldoTotalDeudas, 2) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">deudas activas y parciales</p>
            </div>

        </div>

        {{-- ── Barra de composición del gasto mensual ────────────────────────────── --}}
        @if($totalMensual > 0)
        <div class="mb-6 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Composición del compromiso mensual</p>
            @php
                $pctOp   = $totalMensual > 0 ? ($costosFijosMensual / $totalMensual) * 100 : 0;
                $pctDebt = $totalMensual > 0 ? ($cuotasMesActual / $totalMensual) * 100 : 0;
            @endphp
            <div class="flex rounded-full overflow-hidden h-4">
                @if($pctOp > 0)
                    <div class="bg-gray-400 dark:bg-gray-500 h-full transition-all" style="width: {{ $pctOp }}%"></div>
                @endif
                @if($pctDebt > 0)
                    <div class="bg-blue-500 h-full transition-all" style="width: {{ $pctDebt }}%"></div>
                @endif
            </div>
            <div class="flex gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                    Operativo {{ number_format($pctOp, 1) }}%
                </span>
                <span class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span>
                    Deudas {{ number_format($pctDebt, 1) }}%
                </span>
            </div>
        </div>
        @endif

        {{-- ── Alerta cuotas morosas ─────────────────────────────────────────────── --}}
        @if($cuotasMorosas->count() > 0)
        <div class="mb-6 rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 p-4">
            <div class="flex items-center gap-2 mb-3">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-rose-600 dark:text-rose-400" />
                <p class="font-semibold text-rose-700 dark:text-rose-300">
                    {{ $cuotasMorosas->count() }} cuota(s) vencida(s) sin pagar — Total: ${{ number_format($totalMoroso, 2) }}
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-rose-600 dark:text-rose-400 border-b border-rose-200 dark:border-rose-800">
                            <th class="pb-2 pr-4 font-semibold">Deuda</th>
                            <th class="pb-2 pr-4 font-semibold">Cuota</th>
                            <th class="pb-2 pr-4 font-semibold">Venció</th>
                            <th class="pb-2 pr-4 font-semibold text-right">Capital</th>
                            <th class="pb-2 pr-4 font-semibold text-right">Interés</th>
                            <th class="pb-2 font-semibold text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-100 dark:divide-rose-900">
                        @foreach($cuotasMorosas as $linea)
                        <tr>
                            <td class="py-2 pr-4 font-medium text-gray-800 dark:text-gray-200">
                                {{ $linea->debt->acreedor ?? $linea->debt->numero }}
                            </td>
                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-400">#{{ $linea->numero_cuota }}</td>
                            <td class="py-2 pr-4 text-rose-600 dark:text-rose-400">
                                {{ \Carbon\Carbon::parse($linea->fecha_vencimiento)->format('d/m/Y') }}
                                <span class="text-xs">({{ \Carbon\Carbon::parse($linea->fecha_vencimiento)->diffForHumans() }})</span>
                            </td>
                            <td class="py-2 pr-4 text-right text-gray-700 dark:text-gray-300">${{ number_format($linea->monto_capital, 2) }}</td>
                            <td class="py-2 pr-4 text-right text-gray-700 dark:text-gray-300">${{ number_format($linea->monto_interes, 2) }}</td>
                            <td class="py-2 text-right font-bold text-rose-700 dark:text-rose-300">${{ number_format($linea->total_cuota, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ── Próximas cuotas (60 días) ─────────────────────────────────────────── --}}
        <div>
            <p class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-3">
                Próximas cuotas (60 días)
                @if($proximasCuotas->count() > 0)
                    — {{ $proximasCuotas->count() }} vencimiento(s), total ${{ number_format($proximasCuotas->sum('total_cuota'), 2) }}
                @endif
            </p>

            @if($proximasCuotas->count() > 0)
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-2 font-semibold">Deuda / Acreedor</th>
                            <th class="px-4 py-2 font-semibold">Cuota</th>
                            <th class="px-4 py-2 font-semibold">Vence</th>
                            <th class="px-4 py-2 font-semibold text-right">Capital</th>
                            <th class="px-4 py-2 font-semibold text-right">Interés</th>
                            <th class="px-4 py-2 font-semibold text-right">Total</th>
                            <th class="px-4 py-2 font-semibold">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($proximasCuotas as $linea)
                        @php
                            $diasRestantes = now()->diffInDays(\Carbon\Carbon::parse($linea->fecha_vencimiento), false);
                            $urgente = $diasRestantes <= 7;
                            $proximo = $diasRestantes <= 15;
                        @endphp
                        <tr class="{{ $urgente ? 'bg-amber-50 dark:bg-amber-950/30' : '' }}">
                            <td class="px-4 py-2 font-medium text-gray-800 dark:text-gray-200">
                                {{ $linea->debt->acreedor ?? '—' }}
                                <span class="text-xs text-gray-400 ml-1">{{ $linea->debt->numero }}</span>
                            </td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400">#{{ $linea->numero_cuota }}</td>
                            <td class="px-4 py-2 {{ $urgente ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-gray-600 dark:text-gray-400' }}">
                                {{ \Carbon\Carbon::parse($linea->fecha_vencimiento)->format('d/m/Y') }}
                                @if($urgente)
                                    <span class="text-xs ml-1">({{ $diasRestantes }}d)</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">${{ number_format($linea->monto_capital, 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">${{ number_format($linea->monto_interes, 2) }}</td>
                            <td class="px-4 py-2 text-right font-bold {{ $urgente ? 'text-amber-700 dark:text-amber-300' : 'text-gray-800 dark:text-gray-100' }}">
                                ${{ number_format($linea->total_cuota, 2) }}
                            </td>
                            <td class="px-4 py-2">
                                @if($urgente)
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Urgente</span>
                                @elseif($proximo)
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Próximo</span>
                                @else
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-right text-sm font-semibold text-gray-600 dark:text-gray-300">Total próximos 60 días:</td>
                            <td class="px-4 py-2 text-right font-bold text-gray-900 dark:text-white">${{ number_format($proximasCuotas->sum('total_cuota'), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No hay cuotas pendientes en los próximos 60 días.</p>
            @endif
        </div>

    </x-filament::section>
</x-filament-widgets::widget>
