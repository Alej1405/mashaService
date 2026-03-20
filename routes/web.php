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

    $plan = $empresa->plan ?? 'basic';
    $slug = $empresa->slug;

    return match ($plan) {
        'enterprise' => redirect("/enterprise/{$slug}"),
        'pro'        => redirect("/pro/{$slug}"),
        default      => redirect("/basic/{$slug}"),
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
