<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Vuelve a publicar mashaec.net. El despliegue lo corre después de migrar:
// si la web se compila antes que el ERP, sale sin los datos nuevos.
Artisan::command('sitio:publicar', function () {
    \App\Jobs\PublicarSitio::dispatch()->delay(now()->addSeconds(30));
    $this->info('Publicación de mashaec.net pedida.');
})->purpose('Pide a GitHub que vuelva a compilar y publicar mashaec.net');
