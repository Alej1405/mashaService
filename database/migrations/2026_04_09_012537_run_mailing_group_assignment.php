<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Al ejecutar php artisan migrate en producción, agrupa automáticamente
     * todos los contactos sin grupo asignado en grupos de 1.500.
     * El comando usa chunk(500) → memoria constante sin importar el volumen.
     */
    public function up(): void
    {
        Artisan::call('mailing:reagrupar', [], new \Symfony\Component\Console\Output\NullOutput());
    }

    public function down(): void
    {
        // No hay rollback para agrupaciones
    }
};
