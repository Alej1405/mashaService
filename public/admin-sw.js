/*
 * Service worker del panel /admin instalado como app.
 *
 * No guarda nada: el panel muestra datos de clientes y de la empresa, y una
 * copia en el teléfono podría quedar vieja o a la vista de otro. Todo va a la
 * red; si no hay conexión, se muestra un aviso en vez de una pantalla rota.
 */
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (e) => e.waitUntil(self.clients.claim()));

self.addEventListener('fetch', (e) => {
  if (e.request.mode !== 'navigate') return;

  e.respondWith(
    fetch(e.request).catch(() =>
      new Response(
        '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
          '<title>Sin conexión</title><body style="font-family:system-ui;padding:32px;color:#171b1b">' +
          '<h1 style="font-size:20px">Sin conexión</h1><p>El panel necesita internet. Revisa tu conexión y vuelve a intentar.</p>' +
          '<button onclick="location.reload()" style="margin-top:16px;padding:12px 20px;border:0;border-radius:8px;background:#171b1b;color:#fff;font-size:15px">Reintentar</button>',
        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
      )
    )
  );
});
