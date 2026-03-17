<?php

namespace App\Filament\Pages\Tenancy;

use App\Helpers\PlanHelper;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;

class EditEmpresaProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Configuración de la Empresa';
    }

    public function form(Form $form): Form
    {
        $plan    = PlanHelper::current();
        $label   = PlanHelper::label($plan);

        $badgeHtml = "<span style='display:inline-block;padding:2px 10px;border-radius:9999px;font-size:0.75rem;font-weight:700;
            background:" . match($plan) {
                'enterprise' => '#fef3c7',
                'pro'        => '#dbeafe',
                default      => '#f3f4f6',
            } . ";color:" . match($plan) {
                'enterprise' => '#92400e',
                'pro'        => '#1e40af',
                default      => '#374151',
            } . ";'>{$label}</span>";

        $empresa       = Filament::getTenant();
        $mailingActivo = ! empty($empresa->mailgun_api_key) && ! empty($empresa->mailgun_domain);

        $mailingHtml = $mailingActivo
            ? "<span style='display:inline-flex;align-items:center;gap:6px;color:#059669;font-weight:600;font-size:0.875rem;'>
                <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' style='width:16px;height:16px;'><path stroke-linecap='round' stroke-linejoin='round' d='M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'/></svg>
                Servicio de correo activo
              </span>"
            : "<span style='display:inline-flex;align-items:center;gap:6px;color:#d97706;font-weight:600;font-size:0.875rem;'>
                <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' style='width:16px;height:16px;'><path stroke-linecap='round' stroke-linejoin='round' d='M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'/></svg>
                Servicio de correo no configurado — contacta al administrador
              </span>";

        return $form
            ->schema([
                Section::make('Plan de Suscripción')
                    ->description('Tu plan actual determina los módulos disponibles.')
                    ->schema([
                        Placeholder::make('plan_badge')
                            ->label('Plan activo')
                            ->content(new HtmlString($badgeHtml)),
                    ])
                    ->collapsible(),

                Section::make('Servicio de correo')
                    ->description('El estado del servicio de envío de correos para tu empresa.')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        Placeholder::make('mailing_estado')
                            ->label('Estado')
                            ->content(new HtmlString($mailingHtml)),
                    ]),
            ]);
    }
}
