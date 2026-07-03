<?php

use App\Http\Controllers\Api\CmsController;
use App\Http\Controllers\Api\Store\StoreAuthController;
use App\Http\Controllers\Api\Store\StoreCategoryController;
use App\Http\Controllers\Api\Store\StoreCouponController;
use App\Http\Controllers\Api\Store\StoreOrderController;
use App\Http\Controllers\Api\Store\StoreCustomerController;
use App\Http\Controllers\Api\Store\StorePaymentController;
use App\Http\Controllers\Api\Store\StoreProductController;
use App\Http\Controllers\Api\N8n\AuthController as N8nAuthController;
use App\Http\Controllers\Api\N8n\CmsController as N8nCmsController;
use App\Http\Controllers\Api\N8n\StoreController as N8nStoreController;
use App\Http\Middleware\EnsureStoreCustomer;
use App\Http\Middleware\N8nAuthenticate;
use App\Http\Middleware\N8nGate;
use App\Http\Middleware\N8nRequireModule;
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
| Base URL: /api/ecommerce/{empresa_slug}/...
|
| Rutas públicas: productos, categorías, auth/login, auth/register
| Rutas protegidas (Bearer token del StoreCustomer): órdenes, perfil, direcciones
*/

Route::prefix('ecommerce/{empresa_slug}')
    ->middleware(ResolveStoreEmpresa::class)
    ->group(function () {

        // ── Catálogo público ────────────────────────────────────────────
        Route::get('products',              [StoreProductController::class, 'index']);
        Route::get('products/featured',     [StoreProductController::class, 'featured']);
        Route::get('products/{slug}',       [StoreProductController::class, 'show']);
        Route::get('products/{id}/related', [StoreProductController::class, 'related']);

        Route::get('categories',                [StoreCategoryController::class, 'index']);
        Route::get('categories/{slug}',         [StoreCategoryController::class, 'show']);
        Route::get('categories/{slug}/landing', [StoreCategoryController::class, 'landing']);

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
        Route::get('products',     [CmsController::class, 'products']);
        Route::get('team',         [CmsController::class, 'team']);
        Route::get('clients',      [CmsController::class, 'clients']);
        Route::get('testimonials', [CmsController::class, 'testimonials']);
        Route::get('faq',          [CmsController::class, 'faq']);
        Route::get('contact',      [CmsController::class, 'contact']);
        Route::get('posts',        [CmsController::class, 'posts']);
        Route::get('posts/{post}', [CmsController::class, 'post']);
        Route::get('terminos',     [CmsController::class, 'terminos']);
    });

/*
|--------------------------------------------------------------------------
| API Routes — Integración n8n (Telegram)  [SUPERFICIE AISLADA]
|--------------------------------------------------------------------------
| Base URL: /api/n8n/v1/...
|
| Superficie exclusiva para n8n, separada del resto de APIs. Protegida por:
|   - N8nGate: kill-switch (config n8n.enabled) + secreto compartido (X-N8N-Secret)
|   - throttle: límite por minuto (config n8n.rate_limit)
|   - N8nAuthenticate: token de sesión propio (tabla telegram_sessions)
|
| Suspender n8n sin afectar nada más: N8N_API_ENABLED=false (o rotar el secreto).
| Fases: 1) auth (aquí)  2) cms  3) store — se agregan incrementalmente.
*/

Route::prefix('n8n/v1')
    ->middleware([N8nGate::class, 'throttle:n8n'])
    ->group(function () {

        // Login: único endpoint sin token de sesión (sí exige secreto + gate).
        Route::post('auth/login', [N8nAuthController::class, 'login']);

        // Resto: exige token de sesión de Telegram.
        Route::middleware(N8nAuthenticate::class)->group(function () {
            Route::get('auth/me',              [N8nAuthController::class, 'me']);
            Route::post('auth/select-empresa', [N8nAuthController::class, 'selectEmpresa']);
            Route::post('auth/logout',         [N8nAuthController::class, 'logout']);

            // Módulo CMS (requiere módulo 'marketing' en la empresa activa).
            Route::prefix('cms')->middleware(N8nRequireModule::class . ':marketing')->group(function () {
                Route::get('resumen',          [N8nCmsController::class, 'resumen']);
                Route::get('posts',            [N8nCmsController::class, 'postsIndex']);
                Route::post('posts',           [N8nCmsController::class, 'postsStore']);
                Route::delete('posts/{id}',    [N8nCmsController::class, 'postsDestroy']);
                Route::get('services',         [N8nCmsController::class, 'servicesIndex']);
                Route::post('services',        [N8nCmsController::class, 'servicesStore']);
                Route::delete('services/{id}', [N8nCmsController::class, 'servicesDestroy']);
                Route::get('faq',              [N8nCmsController::class, 'faqIndex']);
                Route::post('faq',             [N8nCmsController::class, 'faqStore']);
                Route::delete('faq/{id}',      [N8nCmsController::class, 'faqDestroy']);
            });

            // Módulo Tienda (requiere módulo 'tienda' en la empresa activa).
            Route::prefix('store')->middleware(N8nRequireModule::class . ':tienda')->group(function () {
                Route::get('resumen',           [N8nStoreController::class, 'resumen']);
                Route::get('coupons',           [N8nStoreController::class, 'couponsIndex']);
                Route::post('coupons',          [N8nStoreController::class, 'couponsStore']);
                Route::delete('coupons/{id}',   [N8nStoreController::class, 'couponsDestroy']);
                Route::get('products',          [N8nStoreController::class, 'productsIndex']);
                Route::put('products/{id}',     [N8nStoreController::class, 'productsUpdate']);
                Route::delete('products/{id}',  [N8nStoreController::class, 'productsDestroy']);
            });
        });
    });
