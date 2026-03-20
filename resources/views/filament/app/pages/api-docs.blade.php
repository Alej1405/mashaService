<x-filament-panels::page>

    {{-- Token activo / nuevo token --}}
    @if ($newToken)
        <x-filament::section>
            <x-slot name="heading">Token generado — cópialo ahora</x-slot>
            <x-slot name="description">Este token solo se muestra una vez. Guárdalo en un lugar seguro.</x-slot>
            <div class="flex items-center gap-3 rounded-lg bg-warning-50 dark:bg-warning-950 border border-warning-300 dark:border-warning-700 p-4">
                <code class="flex-1 break-all font-mono text-sm text-warning-800 dark:text-warning-200 select-all">{{ $newToken }}</code>
                <button
                    onclick="navigator.clipboard.writeText('{{ $newToken }}'); this.textContent='¡Copiado!';"
                    class="shrink-0 rounded px-3 py-1 text-xs font-semibold bg-warning-500 text-white hover:bg-warning-600 transition"
                >Copiar</button>
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

    {{-- Autenticación --}}
    <x-filament::section>
        <x-slot name="heading">Autenticación</x-slot>
        <x-slot name="description">Incluye el token en el header de cada petición.</x-slot>
        <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 overflow-x-auto">Authorization: Bearer &lt;tu-token&gt;</pre>
    </x-filament::section>

    {{-- Endpoint principal --}}
    <x-filament::section>
        <x-slot name="heading">Endpoint principal</x-slot>
        <x-slot name="description">Devuelve todo el contenido activo en una sola llamada.</x-slot>
        <div class="flex items-center gap-3 mb-4">
            <span class="rounded px-2 py-0.5 text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">GET</span>
            <code class="text-sm font-mono text-gray-800 dark:text-gray-200">{{ $baseUrl }}/all</code>
        </div>
    </x-filament::section>

    {{-- Esquema de tipos --}}
    <x-filament::section>
        <x-slot name="heading">Esquema de tipos (Zod)</x-slot>
        <x-slot name="description">Copia este schema directamente en tu proyecto React.</x-slot>

        <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 overflow-x-auto">import { z } from 'zod'

// ── Primitivos reutilizables ────────────────────────────────────────────
const nullable = &lt;T extends z.ZodTypeAny&gt;(s: T) => s.nullable()

// ── Secciones ───────────────────────────────────────────────────────────

export const EmpresaSchema = z.object({
  nombre: z.string(),
  logo:   nullable(z.string().url()),
})

export const HeroSchema = z.object({
  titulo:      z.string(),
  subtitulo:   nullable(z.string()),
  descripcion: nullable(z.string()),
  imagen:      nullable(z.string().url()),
  cta_texto:   nullable(z.string()),
  cta_url:     nullable(z.string().url()),
}).nullable()

export const NosotrosSchema = z.object({
  titulo:      z.string(),
  descripcion: nullable(z.string()),
  imagen:      nullable(z.string().url()),
  por_que_nosotros: z.array(z.object({
    texto: z.string(),
  })),
  numeros: z.array(z.object({
    valor:    z.string(),
    etiqueta: z.string(),
  })),
  caracteristicas: z.array(z.object({
    titulo:      z.string(),
    descripcion: z.string(),
  })),
}).nullable()

export const ServicioSchema = z.object({
  id:          z.number(),
  titulo:      z.string(),
  descripcion: nullable(z.string()),
  icono:       nullable(z.string()),
  imagen:      nullable(z.string().url()),
})

export const MiembroEquipoSchema = z.object({
  id:     z.number(),
  nombre: z.string(),
  cargo:  nullable(z.string()),
  bio:    nullable(z.string()),
  foto:   nullable(z.string().url()),
})

export const ClienteSchema = z.object({
  id:     z.number(),
  nombre: z.string(),
  logo:   nullable(z.string().url()),
  url:    nullable(z.string().url()),
})

export const TestimonioSchema = z.object({
  id:             z.number(),
  autor_nombre:   z.string(),
  autor_cargo:    nullable(z.string()),
  autor_empresa:  nullable(z.string()),
  autor_foto:     nullable(z.string().url()),
  contenido:      z.string(),
  estrellas:      z.number().min(1).max(5),
})

export const FaqSchema = z.object({
  id:        z.number(),
  pregunta:  z.string(),
  respuesta: z.string(),
})

export const ContactoSchema = z.object({
  direccion:  nullable(z.string()),
  telefono:   nullable(z.string()),
  email:      nullable(z.string().email()),
  whatsapp:   nullable(z.string()),
  mapa_embed: nullable(z.string()),
  redes: z.object({
    facebook:  z.string().url().optional(),
    instagram: z.string().url().optional(),
    linkedin:  z.string().url().optional(),
    youtube:   z.string().url().optional(),
    tiktok:    z.string().url().optional(),
  }),
}).nullable()

export const NoticiaSchema = z.object({
  slug:         z.string(),
  titulo:       z.string(),
  imagen:       nullable(z.string().url()),
  publicado_en: nullable(z.string().datetime()),
  extracto:     z.string(),
})

export const NoticiaDetalleSchema = NoticiaSchema.extend({
  id:        z.number(),
  contenido: z.string(),
})

// ── Schema principal (/all) ─────────────────────────────────────────────

export const CmsSchema = z.object({
  empresa:     EmpresaSchema,
  hero:        HeroSchema,
  nosotros:    NosotrosSchema,
  servicios:   z.array(ServicioSchema),
  equipo:      z.array(MiembroEquipoSchema),
  clientes:    z.array(ClienteSchema),
  testimonios: z.array(TestimonioSchema),
  faq:         z.array(FaqSchema),
  contacto:    ContactoSchema,
  noticias:    z.array(NoticiaSchema),
})

export type Cms            = z.infer&lt;typeof CmsSchema&gt;
export type Hero           = z.infer&lt;typeof HeroSchema&gt;
export type Nosotros       = z.infer&lt;typeof NosotrosSchema&gt;
export type Servicio       = z.infer&lt;typeof ServicioSchema&gt;
export type MiembroEquipo  = z.infer&lt;typeof MiembroEquipoSchema&gt;
export type Cliente        = z.infer&lt;typeof ClienteSchema&gt;
export type Testimonio     = z.infer&lt;typeof TestimonioSchema&gt;
export type Faq            = z.infer&lt;typeof FaqSchema&gt;
export type Contacto       = z.infer&lt;typeof ContactoSchema&gt;
export type Noticia        = z.infer&lt;typeof NoticiaSchema&gt;
export type NoticiaDetalle = z.infer&lt;typeof NoticiaDetalleSchema&gt;</pre>
    </x-filament::section>

    {{-- Uso completo --}}
    <x-filament::section>
        <x-slot name="heading">Ejemplo de uso en React + TypeScript</x-slot>
        <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 overflow-x-auto">// lib/cms.ts
import { CmsSchema, type Cms } from './schemas/cms'

const BASE  = '{{ config('app.url') }}/api/cms'
const TOKEN = import.meta.env.VITE_CMS_TOKEN

export async function fetchCms(slug: string): Promise&lt;Cms&gt; {
  const res = await fetch(`${BASE}/${slug}/all`, {
    headers: { Authorization: `Bearer ${TOKEN}` },
  })
  if (!res.ok) throw new Error(`Error ${res.status}`)
  return CmsSchema.parse(await res.json())
}

// .env
VITE_CMS_TOKEN=&lt;tu-token&gt;</pre>
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
                        <th class="pb-2 font-semibold">Schema Zod</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ([
                        ['hero',          'HeroSchema'],
                        ['about',         'NosotrosSchema'],
                        ['services',      'z.array(ServicioSchema)'],
                        ['team',          'z.array(MiembroEquipoSchema)'],
                        ['clients',       'z.array(ClienteSchema)'],
                        ['testimonials',  'z.array(TestimonioSchema)'],
                        ['faq',           'z.array(FaqSchema)'],
                        ['contact',       'ContactoSchema'],
                        ['posts',         'z.array(NoticiaSchema)'],
                        ['posts/{slug}',  'NoticiaDetalleSchema'],
                    ] as [$path, $schema])
                        <tr>
                            <td class="py-2 pr-4">
                                <span class="rounded px-2 py-0.5 text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">GET</span>
                            </td>
                            <td class="py-2 pr-4">
                                <code class="font-mono text-gray-800 dark:text-gray-200">{{ $baseUrl }}/{{ $path }}</code>
                            </td>
                            <td class="py-2">
                                <code class="font-mono text-sky-600 dark:text-sky-400">{{ $schema }}</code>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

</x-filament-panels::page>
