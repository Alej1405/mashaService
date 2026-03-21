<?php

namespace App\Filament\App\Resources\EmpresaUserResource\Pages;

use App\Filament\App\Resources\EmpresaUserResource;
use App\Jobs\SendSmtpMailJob;
use App\Services\MailingService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
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
        $service = new MailingService($empresa);

        if (! $service->hasSmtp() && ! $service->isConfigured()) {
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

        SendSmtpMailJob::dispatch(
            $empresa->id,
            $user->email,
            $user->name,
            "Bienvenido/a a {$empresa->name}",
            $html,
        );
    }
}
