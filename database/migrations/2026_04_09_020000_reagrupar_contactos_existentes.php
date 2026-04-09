<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Reagrupa todos los contactos que quedaron sin grupo en producción.
     * Se ejecuta una sola vez al hacer migrate en el siguiente deploy.
     */
    public function up(): void
    {
        Artisan::call('mailing:reagrupar', [], new \Symfony\Component\Console\Output\NullOutput());
    }

    public function down(): void {}
};
