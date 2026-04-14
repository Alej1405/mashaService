<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\StoreCustomer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreatePortalSuperAdmin extends Command
{
    protected $signature = 'portal:create-superadmin
                            {slug : Slug de la empresa}
                            {--email=admin@masheec.net : Correo del super admin}
                            {--password=12345678 : Contraseña del super admin}
                            {--nombre=Admin : Nombre}';

    protected $description = 'Crea un cliente super admin en el portal de clientes para una empresa';

    public function handle(): int
    {
        $slug = $this->argument('slug');

        $empresa = Empresa::where('slug', $slug)->first();

        if (! $empresa) {
            $this->error("No se encontró ninguna empresa con el slug '{$slug}'.");
            return self::FAILURE;
        }

        $email    = $this->option('email');
        $password = $this->option('password');
        $nombre   = $this->option('nombre');

        $customer = StoreCustomer::withoutGlobalScopes()
            ->updateOrCreate(
                ['empresa_id' => $empresa->id, 'email' => $email],
                [
                    'nombre'        => $nombre,
                    'password'      => Hash::make($password),
                    'activo'        => true,
                    'is_super_admin' => true,
                ]
            );

        $this->info("Super admin del portal creado/actualizado:");
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Empresa', $empresa->name . ' (' . $empresa->slug . ')'],
                ['Correo',  $customer->email],
                ['Nombre',  $customer->nombre],
                ['URL portal', url('/tienda/' . $empresa->slug . '/login')],
            ]
        );

        return self::SUCCESS;
    }
}
