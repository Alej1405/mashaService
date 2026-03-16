<x-filament-panels::page>

    {{-- ── PASO 1: CARGAR ARCHIVO ─────────────────────────────────────────── --}}
    @if ($paso == 1)
        <x-filament::section>
            <x-slot name="heading">Paso 1 — Cargar Factura XML del SRI</x-slot>
            <x-slot name="description">
                Sube el archivo <strong>XML</strong> de la factura electrónica tal como lo entrega el SRI (archivo autorizado).
                El sistema leerá automáticamente el proveedor, los productos, creará la compra y generará el asiento contable.
            </x-slot>

            <form wire:submit.prevent="parsearArchivo">
                {{ $this->form }}

                <div class="mt-6 flex justify-end">
                    <x-filament::button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="parsearArchivo">Analizar archivo →</span>
                        <span wire:loading wire:target="parsearArchivo">Analizando...</span>
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    @endif

    {{-- ── PASO 2: REVISAR Y CONFIRMAR ───────────────────────────────────── --}}
    @if ($paso == 2)

        {{-- Resumen de la factura --}}
        @if ($facturaData)
            <x-filament::section>
                <x-slot name="heading">Datos de la Factura</x-slot>
                <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Número</p>
                        <p class="font-medium">{{ $facturaData['numero'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Fecha</p>
                        <p class="font-medium">{{ $facturaData['fecha'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Forma de pago</p>
                        <p class="font-medium">
                            {{ $facturaData['label_pago'] }}
                            @if($facturaData['codigo_sri'] !== '—')
                                <span class="text-xs text-gray-400">(SRI: {{ $facturaData['codigo_sri'] }})</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Subtotal</p>
                        <p class="font-medium">${{ number_format($facturaData['subtotal'], 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">IVA</p>
                        <p class="font-medium">${{ number_format($facturaData['iva'], 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
                        <p class="font-semibold text-primary-600 dark:text-primary-400">${{ number_format($facturaData['total'], 2) }}</p>
                    </div>
                </div>
            </x-filament::section>
        @endif

        {{-- Proveedor --}}
        <x-filament::section class="mt-4">
            <x-slot name="heading">Proveedor (Emisor)</x-slot>
            @if ($proveedorExiste)
                <x-filament::badge color="success">Ya existe en el sistema — no se modificará</x-filament::badge>
            @else
                <x-filament::badge color="warning">Nuevo — se creará al confirmar</x-filament::badge>
            @endif

            <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                <div><span class="font-medium">Razón Social:</span> {{ $proveedorData['nombre'] }}</div>
                <div><span class="font-medium">RUC:</span> {{ $proveedorData['identificacion'] }}</div>
                @if(!empty($proveedorData['direccion']))
                    <div class="col-span-2"><span class="font-medium">Dirección:</span> {{ $proveedorData['direccion'] }}</div>
                @endif
                @if(!empty($proveedorData['email']))
                    <div><span class="font-medium">Email:</span> {{ $proveedorData['email'] }}</div>
                @endif
            </div>
        </x-filament::section>

        {{-- Ítems ya existentes --}}
        @if (!empty($itemsExistentes))
            <x-filament::section class="mt-4">
                <x-slot name="heading">
                    Ítems ya registrados ({{ count($itemsExistentes) }}) — se incluirán en la compra sin modificar el inventario base
                </x-slot>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="pb-2">Producto</th>
                            <th class="pb-2 text-right">Cantidad</th>
                            <th class="pb-2 text-right">Precio unit.</th>
                            <th class="pb-2 text-center">IVA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($itemsExistentes as $item)
                            <tr>
                                <td class="py-2">{{ $item['nombre'] }}</td>
                                <td class="py-2 text-right">{{ $item['cantidad'] }}</td>
                                <td class="py-2 text-right">${{ number_format($item['precio_compra'], 4) }}</td>
                                <td class="py-2 text-center">
                                    @if($item['aplica_iva'])
                                        <x-filament::badge color="info" size="sm">15%</x-filament::badge>
                                    @else
                                        <x-filament::badge color="gray" size="sm">0%</x-filament::badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::section>
        @endif

        {{-- Ítems nuevos --}}
        @if (!empty($itemsNuevos))
            <x-filament::section class="mt-4">
                <x-slot name="heading">
                    Ítems nuevos a registrar ({{ count($itemsNuevos) }})
                </x-slot>
                <x-slot name="description">
                    Estos productos no existen en el inventario. Indica el tipo de ítem y el número de lote para cada uno.
                </x-slot>

                <div class="space-y-4">
                    @foreach ($itemsNuevos as $index => $item)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 grid grid-cols-2 gap-4 items-start">

                            <div class="col-span-2 flex items-start justify-between">
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $item['nombre'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Precio unitario: ${{ number_format($item['precio_compra'], 4) }}
                                        &nbsp;|&nbsp; Cantidad: {{ $item['cantidad'] }}
                                        &nbsp;|&nbsp; IVA: {{ $item['aplica_iva'] ? '15%' : '0%' }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Tipo de ítem <span class="text-red-500">*</span>
                                </label>
                                <select
                                    wire:model="itemsNuevos.{{ $index }}.type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <option value="">— Selecciona —</option>
                                    <option value="insumo">Insumo</option>
                                    <option value="materia_prima">Materia Prima</option>
                                    <option value="producto_terminado">Producto Terminado</option>
                                    <option value="activo_fijo">Activo Fijo</option>
                                    <option value="servicio">Servicio</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Número de lote
                                </label>
                                <input
                                    type="text"
                                    wire:model="itemsNuevos.{{ $index }}.lote"
                                    placeholder="Ej. LOTE-2026-001"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        @if (empty($itemsNuevos) && empty($itemsExistentes))
            <x-filament::section class="mt-4">
                <p class="text-sm text-gray-500">No se detectaron productos en la factura. Verifica que el XML sea un comprobante válido del SRI.</p>
            </x-filament::section>
        @endif

        @if (empty($itemsNuevos) && !empty($itemsExistentes))
            <x-filament::section class="mt-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Todos los productos de esta factura ya están en el inventario.
                    Se creará la compra y el asiento contable con los ítems existentes.
                </p>
            </x-filament::section>
        @endif

        {{-- Botones --}}
        <div class="mt-6 flex justify-between">
            <x-filament::button color="gray" wire:click="reiniciar">
                ← Volver a cargar
            </x-filament::button>

            <x-filament::button
                wire:click="importar"
                wire:loading.attr="disabled"
                wire:confirm="¿Confirmas la importación? Se registrará la compra y se generará el asiento contable automáticamente."
            >
                <span wire:loading.remove wire:target="importar">Registrar compra y generar asiento ✓</span>
                <span wire:loading wire:target="importar">Procesando...</span>
            </x-filament::button>
        </div>
    @endif

    {{-- ── PASO 3: RESULTADO ──────────────────────────────────────────────── --}}
    @if ($paso == 3)
        <x-filament::section>
            <x-slot name="heading">Compra registrada exitosamente</x-slot>

            <div class="flex flex-col items-center gap-4 py-6 text-center">
                <x-filament::icon icon="heroicon-o-check-circle" class="w-16 h-16 text-success-500" />

                <div class="grid grid-cols-2 gap-4 text-sm mt-2 w-full max-w-sm">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Compra generada</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $purchaseNumero }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Asiento contable</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $journalNumero }}</p>
                    </div>
                </div>

                <p class="text-gray-500 dark:text-gray-400 text-xs mt-2">
                    El stock del inventario fue actualizado y el asiento contable fue creado automáticamente.
                </p>

                <div class="flex gap-3 mt-4">
                    <x-filament::button wire:click="reiniciar">
                        Nueva importación
                    </x-filament::button>
                    <x-filament::button
                        color="gray"
                        tag="a"
                        :href="\App\Filament\App\Resources\InventoryItemResource::getUrl('index')"
                    >
                        Ver inventario
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endif

</x-filament-panels::page>
