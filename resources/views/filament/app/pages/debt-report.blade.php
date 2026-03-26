<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Tarjetas de resumen --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Deudas Activas</p>
                <p class="text-2xl font-bold text-info-600 dark:text-info-400 mt-1">{{ $stats['deudas_activas'] }}</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Monto Total</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($stats['monto_total'], 2) }}</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-red-200 dark:border-red-800 p-5">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Saldo Pendiente</p>
                <p class="text-2xl font-bold text-danger-600 dark:text-danger-400 mt-1">${{ number_format($stats['saldo_pendiente'], 2) }}</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-green-200 dark:border-green-800 p-5">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Pagado</p>
                <p class="text-2xl font-bold text-success-600 dark:text-success-400 mt-1">${{ number_format($stats['total_pagado'], 2) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Pagadas</p>
                <p class="text-2xl font-bold text-success-600 dark:text-success-400 mt-1">{{ $stats['deudas_pagadas'] }}</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-red-200 dark:border-red-800 p-5">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Vencidas</p>
                <p class="text-2xl font-bold text-danger-600 dark:text-danger-400 mt-1">{{ $stats['deudas_vencidas'] }}</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-warning-200 dark:border-warning-800 p-5">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Deudas</p>
                <p class="text-2xl font-bold text-warning-600 dark:text-warning-400 mt-1">{{ $stats['total_deudas'] }}</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-warning-200 dark:border-warning-800 p-5">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Cuotas próx. 30 días</p>
                <p class="text-2xl font-bold text-warning-600 dark:text-warning-400 mt-1">{{ $stats['proximas_cuotas'] }}</p>
            </div>
        </div>

        {{-- Cuotas próximas a vencer --}}
        @if($proximas->isNotEmpty())
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-warning-200 dark:border-warning-800 p-6">
            <h3 class="text-base font-bold text-warning-600 dark:text-warning-400 mb-4 flex items-center gap-2">
                <x-heroicon-o-bell-alert class="w-5 h-5"/>
                Cuotas por vencer en los próximos 30 días
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Deuda</th>
                            <th class="py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Acreedor</th>
                            <th class="py-2 text-center font-semibold text-gray-700 dark:text-gray-300">Cuota #</th>
                            <th class="py-2 text-center font-semibold text-gray-700 dark:text-gray-300">Vencimiento</th>
                            <th class="py-2 text-right font-semibold text-gray-700 dark:text-gray-300">Capital</th>
                            <th class="py-2 text-right font-semibold text-gray-700 dark:text-gray-300">Intereses</th>
                            <th class="py-2 text-right font-semibold text-gray-700 dark:text-gray-300">Total Cuota</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proximas as $cuota)
                        <tr class="border-b border-gray-100 dark:border-gray-800 {{ $cuota->fecha_vencimiento < now()->toDateString() ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                            <td class="py-2 font-mono text-xs">{{ $cuota->debt->numero }}</td>
                            <td class="py-2">{{ $cuota->debt->acreedor }}</td>
                            <td class="py-2 text-center">#{{ $cuota->numero_cuota }}</td>
                            <td class="py-2 text-center {{ $cuota->fecha_vencimiento < now()->toDateString() ? 'text-danger-600 font-bold' : '' }}">
                                {{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') }}
                            </td>
                            <td class="py-2 text-right">${{ number_format($cuota->monto_capital, 2) }}</td>
                            <td class="py-2 text-right">${{ number_format($cuota->monto_interes, 2) }}</td>
                            <td class="py-2 text-right font-bold">${{ number_format($cuota->total_cuota, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-warning-300 dark:border-warning-700 bg-warning-50 dark:bg-warning-900/10">
                            <td colspan="4" class="py-2 font-bold text-warning-700 dark:text-warning-300">TOTAL</td>
                            <td class="py-2 text-right font-bold">${{ number_format($proximas->sum('monto_capital'), 2) }}</td>
                            <td class="py-2 text-right font-bold">${{ number_format($proximas->sum('monto_interes'), 2) }}</td>
                            <td class="py-2 text-right font-bold">${{ number_format($proximas->sum('total_cuota'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif

        {{-- Tabla global de deudas --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4">Detalle de Todas las Deudas</h3>
            {{ $this->table }}
        </div>

    </div>
</x-filament-panels::page>
