<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            {{ $this->form }}
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center justify-center">
                <span class="text-sm font-medium text-gray-500 uppercase">Saldo Inicial Consolidado</span>
                <span class="text-2xl font-black text-gray-900 mt-1">${{ number_format($total_saldo_inicial, 2) }}</span>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-orange-100 flex flex-col items-center justify-center">
                <span class="text-sm font-medium text-orange-600 uppercase">Variación Neta Período</span>
                <span class="text-2xl font-black text-orange-700 mt-1">${{ number_format($total_entradas - $total_salidas, 2) }}</span>
            </div>
            <div class="bg-gray-900 p-6 rounded-xl shadow-sm flex flex-col items-center justify-center">
                <span class="text-sm font-medium text-white opacity-70 uppercase">Saldo Final Consolidado</span>
                <span class="text-2xl font-black text-white mt-1">${{ number_format($total_saldo_final, 2) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-xs text-gray-600 uppercase font-bold">
                    <tr>
                        <th class="px-6 py-4">Código / Cuenta</th>
                        <th class="px-6 py-4 text-right">Saldo Inicial</th>
                        <th class="px-6 py-4 text-right">Entradas (+)</th>
                        <th class="px-6 py-4 text-right">Salidas (-)</th>
                        <th class="px-6 py-4 text-right">Saldo Final</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($cuentasData as $cuenta)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-mono text-xs text-gray-500">{{ $cuenta->code }}</div>
                                <div class="font-bold text-gray-900">{{ $cuenta->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-right text-gray-600">${{ number_format($cuenta->saldo_inicial, 2) }}</td>
                            <td class="px-6 py-4 text-right text-emerald-600 font-medium">${{ number_format($cuenta->entradas, 2) }}</td>
                            <td class="px-6 py-4 text-right text-rose-600 font-medium">${{ number_format($cuenta->salidas, 2) }}</td>
                            <td class="px-6 py-4 text-right font-black text-gray-900">${{ number_format($cuenta->saldo_final, 2) }}</td>
                        </tr>
                    @endforeach
                    
                    <tr class="bg-gray-900 text-white font-bold">
                        <td class="px-6 py-4 uppercase tracking-wider">TOTAL CONSOLIDADO</td>
                        <td class="px-6 py-4 text-right">${{ number_format($total_saldo_inicial, 2) }}</td>
                        <td class="px-6 py-4 text-right text-emerald-400 font-black">+${{ number_format($total_entradas, 2) }}</td>
                        <td class="px-6 py-4 text-right text-rose-400 font-black">-${{ number_format($total_salidas, 2) }}</td>
                        <td class="px-6 py-4 text-right bg-primary-600 text-white text-lg font-black">${{ number_format($total_saldo_final, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
