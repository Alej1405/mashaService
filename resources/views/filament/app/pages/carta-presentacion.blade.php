<x-filament-panels::page>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">

        {{-- ── Formulario ──────────────────────────────────────────────── --}}
        <div>
            <x-filament-panels::form wire:submit="save">
                {{ $this->form }}
                <div class="flex justify-start pt-2">
                    <x-filament::button type="submit" size="lg" icon="heroicon-o-check">
                        Guardar cambios
                    </x-filament::button>
                </div>
            </x-filament-panels::form>
        </div>

        {{-- ── Vista previa ────────────────────────────────────────────── --}}
        <div
            x-data="{ reload() { document.getElementById('carta-preview-frame').contentWindow.location.reload(); } }"
            x-on:carta-saved.window="reload()"
            class="sticky top-6"
        >
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">

                {{-- Barra de la preview --}}
                <div class="flex items-center justify-between px-4 py-2 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Vista previa</span>
                    <button
                        onclick="document.getElementById('carta-preview-frame').contentWindow.location.reload()"
                        class="rounded p-1 hover:bg-gray-200 dark:hover:bg-gray-700 transition text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        title="Recargar"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>

                {{-- iframe --}}
                <iframe
                    id="carta-preview-frame"
                    src="{{ $previewUrl }}"
                    class="w-full bg-white"
                    style="height:680px;border:none;"
                    title="Vista previa carta de presentación"
                ></iframe>
            </div>

            <p class="mt-2 text-xs text-gray-400 text-center">
                La vista previa se actualiza al guardar los cambios.
            </p>
        </div>

    </div>

</x-filament-panels::page>
