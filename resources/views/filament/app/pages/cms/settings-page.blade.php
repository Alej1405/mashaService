<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="flex justify-start pt-2">
            <x-filament::button type="submit" size="lg">
                Guardar cambios
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
