<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-between items-center bg-white dark:bg-gray-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $empresa->name ?? 'Empresa' }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Estado de Flujo de Efectivo
                </p>
            </div>
            
            <x-filament::button wire:click="downloadPdf" icon="heroicon-o-arrow-down-tray" color="primary">
                Descargar PDF
            </x-filament::button>
        </div>

        <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <h3 class="text-lg font-bold mb-6 text-gray-950 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
                Flujos de Efectivo de las Actividades de Operación
            </h3>
            <p class="text-gray-500 italic mb-6">Módulo en construcción: Los datos provendrán de los comprobantes contables.</p>

            <h3 class="text-lg font-bold mb-6 text-gray-950 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mt-8">
                Flujos de Efectivo de las Actividades de Inversión
            </h3>
            <p class="text-gray-500 italic mb-6">Módulo en construcción.</p>

            <h3 class="text-lg font-bold mb-6 text-gray-950 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mt-8">
                Flujos de Efectivo de las Actividades de Financiación
            </h3>
            <p class="text-gray-500 italic mb-6">Módulo en construcción.</p>

            <div class="mt-8 bg-gray-50 dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <span class="font-bold text-lg text-gray-950 dark:text-white">EFECTIVO Y EQUIVALENTES DE EFECTIVO AL FINAL DEL EJERCICIO</span>
                <span class="font-bold text-xl text-primary-600 dark:text-primary-400">
                    $0.00
                </span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
