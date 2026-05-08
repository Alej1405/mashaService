<div class="flex flex-col h-full" style="min-height: 500px; max-height: 650px;">

    {{-- Header del chat --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-primary-600 dark:text-primary-400" />
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                    @if ($isAdmin)
                        {{ $chat->empresa->name }} — {{ $chat->user->name }}
                    @else
                        Soporte técnico
                    @endif
                </p>
                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
                    {{ $chat->status === 'abierto' ? 'bg-green-100 text-green-700' : ($chat->status === 'en_proceso' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') }}">
                    {{ $chat->statusLabel() }}
                </span>
            </div>
        </div>
        @if ($isAdmin)
            <div class="flex gap-2">
                @if ($chat->status !== 'cerrado')
                    <button wire:click="closeChat"
                        class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 transition">
                        Cerrar chat
                    </button>
                @else
                    <button wire:click="reopenChat"
                        class="text-xs px-3 py-1.5 rounded-lg bg-primary-100 hover:bg-primary-200 text-primary-700 transition">
                        Reabrir
                    </button>
                @endif
            </div>
        @endif
    </div>

    {{-- Mensajes --}}
    <div id="chat-messages-{{ $chat->id }}"
         class="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-gray-50 dark:bg-gray-900"
         x-data
         x-init="$el.scrollTop = $el.scrollHeight"
         x-on:livewire:navigated.window="$el.scrollTop = $el.scrollHeight">

        @forelse ($messages as $msg)
            @php $isMine = ($isAdmin && $msg->remitente === 'admin') || (!$isAdmin && $msg->remitente === 'user'); @endphp
            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-xs lg:max-w-md xl:max-w-lg">
                    <div class="px-4 py-2.5 rounded-2xl text-sm shadow-sm
                        {{ $isMine
                            ? 'bg-primary-600 text-white rounded-br-none'
                            : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-700 rounded-bl-none' }}">
                        {{ $msg->mensaje }}
                    </div>
                    <p class="text-xs text-gray-400 mt-1 {{ $isMine ? 'text-right' : 'text-left' }}">
                        {{ $msg->created_at->diffForHumans() }}
                        @if ($isMine && $msg->leido_at)
                            &middot; <span class="text-primary-400">Leído</span>
                        @endif
                    </p>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center h-full py-10 text-center text-gray-400">
                <x-heroicon-o-chat-bubble-oval-left class="w-10 h-10 mb-2 opacity-40" />
                <p class="text-sm">Aún no hay mensajes. ¡Escribe el primero!</p>
            </div>
        @endforelse
    </div>

    {{-- Input de mensaje --}}
    @if ($chat->status !== 'cerrado')
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <form wire:submit="sendMessage" class="flex items-end gap-2">
                <div class="flex-1">
                    <textarea
                        wire:model="newMessage"
                        rows="1"
                        placeholder="Escribe tu mensaje..."
                        class="w-full resize-none rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 transition"
                        x-data
                        x-on:keydown.enter.prevent="if(!$event.shiftKey){ $wire.sendMessage() }"
                    ></textarea>
                </div>
                <button type="submit"
                    class="flex items-center justify-center w-10 h-10 rounded-xl bg-primary-600 hover:bg-primary-700 text-white transition shrink-0">
                    <x-heroicon-o-paper-airplane class="w-5 h-5" />
                </button>
            </form>
            <p class="text-xs text-gray-400 mt-1">Enter para enviar · Shift+Enter para nueva línea</p>
        </div>
    @else
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-center text-sm text-gray-500">
            Este chat está cerrado.
            @if (!$isAdmin)
                <a href="#" wire:click.prevent="$dispatch('open-new-chat')" class="text-primary-600 hover:underline">Abrir uno nuevo</a>
            @endif
        </div>
    @endif
</div>
