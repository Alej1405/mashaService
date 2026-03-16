<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@mashaec.net'],
            [
                'name' => 'Admin Mashaec',
                'password' => Hash::make('password'), // Recomiendo cambiarla después
            ]
        );

        $admin->assignRole('super_admin');
    }
}
