<?php

namespace App\Filament\App\Pages;

use App\Helpers\PlanHelper;
use App\Services\MailingService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MailingDashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Mailing';
    protected static ?string $navigationGroup = 'Mailing';
    protected static ?string $title           = 'Dashboard de Mailing';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.app.pages.mailing-dashboard';

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'basic';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getViewData(): array
    {
        $empresa     = Filament::getTenant();
        $plan        = PlanHelper::current();
        $service     = new MailingService($empresa);
        $configurado = $service->isConfigured();

        return [
            'empresa'     => $empresa,
            'configurado' => $configurado,
            'stats'       => $configurado ? $service->getStats(30) : [],
            'events'      => $configurado ? $service->getEvents(15) : [],
            'plan'        => $plan,
            'planLabel'   => PlanHelper::label($plan),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testEmail')
                ->label('Enviar prueba')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Enviar correo de prueba')
                ->modalDescription(fn () => 'Se enviará un correo de prueba a: ' . Auth::user()?->email)
                ->modalSubmitActionLabel('Enviar')
                ->action(function () {
                    $empresa = Filament::getTenant();
                    $user    = Auth::user();
                    $result  = (new MailingService($empresa))->sendTestEmail($user->email, $user->name);

                    Notification::make()
                        ->title($result['success'] ? 'Correo enviado' : 'Error al enviar')
                        ->body($result['message'])
                        ->{$result['success'] ? 'success' : 'danger'}()
                        ->send();
                })
                ->visible(fn () => (new MailingService(Filament::getTenant()))->isConfigured()),

            Action::make('verificar')
                ->label('Verificar conexión')
                ->icon('heroicon-o-signal')
                ->color('gray')
                ->action(function () {
                    $result = (new MailingService(Filament::getTenant()))->testConnection();

                    Notification::make()
                        ->title($result['success'] ? 'Conexión exitosa' : 'Error de conexión')
                        ->body($result['message'])
                        ->{$result['success'] ? 'success' : 'danger'}()
                        ->send();
                }),
        ];
    }
}
