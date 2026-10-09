<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pide a GitHub que vuelva a compilar y publicar mashaec.net.
 *
 * El sitio es estático: lo que se guarda en /admin no se ve hasta compilar.
 * Se encola con espera y es único, así que diez cambios seguidos en el panel
 * terminan en un solo despliegue con todo incluido.
 */
class PublicarSitio implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function uniqueFor(): int
    {
        return config('sitio.github.espera') + 30;
    }

    public function handle(): void
    {
        $token = config('sitio.github.token');

        if (! $token) {
            return;
        }

        $respuesta = Http::withToken($token)
            ->acceptJson()
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
            ->post('https://api.github.com/repos/' . config('sitio.github.repo') . '/dispatches', [
                'event_type' => 'contenido-sitio',
            ]);

        if ($respuesta->failed()) {
            Log::warning('No se pudo pedir el despliegue del sitio', ['estado' => $respuesta->status()]);
            $respuesta->throw();
        }
    }
}
