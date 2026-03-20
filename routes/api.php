<?php

use App\Http\Controllers\Api\CmsController;
use App\Http\Middleware\ValidateCmsApiToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — CMS público
|--------------------------------------------------------------------------
| Todos los endpoints requieren autenticación con Bearer token.
| El token se genera desde el panel: /app/{slug}/api-docs
|
| Base URL: /api/cms/{slug}/...
|
| Header requerido:
|   Authorization: Bearer {tu-token}
|
| Ejemplo: GET /api/cms/mi-empresa/all
|          GET /api/cms/mi-empresa/posts/mi-primera-noticia
*/

Route::prefix('cms/{slug}')
    ->middleware(ValidateCmsApiToken::class)
    ->group(function () {
        Route::get('all',          [CmsController::class, 'all']);
        Route::get('hero',         [CmsController::class, 'hero']);
        Route::get('about',        [CmsController::class, 'about']);
        Route::get('services',     [CmsController::class, 'services']);
        Route::get('team',         [CmsController::class, 'team']);
        Route::get('clients',      [CmsController::class, 'clients']);
        Route::get('testimonials', [CmsController::class, 'testimonials']);
        Route::get('faq',          [CmsController::class, 'faq']);
        Route::get('contact',      [CmsController::class, 'contact']);
        Route::get('posts',        [CmsController::class, 'posts']);
        Route::get('posts/{post}', [CmsController::class, 'post']);
    });
