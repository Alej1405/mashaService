<?php

namespace App\Filament\Ecommerce\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Documentación de la API de Tienda (E-Commerce), dentro de su propio panel.
 *
 * Solo documenta y gestiona el token de empresa; NO altera los endpoints.
 * Los endpoints se declaran como estructura de datos y la vista los itera.
 */
class EcommerceApiDocsPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-code-bracket';
    protected static ?string $navigationLabel = 'API / Documentación';
    protected static ?string $title           = 'API Tienda — Documentación';
    protected static ?string $navigationGroup = 'Desarrolladores';
    protected static ?int    $navigationSort  = 99;

    protected static string $view = 'filament.ecommerce.pages.ecommerce-api-docs';

    public ?string $newToken = null;

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('tienda');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_token')
                ->label('Generar nuevo token')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('¿Generar nuevo token de Tienda?')
                ->modalDescription('El token anterior quedará inválido. Deberás actualizar la configuración en tu frontend.')
                ->action(function () {
                    $empresa = Filament::getTenant();

                    PersonalAccessToken::where('tokenable_type', \App\Models\Empresa::class)
                        ->where('tokenable_id', $empresa->id)
                        ->where('name', 'ecommerce-api')
                        ->delete();

                    $token = $empresa->createToken('ecommerce-api');
                    $this->newToken = $token->plainTextToken;

                    Notification::make()
                        ->title('Token generado correctamente')
                        ->body('Copia el token ahora — no se volverá a mostrar.')
                        ->warning()
                        ->persistent()
                        ->send();
                }),

            Action::make('revoke_token')
                ->label('Revocar token')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('¿Revocar token de Tienda?')
                ->modalDescription('La API dejará de funcionar hasta que generes uno nuevo.')
                ->action(function () {
                    $empresa = Filament::getTenant();

                    PersonalAccessToken::where('tokenable_type', \App\Models\Empresa::class)
                        ->where('tokenable_id', $empresa->id)
                        ->where('name', 'ecommerce-api')
                        ->delete();

                    $this->newToken = null;

                    Notification::make()->title('Token revocado')->danger()->send();
                }),
        ];
    }

    public function getViewData(): array
    {
        $empresa = Filament::getTenant();

        $token = PersonalAccessToken::where('tokenable_type', \App\Models\Empresa::class)
            ->where('tokenable_id', $empresa->id)
            ->where('name', 'ecommerce-api')
            ->latest()
            ->first();

        return [
            'empresa'       => $empresa,
            'baseUrl'       => 'https://erp.mashaec.net/api/ecommerce/' . $empresa->slug,
            'tieneToken'    => (bool) $token,
            'tokenCreadoEn' => $token?->created_at?->format('d/m/Y H:i'),
            'tokenUsadoEn'  => $token?->last_used_at?->format('d/m/Y H:i') ?? 'Nunca',
            'newToken'      => $this->newToken,
            'grupos'        => self::grupos(),
        ];
    }

    /**
     * Endpoints de la API de Tienda agrupados. Estructura de datos → la vista itera.
     * 'auth' = requiere token del cliente (obtenido en /auth/login), no el de empresa.
     *
     * @return array<int,array{titulo:string, endpoints:array<int,array<string,mixed>>}>
     */
    private static function grupos(): array
    {
        return [
            [
                'titulo' => 'Autenticación de clientes',
                'endpoints' => [
                    ['metodo' => 'POST', 'ruta' => '/auth/register', 'auth' => false, 'desc' => 'Registra un cliente nuevo.', 'ejemplo' => '{ "nombre": "Ana", "email": "ana@mail.com", "password": "secret123" }'],
                    ['metodo' => 'POST', 'ruta' => '/auth/login', 'auth' => false, 'desc' => 'Inicia sesión y devuelve el token del cliente.', 'ejemplo' => '{ "email": "ana@mail.com", "password": "secret123" }'],
                    ['metodo' => 'POST', 'ruta' => '/auth/logout', 'auth' => true, 'desc' => 'Cierra la sesión del cliente.', 'ejemplo' => null],
                    ['metodo' => 'GET', 'ruta' => '/auth/me', 'auth' => true, 'desc' => 'Datos del cliente autenticado.', 'ejemplo' => null],
                    ['metodo' => 'POST', 'ruta' => '/auth/forgot-password', 'auth' => false, 'desc' => 'Envía correo de recuperación.', 'ejemplo' => '{ "email": "ana@mail.com" }'],
                    ['metodo' => 'POST', 'ruta' => '/auth/reset-password', 'auth' => false, 'desc' => 'Restablece la contraseña con el token recibido.', 'ejemplo' => '{ "token": "…", "email": "ana@mail.com", "password": "nueva123" }'],
                ],
            ],
            [
                'titulo' => 'Productos',
                'endpoints' => [
                    ['metodo' => 'GET', 'ruta' => '/products', 'auth' => false, 'desc' => 'Lista de productos publicados (con paginación y filtros).', 'ejemplo' => null],
                    ['metodo' => 'GET', 'ruta' => '/products/featured', 'auth' => false, 'desc' => 'Productos destacados.', 'ejemplo' => null],
                    ['metodo' => 'GET', 'ruta' => '/products/{slug}', 'auth' => false, 'desc' => 'Detalle de un producto por slug.', 'ejemplo' => null],
                    ['metodo' => 'GET', 'ruta' => '/products/{id}/related', 'auth' => false, 'desc' => 'Productos relacionados.', 'ejemplo' => null],
                ],
            ],
            [
                'titulo' => 'Categorías',
                'endpoints' => [
                    ['metodo' => 'GET', 'ruta' => '/categories', 'auth' => false, 'desc' => 'Árbol de categorías.', 'ejemplo' => null],
                    ['metodo' => 'GET', 'ruta' => '/categories/{slug}', 'auth' => false, 'desc' => 'Detalle de una categoría con sus productos.', 'ejemplo' => null],
                ],
            ],
            [
                'titulo' => 'Cupones',
                'endpoints' => [
                    ['metodo' => 'POST', 'ruta' => '/coupons/validate', 'auth' => false, 'desc' => 'Valida un cupón y devuelve el descuento.', 'ejemplo' => '{ "codigo": "VERANO10", "subtotal": 100.00 }'],
                ],
            ],
            [
                'titulo' => 'Cuenta del cliente',
                'endpoints' => [
                    ['metodo' => 'GET', 'ruta' => '/customers/me', 'auth' => true, 'desc' => 'Perfil del cliente.', 'ejemplo' => null],
                    ['metodo' => 'PUT', 'ruta' => '/customers/me', 'auth' => true, 'desc' => 'Actualiza el perfil.', 'ejemplo' => '{ "nombre": "Ana María", "telefono": "+593…" }'],
                    ['metodo' => 'PUT', 'ruta' => '/customers/me/password', 'auth' => true, 'desc' => 'Cambia la contraseña.', 'ejemplo' => '{ "password_actual": "…", "password": "nueva123" }'],
                    ['metodo' => 'GET', 'ruta' => '/customers/me/addresses', 'auth' => true, 'desc' => 'Direcciones del cliente.', 'ejemplo' => null],
                    ['metodo' => 'POST', 'ruta' => '/customers/me/addresses', 'auth' => true, 'desc' => 'Agrega una dirección.', 'ejemplo' => '{ "linea1": "Av. 123", "ciudad": "Quito", "provincia": "Pichincha" }'],
                    ['metodo' => 'PUT', 'ruta' => '/customers/me/addresses/{id}', 'auth' => true, 'desc' => 'Edita una dirección.', 'ejemplo' => null],
                    ['metodo' => 'DELETE', 'ruta' => '/customers/me/addresses/{id}', 'auth' => true, 'desc' => 'Elimina una dirección.', 'ejemplo' => null],
                ],
            ],
            [
                'titulo' => 'Pedidos',
                'endpoints' => [
                    ['metodo' => 'POST', 'ruta' => '/orders', 'auth' => true, 'desc' => 'Crea un pedido.', 'ejemplo' => '{ "items": [{ "product_id": 1, "cantidad": 2 }], "direccion_id": 5, "cupon": "VERANO10" }'],
                    ['metodo' => 'GET', 'ruta' => '/orders', 'auth' => true, 'desc' => 'Pedidos del cliente.', 'ejemplo' => null],
                    ['metodo' => 'GET', 'ruta' => '/orders/{id}', 'auth' => true, 'desc' => 'Detalle de un pedido.', 'ejemplo' => null],
                ],
            ],
            [
                'titulo' => 'Pagos',
                'endpoints' => [
                    ['metodo' => 'POST', 'ruta' => '/payments/intent', 'auth' => true, 'desc' => 'Crea la intención de pago de un pedido.', 'ejemplo' => '{ "order_id": 10 }'],
                    ['metodo' => 'POST', 'ruta' => '/payments/webhook', 'auth' => false, 'desc' => 'Webhook de la pasarela de pago (uso interno).', 'ejemplo' => null],
                ],
            ],
        ];
    }
}
