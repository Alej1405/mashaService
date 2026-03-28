<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Empresa;

Route::get('/', function () {
    return redirect('/app/login');
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
