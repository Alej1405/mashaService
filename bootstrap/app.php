<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cloudflare y proxies: leer X-Forwarded-Proto para detectar HTTPS correctamente
        $middleware->trustProxies(
            at: '*',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        // Detectar teléfonos y redirigir al portal móvil
        $middleware->web(append: [
            \App\Http\Middleware\RedirectMobileToPortal::class,
        ]);

        // Alias para autenticación del portal móvil
        $middleware->alias([
            'mobile.auth' => \App\Http\Middleware\MobileAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Livewire no dice qué página reventó.
         *
         * Cuando el navegador manda un snapshot que ya no casa con la clase
         * PHP, el error sale como un TypeError pelado dentro de vendor/ y el
         * log solo guarda el stack: ni la URL, ni el componente, ni el
         * usuario. Así no se diagnostica nada. Esto le añade el contexto.
         */
        $exceptions->context(function (): array {
            if (! request()->is('livewire/*')) {
                return [];
            }

            $componentes = collect(request()->input('components', []))
                ->map(fn ($c) => data_get($c, 'snapshot.memo.name'))
                ->filter()
                ->values()
                ->all();

            return [
                'livewire_componentes' => $componentes,
                'pagina'    => request()->header('referer'),
                'usuario'   => auth()->id(),
                'navegador' => str(request()->userAgent())->limit(120)->value(),
            ];
        });
    })->create();
