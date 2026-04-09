<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Re-agrupa con --force para corregir los contactos que quedaron sin grupo
     * por el bug de chunk+offset en la migración anterior.
     */
    public function up(): void
    {
        Artisan::call('mailing:reagrupar', ['--force' => true], new \Symfony\Component\Console\Output\NullOutput());
    }

    public function down(): void {}
};
