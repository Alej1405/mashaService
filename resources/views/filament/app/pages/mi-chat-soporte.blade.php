<x-filament-panels::page>
    <div class="max-w-2xl mx-auto">
        @if ($this->chat)
            <livewire:support-chat-box :chat="$this->chat" :is-admin="false" :key="'user-chat-'.$this->chat->id" />
        @else
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-10 flex flex-col items-center text-center gap-4">
                <div class="w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                    <x-heroicon-o-chat-bubble-left-right class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">¿Necesitas ayuda?</h3>
                    <p class="text-sm text-gray-500 mt-1">Nuestro equipo de soporte está disponible para ayudarte. Inicia una conversación y te responderemos a la brevedad.</p>
                </div>
                <button
                    wire:click="startNewChat"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-xl transition shadow-sm">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    Iniciar chat con soporte
                </button>
            </div>
        @endif
    </div>
</x-filament-panels::page>
