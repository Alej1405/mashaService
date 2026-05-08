<x-filament-panels::page>
    @php
        $chats      = $this->getChats();
        $activeChat = $this->getActiveChat();
        $unread     = $this->getUnreadCount();
    @endphp

    <div class="flex gap-0 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800 shadow-sm" style="height: 700px;">

        {{-- Lista de chats --}}
        <div class="w-80 shrink-0 border-r border-gray-200 dark:border-gray-700 flex flex-col">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Conversaciones</h3>
                    @if ($unread > 0)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-danger-500 rounded-full">
                            {{ $unread }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($chats as $chat)
                    @php
                        $last      = $chat->messages->first();
                        $isActive  = $this->activeChatId === $chat->id;
                        $hasUnread = $chat->messages->where('remitente', 'user')->whereNull('leido_at')->isNotEmpty();
                    @endphp
                    <button
                        wire:click="selectChat({{ $chat->id }})"
                        class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition
                            {{ $isActive ? 'bg-primary-50 dark:bg-primary-900/20 border-l-2 border-primary-500' : '' }}">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center shrink-0">
                                <span class="text-sm font-semibold text-primary-700 dark:text-primary-300">
                                    {{ strtoupper(substr($chat->empresa->name, 0, 1)) }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1">
                                    <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $chat->empresa->name }}
                                    </p>
                                    @if ($hasUnread)
                                        <span class="w-2 h-2 rounded-full bg-primary-500 shrink-0"></span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 truncate">{{ $chat->user->name }}</p>
                                @if ($last)
                                    <p class="text-xs text-gray-400 truncate mt-0.5">
                                        {{ Str::limit($last->mensaje, 35) }}
                                    </p>
                                @endif
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs px-1.5 py-0.5 rounded-full
                                        {{ $chat->status === 'abierto' ? 'bg-green-100 text-green-700' : ($chat->status === 'en_proceso' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500') }}">
                                        {{ $chat->statusLabel() }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ $chat->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="flex flex-col items-center justify-center h-full py-10 text-center text-gray-400 px-4">
                        <x-heroicon-o-chat-bubble-left-right class="w-10 h-10 mb-2 opacity-40" />
                        <p class="text-sm">No hay conversaciones aún</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Panel del chat activo --}}
        <div class="flex-1 flex flex-col">
            @if ($activeChat)
                <livewire:support-chat-box :chat="$activeChat" :is-admin="true" :key="'admin-chat-'.$activeChat->id" />
            @else
                <div class="flex flex-col items-center justify-center h-full text-center text-gray-400 px-8">
                    <x-heroicon-o-chat-bubble-oval-left class="w-16 h-16 mb-4 opacity-30" />
                    <p class="text-base font-medium text-gray-500 dark:text-gray-400">Selecciona una conversación</p>
                    <p class="text-sm text-gray-400 mt-1">Haz clic en un chat de la lista para responder</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
