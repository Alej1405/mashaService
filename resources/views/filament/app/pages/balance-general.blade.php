<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-between items-center bg-white dark:bg-gray-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $empresa->name ?? 'Empresa' }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Estado de Situación Financiera (Balance General)
                </p>
            </div>
            
            <x-filament::button wire:click="downloadPdf" icon="heroicon-o-arrow-down-tray" color="primary">
                Descargar PDF
            </x-filament::button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Columna Activos -->
            <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-bold mb-4 text-primary-600 dark:text-primary-400">Activos</h3>
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
                            @forelse($activos as $cuenta)
                            <tr>
                                <td class="py-2 text-gray-500 dark:text-gray-400">{{ $cuenta->code }}</td>
                                <td class="py-2 text-gray-900 dark:text-white">{{ $cuenta->name }}</td>
                                <td class="py-2 text-right font-medium text-gray-900 dark:text-white">{{ number_format($cuenta->saldo, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-gray-500 italic">No hay registros de activos.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-primary-500">
                                <td colspan="2" class="py-3 font-bold text-gray-950 dark:text-white">TOTAL ACTIVOS</td>
                                <td class="py-3 text-right font-bold text-primary-600 dark:text-primary-400">
                                    ${{ number_format($activos->sum('saldo'), 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Columna Pasivos y Patrimonio -->
            <div class="space-y-6">
                <!-- Pasivos -->
                <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                    <h3 class="text-lg font-bold mb-4 text-danger-600 dark:text-danger-400">Pasivos</h3>
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
                                @forelse($pasivos as $cuenta)
                                <tr>
                                    <td class="py-2 text-gray-500 dark:text-gray-400">{{ $cuenta->code }}</td>
                                    <td class="py-2 text-gray-900 dark:text-white">{{ $cuenta->name }}</td>
                                    <td class="py-2 text-right font-medium text-gray-900 dark:text-white">{{ number_format($cuenta->saldo, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-gray-500 italic">No hay registros de pasivos.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-danger-500">
                                    <td colspan="2" class="py-3 font-bold text-gray-950 dark:text-white">TOTAL PASIVOS</td>
                                    <td class="py-3 text-right font-bold text-danger-600 dark:text-danger-400">
                                        ${{ number_format($pasivos->sum('saldo'), 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Patrimonio -->
                <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                    <h3 class="text-lg font-bold mb-4 text-success-600 dark:text-success-400">Patrimonio</h3>
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
                                @forelse($patrimonio as $cuenta)
                                <tr>
                                    <td class="py-2 text-gray-500 dark:text-gray-400">{{ $cuenta->code }}</td>
                                    <td class="py-2 text-gray-900 dark:text-white">{{ $cuenta->name }}</td>
                                    <td class="py-2 text-right font-medium text-gray-900 dark:text-white">{{ number_format($cuenta->saldo, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-gray-500 italic">No hay registros de patrimonio.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-success-500">
                                    <td colspan="2" class="py-3 font-bold text-gray-950 dark:text-white">TOTAL PATRIMONIO</td>
                                    <td class="py-3 text-right font-bold text-success-600 dark:text-success-400">
                                        ${{ number_format($patrimonio->sum('saldo'), 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Resumen Pasivo + Patrimonio -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <span class="font-bold text-lg text-gray-950 dark:text-white">TOTAL PASIVO + PATRIMONIO</span>
                    <span class="font-bold text-lg text-gray-950 dark:text-white">
                        ${{ number_format($pasivos->sum('saldo') + $patrimonio->sum('saldo'), 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
