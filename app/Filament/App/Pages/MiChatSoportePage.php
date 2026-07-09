<?php

namespace App\Filament\App\Pages;

use App\Models\SupportChat;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class MiChatSoportePage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Chat con Soporte';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int    $navigationSort  = 90;
    protected static string  $view            = 'filament.app.pages.mi-chat-soporte';

    public ?SupportChat $chat = null;

    public static function shouldRegisterNavigation(): bool
    {
        if (\App\Helpers\PlanHelper::aislarProducto()) {
            return false;
        }

        return parent::shouldRegisterNavigation();
    }

    public function mount(): void
    {
        $empresa = Filament::getTenant();
        $user    = auth()->user();

        $this->chat = SupportChat::where('empresa_id', $empresa->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['abierto', 'en_proceso'])
            ->latest()
            ->first();
    }

    public function startNewChat(): void
    {
        $empresa = Filament::getTenant();
        $user    = auth()->user();

        $this->chat = SupportChat::create([
            'empresa_id' => $empresa->id,
            'user_id'    => $user->id,
            'status'     => 'abierto',
        ]);
    }
}
