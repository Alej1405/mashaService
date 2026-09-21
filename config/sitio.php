<?php

/*
|--------------------------------------------------------------------------
| Sitio propio (MashaCorp)
|--------------------------------------------------------------------------
| El sitio de MashaCorp corre sobre el mismo ERP que los sitios de los
| clientes: es una Empresa mas en la base de datos. Lo unico que cambia es
| donde se administra — en el panel /admin y no en /cms/{slug} — y que su
| contenido se sirve por una API pensada para el front en Svelte.
|
| Cambiar de empresa propia es cambiar SITIO_EMPRESA_SLUG en el .env.
*/

return [

    // Slug de la Empresa que representa al sitio propio.
    'empresa_slug' => env('SITIO_EMPRESA_SLUG', 'masha-corp-sas'),

    // Segundos que el payload vive en el cache del servidor.
    'ttl' => (int) env('SITIO_TTL', 600),

    // Segundos que el navegador y el CDN pueden reusar la respuesta
    // antes de revalidar con el ETag.
    'max_age' => (int) env('SITIO_MAX_AGE', 60),

    // Paginas que el front resuelve como secciones autonomas con URL propia.
    'paginas' => ['inicio', 'desarrollo', 'fotografia', 'laboratorio', 'proceso'],

    // Servicios a los que puede pertenecer un caso del portafolio.
    'servicios' => ['desarrollo', 'fotografia'],

];
