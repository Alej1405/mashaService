<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El repositorio de cada caso del portafolio.
 *
 * Para quien contrata desarrollo, el código abierto vale más que una captura:
 * es lo único que demuestra cómo trabajamos. El campo admite nulo porque no
 * todo proyecto es público.
 */
return new class extends Migration
{
    public function up(): void
    {
        // La columna se añadió a mano en producción antes de que esta
        // migración llegara al repositorio. Sin esta comprobación, el
        // despliegue se cae con «column already exists» y no publica nada.
        if (Schema::hasColumn('sitio_casos', 'enlace_repo')) {
            return;
        }

        Schema::table('sitio_casos', function (Blueprint $t) {
            $t->string('enlace_repo')->nullable()->after('enlace_sitio');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sitio_casos', 'enlace_repo')) {
            return;
        }

        Schema::table('sitio_casos', fn (Blueprint $t) => $t->dropColumn('enlace_repo'));
    }
};
