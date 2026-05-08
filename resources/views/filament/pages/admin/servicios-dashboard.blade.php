<x-filament-panels::page>
    @php
        $stats    = $this->getStats();
        $empresas = $this->getEmpresasConActividad();
    @endphp

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total empresas</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['total'] }}</p>
        </div>

        <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-5 shadow-sm">
            <p class="text-xs font-medium text-green-600 uppercase tracking-wide">Activas</p>
            <p class="text-3xl font-bold text-green-700 dark:text-green-400 mt-2">{{ $stats['activas'] }}</p>
        </div>

        <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-5 shadow-sm">
            <p class="text-xs font-medium text-red-600 uppercase tracking-wide">Inactivas</p>
            <p class="text-3xl font-bold text-red-700 dark:text-red-400 mt-2">{{ $stats['inactivas'] }}</p>
        </div>

        <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-5 shadow-sm">
            <p class="text-xs font-medium text-blue-600 uppercase tracking-wide flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse inline-block"></span>
                Online ahora
            </p>
            <p class="text-3xl font-bold text-blue-700 dark:text-blue-400 mt-2">{{ $stats['onlineAhora'] }}</p>
            <p class="text-xs text-blue-400 mt-1">usuarios activos</p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nuevas este mes</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['nuevasEsteMes'] }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ now()->format('F Y') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        {{-- Distribución por plan --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Distribución por plan</h3>
            </div>
            <div class="p-5 space-y-4">
                @foreach (['enterprise' => ['Enterprise', 'bg-yellow-500', 'text-yellow-700'], 'pro' => ['Pro', 'bg-blue-500', 'text-blue-700'], 'basic' => ['Basic', 'bg-gray-400', 'text-gray-600']] as $key => [$label, $bg, $text])
                    @php $count = $stats['porPlan'][$key] ?? 0; $pct = $stats['activas'] > 0 ? round($count / $stats['activas'] * 100) : 0; @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            <span class="{{ $text }} font-bold">{{ $count }}</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full {{ $bg }}" style="width: {{ $pct }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $pct }}%</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Estado de empresas --}}
        <div class="lg:col-span-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Estado de empresas</h3>
                <a href="{{ route('filament.admin.resources.servicios-empresas.index') }}"
                   class="text-xs text-primary-600 hover:underline">Gestionar</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Empresa</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Plan</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Usuarios</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Online</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Último login</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($empresas as $emp)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full {{ $emp->activo ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $emp->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $emp->plan === 'enterprise' ? 'bg-yellow-100 text-yellow-700' : ($emp->plan === 'pro' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                                        {{ ucfirst($emp->plan) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-gray-500">{{ $emp->users_count }}</td>
                                <td class="px-4 py-2.5">
                                    @if ($emp->online > 0)
                                        <span class="inline-flex items-center gap-1 text-xs text-green-600 font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                            {{ $emp->online }} activo(s)
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-xs text-gray-400">
                                    {{ $emp->ultimo_login ? \Carbon\Carbon::parse($emp->ultimo_login)->diffForHumans() : 'Nunca' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
