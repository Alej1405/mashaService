<x-filament-panels::page>
    @php
        $stats    = $this->getStats();
        $eventos  = $this->getEventosRecientes();
        $jobs     = $this->getJobsFallidos();
    @endphp

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-5 shadow-sm">
            <p class="text-xs font-medium text-red-600 uppercase tracking-wide">Errores activos</p>
            <p class="text-3xl font-bold text-red-700 dark:text-red-400 mt-2">{{ $stats['errores'] }}</p>
        </div>

        <div class="rounded-xl border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 p-5 shadow-sm">
            <p class="text-xs font-medium text-yellow-600 uppercase tracking-wide">Advertencias</p>
            <p class="text-3xl font-bold text-yellow-700 dark:text-yellow-400 mt-2">{{ $stats['warnings'] }}</p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total activos</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['activos'] }}</p>
        </div>

        <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-5 shadow-sm">
            <p class="text-xs font-medium text-green-600 uppercase tracking-wide">Resueltos</p>
            <p class="text-3xl font-bold text-green-700 dark:text-green-400 mt-2">{{ $stats['resueltos'] }}</p>
        </div>

        <div class="rounded-xl border border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-900/20 p-5 shadow-sm">
            <p class="text-xs font-medium text-orange-600 uppercase tracking-wide">Jobs fallidos</p>
            <p class="text-3xl font-bold text-orange-700 dark:text-orange-400 mt-2">{{ $stats['jobsFallidos'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Top empresas con más errores --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Más errores por empresa</h3>
            </div>
            <div class="p-4 space-y-3">
                @forelse ($stats['porEmpresa'] as $item)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $item->empresa }}</span>
                        <span class="ml-2 inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-700 text-xs font-bold shrink-0">{{ $item->total }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-4">Sin errores activos</p>
                @endforelse
            </div>
        </div>

        {{-- Errores por módulo --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Errores por módulo</h3>
            </div>
            <div class="p-4 space-y-3">
                @forelse ($stats['porModulo'] as $item)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ $item->modulo }}</span>
                        <span class="ml-2 inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-orange-700 text-xs font-bold shrink-0">{{ $item->total }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-4">Sin datos</p>
                @endforelse
            </div>
        </div>

        {{-- Jobs fallidos --}}
        <div class="rounded-xl border border-orange-200 dark:border-orange-800 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-orange-200 dark:border-orange-800 flex items-center gap-2">
                <x-heroicon-o-exclamation-circle class="w-4 h-4 text-orange-500" />
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Últimos jobs fallidos</h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($jobs as $job)
                    <div class="px-4 py-3">
                        <p class="text-xs font-semibold text-orange-700 dark:text-orange-400">{{ $job->job_name }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $job->failed_human }}</p>
                        <p class="text-xs text-gray-400 mt-1 truncate">{{ $job->exception_short }}</p>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm text-gray-400">
                        Sin jobs fallidos
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Eventos recientes --}}
    <div class="mt-6 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Eventos recientes sin resolver</h3>
            <a href="{{ route('filament.admin.resources.system-events.index') }}"
               class="text-xs text-primary-600 hover:underline">Ver todos</a>
        </div>
        @if ($eventos->isEmpty())
            <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                <x-heroicon-o-check-circle class="w-10 h-10 mb-2 text-green-400" />
                <p class="text-sm font-medium text-green-600">Todo en orden — sin eventos activos</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Tipo</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Empresa</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Módulo</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Título</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Ocurrió</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($eventos as $ev)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ in_array($ev->tipo, ['error','job_fallido']) ? 'bg-red-100 text-red-700' : ($ev->tipo === 'warning' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700') }}">
                                        {{ $ev->tipoLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $ev->empresa?->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-500 capitalize">{{ $ev->modulo ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-800 dark:text-gray-200 truncate max-w-xs">{{ $ev->titulo }}</td>
                                <td class="px-4 py-2 text-xs text-gray-400">{{ $ev->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
