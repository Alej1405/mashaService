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
    'paginas' => ['inicio', 'desarrollo', 'fotografia', 'laboratorio', 'erp', 'erp-modulos', 'herramientas', 'microservicios'],

    /*
    | Secciones de cada pagina. Un bloque que dice su seccion reemplaza el
    | contenido por defecto de esa parte de la web; un bloque sin seccion se
    | pinta al final de la pagina, tal cual. La clave la lee el front.
    */
    'secciones' => [
        'inicio' => [
            'apertura'    => 'Botones del hero (encabezado = principal; tarjeta = secundario)',
            'desarrollo'  => '01 · Desarrollo',
            'fotografia'  => '02 · Fotografía (pies de foto)',
            'laboratorio' => '03 · Laboratorio (enlaces de salida)',
            'diagnostico' => 'Autodiagnóstico',
            'contacto'    => 'Contacto',
            'pie'         => 'Pie de página (todas las páginas)',
        ],
        'desarrollo' => [
            'incluye'    => 'Incluye',
            'no-incluye' => 'No incluye',
            'trabajos'   => 'Trabajos',
            'precios'    => 'Qué cuesta',
            'cierre'     => 'Cierre',
        ],
        'fotografia' => [
            'galeria' => 'Galería (pie de la galería)',
            'tipos'   => 'Qué fotografiamos',
            'limite'  => 'Lo que no hacemos',
            'enlaces' => 'Dónde ver más',
            'precios' => 'Qué cuesta',
            'cierre'  => 'Cierre',
        ],
        'erp' => [
            'portada' => 'Botones bajo el título (encabezado = principal; tarjeta = secundario)',
            'modulos' => 'Índice de módulos',
            'demo'    => 'Panel de prueba',
            'planes'  => 'Qué plan te toca',
            'cierre'  => 'Cierre',
        ],
        'erp-modulos' => [
            'portada'     => 'Portada',
            'recorrido'   => 'El dato se escribe una vez',
            'modulos'     => 'Módulos y automatismos',
            'paneles'     => 'Cada oficio, su pantalla',
            'datos'       => 'Tu información, en claro',
            'comparacion' => 'Comparación',
            'edita'       => 'Qué cambias tú',
            'limites'     => 'Qué no hace el panel',
            'equipos'     => 'Equipos de marketing',
            'planes'      => 'Planes',
        ],
        'laboratorio' => [
            'accesos'     => 'Herramientas y microservicios',
            'suscripcion' => 'Suscripción',
        ],
        'herramientas'   => [],
        'microservicios' => [
            'servicios' => 'Servicios',
            'pruebas'   => 'Llámalos tú mismo',
        ],
    ],

    /*
    | Despliegue continuo. Al guardar contenido del sitio propio, el ERP pide
    | a GitHub que vuelva a compilar y publicar la web. Sin token no hace nada.
    */
    'github' => [
        'repo'    => env('SITIO_GITHUB_REPO', 'Alej1405/mashaec.net'),
        'token'   => env('SITIO_GITHUB_TOKEN'),
        'espera'  => (int) env('SITIO_PUBLICAR_ESPERA', 60),
    ],

    // Servicios a los que puede pertenecer un caso del portafolio.
    'servicios' => ['desarrollo', 'fotografia'],

];
