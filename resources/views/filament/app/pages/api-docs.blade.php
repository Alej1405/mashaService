<x-filament-panels::page>

    {{-- Token activo / nuevo token --}}
    @if ($newToken)
        <x-filament::section>
            <x-slot name="heading">Token generado — cópialo ahora</x-slot>
            <x-slot name="description">Este token solo se muestra una vez. Guárdalo en un lugar seguro.</x-slot>

            <div class="flex items-center gap-3 rounded-lg bg-warning-50 dark:bg-warning-950 border border-warning-300 dark:border-warning-700 p-4">
                <code class="flex-1 break-all font-mono text-sm text-warning-800 dark:text-warning-200 select-all">
                    {{ $newToken }}
                </code>
                <button
                    onclick="navigator.clipboard.writeText('{{ $newToken }}'); this.textContent='¡Copiado!';"
                    class="shrink-0 rounded px-3 py-1 text-xs font-semibold bg-warning-500 text-white hover:bg-warning-600 transition"
                >
                    Copiar
                </button>
            </div>
        </x-filament::section>
    @elseif ($tieneToken)
        <x-filament::section>
            <x-slot name="heading">Token activo</x-slot>
            <div class="flex flex-wrap gap-6 text-sm text-gray-600 dark:text-gray-400">
                <span><span class="font-semibold text-gray-800 dark:text-gray-200">Creado:</span> {{ $tokenCreadoEn }}</span>
                <span><span class="font-semibold text-gray-800 dark:text-gray-200">Último uso:</span> {{ $tokenUsadoEn }}</span>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">Sin token</x-slot>
            <p class="text-sm text-gray-500">Aún no has generado un token. Haz clic en <strong>Generar nuevo token</strong> para empezar.</p>
        </x-filament::section>
    @endif

    {{-- Uso del token --}}
    <x-filament::section>
        <x-slot name="heading">Autenticación</x-slot>
        <x-slot name="description">Incluye el token en el header de cada petición.</x-slot>

        <div class="space-y-3">
            <p class="text-sm text-gray-600 dark:text-gray-400">Todas las peticiones deben incluir:</p>
            <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 overflow-x-auto">Authorization: Bearer &lt;tu-token&gt;</pre>
        </div>
    </x-filament::section>

    {{-- Endpoint principal --}}
    <x-filament::section>
        <x-slot name="heading">Endpoint principal (recomendado)</x-slot>
        <x-slot name="description">Devuelve todo el contenido activo en una sola llamada.</x-slot>

        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <span class="rounded px-2 py-0.5 text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">GET</span>
                <code class="text-sm font-mono text-gray-800 dark:text-gray-200">{{ $baseUrl }}/all</code>
            </div>

            <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 overflow-x-auto">{
  "empresa":     { "nombre", "logo" },
  "hero":        { "titulo", "subtitulo", "descripcion", "imagen", "cta_texto", "cta_url" },
  "nosotros":    { "titulo", "cuerpo", "imagen" },
  "servicios":   [ { "titulo", "descripcion", "icono", "imagen" } ],
  "equipo":      [ { "nombre", "cargo", "bio", "foto" } ],
  "clientes":    [ { "nombre", "logo", "url" } ],
  "testimonios": [ { "autor_nombre", "autor_cargo", "autor_empresa", "autor_foto", "contenido", "estrellas" } ],
  "faq":         [ { "pregunta", "respuesta" } ],
  "contacto":    { "direccion", "telefono", "email", "whatsapp", "mapa_embed", "redes": { "facebook", "instagram", "linkedin", "youtube", "tiktok" } },
  "noticias":    [ { "slug", "titulo", "imagen", "publicado_en", "extracto" } ]
}</pre>
        </div>
    </x-filament::section>

    {{-- Endpoints individuales --}}
    <x-filament::section>
        <x-slot name="heading">Endpoints individuales</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-gray-500 dark:text-gray-400">
                        <th class="pb-2 pr-4 font-semibold">Método</th>
                        <th class="pb-2 pr-4 font-semibold">Endpoint</th>
                        <th class="pb-2 font-semibold">Devuelve</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ([
                        ['hero',          'Sección Hero'],
                        ['about',         'Sección Nosotros'],
                        ['services',      'Array de servicios'],
                        ['team',          'Array de equipo'],
                        ['clients',       'Array de logos de clientes'],
                        ['testimonials',  'Array de testimonios'],
                        ['faq',           'Array de preguntas frecuentes'],
                        ['contact',       'Datos de contacto y redes sociales'],
                        ['posts',         'Array de noticias (con extracto)'],
                        ['posts/{slug}',  'Noticia completa'],
                    ] as [$path, $desc])
                        <tr>
                            <td class="py-2 pr-4">
                                <span class="rounded px-2 py-0.5 text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">GET</span>
                            </td>
                            <td class="py-2 pr-4">
                                <code class="font-mono text-gray-800 dark:text-gray-200">{{ $baseUrl }}/{{ $path }}</code>
                            </td>
                            <td class="py-2 text-gray-600 dark:text-gray-400">{{ $desc }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

    {{-- Ejemplo React --}}
    <x-filament::section>
        <x-slot name="heading">Ejemplo en React + TypeScript</x-slot>

        <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 overflow-x-auto">// hooks/useCms.ts
const CMS_BASE = '{{ config('app.url') }}/api/cms';
const TOKEN    = import.meta.env.VITE_CMS_TOKEN; // guarda el token en .env

export async function fetchCms(slug: string) {
  const res = await fetch(`${CMS_BASE}/${slug}/all`, {
    headers: { Authorization: `Bearer ${TOKEN}` },
  });
  if (!res.ok) throw new Error('Error al cargar el CMS');
  return res.json();
}

// .env (en el proyecto React)
VITE_CMS_TOKEN=&lt;tu-token&gt;</pre>
    </x-filament::section>

</x-filament-panels::page>
