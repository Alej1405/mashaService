<?php

namespace App\Filament\Pages\Tenancy;

use App\Helpers\PlanHelper;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;
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
        $color   = PlanHelper::color($plan);
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

        return $form
            ->schema([
                // ── Información del plan ────────────────────────────────────
                Section::make('Plan de Suscripción')
                    ->description('Tu plan actual determina los módulos disponibles.')
                    ->schema([
                        Placeholder::make('plan_badge')
                            ->label('Plan activo')
                            ->content(new HtmlString($badgeHtml)),
                    ])
                    ->collapsible(),

                // ── Integración Mailgun ─────────────────────────────────────
                Section::make('Integración Mailgun')
                    ->description('Configura tus credenciales de Mailgun para envío de correos desde esta empresa.')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        TextInput::make('mailgun_api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->placeholder('key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                            ->helperText('Encuéntrala en mailgun.com → Account → API Keys.')
                            ->maxLength(255),

                        TextInput::make('mailgun_domain')
                            ->label('Dominio')
                            ->placeholder('mg.tudominio.com')
                            ->helperText('El dominio verificado en tu cuenta de Mailgun.')
                            ->maxLength(255),

                        TextInput::make('mailgun_from_email')
                            ->label('Email de origen')
                            ->email()
                            ->placeholder('no-reply@tudominio.com')
                            ->maxLength(255),

                        TextInput::make('mailgun_from_name')
                            ->label('Nombre de origen')
                            ->placeholder('Mi Empresa')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }
}
