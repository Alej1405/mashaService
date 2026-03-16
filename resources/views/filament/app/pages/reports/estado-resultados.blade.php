<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            {{ $this->form }}
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden max-w-4xl mx-auto">
            <table class="w-full text-sm text-left">
                <tbody>
                    {{-- INGRESOS --}}
                    <tr class="bg-blue-50 font-bold text-blue-900">
                        <td colspan="2" class="px-6 py-3 uppercase tracking-wider">INGRESOS</td>
                        <td class="px-6 py-3 text-right">Monto</td>
                    </tr>
                    @foreach($ingresos as $item)
                        <tr class="{{ $item->level == 1 ? 'font-semibold text-gray-900 bg-gray-50' : 'text-gray-600' }}">
                            <td class="px-8 py-2 font-mono w-32">{{ $item->code }}</td>
                            <td class="px-4 py-2" style="padding-left: {{ ($item->level - 1) * 1.5 }}rem">{{ $item->name }}</td>
                            <td class="px-6 py-2 text-right">${{ number_format($item->saldo, 2) }}</td>
                        </tr>
                    @endforeach
                    @php $totalIngresos = $ingresos->where('level', 1)->sum('saldo'); @endphp
                    <tr class="font-bold border-t-2 border-blue-200">
                        <td colspan="2" class="px-6 py-3 text-right uppercase">TOTAL INGRESOS</td>
                        <td class="px-6 py-3 text-right font-black">${{ number_format($totalIngresos, 2) }}</td>
                    </tr>

                    {{-- COSTOS --}}
                    <tr class="bg-orange-50 font-bold text-orange-900 mt-4">
                        <td colspan="2" class="px-6 py-3 uppercase tracking-wider">COSTOS</td>
                        <td class="px-6 py-3 text-right"></td>
                    </tr>
                    @foreach($costos as $item)
                        <tr class="{{ $item->level == 1 ? 'font-semibold text-gray-900 bg-gray-50' : 'text-gray-600' }}">
                            <td class="px-8 py-2 font-mono">{{ $item->code }}</td>
                            <td class="px-4 py-2" style="padding-left: {{ ($item->level - 1) * 1.5 }}rem">{{ $item->name }}</td>
                            <td class="px-6 py-2 text-right">${{ number_format($item->saldo, 2) }}</td>
                        </tr>
                    @endforeach
                    @php $totalCostos = $costos->where('level', 1)->sum('saldo'); @endphp
                    <tr class="font-bold border-t border-gray-200">
                        <td colspan="2" class="px-6 py-2 text-right grayscale opacity-70">(-) TOTAL COSTOS</td>
                        <td class="px-6 py-2 text-right">(${{ number_format($totalCostos, 2) }})</td>
                    </tr>

                    <tr class="bg-gray-900 text-white font-bold">
                        <td colspan="2" class="px-6 py-3 text-right uppercase">UTILIDAD BRUTA</td>
                        <td class="px-6 py-3 text-right text-lg font-black">${{ number_format($totalIngresos - $totalCostos, 2) }}</td>
                    </tr>

                    {{-- GASTOS --}}
                    <tr class="bg-red-50 font-bold text-red-900 mt-4">
                        <td colspan="2" class="px-6 py-3 uppercase tracking-wider">GASTOS</td>
                        <td class="px-6 py-3 text-right"></td>
                    </tr>
                    @foreach($gastos as $item)
                        <tr class="{{ $item->level == 1 ? 'font-semibold text-gray-900 bg-gray-50' : 'text-gray-600' }}">
                            <td class="px-8 py-2 font-mono">{{ $item->code }}</td>
                            <td class="px-4 py-2" style="padding-left: {{ ($item->level - 1) * 1.5 }}rem">{{ $item->name }}</td>
                            <td class="px-6 py-2 text-right">${{ number_format($item->saldo, 2) }}</td>
                        </tr>
                    @endforeach
                    @php $totalGastos = $gastos->where('level', 1)->sum('saldo'); @endphp
                    <tr class="font-bold border-t border-gray-200">
                        <td colspan="2" class="px-6 py-2 text-right grayscale opacity-70">(-) TOTAL GASTOS</td>
                        <td class="px-6 py-2 text-right">(${{ number_format($totalGastos, 2) }})</td>
                    </tr>

                    @php $utilidadNeta = $totalIngresos - $totalCostos - $totalGastos; @endphp
                    <tr class="{{ $utilidadNeta >= 0 ? 'bg-emerald-600' : 'bg-rose-600' }} text-white font-bold">
                        <td colspan="2" class="px-6 py-4 text-right text-xl uppercase tracking-widest">UTILIDAD / PÉRDIDA NETA</td>
                        <td class="px-6 py-4 text-right text-2xl font-black">${{ number_format($utilidadNeta, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
