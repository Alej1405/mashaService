<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Empresa;

Route::get('/', function () {
    return redirect('/app/login');
});

Route::get('/app', function () {
    $user = Auth::user();
    
    if (!$user) {
        return redirect('/app/login');
    }
    
    // Si es super_admin y no tiene empresa asignada, intentar redirigir a la primera empresa activa
    if ($user instanceof \App\Models\User && $user->hasRole('super_admin') && !$user->empresa_id) {
        $firstEmpresa = Empresa::where('activo', true)->first();
        if ($firstEmpresa) {
            return redirect("/app/{$firstEmpresa->slug}");
        }
    }

    // Redirigir al panel de administración si no hay empresa vinculada (evita bucles o 404)
    if (!$user->empresa_id || !$user->empresa) {
        return redirect('/admin');
    }
    
    return redirect("/app/{$user->empresa->slug}");
});

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
