<x-filament-panels::page>
    <p class="-mt-4 text-sm text-gray-500">Una pantalla. Sin pasos intermedios.</p>

    <form wire:submit="registrar" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center gap-3">
            <x-filament::button type="submit" size="lg">
                Registrar movimiento
            </x-filament::button>
            <span class="text-xs text-gray-500">
                El saldo y el costo promedio los calcula el kardex, no esta pantalla.
            </span>
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
