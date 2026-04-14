<x-filament-panels::page>

<script>
function copyBlock(btn) {
    const code = btn.closest('.code-block').querySelector('pre').innerText;
    navigator.clipboard.writeText(code).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '¡Copiado!';
        btn.classList.add('bg-emerald-500');
        btn.classList.remove('bg-gray-700', 'hover:bg-gray-600');
        setTimeout(() => {
            btn.innerHTML = orig;
            btn.classList.remove('bg-emerald-500');
            btn.classList.add('bg-gray-700', 'hover:bg-gray-600');
        }, 2000);
    });
}
</script>

{{-- ── Token activo / nuevo token ──────────────────────────────────────────── --}}
@if ($newToken)
    <x-filament::section>
        <x-slot name="heading">🔑 Token generado — cópialo ahora</x-slot>
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
        <x-slot name="heading">✅ Token activo</x-slot>
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

{{-- ── Autenticación ────────────────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Autenticación</x-slot>
    <x-slot name="description">Incluye el token en el header Authorization de cada petición.</x-slot>
    <div class="code-block relative">
        <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Copiar
        </button>
        <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-24 overflow-x-auto">Authorization: Bearer &lt;tu-token&gt;</pre>
    </div>
</x-filament::section>

{{-- ── Endpoint /all ────────────────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Endpoint principal — todo en una llamada</x-slot>
    <x-slot name="description">Devuelve hero, nosotros, servicios, productos, equipo, clientes, testimonios, FAQ, contacto, noticias y términos.</x-slot>
    <div class="code-block relative">
        <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Copiar
        </button>
        <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-24 overflow-x-auto">GET {{ $baseUrl }}/all</pre>
    </div>
</x-filament::section>

{{-- ── Endpoints individuales ───────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Endpoints individuales</x-slot>
    <x-slot name="description">Cada sección puede consultarse de forma independiente.</x-slot>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-gray-500 dark:text-gray-400">
                    <th class="pb-2 pr-4 font-semibold">Método</th>
                    <th class="pb-2 pr-4 font-semibold">Endpoint</th>
                    <th class="pb-2 pr-4 font-semibold">Descripción</th>
                    <th class="pb-2 font-semibold">Plan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ([
                    ['all',          'Todo el contenido en una sola llamada',                         'Todos'],
                    ['hero',         'Sección hero / banner principal',                               'Todos'],
                    ['about',        'Quiénes somos, valores y números',                              'Todos'],
                    ['services',     'Servicios CMS + Diseño de Servicios (enterprise)',              'Todos'],
                    ['products',     'Productos CMS + Diseño de Productos (enterprise)',              'Todos'],
                    ['team',         'Equipo / personas',                                             'Todos'],
                    ['clients',      'Logos de clientes',                                             'Todos'],
                    ['testimonials', 'Testimonios de clientes',                                      'Todos'],
                    ['faq',          'Preguntas frecuentes',                                          'Todos'],
                    ['contact',      'Datos de contacto y redes sociales',                            'Todos'],
                    ['posts',        'Listado de publicaciones / noticias',                           'Todos'],
                    ['posts/{slug}', 'Detalle de una publicación',                                   'Todos'],
                    ['terminos',     'Términos y condiciones',                                        'Todos'],
                ] as [$path, $desc, $plan])
                    <tr>
                        <td class="py-2 pr-4">
                            <span class="rounded px-2 py-0.5 text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">GET</span>
                        </td>
                        <td class="py-2 pr-4">
                            <div class="code-block relative inline-flex items-center gap-2">
                                <code class="font-mono text-xs text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">{{ $baseUrl }}/{{ $path }}</code>
                                <button onclick="navigator.clipboard.writeText('{{ $baseUrl }}/{{ $path }}'); this.textContent='✓';" class="text-xs text-gray-400 hover:text-gray-600 transition">📋</button>
                            </div>
                        </td>
                        <td class="py-2 pr-4 text-gray-600 dark:text-gray-400 text-xs">{{ $desc }}</td>
                        <td class="py-2">
                            <span class="rounded px-2 py-0.5 text-xs font-semibold
                                {{ $plan === 'Enterprise' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                                {{ $plan }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::section>

{{-- ── Servicios y Productos — detalle ─────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Servicios y Productos — comportamiento según plan</x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Servicios --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-sky-50 dark:bg-sky-950 px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                <p class="text-sm font-semibold text-sky-800 dark:text-sky-200">GET /services</p>
            </div>
            <div class="p-4 text-sm text-gray-600 dark:text-gray-400 space-y-2">
                <p><span class="font-semibold text-gray-800 dark:text-gray-200">Todos los planes:</span> servicios creados en CMS → Servicios del panel mailing.</p>
                <p><span class="font-semibold text-amber-700 dark:text-amber-300">Plan Enterprise:</span> también incluye los <strong>Diseños de Servicio</strong> marcados como <em>"Publicar en catálogo web"</em>.</p>
                <p class="text-xs text-gray-400">Campo <code>source</code>: <code>"cms"</code> o <code>"design"</code></p>
            </div>
        </div>

        {{-- Productos --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-emerald-50 dark:bg-emerald-950 px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">GET /products</p>
            </div>
            <div class="p-4 text-sm text-gray-600 dark:text-gray-400 space-y-2">
                <p><span class="font-semibold text-gray-800 dark:text-gray-200">Todos los planes:</span> productos creados en CMS → Productos del panel mailing.</p>
                <p><span class="font-semibold text-amber-700 dark:text-amber-300">Plan Enterprise:</span> también incluye los <strong>Diseños de Producto</strong> marcados como <em>"Publicar en catálogo web"</em>.</p>
                <p class="text-xs text-gray-400">Campo <code>source</code>: <code>"cms"</code> o <code>"design"</code></p>
            </div>
        </div>
    </div>

    <div class="mt-4 p-3 rounded-lg bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 text-sm text-amber-800 dark:text-amber-200">
        <strong>Enterprise:</strong> Para publicar en catálogo, activa el toggle <em>"Publicar en catálogo web"</em> en cada Diseño de Producto o Diseño de Servicio desde el panel Enterprise.
    </div>
</x-filament::section>

{{-- ── Schema Zod ───────────────────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Esquema de tipos (Zod)</x-slot>
    <x-slot name="description">Copia este schema directamente en tu proyecto React / Next.js.</x-slot>
    <div class="code-block relative">
        <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Copiar todo
        </button>
        <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-24 overflow-x-auto">import { z } from 'zod'

// ── Primitivos ──────────────────────────────────────────────────────────────
const nullable = &lt;T extends z.ZodTypeAny&gt;(s: T) => s.nullable()

// ── Empresa ─────────────────────────────────────────────────────────────────
export const EmpresaSchema = z.object({
  nombre: z.string(),
  logo:   nullable(z.string().url()),
})

// ── Hero ────────────────────────────────────────────────────────────────────
export const HeroSchema = z.object({
  titulo:      z.string(),
  subtitulo:   nullable(z.string()),
  descripcion: nullable(z.string()),
  imagen:      nullable(z.string().url()),
  cta_texto:   nullable(z.string()),
  cta_url:     nullable(z.string()),
}).nullable()

// ── Nosotros ────────────────────────────────────────────────────────────────
export const NosotrosSchema = z.object({
  titulo:           z.string(),
  descripcion:      nullable(z.string()),
  imagen:           nullable(z.string().url()),
  por_que_nosotros: z.array(z.object({ texto: z.string() })),
  numeros:          z.array(z.object({ valor: z.string(), etiqueta: z.string() })),
  caracteristicas:  z.array(z.object({ titulo: z.string(), descripcion: z.string() })),
}).nullable()

// ── Servicios (/services) ───────────────────────────────────────────────────
// Paquete de un diseño de servicio (enterprise)
export const PaqueteServicioSchema = z.object({
  nombre:      nullable(z.string()),
  descripcion: nullable(z.string()),
  precio:      nullable(z.number()),
  base_cobro:  z.string().optional(),  // 'fijo' | 'peso' | 'volumen' | ...
  unidad:      nullable(z.string()),
})

export const ServicioSchema = z.object({
  id:               z.number(),
  source:           z.enum(['cms', 'design']),
  titulo:           z.string(),
  descripcion:      nullable(z.string()),
  caracteristicas:  z.array(z.object({ texto: z.string() })),
  icono:            nullable(z.string()),
  imagen:           nullable(z.string().url()),
  // Solo presentes cuando source === 'design'
  propuesta_valor:  z.string().optional(),
  categoria:        z.string().optional(),
  paquetes:         z.array(PaqueteServicioSchema).optional(),
})

// ── Productos (/products) ───────────────────────────────────────────────────
// Presentación de un diseño de producto (enterprise)
export const PresentacionProductoSchema = z.object({
  nombre: nullable(z.string()),
  precio: nullable(z.number()),
  margen: nullable(z.number()),
})

export const ProductoSchema = z.object({
  id:               z.number(),
  source:           z.enum(['cms', 'design']),
  nombre:           z.string(),
  descripcion:      nullable(z.string()),
  precio:           nullable(z.number()),
  unidad_precio:    nullable(z.string()),  // 'por kg', 'por hora', etc.
  categoria:        nullable(z.string()),
  caracteristicas:  z.array(z.object({ texto: z.string() })),
  icono:            nullable(z.string()),
  imagen:           nullable(z.string().url()),
  // Solo presentes cuando source === 'design'
  presentaciones:   z.array(PresentacionProductoSchema).optional(),
})

// ── Equipo ──────────────────────────────────────────────────────────────────
export const MiembroEquipoSchema = z.object({
  id:     z.number(),
  nombre: z.string(),
  cargo:  nullable(z.string()),
  bio:    nullable(z.string()),
  foto:   nullable(z.string().url()),
})

// ── Clientes ────────────────────────────────────────────────────────────────
export const ClienteSchema = z.object({
  id:     z.number(),
  nombre: z.string(),
  logo:   nullable(z.string().url()),
  url:    nullable(z.string()),
})

// ── Testimonios ─────────────────────────────────────────────────────────────
export const TestimonioSchema = z.object({
  id:             z.number(),
  autor_nombre:   z.string(),
  autor_cargo:    nullable(z.string()),
  autor_empresa:  nullable(z.string()),
  autor_foto:     nullable(z.string().url()),
  contenido:      z.string(),
  estrellas:      z.number().min(1).max(5),
})

// ── FAQ ─────────────────────────────────────────────────────────────────────
export const FaqSchema = z.object({
  id:        z.number(),
  pregunta:  z.string(),
  respuesta: z.string(),
})

// ── Contacto ────────────────────────────────────────────────────────────────
export const ContactoSchema = z.object({
  direccion:  nullable(z.string()),
  telefono:   nullable(z.string()),
  email:      nullable(z.string()),
  whatsapp:   nullable(z.string()),
  mapa_embed: nullable(z.string()),
  redes: z.object({
    facebook:  z.string().optional(),
    instagram: z.string().optional(),
    linkedin:  z.string().optional(),
    youtube:   z.string().optional(),
    tiktok:    z.string().optional(),
  }),
}).nullable()

// ── Noticias ────────────────────────────────────────────────────────────────
export const NoticiaSchema = z.object({
  slug:         z.string(),
  titulo:       z.string(),
  imagen:       nullable(z.string().url()),
  publicado_en: nullable(z.string()),
  extracto:     z.string(),
})

export const NoticiaDetalleSchema = NoticiaSchema.extend({
  id:        z.number(),
  contenido: z.string(),
})

// ── Términos ────────────────────────────────────────────────────────────────
export const TerminosSchema = z.object({
  titulo:               z.string(),
  contenido:            nullable(z.string()),
  ultima_actualizacion: nullable(z.string()),
}).nullable()

// ── Schema principal (/all) ─────────────────────────────────────────────────
export const CmsSchema = z.object({
  empresa:     EmpresaSchema,
  hero:        HeroSchema,
  nosotros:    NosotrosSchema,
  servicios:   z.array(ServicioSchema),
  productos:   z.array(ProductoSchema),
  equipo:      z.array(MiembroEquipoSchema),
  clientes:    z.array(ClienteSchema),
  testimonios: z.array(TestimonioSchema),
  faq:         z.array(FaqSchema),
  contacto:    ContactoSchema,
  noticias:    z.array(NoticiaSchema),
  terminos:    TerminosSchema,
})

// ── Tipos inferidos ─────────────────────────────────────────────────────────
export type Cms                 = z.infer&lt;typeof CmsSchema&gt;
export type Hero                = z.infer&lt;typeof HeroSchema&gt;
export type Nosotros            = z.infer&lt;typeof NosotrosSchema&gt;
export type Servicio            = z.infer&lt;typeof ServicioSchema&gt;
export type PaqueteServicio     = z.infer&lt;typeof PaqueteServicioSchema&gt;
export type Producto            = z.infer&lt;typeof ProductoSchema&gt;
export type PresentacionProducto = z.infer&lt;typeof PresentacionProductoSchema&gt;
export type MiembroEquipo       = z.infer&lt;typeof MiembroEquipoSchema&gt;
export type Cliente             = z.infer&lt;typeof ClienteSchema&gt;
export type Testimonio          = z.infer&lt;typeof TestimonioSchema&gt;
export type Faq                 = z.infer&lt;typeof FaqSchema&gt;
export type Contacto            = z.infer&lt;typeof ContactoSchema&gt;
export type Noticia             = z.infer&lt;typeof NoticiaSchema&gt;
export type NoticiaDetalle      = z.infer&lt;typeof NoticiaDetalleSchema&gt;
export type Terminos            = z.infer&lt;typeof TerminosSchema&gt;</pre>
    </div>
</x-filament::section>

{{-- ── Ejemplo React ────────────────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Ejemplo de uso — React + TypeScript</x-slot>
    <div class="code-block relative">
        <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Copiar todo
        </button>
        <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-24 overflow-x-auto">// lib/cms.ts
import { CmsSchema, ServicioSchema, ProductoSchema, type Cms } from './schemas/cms'

const BASE  = 'https://erp.mashaec.net/api/cms'
const TOKEN = import.meta.env.VITE_CMS_TOKEN

const headers = { Authorization: `Bearer ${TOKEN}` }

// ── Todo en una llamada ─────────────────────────────────────────────────────
export async function fetchCms(slug: string): Promise&lt;Cms&gt; {
  const res = await fetch(`${BASE}/${slug}/all`, { headers })
  if (!res.ok) throw new Error(`Error ${res.status}`)
  return CmsSchema.parse(await res.json())
}

// ── Solo servicios ──────────────────────────────────────────────────────────
export async function fetchServicios(slug: string) {
  const res = await fetch(`${BASE}/${slug}/services`, { headers })
  if (!res.ok) throw new Error(`Error ${res.status}`)
  return ServicioSchema.array().parse(await res.json())
}

// ── Solo productos ──────────────────────────────────────────────────────────
export async function fetchProductos(slug: string) {
  const res = await fetch(`${BASE}/${slug}/products`, { headers })
  if (!res.ok) throw new Error(`Error ${res.status}`)
  return ProductoSchema.array().parse(await res.json())
}

// .env.local
// VITE_CMS_TOKEN=&lt;tu-token&gt;</pre>
    </div>
</x-filament::section>

{{-- ── Ejemplo respuesta /products ─────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Ejemplo de respuesta — /products</x-slot>
    <div class="code-block relative">
        <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Copiar
        </button>
        <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-24 overflow-x-auto">[
  // Producto CMS (todos los planes)
  {
    "id": 1,
    "source": "cms",
    "nombre": "Jabón artesanal lavanda",
    "descripcion": "Jabón 100% natural.",
    "precio": 3.50,
    "unidad_precio": "por unidad",
    "categoria": "Cuidado personal",
    "caracteristicas": [{ "texto": "Sin parabenos" }],
    "icono": "🧼",
    "imagen": "https://erp.mashaec.net/storage/cms/products/jabon.jpg"
  },
  // Diseño de Producto Enterprise (publicado_catalogo = true)
  {
    "id": 12,
    "source": "design",
    "nombre": "Crema hidratante premium",
    "descripcion": "&lt;p&gt;Propuesta de valor del diseño...&lt;/p&gt;",
    "precio": null,
    "unidad_precio": null,
    "categoria": "cosmetica",
    "caracteristicas": [],
    "icono": null,
    "imagen": null,
    "presentaciones": [
      { "nombre": "50ml", "precio": 12.50, "margen": 40 },
      { "nombre": "100ml", "precio": 22.00, "margen": 42 }
    ]
  }
]</pre>
    </div>
</x-filament::section>

{{-- ── Ejemplo respuesta /services ─────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Ejemplo de respuesta — /services</x-slot>
    <div class="code-block relative">
        <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Copiar
        </button>
        <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-24 overflow-x-auto">[
  // Servicio CMS (todos los planes)
  {
    "id": 3,
    "source": "cms",
    "titulo": "Diseño gráfico",
    "descripcion": "Identidad visual para tu marca.",
    "caracteristicas": [{ "texto": "Entrega en 5 días" }],
    "icono": "🎨",
    "imagen": "https://erp.mashaec.net/storage/cms/services/diseno.jpg"
  },
  // Diseño de Servicio Enterprise (publicado_catalogo = true)
  {
    "id": 7,
    "source": "design",
    "titulo": "Consultoría de procesos",
    "descripcion": "Diagnóstico y optimización de procesos.",
    "propuesta_valor": "&lt;p&gt;Reducimos costos operativos...&lt;/p&gt;",
    "categoria": "consultoria",
    "caracteristicas": [],
    "icono": null,
    "imagen": null,
    "paquetes": [
      { "nombre": "Básico",   "precio": 350, "base_cobro": "fijo",  "unidad": null },
      { "nombre": "Estándar", "precio": 150, "base_cobro": "tiempo", "unidad": "h" },
      { "nombre": "Premium",  "precio": 2.5, "base_cobro": "peso",  "unidad": "kg" }
    ]
  }
]</pre>
    </div>
</x-filament::section>

</x-filament-panels::page>
