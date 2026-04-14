<?php

namespace App\Filament\App\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Laravel\Sanctum\PersonalAccessToken;

class EcommerceApiDocsPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-code-bracket';
    protected static ?string $navigationLabel = 'API / Documentación';
    protected static ?string $title           = 'E-Commerce API — Documentación';
    protected static ?string $navigationGroup = 'E-Commerce';
    protected static ?int    $navigationSort  = 99;

    protected static string $view = 'filament.app.pages.ecommerce-api-docs';

    public ?string $newToken = null;

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'enterprise'
            && \App\Helpers\PlanHelper::can('enterprise');
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
                ->modalHeading('¿Generar nuevo token de E-Commerce?')
                ->modalDescription('El token anterior quedará inválido. Deberás actualizar la configuración en tu frontend.')
                ->action(function () {
                    $empresa = Filament::getTenant();

                    PersonalAccessToken::where('tokenable_type', \App\Models\Empresa::class)
                        ->where('tokenable_id', $empresa->id)
                        ->where('name', 'ecommerce-api')
                        ->delete();

                    $token = $empresa->createToken('ecommerce-api');

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
                ->modalHeading('¿Revocar token de E-Commerce?')
                ->modalDescription('La API dejará de funcionar hasta que generes uno nuevo.')
                ->action(function () {
                    $empresa = Filament::getTenant();

                    PersonalAccessToken::where('tokenable_type', \App\Models\Empresa::class)
                        ->where('tokenable_id', $empresa->id)
                        ->where('name', 'ecommerce-api')
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
            ->where('name', 'ecommerce-api')
            ->latest()
            ->first();

        return [
            'empresa'        => $empresa,
            'baseUrl'        => 'https://erp.mashaec.net/api/ecommerce/' . $empresa->slug,
            'tieneToken'     => (bool) $token,
            'tokenCreadoEn'  => $token?->created_at?->format('d/m/Y H:i'),
            'tokenUsadoEn'   => $token?->last_used_at?->format('d/m/Y H:i') ?? 'Nunca',
            'newToken'       => $this->newToken,
        ];
    }
}
