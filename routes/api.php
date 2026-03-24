<?php

use App\Http\Controllers\Api\CmsController;
use App\Http\Controllers\Api\Store\StoreAuthController;
use App\Http\Controllers\Api\Store\StoreCategoryController;
use App\Http\Controllers\Api\Store\StoreCouponController;
use App\Http\Controllers\Api\Store\StoreOrderController;
use App\Http\Controllers\Api\Store\StoreCustomerController;
use App\Http\Controllers\Api\Store\StorePaymentController;
use App\Http\Controllers\Api\Store\StoreProductController;
use App\Http\Middleware\EnsureStoreCustomer;
use App\Http\Middleware\ResolveStoreEmpresa;
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

/*
|--------------------------------------------------------------------------
| API Routes — E-Commerce Store
|--------------------------------------------------------------------------
| Base URL: /api/store/{slug}/...
|
| Rutas públicas: productos, categorías, auth/login, auth/register
| Rutas protegidas (Bearer token del StoreCustomer): órdenes, perfil, direcciones
*/

Route::prefix('store/{slug}')
    ->middleware(ResolveStoreEmpresa::class)
    ->group(function () {

        // ── Catálogo público ────────────────────────────────────────────
        Route::get('products',              [StoreProductController::class, 'index']);
        Route::get('products/featured',     [StoreProductController::class, 'featured']);
        Route::get('products/{slug}',       [StoreProductController::class, 'show']);
        Route::get('products/{id}/related', [StoreProductController::class, 'related']);

        Route::get('categories',            [StoreCategoryController::class, 'index']);
        Route::get('categories/{slug}',     [StoreCategoryController::class, 'show']);

        // ── Auth ────────────────────────────────────────────────────────
        Route::prefix('auth')->group(function () {
            Route::post('login',            [StoreAuthController::class, 'login']);
            Route::post('register',         [StoreAuthController::class, 'register']);
            Route::post('forgot-password',  [StoreAuthController::class, 'forgotPassword']);
            Route::post('reset-password',   [StoreAuthController::class, 'resetPassword']);

            Route::middleware(['auth:sanctum', EnsureStoreCustomer::class])->group(function () {
                Route::get('me',            [StoreAuthController::class, 'me']);
                Route::post('logout',       [StoreAuthController::class, 'logout']);
            });
        });

        // ── Rutas protegidas ────────────────────────────────────────────
        Route::middleware(['auth:sanctum', EnsureStoreCustomer::class])->group(function () {

            Route::post('orders',           [StoreOrderController::class, 'store']);
            Route::get('orders',            [StoreOrderController::class, 'index']);
            Route::get('orders/{id}',       [StoreOrderController::class, 'show']);

            Route::prefix('customers/me')->group(function () {
                Route::get('/',                 [StoreCustomerController::class, 'show']);
                Route::put('/',                 [StoreCustomerController::class, 'update']);
                Route::put('password',          [StoreCustomerController::class, 'updatePassword']);
                Route::get('addresses',         [StoreCustomerController::class, 'addresses']);
                Route::post('addresses',        [StoreCustomerController::class, 'storeAddress']);
                Route::put('addresses/{id}',    [StoreCustomerController::class, 'updateAddress']);
                Route::delete('addresses/{id}', [StoreCustomerController::class, 'destroyAddress']);
            });
        });

        // ── Cupones y pagos ─────────────────────────────────────────────
        Route::post('coupons/validate',     [StoreCouponController::class, 'validate']);
        Route::post('payments/intent',      [StorePaymentController::class, 'intent']);
        Route::post('payments/webhook',     [StorePaymentController::class, 'webhook']);
    });

/*
|--------------------------------------------------------------------------
| API Routes — CMS público
|--------------------------------------------------------------------------
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
        Route::get('terminos',     [CmsController::class, 'terminos']);
    });
