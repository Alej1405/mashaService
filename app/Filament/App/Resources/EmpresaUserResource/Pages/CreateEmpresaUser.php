<?php

namespace App\Filament\App\Resources\EmpresaUserResource\Pages;

use App\Filament\App\Resources\EmpresaUserResource;
use App\Mail\EmpresaPlainMail;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

class CreateEmpresaUser extends CreateRecord
{
    protected static string $resource = EmpresaUserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Extrae el rol antes de crear, asigna empresa_id y luego sincroniza el rol.
     * Intenta enviar correo de bienvenida si la empresa tiene SMTP configurado.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $role          = $data['role'] ?? null;
        $plainPassword = $data['password'] ?? null;
        unset($data['role']);

        $data['empresa_id'] = Filament::getTenant()->id;

        $record = static::getModel()::create($data);

        if ($role) {
            $record->syncRoles([$role]);
        }

        if ($plainPassword) {
            $this->sendWelcomeEmail($record, $role, $plainPassword);
        }

        return $record;
    }

    private function sendWelcomeEmail(Model $user, ?string $role, string $plainPassword): void
    {
        $empresa = Filament::getTenant();

        if (empty($empresa->smtp_host) || empty($empresa->smtp_username)) {
            return;
        }

        $rolLabels = [
            'admin_empresa' => 'Administrador',
            'contador'      => 'Contador',
            'inventario'    => 'Encargado de inventario',
            'marketing'     => 'Marketing',
        ];

        $html = View::make('emails.bienvenida-usuario', [
            'empresa'   => $empresa,
            'usuario'   => $user,
            'password'  => $plainPassword,
            'rolLabel'  => $rolLabels[$role] ?? ucfirst($role ?? ''),
            'loginUrl'  => url('/app/' . $empresa->slug),
        ])->render();

        $fromEmail = ! empty($empresa->smtp_from_email) ? $empresa->smtp_from_email : $empresa->smtp_username;
        $fromName  = ! empty($empresa->smtp_from_name)  ? $empresa->smtp_from_name  : $empresa->name;

        config([
            'mail.mailers.empresa_smtp' => [
                'transport'  => 'smtp',
                'host'       => $empresa->smtp_host,
                'port'       => $empresa->smtp_port ?? 587,
                'encryption' => $empresa->smtp_encryption ?? 'tls',
                'username'   => $empresa->smtp_username,
                'password'   => $empresa->smtp_password,
            ],
        ]);

        try {
            Mail::mailer('empresa_smtp')
                ->to($user->email, $user->name)
                ->send(new EmpresaPlainMail(
                    "Bienvenido/a a {$empresa->name}",
                    $html,
                    $fromEmail,
                    $fromName,
                ));
        } catch (\Exception $e) {
            Notification::make()
                ->title('Usuario creado, pero no se pudo enviar el correo de bienvenida')
                ->body($e->getMessage())
                ->warning()
                ->send();
        }
    }
}
