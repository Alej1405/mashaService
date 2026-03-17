<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Empresa;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', function () {
    $user = Auth::user();

    if (!$user) {
        return redirect('/pro/login');
    }

    return redirect('/panel');
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
