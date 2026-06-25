<x-filament-panels::page>
    @php
        $sesiones = $this->getSesionesActivas();
        $stats    = $this->getStats();
    @endphp

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Usuarios online</p>
            <div class="flex items-center gap-2 mt-2">
                <span class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></span>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['totalOnline'] }}</p>
            </div>
            <p class="text-xs text-gray-400 mt-1">de {{ $stats['totalUsers'] }} usuarios totales</p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Empresas activas ahora</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['empresasOnline'] }}</p>
            <p class="text-xs text-gray-400 mt-1">de {{ $stats['totalEmpresas'] }} empresas activas</p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Tiempo de sesión</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">5 min</p>
            <p class="text-xs text-gray-400 mt-1">Ventana de actividad</p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Actualización</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">Auto</p>
            <p class="text-xs text-gray-400 mt-1">{{ now()->format('H:i:s') }}</p>
        </div>
    </div>

    {{-- Tabla de sesiones --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                Sesiones activas en este momento
            </h3>
        </div>

        @if ($sesiones->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-center text-gray-400">
                <x-heroicon-o-users class="w-10 h-10 mb-2 opacity-40" />
                <p class="text-sm">No hay usuarios activos en este momento</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Usuario</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Empresa</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Plan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">IP</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dispositivo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Último login</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actividad</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($sesiones as $sesion)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $sesion->user_name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $sesion->empresa_name }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $sesion->plan === 'enterprise' ? 'bg-yellow-100 text-yellow-700' : ($sesion->plan === 'pro' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                                        {{ ucfirst($sesion->plan) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $sesion->ip_address ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ $sesion->device }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ $sesion->login_human }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ $sesion->last_activity_human }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
