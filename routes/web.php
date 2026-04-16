<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Empresa;

Route::get('/', function () {
    return redirect('/app/login');
});

// ── Portal Cliente ────────────────────────────────────────────────────────
Route::prefix('tienda/{slug}')->name('portal.')->group(function () {
    Route::get('/login',  [\App\Http\Controllers\Portal\PortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Portal\PortalAuthController::class, 'login'])->name('login.post');
    Route::post('/logout',[\App\Http\Controllers\Portal\PortalAuthController::class, 'logout'])->name('logout');

    Route::middleware(\App\Http\Middleware\AuthenticateStoreCustomer::class)->group(function () {
        Route::get('/',               [\App\Http\Controllers\Portal\PortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders',         [\App\Http\Controllers\Portal\PortalController::class, 'orders'])->name('orders');
        Route::get('/orders/{id}',    [\App\Http\Controllers\Portal\PortalController::class, 'orderShow'])->name('orders.show');
        Route::get('/services',       [\App\Http\Controllers\Portal\PortalController::class, 'services'])->name('services');
        Route::get('/packages',       [\App\Http\Controllers\Portal\PortalController::class, 'packages'])->name('packages');
        Route::get('/profile',        [\App\Http\Controllers\Portal\PortalController::class, 'profile'])->name('profile');
        Route::post('/profile',       [\App\Http\Controllers\Portal\PortalController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/password', [\App\Http\Controllers\Portal\PortalController::class, 'updatePassword'])->name('profile.password');
        Route::get('/customers',      [\App\Http\Controllers\Portal\PortalController::class, 'customers'])->name('customers');
        Route::post('/payments',      [\App\Http\Controllers\Portal\PortalController::class, 'submitPayment'])->name('payments.store');
    });
});

// ── Portal Móvil ──────────────────────────────────────────────────────────
Route::prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/login',  [\App\Http\Controllers\MobileController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\MobileController::class, 'login'])->name('login.post');

    Route::middleware('mobile.auth')->group(function () {
        Route::get('/',                                    [\App\Http\Controllers\MobileController::class, 'index'])->name('index');
        Route::post('/logout',                             [\App\Http\Controllers\MobileController::class, 'logout'])->name('logout');
        Route::get('/almacenes',                                              [\App\Http\Controllers\MobileController::class, 'listAlmacenes'])->name('almacenes.index');
        Route::get('/almacenes/nuevo',                                        [\App\Http\Controllers\MobileController::class, 'showAlmacenForm'])->name('almacenes.nuevo');
        Route::get('/almacenes/{almacen}/editar',                             [\App\Http\Controllers\MobileController::class, 'showAlmacenForm'])->name('almacenes.editar');
        Route::post('/almacenes/guardar',                                     [\App\Http\Controllers\MobileController::class, 'guardarAlmacen'])->name('almacenes.guardar');
        Route::post('/almacenes/{almacen}/eliminar',                          [\App\Http\Controllers\MobileController::class, 'eliminarAlmacen'])->name('almacenes.eliminar');
        Route::get('/almacenes/{almacen}/zonas',                              [\App\Http\Controllers\MobileController::class, 'listZonas'])->name('almacenes.zonas.index');
        Route::get('/almacenes/{almacen}/zonas/nueva',                        [\App\Http\Controllers\MobileController::class, 'showZonaForm'])->name('almacenes.zonas.nueva');
        Route::get('/almacenes/{almacen}/zonas/{zona}/editar',                [\App\Http\Controllers\MobileController::class, 'showZonaForm'])->name('almacenes.zonas.editar');
        Route::post('/almacenes/{almacen}/zonas/guardar',                     [\App\Http\Controllers\MobileController::class, 'guardarZona'])->name('almacenes.zonas.guardar');
        Route::post('/almacenes/{almacen}/zonas/{zona}/eliminar',             [\App\Http\Controllers\MobileController::class, 'eliminarZona'])->name('almacenes.zonas.eliminar');
        Route::get('/almacenes/{almacen}/zonas-json',                         [\App\Http\Controllers\MobileController::class, 'getZonasJson'])->name('almacenes.zonas-json');
        Route::get('/zonas/{zona}/ubicaciones-json',                          [\App\Http\Controllers\MobileController::class, 'getUbicacionesJson'])->name('zonas.ubicaciones-json');
        Route::get('/almacenes/{almacen}/zonas/{zona}/posiciones',            [\App\Http\Controllers\MobileController::class, 'listUbicaciones'])->name('almacenes.zonas.posiciones.index');
        Route::get('/almacenes/{almacen}/zonas/{zona}/posiciones/nueva',      [\App\Http\Controllers\MobileController::class, 'showUbicacionForm'])->name('almacenes.zonas.posiciones.nueva');
        Route::get('/almacenes/{almacen}/zonas/{zona}/posiciones/{ubicacion}/editar', [\App\Http\Controllers\MobileController::class, 'showUbicacionForm'])->name('almacenes.zonas.posiciones.editar');
        Route::post('/almacenes/{almacen}/zonas/{zona}/posiciones/guardar',   [\App\Http\Controllers\MobileController::class, 'guardarUbicacion'])->name('almacenes.zonas.posiciones.guardar');
        Route::post('/almacenes/{almacen}/zonas/{zona}/posiciones/{ubicacion}/eliminar', [\App\Http\Controllers\MobileController::class, 'eliminarUbicacion'])->name('almacenes.zonas.posiciones.eliminar');
        Route::get('/inventario/nuevo',                                       [\App\Http\Controllers\MobileController::class, 'showInventario'])->name('inventario.nueva');
        Route::post('/inventario/guardar',                 [\App\Http\Controllers\MobileController::class, 'guardarInventario'])->name('inventario.guardar');
        Route::get('/compra/nueva',                        [\App\Http\Controllers\MobileController::class, 'showCompraOcr'])->name('compra.ocr');
        Route::post('/compra/ocr',                         [\App\Http\Controllers\MobileController::class, 'procesarOcr'])->name('compra.procesar-ocr');
        Route::post('/compra/guardar',                     [\App\Http\Controllers\MobileController::class, 'guardarCompra'])->name('compra.guardar');
        Route::get('/compras/validar',                     [\App\Http\Controllers\MobileController::class, 'listValidarCompras'])->name('compras.validar');
        Route::post('/compras/{purchase}/confirmar',       [\App\Http\Controllers\MobileController::class, 'confirmarCompra'])->name('compras.confirmar');
        Route::get('/venta/nueva',                         [\App\Http\Controllers\MobileController::class, 'showVenta'])->name('venta.nueva');
        Route::post('/venta/guardar',                      [\App\Http\Controllers\MobileController::class, 'guardarVenta'])->name('venta.guardar');
        Route::get('/produccion/nueva',                    [\App\Http\Controllers\MobileController::class, 'showProduccion'])->name('produccion.nueva');
        Route::post('/produccion/guardar',                 [\App\Http\Controllers\MobileController::class, 'guardarProduccion'])->name('produccion.guardar');
        Route::get('/deuda/nueva',                         [\App\Http\Controllers\MobileController::class, 'showDeuda'])->name('deuda.nueva');
        Route::post('/deuda/guardar',                      [\App\Http\Controllers\MobileController::class, 'guardarDeuda'])->name('deuda.guardar');
        Route::get('/deudas/validar',                      [\App\Http\Controllers\MobileController::class, 'listValidarDeudas'])->name('deudas.validar');
        Route::post('/deudas/{debt}/activar',              [\App\Http\Controllers\MobileController::class, 'activarDeuda'])->name('deudas.activar');
        Route::get('/diseno-producto/nuevo',               [\App\Http\Controllers\MobileController::class, 'showDisenoProducto'])->name('diseno-producto.nuevo');
        Route::post('/diseno-producto/guardar',            [\App\Http\Controllers\MobileController::class, 'guardarDisenoProducto'])->name('diseno-producto.guardar');

        // ── Listas de consulta ────────────────────────────────────────────
        Route::get('/inventario',                          [\App\Http\Controllers\MobileController::class, 'listInventario'])->name('inventario.lista');
        Route::get('/ventas',                              [\App\Http\Controllers\MobileController::class, 'listVentas'])->name('ventas.lista');
        Route::get('/compras',                             [\App\Http\Controllers\MobileController::class, 'listCompras'])->name('compras.lista');
        Route::get('/deudas',                              [\App\Http\Controllers\MobileController::class, 'listDeudas'])->name('deudas.lista');
        Route::get('/produccion/lista',                    [\App\Http\Controllers\MobileController::class, 'listProduccion'])->name('produccion.lista');
        Route::get('/disenos-producto/lista',              [\App\Http\Controllers\MobileController::class, 'listDisenosProducto'])->name('disenos-producto.lista');

        // ── Ecommerce / CMS / Tienda ──────────────────────────────────────
        Route::get('/ecommerce',                           [\App\Http\Controllers\MobileController::class, 'showEcommerce'])->name('ecommerce.index');

        // CMS Singletons
        Route::get('/cms/hero',                            [\App\Http\Controllers\MobileController::class, 'showCmsHero'])->name('cms.hero');
        Route::post('/cms/hero/guardar',                   [\App\Http\Controllers\MobileController::class, 'guardarCmsHero'])->name('cms.hero.guardar');
        Route::get('/cms/about',                           [\App\Http\Controllers\MobileController::class, 'showCmsAbout'])->name('cms.about');
        Route::post('/cms/about/guardar',                  [\App\Http\Controllers\MobileController::class, 'guardarCmsAbout'])->name('cms.about.guardar');
        Route::get('/cms/contacto',                        [\App\Http\Controllers\MobileController::class, 'showCmsContacto'])->name('cms.contacto');
        Route::post('/cms/contacto/guardar',               [\App\Http\Controllers\MobileController::class, 'guardarCmsContacto'])->name('cms.contacto.guardar');

        // CMS Servicios
        Route::get('/cms/servicios',                       [\App\Http\Controllers\MobileController::class, 'listCmsServicios'])->name('cms.servicios.index');
        Route::get('/cms/servicios/nuevo',                 [\App\Http\Controllers\MobileController::class, 'showCmsServicioForm'])->name('cms.servicios.nuevo');
        Route::get('/cms/servicios/{id}/editar',           [\App\Http\Controllers\MobileController::class, 'showCmsServicioForm'])->name('cms.servicios.editar');
        Route::post('/cms/servicios/guardar',              [\App\Http\Controllers\MobileController::class, 'guardarCmsServicio'])->name('cms.servicios.guardar');
        Route::post('/cms/servicios/{id}/eliminar',        [\App\Http\Controllers\MobileController::class, 'eliminarCmsServicio'])->name('cms.servicios.eliminar');

        // CMS Equipo
        Route::get('/cms/equipo',                          [\App\Http\Controllers\MobileController::class, 'listCmsEquipo'])->name('cms.equipo.index');
        Route::get('/cms/equipo/nuevo',                    [\App\Http\Controllers\MobileController::class, 'showCmsEquipoForm'])->name('cms.equipo.nuevo');
        Route::get('/cms/equipo/{id}/editar',              [\App\Http\Controllers\MobileController::class, 'showCmsEquipoForm'])->name('cms.equipo.editar');
        Route::post('/cms/equipo/guardar',                 [\App\Http\Controllers\MobileController::class, 'guardarCmsEquipo'])->name('cms.equipo.guardar');
        Route::post('/cms/equipo/{id}/eliminar',           [\App\Http\Controllers\MobileController::class, 'eliminarCmsEquipo'])->name('cms.equipo.eliminar');

        // CMS Testimonios
        Route::get('/cms/testimonios',                     [\App\Http\Controllers\MobileController::class, 'listCmsTestimonios'])->name('cms.testimonios.index');
        Route::get('/cms/testimonios/nuevo',               [\App\Http\Controllers\MobileController::class, 'showCmsTestimonioForm'])->name('cms.testimonios.nuevo');
        Route::get('/cms/testimonios/{id}/editar',         [\App\Http\Controllers\MobileController::class, 'showCmsTestimonioForm'])->name('cms.testimonios.editar');
        Route::post('/cms/testimonios/guardar',            [\App\Http\Controllers\MobileController::class, 'guardarCmsTestimonio'])->name('cms.testimonios.guardar');
        Route::post('/cms/testimonios/{id}/eliminar',      [\App\Http\Controllers\MobileController::class, 'eliminarCmsTestimonio'])->name('cms.testimonios.eliminar');

        // CMS FAQs
        Route::get('/cms/faqs',                            [\App\Http\Controllers\MobileController::class, 'listCmsFaqs'])->name('cms.faqs.index');
        Route::get('/cms/faqs/nuevo',                      [\App\Http\Controllers\MobileController::class, 'showCmsFaqForm'])->name('cms.faqs.nuevo');
        Route::get('/cms/faqs/{id}/editar',                [\App\Http\Controllers\MobileController::class, 'showCmsFaqForm'])->name('cms.faqs.editar');
        Route::post('/cms/faqs/guardar',                   [\App\Http\Controllers\MobileController::class, 'guardarCmsFaq'])->name('cms.faqs.guardar');
        Route::post('/cms/faqs/{id}/eliminar',             [\App\Http\Controllers\MobileController::class, 'eliminarCmsFaq'])->name('cms.faqs.eliminar');

        // CMS Posts
        Route::get('/cms/posts',                           [\App\Http\Controllers\MobileController::class, 'listCmsPosts'])->name('cms.posts.index');
        Route::get('/cms/posts/nuevo',                     [\App\Http\Controllers\MobileController::class, 'showCmsPostForm'])->name('cms.posts.nuevo');
        Route::get('/cms/posts/{id}/editar',               [\App\Http\Controllers\MobileController::class, 'showCmsPostForm'])->name('cms.posts.editar');
        Route::post('/cms/posts/guardar',                   [\App\Http\Controllers\MobileController::class, 'guardarCmsPost'])->name('cms.posts.guardar');
        Route::post('/cms/posts/{id}/eliminar',             [\App\Http\Controllers\MobileController::class, 'eliminarCmsPost'])->name('cms.posts.eliminar');

        // CMS Logos de Clientes
        Route::get('/cms/logos',                           [\App\Http\Controllers\MobileController::class, 'listCmsLogos'])->name('cms.logos.index');
        Route::get('/cms/logos/nuevo',                     [\App\Http\Controllers\MobileController::class, 'showCmsLogoForm'])->name('cms.logos.nuevo');
        Route::get('/cms/logos/{id}/editar',               [\App\Http\Controllers\MobileController::class, 'showCmsLogoForm'])->name('cms.logos.editar');
        Route::post('/cms/logos/guardar',                   [\App\Http\Controllers\MobileController::class, 'guardarCmsLogo'])->name('cms.logos.guardar');
        Route::post('/cms/logos/{id}/eliminar',             [\App\Http\Controllers\MobileController::class, 'eliminarCmsLogo'])->name('cms.logos.eliminar');

        // Tienda — Productos
        Route::get('/tienda/productos',                    [\App\Http\Controllers\MobileController::class, 'listTiendaProductos'])->name('tienda.productos.index');
        Route::get('/tienda/productos/nuevo',              [\App\Http\Controllers\MobileController::class, 'showTiendaProductoForm'])->name('tienda.productos.nuevo');
        Route::get('/tienda/productos/{id}/editar',        [\App\Http\Controllers\MobileController::class, 'showTiendaProductoForm'])->name('tienda.productos.editar');
        Route::post('/tienda/productos/guardar',           [\App\Http\Controllers\MobileController::class, 'guardarTiendaProducto'])->name('tienda.productos.guardar');
        Route::post('/tienda/productos/{id}/eliminar',     [\App\Http\Controllers\MobileController::class, 'eliminarTiendaProducto'])->name('tienda.productos.eliminar');

        // Tienda — Pedidos
        Route::get('/tienda/pedidos',                      [\App\Http\Controllers\MobileController::class, 'listTiendaPedidos'])->name('tienda.pedidos.index');
        Route::post('/tienda/pedidos/{id}/estado',         [\App\Http\Controllers\MobileController::class, 'actualizarEstadoPedido'])->name('tienda.pedidos.estado');

        // Tienda — Categorías
        Route::get('/tienda/categorias',                   [\App\Http\Controllers\MobileController::class, 'listTiendaCategorias'])->name('tienda.categorias.index');
        Route::get('/tienda/categorias/nuevo',             [\App\Http\Controllers\MobileController::class, 'showTiendaCategoriaForm'])->name('tienda.categorias.nuevo');
        Route::get('/tienda/categorias/{id}/editar',       [\App\Http\Controllers\MobileController::class, 'showTiendaCategoriaForm'])->name('tienda.categorias.editar');
        Route::post('/tienda/categorias/guardar',          [\App\Http\Controllers\MobileController::class, 'guardarTiendaCategoria'])->name('tienda.categorias.guardar');
        Route::post('/tienda/categorias/{id}/eliminar',    [\App\Http\Controllers\MobileController::class, 'eliminarTiendaCategoria'])->name('tienda.categorias.eliminar');

        // Tienda — Clientes
        Route::get('/tienda/clientes',                     [\App\Http\Controllers\MobileController::class, 'listTiendaClientes'])->name('tienda.clientes.index');
    });
});

Route::get('/panel', function () {
    $user = Auth::user();

    if (!$user) {
        return redirect('/pro/login');
    }

    if ($user->hasRole('super_admin')) {
        return redirect('/admin');
    }

    $empresa = $user->empresa;

    if (!$empresa) {
        return redirect('/pro/login');
    }

    $slug = $empresa->slug;

    // El rol marketing siempre va al panel básico (mailing)
    if ($user->hasRole('marketing')) {
        return redirect("/app/{$slug}");
    }

    $plan = $empresa->plan ?? 'basic';

    return match ($plan) {
        'enterprise' => redirect("/enterprise/{$slug}"),
        'pro'        => redirect("/pro/{$slug}"),
        default      => redirect("/app/{$slug}"),
    };
});

// ── Plantilla CSV para importación de contactos de mailing ────────────────
Route::get('/mailing/contacts/template', function () {
    if (! Auth::check()) {
        abort(403);
    }

    $csv = implode("\n", [
        'nombre,email,telefono,notas',
        'Juan Pérez,juan.perez@ejemplo.com,+506-8888-1234,Cliente frecuente',
        'María González,maria.gonzalez@ejemplo.com,,Contacto nuevo',
        'Empresa ABC,contacto@empresaabc.com,+506-2222-3333,Proveedor',
    ]);

    return response($csv, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="plantilla_contactos_mailing.csv"',
    ]);
})->name('mailing.contacts.template');

// ── Preview carta de presentación ────────────────────────────────────────
Route::get('/carta-preview/{slug}', function (string $slug) {
    if (! Auth::check()) abort(403);

    $empresa = Empresa::where('slug', $slug)->firstOrFail();

    if (Auth::user()->empresa_id !== $empresa->id && ! Auth::user()->hasRole('super_admin')) {
        abort(403);
    }

    $carta    = \App\Models\CartaPresentacion::withoutGlobalScopes()->where('empresa_id', $empresa->id)->first();
    $servicios = \App\Models\CmsService::withoutGlobalScopes()->where('empresa_id', $empresa->id)->where('activo', true)->orderBy('sort_order')->get();
    $contacto  = \App\Models\CmsContact::withoutGlobalScopes()->where('empresa_id', $empresa->id)->first();

    if (! $carta) {
        return '<p style="font-family:sans-serif;color:#888;padding:40px;text-align:center;">Guarda la carta primero para ver la vista previa.</p>';
    }

    $template = in_array($carta->template, ['ejecutivo', 'vanguardia', 'elite'])
        ? $carta->template
        : 'ejecutivo';

    return view("emails.carta-templates.{$template}", compact('empresa', 'carta', 'servicios', 'contacto'));
})->name('carta.preview');

Route::get('/app/{empresa}/debts/{debt}/payments/print', [\App\Http\Controllers\DebtPrintController::class, 'paymentHistory'])
    ->middleware(['auth'])
    ->name('debt.payments.print');

Route::get('/enterprise/{empresa}/product-designs/{design}/equilibrio/print', [\App\Http\Controllers\ProductDesignPrintController::class, 'equilibrio'])
    ->middleware(['auth'])
    ->name('product-design.equilibrio.print');

Route::get('/fichas/download/{file}', function ($file) {
    if (!Auth::check()) {
        abort(403);
    }
    
    $path = "fichas/{$file}";
    if (!Storage::disk('local')->exists($path)) {
        abort(404);
    }
    
    return Storage::disk('local')->download($path);
})->name('fichas.download');
