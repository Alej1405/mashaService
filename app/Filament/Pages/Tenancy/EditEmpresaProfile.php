<?php

namespace App\Filament\Pages\Tenancy;

use App\Helpers\PlanHelper;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;

class EditEmpresaProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Configuración de la Empresa';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_smtp')
                ->label('Verificar conexión SMTP')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->modalHeading('Verificar conexión SMTP')
                ->modalDescription('Se enviará un correo de prueba usando las credenciales SMTP guardadas de la empresa.')
                ->modalWidth('md')
                ->form([
                    TextInput::make('email_destino')
                        ->label('Enviar correo de prueba a')
                        ->email()
                        ->required()
                        ->default(fn () => auth()->user()->email)
                        ->helperText('Por defecto se usa tu correo. Puedes cambiarlo.'),
                ])
                ->action(function (array $data): void {
                    $empresa = Filament::getTenant();

                    if (empty($empresa->smtp_host) || empty($empresa->smtp_username)) {
                        Notification::make()
                            ->title('SMTP no configurado')
                            ->body('Completa los campos de servidor SMTP antes de verificar.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $fromEmail = ! empty($empresa->smtp_from_email) ? $empresa->smtp_from_email : $empresa->smtp_username;
                    $fromName  = ! empty($empresa->smtp_from_name)  ? $empresa->smtp_from_name  : $empresa->name;

                    $html = '<p>Este es un correo de prueba enviado desde <strong>' . e($empresa->name) . '</strong> '
                          . 'para verificar que las credenciales SMTP están correctamente configuradas. ✅</p>'
                          . '<p style="color:#64748b;font-size:13px;">Servidor: ' . e($empresa->smtp_host) . ':' . ($empresa->smtp_port ?? 587) . '</p>';

                    config([
                        'mail.mailers.empresa_smtp' => [
                            'transport'  => 'smtp',
                            'host'       => $empresa->smtp_host,
                            'port'       => $empresa->smtp_port ?? 587,
                            'encryption' => $empresa->smtp_encryption ?? 'tls',
                            'username'   => $empresa->smtp_username,
                            'password'   => $empresa->smtp_password,
                            'timeout'    => 10,
                        ],
                    ]);

                    $host       = $empresa->smtp_host;
                    $port       = $empresa->smtp_port ?? 587;
                    $encryption = $empresa->smtp_encryption ?? 'tls';
                    $address    = ($encryption === 'ssl' ? 'ssl://' : '') . $host;

                    $socket = @fsockopen($address, $port, $errno, $errstr, 5);

                    if (! $socket) {
                        Notification::make()
                            ->title('No se puede conectar al servidor SMTP')
                            ->body("Puerto {$port} inaccesible en {$host}: {$errstr}")
                            ->danger()
                            ->persistent()
                            ->send();
                        return;
                    }

                    fclose($socket);

                    \App\Jobs\SendSmtpMailJob::dispatch(
                        [
                            'transport'  => 'smtp',
                            'host'       => $host,
                            'port'       => $port,
                            'encryption' => $encryption,
                            'username'   => $empresa->smtp_username,
                            'password'   => $empresa->smtp_password,
                            'timeout'    => 15,
                        ],
                        $data['email_destino'],
                        '',
                        'Prueba de conexión SMTP — ' . $empresa->name,
                        $html,
                        $fromEmail,
                        $fromName,
                    );

                    Notification::make()
                        ->title('Servidor SMTP alcanzable')
                        ->body('El correo de prueba fue enviado a la cola — revisa tu bandeja en unos segundos.')
                        ->success()
                        ->send();
                }),
        ];
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
                // ── Plan de suscripción ────────────────────────────────────
                Section::make('Plan de Suscripción')
                    ->description('Tu plan actual determina los módulos disponibles.')
                    ->schema([
                        Placeholder::make('plan_badge')
                            ->label('Plan activo')
                            ->content(new HtmlString($badgeHtml)),
                    ])
                    ->collapsible(),

                // ── Identidad de la empresa ────────────────────────────────
                Section::make('Identidad de la empresa')
                    ->description('El logo aparecerá en tu dashboard y en los correos que envíes.')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo de la empresa')
                            ->image()
                            ->disk('public')
                            ->directory('logos')
                            ->imagePreviewHeight('80')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'])
                            ->helperText('PNG, JPG, SVG o WebP. Máximo 2 MB. Recomendado: fondo transparente.')
                            ->columnSpanFull(),
                    ]),

                // ── Servicio de correo Mailgun ─────────────────────────────
                Section::make('Servicio de correo')
                    ->description('El estado del servicio de envío masivo (Mailgun).')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        Placeholder::make('mailing_estado')
                            ->label('Estado')
                            ->content(new HtmlString($mailingHtml)),
                    ]),

                // ── SMTP personalizado ─────────────────────────────────────
                Section::make('Correo SMTP personalizado')
                    ->description('Configura un servidor SMTP propio para notificaciones. Si no se configura, se usará el servicio por defecto del sistema.')
                    ->icon('heroicon-o-server')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('smtp_host')
                                ->label('Servidor SMTP')
                                ->placeholder('smtp.gmail.com')
                                ->maxLength(255),

                            TextInput::make('smtp_port')
                                ->label('Puerto')
                                ->numeric()
                                ->placeholder('587')
                                ->minValue(1)
                                ->maxValue(65535),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('smtp_username')
                                ->label('Usuario / Email')
                                ->placeholder('tu@correo.com')
                                ->maxLength(255),

                            TextInput::make('smtp_password')
                                ->label('Contraseña')
                                ->password()
                                ->revealable()
                                ->maxLength(255),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('smtp_encryption')
                                ->label('Cifrado')
                                ->options([
                                    'tls'  => 'TLS (recomendado)',
                                    'ssl'  => 'SSL',
                                    'none' => 'Sin cifrado',
                                ])
                                ->default('tls'),

                            TextInput::make('smtp_from_email')
                                ->label('Correo de origen')
                                ->email()
                                ->placeholder('noreply@miempresa.com')
                                ->maxLength(255),
                        ]),

                        TextInput::make('smtp_from_name')
                            ->label('Nombre del remitente')
                            ->placeholder('Mi Empresa')
                            ->maxLength(150)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
