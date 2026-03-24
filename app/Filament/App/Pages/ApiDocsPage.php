<?php

namespace App\Filament\App\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Laravel\Sanctum\PersonalAccessToken;

class ApiDocsPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-code-bracket';
    protected static ?string $navigationLabel = 'API / Documentación';
    protected static ?string $title           = 'API CMS — Documentación y Tokens';
    protected static ?string $navigationGroup = 'CMS';
    protected static ?int    $navigationSort  = 99;

    protected static string $view = 'filament.app.pages.api-docs';

    public ?string $newToken = null;

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_token')
                ->label('Generar nuevo token')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('¿Generar nuevo token?')
                ->modalDescription('El token anterior quedará inválido. Deberás actualizar la configuración en tu proyecto React.')
                ->action(function () {
                    $empresa = Filament::getTenant();

                    // Revocar todos los tokens anteriores
                    PersonalAccessToken::where('tokenable_type', \App\Models\Empresa::class)
                        ->where('tokenable_id', $empresa->id)
                        ->delete();

                    $token = $empresa->createToken('cms-api');

                    $this->newToken = $token->plainTextToken;

                    Notification::make()
                        ->title('Token generado correctamente')
                        ->body('Copia el token ahora — no se volverá a mostrar.')
                        ->warning()
                        ->persistent()
                        ->send();
                }),

            Action::make('revoke_token')
                ->label('Revocar token')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('¿Revocar token?')
                ->modalDescription('La API dejará de funcionar hasta que generes uno nuevo.')
                ->action(function () {
                    $empresa = Filament::getTenant();

                    PersonalAccessToken::where('tokenable_type', \App\Models\Empresa::class)
                        ->where('tokenable_id', $empresa->id)
                        ->delete();

                    $this->newToken = null;

                    Notification::make()
                        ->title('Token revocado')
                        ->danger()
                        ->send();
                }),
        ];
    }

    public function getViewData(): array
    {
        $empresa = Filament::getTenant();

        $token = PersonalAccessToken::where('tokenable_type', \App\Models\Empresa::class)
            ->where('tokenable_id', $empresa->id)
            ->latest()
            ->first();

        return [
            'empresa'       => $empresa,
            'tieneToken'    => (bool) $token,
            'tokenCreadoEn' => $token?->created_at?->format('d/m/Y H:i'),
            'tokenUsadoEn'  => $token?->last_used_at?->format('d/m/Y H:i') ?? 'Nunca',
            'newToken'      => $this->newToken,
            'baseUrl'       => 'https://mashaec.net/api/cms/' . $empresa->slug,
        ];
    }
}
