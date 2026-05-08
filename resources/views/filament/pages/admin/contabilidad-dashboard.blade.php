<x-filament-panels::page>
    @php
        $stats    = $this->getStats();
        $ultimas  = $this->getUltimasFacturas();
        $proximas = $this->getVencidasProximas();
    @endphp

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Ingresos este mes</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">${{ number_format($stats['ingresosMes'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ now()->format('F Y') }}</p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total acumulado</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">${{ number_format($stats['ingresosTotales'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Todas las épocas</p>
        </div>

        <div class="rounded-xl border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 p-5 shadow-sm">
            <p class="text-xs font-medium text-yellow-600 uppercase tracking-wide">Pendiente de cobro</p>
            <p class="text-3xl font-bold text-yellow-700 dark:text-yellow-400 mt-2">${{ number_format($stats['montoPendiente'], 2) }}</p>
            <p class="text-xs text-yellow-500 mt-1">Facturas pendientes</p>
        </div>

        <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-5 shadow-sm">
            <p class="text-xs font-medium text-red-600 uppercase tracking-wide">Vencidas</p>
            <p class="text-3xl font-bold text-red-700 dark:text-red-400 mt-2">{{ $stats['vencidos'] }}</p>
            <p class="text-xs text-red-500 mt-1">${{ number_format($stats['montoVencido'], 2) }} en riesgo</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Ingresos por plan --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Ingresos por plan</h3>
            </div>
            <div class="p-5 space-y-4">
                @foreach (['enterprise' => 'Enterprise', 'pro' => 'Pro', 'basic' => 'Basic'] as $key => $label)
                    @php $monto = $stats['porPlan'][$key] ?? 0; $total = array_sum($stats['porPlan']) ?: 1; $pct = round($monto / $total * 100); @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            <span class="text-gray-500">${{ number_format($monto, 2) }}</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full {{ $key === 'enterprise' ? 'bg-yellow-500' : ($key === 'pro' ? 'bg-blue-500' : 'bg-gray-400') }}"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Últimas facturas --}}
        <div class="lg:col-span-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Últimas facturas</h3>
                <a href="{{ route('filament.admin.resources.service-invoices.index') }}"
                   class="text-xs text-primary-600 hover:underline">Ver todas</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">N°</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Empresa</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Monto</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Estado</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Vence</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($ultimas as $f)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $f->numero }}</td>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $f->empresa->name }}</td>
                                <td class="px-4 py-2 font-semibold text-gray-900 dark:text-white">${{ number_format($f->monto, 2) }}</td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $f->estado === 'pagado' ? 'bg-green-100 text-green-700' : ($f->estado === 'vencido' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                        {{ ucfirst($f->estado) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-500">{{ $f->fecha_vencimiento->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">Sin facturas aún</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Vencimientos próximos --}}
    @if ($proximas->count() > 0)
        <div class="mt-6 rounded-xl border border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-900/10 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-orange-200 dark:border-orange-800 flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-orange-500" />
                <h3 class="text-sm font-semibold text-orange-800 dark:text-orange-300">Vencen en los próximos 7 días</h3>
            </div>
            <div class="p-4 flex flex-wrap gap-3">
                @foreach ($proximas as $f)
                    <div class="flex items-center gap-2 bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-orange-200 dark:border-orange-700 text-sm">
                        <span class="font-medium text-gray-800 dark:text-white">{{ $f->empresa->name }}</span>
                        <span class="text-orange-600 font-semibold">${{ number_format($f->monto, 2) }}</span>
                        <span class="text-xs text-gray-400">{{ $f->fecha_vencimiento->format('d/m') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-filament-panels::page>
