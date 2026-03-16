<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-between items-center bg-white dark:bg-gray-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $empresa->name ?? 'Empresa' }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Estado de Resultados Integrales
                </p>
            </div>
            
            <x-filament::button wire:click="downloadPdf" icon="heroicon-o-arrow-down-tray" color="primary">
                Descargar PDF
            </x-filament::button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Columna Ingresos -->
            <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-bold mb-4 text-success-600 dark:text-success-400">Ingresos</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="py-2 font-semibold text-gray-950 dark:text-white">Código</th>
                                <th class="py-2 font-semibold text-gray-950 dark:text-white">Cuenta</th>
                                <th class="py-2 text-right font-semibold text-gray-950 dark:text-white">Saldo ($)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($ingresos as $cuenta)
                            <tr>
                                <td class="py-2 text-gray-500 dark:text-gray-400">{{ $cuenta->code }}</td>
                                <td class="py-2 text-gray-900 dark:text-white">{{ $cuenta->name }}</td>
                                <td class="py-2 text-right font-medium text-gray-900 dark:text-white">{{ number_format($cuenta->saldo, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-gray-500 italic">No hay registros de ingresos.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-success-500">
                                <td colspan="2" class="py-3 font-bold text-gray-950 dark:text-white">TOTAL INGRESOS</td>
                                <td class="py-3 text-right font-bold text-success-600 dark:text-success-400">
                                    ${{ number_format($ingresos->sum('saldo'), 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Columna Egresos (Costos y Gastos) -->
            <div class="space-y-6">
                <!-- Costos -->
                <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                    <h3 class="text-lg font-bold mb-4 text-warning-600 dark:text-warning-400">Costos</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-2 font-semibold text-gray-950 dark:text-white">Código</th>
                                    <th class="py-2 font-semibold text-gray-950 dark:text-white">Cuenta</th>
                                    <th class="py-2 text-right font-semibold text-gray-950 dark:text-white">Saldo ($)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse($costos as $cuenta)
                                <tr>
                                    <td class="py-2 text-gray-500 dark:text-gray-400">{{ $cuenta->code }}</td>
                                    <td class="py-2 text-gray-900 dark:text-white">{{ $cuenta->name }}</td>
                                    <td class="py-2 text-right font-medium text-gray-900 dark:text-white">{{ number_format($cuenta->saldo, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-gray-500 italic">No hay registros de costos.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-warning-500">
                                    <td colspan="2" class="py-3 font-bold text-gray-950 dark:text-white">TOTAL COSTOS</td>
                                    <td class="py-3 text-right font-bold text-warning-600 dark:text-warning-400">
                                        ${{ number_format($costos->sum('saldo'), 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Gastos -->
                <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                    <h3 class="text-lg font-bold mb-4 text-danger-600 dark:text-danger-400">Gastos</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-2 font-semibold text-gray-950 dark:text-white">Código</th>
                                    <th class="py-2 font-semibold text-gray-950 dark:text-white">Cuenta</th>
                                    <th class="py-2 text-right font-semibold text-gray-950 dark:text-white">Saldo ($)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse($gastos as $cuenta)
                                <tr>
                                    <td class="py-2 text-gray-500 dark:text-gray-400">{{ $cuenta->code }}</td>
                                    <td class="py-2 text-gray-900 dark:text-white">{{ $cuenta->name }}</td>
                                    <td class="py-2 text-right font-medium text-gray-900 dark:text-white">{{ number_format($cuenta->saldo, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-gray-500 italic">No hay registros de gastos.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-danger-500">
                                    <td colspan="2" class="py-3 font-bold text-gray-950 dark:text-white">TOTAL GASTOS</td>
                                    <td class="py-3 text-right font-bold text-danger-600 dark:text-danger-400">
                                        ${{ number_format($gastos->sum('saldo'), 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Utilidad / Pérdida del Ejercicio -->
                @php
                    $totalIngresos = $ingresos->sum('saldo');
                    $totalEgresos = $costos->sum('saldo') + $gastos->sum('saldo');
                    $resultado = $totalIngresos - $totalEgresos;
                    $esUtilidad = $resultado >= 0;
                @endphp
                <div class="{{ $esUtilidad ? 'bg-success-50 dark:bg-success-900' : 'bg-danger-50 dark:bg-danger-900' }} rounded-xl p-4 border {{ $esUtilidad ? 'border-success-200 dark:border-success-700' : 'border-danger-200 dark:border-danger-700' }} flex justify-between items-center">
                    <span class="font-bold text-lg {{ $esUtilidad ? 'text-success-700 dark:text-success-300' : 'text-danger-700 dark:text-danger-300' }}">
                        {{ $esUtilidad ? 'UTILIDAD DEL EJERCICIO' : 'PÉRDIDA DEL EJERCICIO' }}
                    </span>
                    <span class="font-bold text-2xl {{ $esUtilidad ? 'text-success-700 dark:text-success-300' : 'text-danger-700 dark:text-danger-300' }}">
                        ${{ number_format(abs($resultado), 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
