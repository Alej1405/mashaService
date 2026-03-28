<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Empresa;

Route::get('/', function () {
    return redirect('/app/login');
});

// ── Portal Móvil ──────────────────────────────────────────────────────────
Route::prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/login',  [\App\Http\Controllers\MobileController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\MobileController::class, 'login'])->name('login.post');

    Route::middleware('mobile.auth')->group(function () {
        Route::get('/',                                    [\App\Http\Controllers\MobileController::class, 'index'])->name('index');
        Route::post('/logout',                             [\App\Http\Controllers\MobileController::class, 'logout'])->name('logout');
        Route::get('/inventario/nuevo',                    [\App\Http\Controllers\MobileController::class, 'showInventario'])->name('inventario.nueva');
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
