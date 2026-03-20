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

        {{-- ── Vista previa (srcdoc — reactiva al formulario) ─────────── --}}
        <div class="sticky top-6">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">

                {{-- Barra --}}
                <div class="flex items-center justify-between px-4 py-2 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Vista previa en tiempo real
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        Se actualiza al cambiar cualquier campo
                    </span>
                </div>

                {{-- iframe con srcdoc --}}
                @if($previewHtml)
                    <iframe
                        srcdoc="{{ $previewHtml }}"
                        class="w-full bg-white"
                        style="height:680px;border:none;"
                        title="Vista previa carta de presentación"
                    ></iframe>
                @else
                    <div class="flex items-center justify-center bg-gray-50 dark:bg-gray-900" style="height:680px;">
                        <p class="text-sm text-gray-400">Completa el formulario para ver la vista previa.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>

</x-filament-panels::page>
