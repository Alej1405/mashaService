# API del sitio propio

Contrato entre el ERP y el front del sitio de MashaCorp. Todo el contenido se
ingresa en `/admin` → grupo **Sitio MashaCorp**; el front solo lo muestra.

MashaCorp es una Empresa en la base para reusar el CMS, pero **no es un cliente**:
no aparece en el selector de empresas de ningún panel y no se puede abrir como
tenant. Se administra solo desde `/admin`.

- **Base:** `/api/sitio/{slug}/…` — `slug` es el de la empresa propia (`masha-corp-sas`,
  configurable en `SITIO_EMPRESA_SLUG`).
- **Autenticación:** `Authorization: Bearer {token}`, el mismo token del CMS.
  Se genera en `/cms/{slug}/api-docs`.
- **El token vive en el servidor de SvelteKit**, nunca en el navegador: todas
  las llamadas se hacen desde `+layout.server.ts` / `+page.server.ts`.

## Endpoints

| Método | Ruta | Devuelve |
|---|---|---|
| GET | `layout` | marca, navegación (superior, inferior, pie) y datos de contacto |
| GET | `inicio` | hero, fuerzas, casos, logos, testimonios, FAQ, SEO |
| GET | `paginas/{pagina}` | la sección + sus casos + sus precios + SEO |
| GET | `casos/{caso}` | un caso del portafolio con su galería |
| GET | `articulos` | índice del Laboratorio |
| GET | `articulos/{articulo}` | un artículo completo |
| POST | `mensajes` | guarda el formulario de un solo campo |

`{pagina}` ∈ `inicio`, `desarrollo`, `fotografia`, `laboratorio`, `proceso`
(configurable en `config/sitio.php`).

## Una petición por ruta

El layout pide `layout` una vez y cada página pide lo suyo completo. No hay
que encadenar llamadas por sección.

```ts
// src/routes/+layout.server.ts
export const load = async ({ fetch }) => ({
  layout: await (await fetch(`${API}/layout`, { headers })).json(),
});

// src/routes/desarrollo/+page.server.ts
export const load = async ({ fetch }) => ({
  ...(await (await fetch(`${API}/paginas/desarrollo`, { headers })).json()),
});
```

## Caché y ETag

Toda respuesta GET trae `ETag` y `Cache-Control: public, max-age=60`.

El ETag sale de un **sello** que cambia cuando se guarda contenido en el panel,
no del cuerpo de la respuesta. Mientras nadie edite, la revalidación responde
`304` sin cuerpo. Guardar cualquier registro del sitio renueva el sello y la
siguiente petición trae el contenido nuevo: no hay que esperar a que expire.

TTL del servidor y `max-age` se ajustan en `config/sitio.php`.

## Formas que conviene fijar en el front

```jsonc
// layout
{
  "empresa":  { "nombre": "MashaCorp", "slug": "mashacorp", "logo": "https://…" },
  "navegacion": {
    "superior": [{ "etiqueta": "Desarrollo", "ruta": "/desarrollo", "icono": null, "dispositivo": "ambos" }],
    "inferior": [{ "etiqueta": "Inicio", "ruta": "/", "icono": "casa", "dispositivo": "ambos" }],
    "pie":      []
  },
  "contacto": { "direccion": "…", "telefono": "…", "email": "…", "whatsapp": "…", "redes": {} }
}

// inicio
{
  "hero":    { "titulo": "…", "subtitulo": "…", "descripcion": "…", "imagen": "https://…", "cta_texto": "…", "cta_url": "…" },
  "fuerzas": [{ "slug": "desarrollo", "titulo": "…", "subtitulo": "…", "descripcion": "…", "imagen": "…", "ruta": "/desarrollo" }],
  "casos":   [/* casos completos, con galería: en el inicio se expanden sin navegar */],
  "logos":       [{ "nombre": "…", "logo": "…", "url": "…" }],
  "testimonios": [{ "autor_nombre": "…", "autor_cargo": "…", "contenido": "…", "estrellas": 5 }],
  "faq":         [{ "pregunta": "…", "respuesta": "…" }],
  "seo":         { "titulo": "…", "descripcion": "…", "og_imagen": "…", "robots": "index,follow", "json_ld": null }
}

// caso
{
  "slug": "…", "titulo": "…", "cliente": "…", "sector": "…", "servicio": "desarrollo",
  "resumen": "…", "problema": "…", "solucion": "…", "resultado": "…",
  "metricas": [{ "etiqueta": "…", "valor": "…" }],
  "enlace": "https://…", "destacado": true,
  "portada": { "url": "…", "ancho": 1600, "alto": 900 },
  "galeria": [{ "url": "…", "alt": "…", "ancho": 1600, "alto": 900 }]
}
```

Cada imagen viene con `ancho` y `alto` para reservar el espacio y que la página
no salte mientras carga.

## Reglas que el front no puede saltarse

- **Un caso solo sale si `publicable` está activo.** El permiso del cliente se
  marca en el panel; la API filtra, el front no decide.
- **El caso `destacado` viene primero** en la lista: es el que abre expandido.
- **Un artículo sin fecha de publicación no sale**, aunque esté activo.
- **Los precios pueden venir en `null`** (`precio_desde`): eso significa
  "a convenir", no un error.

## Formulario de contacto

```http
POST /api/sitio/{slug}/mensajes
{ "contacto": "correo o teléfono", "mensaje": "opcional", "origen": "/desarrollo" }
```

- `contacto` es el único campo obligatorio: se acepta un correo o un teléfono.
- `sitio_web` es una trampa para robots: debe ir vacío o no enviarse.
- Límite de 6 envíos por minuto y por IP. Se guarda el hash de la IP, nunca la IP.
- Los mensajes llegan a `/admin` → **Mensajes**, con contador de pendientes.

## Puesta en marcha

```bash
php artisan migrate
php artisan db:seed --class=SitioSeeder   # empresa, páginas y navegación base
```
