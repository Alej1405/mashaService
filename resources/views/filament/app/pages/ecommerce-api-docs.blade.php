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

{{-- ── Introducción ──────────────────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">E-Commerce REST API</x-slot>
    <x-slot name="description">Integra la tienda en línea de {{ $empresa->name }} con cualquier frontend (React, Next.js, Vue, etc.).</x-slot>

    <div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
        <div>
            <span class="font-semibold text-gray-800 dark:text-gray-200">Base URL — API</span>
            <div class="code-block relative mt-1">
                <button onclick="navigator.clipboard.writeText('{{ $baseUrl }}'); this.textContent='✓';" class="absolute top-2 right-2 text-xs text-gray-400 hover:text-gray-600 transition">📋</button>
                <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-3 pr-10 overflow-x-auto">{{ $baseUrl }}</pre>
            </div>
        </div>
        <div>
            <span class="font-semibold text-gray-800 dark:text-gray-200">Portal web del cliente (incluido, sin frontend propio)</span>
            <div class="code-block relative mt-1">
                <button onclick="navigator.clipboard.writeText('{{ str_replace('/api/ecommerce/', '/tienda/', $baseUrl) }}'); this.textContent='✓';" class="absolute top-2 right-2 text-xs text-gray-400 hover:text-gray-600 transition">📋</button>
                <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-3 pr-10 overflow-x-auto">{{ str_replace('/api/ecommerce/', '/tienda/', $baseUrl) }}</pre>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                Portal Blade listo para usar: login, órdenes, servicios contratados y perfil. No requiere frontend.
                El admin registra clientes desde el panel Enterprise — ellos acceden con email y contraseña.
            </p>
        </div>
        <p>
            Las rutas de API <strong>públicas</strong> no requieren ningún token.
            Las rutas <strong>protegidas</strong> (🔒) requieren el header
            <code class="bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded text-xs font-mono">Authorization: Bearer {token_del_cliente}</code>,
            que se obtiene al hacer login o registro.
        </p>
    </div>
</x-filament::section>

{{-- ── Portal web ──────────────────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Portal web del cliente — rutas incluidas</x-slot>
    <x-slot name="description">Interfaz Blade lista para usar sin necesidad de frontend externo. URL base: /tienda/{{ $empresa->slug }}/</x-slot>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-gray-500 dark:text-gray-400">
                    <th class="pb-2 pr-4 font-semibold">Ruta</th>
                    <th class="pb-2 pr-4 font-semibold">Página</th>
                    <th class="pb-2 font-semibold">Auth</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach([
                    ['/tienda/'.$empresa->slug.'/login',           'Login con email y contraseña',                           false],
                    ['/tienda/'.$empresa->slug.'/',                 'Dashboard: resumen de órdenes y servicios',              true],
                    ['/tienda/'.$empresa->slug.'/orders',           'Historial de órdenes paginado',                         true],
                    ['/tienda/'.$empresa->slug.'/orders/{id}',      'Detalle de orden con ítems, totales y cupón',            true],
                    ['/tienda/'.$empresa->slug.'/services',         'Servicios contratados (asignados por el admin)',         true],
                    ['/tienda/'.$empresa->slug.'/profile',          'Editar nombre/teléfono y cambiar contraseña',            true],
                ] as [$ruta, $desc, $auth])
                <tr>
                    <td class="py-2 pr-4">
                        <code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded text-gray-800 dark:text-gray-200">{{ $ruta }}</code>
                    </td>
                    <td class="py-2 pr-4 text-gray-600 dark:text-gray-400 text-xs">{{ $desc }}</td>
                    <td class="py-2">
                        @if($auth)
                            <span class="rounded px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Sesión</span>
                        @else
                            <span class="rounded px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">Pública</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 p-3 rounded-lg bg-sky-50 dark:bg-sky-950 border border-sky-200 dark:border-sky-800 text-sm text-sky-800 dark:text-sky-200">
        <strong>Flujo de registro:</strong> El admin crea el cliente en <em>Enterprise → E-Commerce → Clientes</em> con email y contraseña.
        El cliente recibe sus credenciales y accede directamente al portal sin necesidad de registrarse.
    </div>
</x-filament::section>

{{-- ── Endpoints: Catálogo ────────────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Catálogo — Productos y Categorías</x-slot>
    <x-slot name="description">Rutas públicas para mostrar el catálogo de la tienda.</x-slot>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-gray-500 dark:text-gray-400">
                    <th class="pb-2 pr-4 font-semibold">Método</th>
                    <th class="pb-2 pr-4 font-semibold">Endpoint</th>
                    <th class="pb-2 pr-4 font-semibold">Descripción</th>
                    <th class="pb-2 font-semibold">Auth</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ([
                    ['GET', 'products',              'Listado paginado (24/pág) con filtros'],
                    ['GET', 'products/featured',     'Productos destacados (máx 12)'],
                    ['GET', 'products/{slug}',       'Detalle de un producto'],
                    ['GET', 'products/{id}/related', 'Productos relacionados (misma categoría)'],
                    ['GET', 'categories',            'Árbol de categorías con conteo de productos'],
                    ['GET', 'categories/{slug}',     'Categoría + productos paginados'],
                ] as [$method, $path, $desc])
                <tr>
                    <td class="py-2 pr-4">
                        <span class="rounded px-2 py-0.5 text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">{{ $method }}</span>
                    </td>
                    <td class="py-2 pr-4">
                        <div class="inline-flex items-center gap-2">
                            <code class="font-mono text-xs text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">/{{ $path }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ $baseUrl }}/{{ $path }}'); this.textContent='✓';" class="text-xs text-gray-400 hover:text-gray-600 transition">📋</button>
                        </div>
                    </td>
                    <td class="py-2 pr-4 text-gray-600 dark:text-gray-400 text-xs">{{ $desc }}</td>
                    <td class="py-2"><span class="rounded px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">Pública</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400">
        <strong class="text-gray-800 dark:text-gray-200">Filtros disponibles en <code class="font-mono text-xs">/products</code>:</strong>
        <span class="ml-2">
            <code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">category</code> (slug) ·
            <code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">search</code> ·
            <code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">minPrice</code> ·
            <code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">maxPrice</code> ·
            <code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">sort</code> (precio_asc · precio_desc · nombre) ·
            <code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">page</code>
        </span>
    </div>
</x-filament::section>

{{-- ── Endpoints: Auth ─────────────────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Autenticación de clientes</x-slot>
    <x-slot name="description">Registro, login y gestión de sesión del cliente de la tienda.</x-slot>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-gray-500 dark:text-gray-400">
                    <th class="pb-2 pr-4 font-semibold">Método</th>
                    <th class="pb-2 pr-4 font-semibold">Endpoint</th>
                    <th class="pb-2 pr-4 font-semibold">Descripción</th>
                    <th class="pb-2 font-semibold">Auth</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ([
                    ['POST', 'auth/register',       'Registro de nuevo cliente',                  false],
                    ['POST', 'auth/login',           'Login — devuelve token Bearer',              false],
                    ['POST', 'auth/forgot-password', 'Solicitar recuperación de contraseña',      false],
                    ['POST', 'auth/reset-password',  'Restablecer contraseña con token',          false],
                    ['GET',  'auth/me',              'Perfil del cliente autenticado',             true],
                    ['POST', 'auth/logout',          'Cerrar sesión',                             true],
                ] as [$method, $path, $desc, $protected])
                <tr>
                    <td class="py-2 pr-4">
                        <span class="rounded px-2 py-0.5 text-xs font-bold
                            {{ $method === 'GET' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200' }}">
                            {{ $method }}
                        </span>
                    </td>
                    <td class="py-2 pr-4">
                        <div class="inline-flex items-center gap-2">
                            <code class="font-mono text-xs text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">/{{ $path }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ $baseUrl }}/{{ $path }}'); this.textContent='✓';" class="text-xs text-gray-400 hover:text-gray-600 transition">📋</button>
                        </div>
                    </td>
                    <td class="py-2 pr-4 text-gray-600 dark:text-gray-400 text-xs">{{ $desc }}</td>
                    <td class="py-2">
                        @if($protected)
                            <span class="rounded px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">🔒 Bearer</span>
                        @else
                            <span class="rounded px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">Pública</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::section>

{{-- ── Endpoints: Cliente y Órdenes ──────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Perfil, Direcciones y Órdenes</x-slot>
    <x-slot name="description">Requieren el token Bearer del cliente autenticado.</x-slot>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-gray-500 dark:text-gray-400">
                    <th class="pb-2 pr-4 font-semibold">Método</th>
                    <th class="pb-2 pr-4 font-semibold">Endpoint</th>
                    <th class="pb-2 font-semibold">Descripción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ([
                    ['GET',    'customers/me',              'Ver perfil del cliente'],
                    ['PUT',    'customers/me',              'Actualizar nombre / teléfono'],
                    ['PUT',    'customers/me/password',     'Cambiar contraseña'],
                    ['GET',    'customers/me/addresses',    'Listar direcciones guardadas'],
                    ['POST',   'customers/me/addresses',    'Agregar nueva dirección'],
                    ['PUT',    'customers/me/addresses/{id}', 'Editar dirección'],
                    ['DELETE', 'customers/me/addresses/{id}', 'Eliminar dirección'],
                    ['POST',   'orders',   'Crear nueva orden'],
                    ['GET',    'orders',   'Historial de órdenes del cliente (paginado)'],
                    ['GET',    'orders/{id}', 'Detalle de una orden con ítems'],
                ] as [$method, $path, $desc])
                <tr>
                    <td class="py-2 pr-4">
                        <span class="rounded px-2 py-0.5 text-xs font-bold
                            @if($method === 'GET') bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200
                            @elseif($method === 'POST') bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200
                            @elseif($method === 'PUT') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200
                            @else bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200
                            @endif">
                            {{ $method }}
                        </span>
                    </td>
                    <td class="py-2 pr-4">
                        <div class="inline-flex items-center gap-2">
                            <code class="font-mono text-xs text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">/{{ $path }}</code>
                        </div>
                    </td>
                    <td class="py-2 text-gray-600 dark:text-gray-400 text-xs">{{ $desc }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::section>

{{-- ── Endpoints: Cupones y Pagos ──────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Cupones y Pagos</x-slot>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-gray-500 dark:text-gray-400">
                    <th class="pb-2 pr-4 font-semibold">Método</th>
                    <th class="pb-2 pr-4 font-semibold">Endpoint</th>
                    <th class="pb-2 pr-4 font-semibold">Descripción</th>
                    <th class="pb-2 font-semibold">Auth</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr>
                    <td class="py-2 pr-4"><span class="rounded px-2 py-0.5 text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200">POST</span></td>
                    <td class="py-2 pr-4"><code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">/coupons/validate</code></td>
                    <td class="py-2 pr-4 text-gray-600 dark:text-gray-400 text-xs">Validar cupón y calcular descuento</td>
                    <td class="py-2"><span class="rounded px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">Pública</span></td>
                </tr>
                <tr>
                    <td class="py-2 pr-4"><span class="rounded px-2 py-0.5 text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200">POST</span></td>
                    <td class="py-2 pr-4"><code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">/payments/intent</code></td>
                    <td class="py-2 pr-4 text-gray-600 dark:text-gray-400 text-xs">Crear intención de pago en el gateway <span class="text-amber-600 dark:text-amber-400">(pendiente)</span></td>
                    <td class="py-2"><span class="rounded px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">Pública</span></td>
                </tr>
                <tr>
                    <td class="py-2 pr-4"><span class="rounded px-2 py-0.5 text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200">POST</span></td>
                    <td class="py-2 pr-4"><code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">/payments/webhook</code></td>
                    <td class="py-2 pr-4 text-gray-600 dark:text-gray-400 text-xs">Callback del gateway de pagos</td>
                    <td class="py-2"><span class="rounded px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">Pública</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</x-filament::section>

{{-- ── Bodies de request ───────────────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Cuerpos de Request</x-slot>
    <x-slot name="description">Campos requeridos y opcionales para cada endpoint POST/PUT.</x-slot>

    <div class="space-y-6">

        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">POST /auth/register</p>
            <div class="code-block relative">
                <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition">Copiar</button>
                <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-20 overflow-x-auto">{
  "nombre":              "María",           // requerido
  "apellido":            "López",           // opcional
  "email":               "maria@email.com", // requerido, único por tienda
  "password":            "secreto123",      // requerido, mín 8 caracteres
  "password_confirmation": "secreto123",    // requerido
  "telefono":            "0991234567"       // opcional
}</pre>
            </div>
        </div>

        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">POST /auth/login</p>
            <div class="code-block relative">
                <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition">Copiar</button>
                <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-20 overflow-x-auto">{
  "email":    "maria@email.com",
  "password": "secreto123"
}</pre>
            </div>
        </div>

        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">POST /orders</p>
            <div class="code-block relative">
                <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition">Copiar</button>
                <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-20 overflow-x-auto">{
  "items": [
    { "store_product_id": 12, "cantidad": 2 },
    { "store_product_id": 5,  "cantidad": 1 }
  ],
  "shipping_address": {
    "linea1":   "Av. República E7-123",  // requerido
    "linea2":   "Piso 3, Of. 12",        // opcional
    "ciudad":   "Quito",                 // requerido
    "provincia": "Pichincha",            // opcional
    "pais":     "Ecuador",               // opcional
    "codigo_postal": "170150"            // opcional
  },
  "coupon_code": "VERANO20",             // opcional
  "notas":       "Entregar en portería"  // opcional, máx 500 chars
}</pre>
            </div>
        </div>

        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">POST /coupons/validate</p>
            <div class="code-block relative">
                <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition">Copiar</button>
                <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-20 overflow-x-auto">{
  "code":     "VERANO20",
  "subtotal": 85.50        // opcional — para calcular el descuento
}</pre>
            </div>
        </div>

        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">POST /customers/me/addresses</p>
            <div class="code-block relative">
                <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition">Copiar</button>
                <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-20 overflow-x-auto">{
  "nombre_destinatario": "María López",   // requerido
  "linea1":              "Av. Colón N24-5", // requerido
  "linea2":              "Dpto 4B",       // opcional
  "ciudad":              "Quito",         // requerido
  "provincia":           "Pichincha",     // opcional
  "pais":                "Ecuador",       // opcional
  "codigo_postal":       "170515",        // opcional
  "telefono":            "0991234567",    // opcional
  "es_principal":        true             // opcional
}</pre>
            </div>
        </div>

    </div>
</x-filament::section>

{{-- ── Respuestas de ejemplo ───────────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Respuestas de ejemplo</x-slot>

    <div class="space-y-6">

        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">GET /products (paginado)</p>
            <div class="code-block relative">
                <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition">Copiar</button>
                <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-20 overflow-x-auto">{
  "data": [
    {
      "id":             12,
      "nombre":         "Camiseta Básica",
      "slug":           "camiseta-basica",
      "descripcion":    "100% algodón peinado",
      "precio_venta":   "12.5000",
      "imagen_principal": "/storage/products/cam.jpg",
      "galeria":        ["/storage/products/cam2.jpg"],
      "publicado":      true,
      "destacado":      false,
      "store_category": { "id": 3, "nombre": "Ropa", "slug": "ropa" }
    }
  ],
  "current_page": 1,
  "per_page":     24,
  "total":        48,
  "last_page":    2
}</pre>
            </div>
        </div>

        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">POST /auth/login — respuesta exitosa</p>
            <div class="code-block relative">
                <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition">Copiar</button>
                <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-20 overflow-x-auto">{
  "token": "1|abc123xyz...",
  "customer": {
    "id":       7,
    "nombre":   "María",
    "apellido": "López",
    "email":    "maria@email.com",
    "telefono": "0991234567"
  }
}</pre>
            </div>
        </div>

        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">POST /coupons/validate — respuesta</p>
            <div class="code-block relative">
                <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition">Copiar</button>
                <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-20 overflow-x-auto">{
  "codigo":    "VERANO20",
  "tipo":      "porcentaje",
  "valor":     20,
  "descuento": 17.10,
  "total":     68.40
}</pre>
            </div>
        </div>

        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">GET /orders/{id} — detalle completo</p>
            <div class="code-block relative">
                <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition">Copiar</button>
                <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-20 overflow-x-auto">{
  "id":            34,
  "numero":        "ECO-2026-00034",   // número de orden legible
  "estado":        "pendiente",         // pendiente | confirmada | procesando | enviada | entregada | cancelada
  "estado_pago":   "pendiente",         // pendiente | aprobado | rechazado | reembolsado
  "subtotal":      "85.5000",
  "descuento":     "17.1000",
  "total":         "68.4000",
  "notas_cliente": "Entregar en portería",
  "direccion_envio": {
    "linea1":   "Av. República E7-123",
    "ciudad":   "Quito",
    "provincia": "Pichincha",
    "pais":     "Ecuador"
  },
  "created_at":    "2026-04-10T15:30:00Z",
  "coupon":        { "codigo": "VERANO20" },
  "order_items": [
    {
      "store_product_id": 12,
      "inventory_item_id": 5,
      "nombre_snapshot":  "Camiseta Básica",  // nombre capturado al momento de la compra
      "cantidad":         "2.0000",
      "precio_unitario":  "12.5000",
      "subtotal":         "25.0000",
      "product": { "nombre": "Camiseta Básica", "slug": "camiseta-basica" }
    }
  ]
}</pre>
            </div>
        </div>

        <div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Flujo del carrito</p>
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 space-y-2">
                <p>El carrito <strong>no tiene endpoint propio</strong> — se gestiona en el estado del cliente (React state, localStorage, Zustand, etc.).</p>
                <p>Al hacer checkout, se envía el carrito completo en un solo <code class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">POST /orders</code>. El servidor valida stock, aplica el cupón y crea la orden en un mismo paso.</p>
                <ol class="list-decimal list-inside space-y-1 text-xs mt-2">
                    <li>Usuario navega el catálogo → <code class="font-mono bg-gray-100 dark:bg-gray-800 px-1 rounded">GET /products</code></li>
                    <li>Agrega productos al carrito local (frontend)</li>
                    <li>Valida cupón si aplica → <code class="font-mono bg-gray-100 dark:bg-gray-800 px-1 rounded">POST /coupons/validate</code></li>
                    <li>Confirma compra → <code class="font-mono bg-gray-100 dark:bg-gray-800 px-1 rounded">POST /orders</code> con todos los ítems</li>
                    <li>Redirige al portal de cliente → <code class="font-mono bg-gray-100 dark:bg-gray-800 px-1 rounded">/tienda/{{ $empresa->slug }}/orders/{id}</code></li>
                </ol>
            </div>
        </div>

    </div>
</x-filament::section>

{{-- ── Zod / TypeScript ────────────────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Esquema de tipos (Zod)</x-slot>
    <x-slot name="description">Copia este schema directamente en tu proyecto React / Next.js.</x-slot>

    <div class="code-block relative">
        <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Copiar todo
        </button>
        <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-24 overflow-x-auto">import { z } from 'zod'

// ── Producto de tienda ───────────────────────────────────────────────────────
export const ProductoTiendaSchema = z.object({
  id:               z.number(),
  nombre:           z.string(),
  slug:             z.string(),
  descripcion:      z.string().nullable(),
  precio_venta:     z.string(),
  imagen_principal: z.string().nullable(),
  galeria:          z.array(z.string()).nullable(),
  publicado:        z.boolean(),
  destacado:        z.boolean(),
  store_category: z.object({
    id: z.number(), nombre: z.string(), slug: z.string(),
  }).nullable(),
})

export const ProductosPaginadosSchema = z.object({
  data:         z.array(ProductoTiendaSchema),
  current_page: z.number(),
  per_page:     z.number(),
  total:        z.number(),
  last_page:    z.number(),
})

// ── Categoría ────────────────────────────────────────────────────────────────
export const CategoriaSchema: z.ZodTypeAny = z.object({
  id:             z.number(),
  nombre:         z.string(),
  slug:           z.string(),
  imagen:         z.string().nullable(),
  products_count: z.number(),
  children:       z.lazy(() => z.array(CategoriaSchema)),
})

// ── Auth ─────────────────────────────────────────────────────────────────────
export const ClienteSchema = z.object({
  id:       z.number(),
  nombre:   z.string(),
  apellido: z.string().nullable(),
  email:    z.string().email(),
  telefono: z.string().nullable(),
})

export const AuthResponseSchema = z.object({
  token:    z.string(),
  customer: ClienteSchema,
})

// ── Dirección ────────────────────────────────────────────────────────────────
export const DireccionSchema = z.object({
  id:                  z.number(),
  nombre_destinatario: z.string(),
  linea1:              z.string(),
  linea2:              z.string().nullable(),
  ciudad:              z.string(),
  provincia:           z.string().nullable(),
  pais:                z.string().nullable(),
  codigo_postal:       z.string().nullable(),
  telefono:            z.string().nullable(),
  es_principal:        z.boolean(),
})

// ── Ítem de orden ────────────────────────────────────────────────────────────
export const OrdenItemSchema = z.object({
  store_product_id:  z.number(),
  inventory_item_id: z.number().nullable(),
  nombre_snapshot:   z.string(),        // nombre capturado al momento de la compra
  cantidad:          z.string(),
  precio_unitario:   z.string(),
  subtotal:          z.string(),
  product: z.object({ nombre: z.string(), slug: z.string() }).nullable(),
})

// ── Dirección de envío ───────────────────────────────────────────────────────
export const DireccionEnvioSchema = z.object({
  linea1:        z.string(),
  linea2:        z.string().optional(),
  ciudad:        z.string(),
  provincia:     z.string().optional(),
  pais:          z.string().optional(),
  codigo_postal: z.string().optional(),
})

// ── Orden ────────────────────────────────────────────────────────────────────
export const OrdenSchema = z.object({
  id:              z.number(),
  numero:          z.string(),          // ECO-YYYY-NNNNN
  estado:          z.enum(['pendiente', 'confirmada', 'procesando', 'enviada', 'entregada', 'cancelada']),
  estado_pago:     z.enum(['pendiente', 'aprobado', 'rechazado', 'reembolsado']),
  subtotal:        z.string(),
  descuento:       z.string(),
  total:           z.string(),
  notas_cliente:   z.string().nullable(),
  direccion_envio: DireccionEnvioSchema.nullable(),
  created_at:      z.string(),
  coupon:          z.object({ codigo: z.string() }).nullable(),
  order_items:     z.array(OrdenItemSchema).optional(),
})

// ── Cupón ────────────────────────────────────────────────────────────────────
export const CuponValidadoSchema = z.object({
  codigo:    z.string(),
  tipo:      z.enum(['porcentaje', 'fijo']),
  valor:     z.number(),
  descuento: z.number(),
  total:     z.number(),
})

// ── Tipos inferidos ──────────────────────────────────────────────────────────
export type ProductoTienda = z.infer&lt;typeof ProductoTiendaSchema&gt;
export type Categoria      = z.infer&lt;typeof CategoriaSchema&gt;
export type Cliente        = z.infer&lt;typeof ClienteSchema&gt;
export type AuthResponse   = z.infer&lt;typeof AuthResponseSchema&gt;
export type Direccion      = z.infer&lt;typeof DireccionSchema&gt;
export type Orden          = z.infer&lt;typeof OrdenSchema&gt;
export type CuponValidado  = z.infer&lt;typeof CuponValidadoSchema&gt;</pre>
    </div>
</x-filament::section>

{{-- ── Ejemplo React ───────────────────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Ejemplo de integración React / Next.js</x-slot>

    <div class="code-block relative">
        <button onclick="copyBlock(this)" class="absolute top-2 right-2 rounded px-2 py-1 text-xs font-semibold bg-gray-700 hover:bg-gray-600 text-gray-200 transition flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Copiar todo
        </button>
        <pre class="rounded-lg bg-gray-900 text-green-400 text-sm p-4 pr-24 overflow-x-auto">// lib/ecommerce-api.ts
const BASE = '{{ $baseUrl }}'

let authToken: string | null = null
export const setToken = (t: string | null) => { authToken = t }

const headers = (extra: Record&lt;string, string&gt; = {}) => ({
  'Content-Type': 'application/json',
  ...(authToken ? { 'Authorization': `Bearer ${authToken}` } : {}),
  ...extra,
})

// ── Catálogo ─────────────────────────────────────────────────────────────────
export async function fetchProductos(params?: {
  category?: string; search?: string
  minPrice?: number; maxPrice?: number
  sort?: string; page?: number
}) {
  const q = new URLSearchParams(Object.entries(params ?? {})
    .filter(([, v]) => v !== undefined).map(([k, v]) => [k, String(v)]))
  const res = await fetch(`${BASE}/products?${q}`, { headers: headers() })
  return res.json() // ProductosPaginadosSchema
}

export async function fetchProducto(slug: string) {
  const res = await fetch(`${BASE}/products/${slug}`, { headers: headers() })
  return res.json()
}

export async function fetchCategorias() {
  const res = await fetch(`${BASE}/categories`, { headers: headers() })
  return res.json()
}

// ── Auth ─────────────────────────────────────────────────────────────────────
export async function registrarCliente(data: {
  nombre: string; apellido?: string; email: string
  password: string; password_confirmation: string; telefono?: string
}) {
  const res = await fetch(`${BASE}/auth/register`, {
    method: 'POST', headers: headers(), body: JSON.stringify(data),
  })
  if (!res.ok) throw await res.json()
  const json = await res.json()
  setToken(json.token)
  return json // AuthResponseSchema
}

export async function loginCliente(email: string, password: string) {
  const res = await fetch(`${BASE}/auth/login`, {
    method: 'POST', headers: headers(),
    body: JSON.stringify({ email, password }),
  })
  if (!res.ok) throw await res.json()
  const json = await res.json()
  setToken(json.token)
  return json // AuthResponseSchema
}

export async function logoutCliente() {
  await fetch(`${BASE}/auth/logout`, { method: 'POST', headers: headers() })
  setToken(null)
}

// ── Órdenes ──────────────────────────────────────────────────────────────────
export async function crearOrden(data: {
  items: { store_product_id: number; cantidad: number }[]
  shipping_address: { linea1: string; ciudad: string; [k: string]: string }
  coupon_code?: string; notas?: string
}) {
  const res = await fetch(`${BASE}/orders`, {
    method: 'POST', headers: headers(), body: JSON.stringify(data),
  })
  if (!res.ok) throw await res.json()
  return res.json() // OrdenSchema
}

export async function fetchOrdenes() {
  const res = await fetch(`${BASE}/orders`, { headers: headers() })
  return res.json()
}

// ── Cupones ───────────────────────────────────────────────────────────────────
export async function validarCupon(code: string, subtotal?: number) {
  const res = await fetch(`${BASE}/coupons/validate`, {
    method: 'POST', headers: headers(),
    body: JSON.stringify({ code, subtotal }),
  })
  if (!res.ok) throw await res.json()
  return res.json() // CuponValidadoSchema
}</pre>
    </div>

    <div class="mt-4 p-3 rounded-lg bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 text-sm text-amber-800 dark:text-amber-200">
        <strong>Pagos:</strong> El endpoint <code class="font-mono text-xs">/payments/intent</code> está pendiente de configuración del gateway (Stripe, Payphone, etc.).
        Actualmente devuelve <code class="font-mono text-xs">501</code>. Al integrarlo, se pasará el <code class="font-mono text-xs">intent_id</code> al confirmar la orden.
    </div>
</x-filament::section>

</x-filament-panels::page>
