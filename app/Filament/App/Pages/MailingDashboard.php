<?php

namespace App\Filament\App\Pages;

use App\Helpers\PlanHelper;
use App\Models\SupportTicket;
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
        return \App\Helpers\PlanHelper::hasModule('marketing');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getViewData(): array
    {
        $empresa        = Filament::getTenant();
        $servicioActivo = (bool) $empresa->servicio_mailing_activo;

        if (! $servicioActivo) {
            return [
                'empresa'        => $empresa,
                'servicio_activo' => false,
                'configurado'    => false,
                'stats'          => [],
                'events'         => [],
                'quota'          => [],
                'plan'           => PlanHelper::current(),
                'planLabel'      => PlanHelper::label(PlanHelper::current()),
            ];
        }

        $plan        = PlanHelper::current();
        $service     = new MailingService($empresa);
        $configurado = $service->isConfigured();

        return [
            'empresa'        => $empresa,
            'servicio_activo' => true,
            'configurado'    => $configurado,
            'stats'          => $service->getStats(30),
            'events'         => $configurado ? $service->getEvents(15) : [],
            'quota'          => $service->getQuotaInfo(),
            'plan'           => $plan,
            'planLabel'      => PlanHelper::label($plan),
        ];
    }

    public function solicitarAmpliarPlan(): void
    {
        $empresa = Filament::getTenant();

        $existente = SupportTicket::where('empresa_id', $empresa->id)
            ->where('asunto', 'like', '%Mailing%')
            ->whereIn('status', ['abierto', 'en_proceso'])
            ->exists();

        if ($existente) {
            Notification::make()
                ->title('Solicitud ya registrada')
                ->body('Ya tienes un ticket de soporte activo para activar el servicio de Mailing. El equipo lo está gestionando.')
                ->warning()
                ->send();

            return;
        }

        SupportTicket::create([
            'empresa_id'  => $empresa->id,
            'user_id'     => Auth::id(),
            'asunto'      => 'Solicitud de activación del servicio de Mailing',
            'descripcion' => "La empresa \"{$empresa->name}\" solicita activar el módulo de Mailing para gestionar campañas de correo masivo, contactos y plantillas. Por favor contactar para gestionar la ampliación del plan.",
            'prioridad'   => 'media',
            'status'      => 'abierto',
        ]);

        Notification::make()
            ->title('Solicitud enviada a soporte')
            ->body('El equipo de soporte se pondrá en contacto contigo para activar el servicio de Mailing.')
            ->success()
            ->send();
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
