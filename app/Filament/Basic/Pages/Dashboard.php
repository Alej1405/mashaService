<?php

namespace App\Filament\Basic\Pages;

use App\Helpers\PlanHelper;
use App\Models\MailTemplate;
use App\Models\SupportTicket;
use App\Services\MailingService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Inicio';
    protected static ?string $title           = 'Dashboard';
    protected static ?int    $navigationSort  = -2;
    protected static string  $view            = 'filament.basic.pages.dashboard';

    public function getViewData(): array
    {
        $empresa = Filament::getTenant();
        $user    = auth()->user();
        $hora    = now()->hour;

        Carbon::setLocale('es');

        $saludo = match (true) {
            $hora >= 6 && $hora < 12  => 'Buenos días',
            $hora >= 12 && $hora < 19 => 'Buenas tardes',
            default                    => 'Buenas noches',
        };

        $servicioActivo = (bool) $empresa->servicio_mailing_activo;

        $plan       = PlanHelper::current();
        $features   = \App\Models\ServicePlan::where('key', $plan)->value('caracteristicas') ?? [];
        $badgeColor = match ($plan) {
            'basic'      => '#64748b',
            'pro'        => '#2563eb',
            'enterprise' => '#d97706',
            default      => '#64748b',
        };
        $badgeBg = match ($plan) {
            'basic'      => '#f1f5f9',
            'pro'        => '#eff6ff',
            'enterprise' => '#fffbeb',
            default      => '#f1f5f9',
        };

        $websiteUrl   = $empresa->website_url ?? null;
        $thumbnailUrl = $websiteUrl
            ? 'https://api.microlink.io/?url=' . urlencode($websiteUrl) . '&screenshot=true&meta=false&embed=screenshot.url'
            : null;

        $base = [
            'saludo'      => $saludo . ', ' . explode(' ', $user->name)[0],
            'fecha'       => ucfirst(now()->translatedFormat('l, d \d\e F \d\e Y')),
            'empresa'     => $empresa,
            'user'        => $user,
            'plan'        => $plan,
            'planLabel'   => PlanHelper::label($plan),
            'features'    => $features,
            'badgeColor'  => $badgeColor,
            'badgeBg'     => $badgeBg,
            'websiteUrl'  => $websiteUrl,
            'thumbnailUrl' => $thumbnailUrl,
        ];

        if (! $servicioActivo) {
            return array_merge($base, [
                'servicio_activo' => false,
                'configurado'     => false,
                'stats'           => [],
                'stats7'          => [],
                'events'          => [],
                'plantillas'      => 0,
            ]);
        }

        $service     = new MailingService($empresa);
        $configurado = $service->isConfigured();

        return array_merge($base, [
            'servicio_activo' => true,
            'configurado'     => $configurado,
            'stats'           => $configurado ? $service->getStats(30) : [],
            'stats7'          => $configurado ? $service->getStats(7)  : [],
            'events'          => $configurado ? $service->getEvents(8) : [],
            'plantillas'      => MailTemplate::count(),
        ]);
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
}
