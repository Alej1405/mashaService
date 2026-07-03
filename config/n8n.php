<?php

/*
|--------------------------------------------------------------------------
| Integración n8n — superficie de API aislada
|--------------------------------------------------------------------------
| Todo el lado n8n vive detrás de estas llaves. Si algo falla, poner
| N8N_API_ENABLED=false suspende SOLO la integración n8n; el resto del ERP
| sigue funcionando. Nada aquí debe quedar hardcodeado en el código.
*/

return [

    // Kill-switch global. En false, /api/n8n/* responde 503 y nada entra.
    'enabled' => (bool) env('N8N_API_ENABLED', false),

    // Secreto compartido que SOLO conoce n8n. Se envía en el header X-N8N-Secret.
    // Es el control principal de "esta petición viene de n8n" (la IP no es fiable
    // porque trustProxies está en '*').
    'secret' => env('N8N_API_SECRET'),

    // Vida de la sesión de Telegram en minutos (tras login por usuario/clave).
    'session_ttl' => (int) env('N8N_SESSION_TTL', 720),

    // Límite de peticiones por minuto por chat/IP sobre la superficie n8n.
    'rate_limit' => (int) env('N8N_RATE_LIMIT', 60),

    // Endurecimiento opcional: lista de IPs permitidas (coma-separadas). Vacío = no
    // se aplica. NO es el control principal (ver nota del secreto).
    'allowed_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('N8N_ALLOWED_IPS', ''))
    ))),
];
