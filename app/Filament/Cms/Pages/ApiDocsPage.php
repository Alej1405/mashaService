<?php

namespace App\Filament\Cms\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Documentación de la API pública de CMS, dentro de su propio panel.
 *
 * Solo documenta y gestiona el token; NO altera los endpoints (intocables).
 * Los endpoints se declaran como estructura de datos y la vista los itera.
 */
class ApiDocsPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-code-bracket';
    protected static ?string $navigationLabel = 'API / Documentación';
    protected static ?string $title           = 'API CMS — Documentación';
    protected static ?string $navigationGroup = 'Desarrolladores';
    protected static ?int    $navigationSort  = 99;

    protected static string $view = 'filament.cms.pages.api-docs';

    public ?string $newToken = null;

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('marketing');
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
                ->modalHeading('¿Generar nuevo token?')
                ->modalDescription('El token anterior quedará inválido. Deberás actualizar la configuración en tu proyecto que consume la API.')
                ->action(function () {
                    $empresa = Filament::getTenant();

                    PersonalAccessToken::where('tokenable_type', \App\Models\Empresa::class)
                        ->where('tokenable_id', $empresa->id)
                        ->where('name', 'cms-api')
                        ->delete();

                    $token = $empresa->createToken('cms-api');
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
                ->modalHeading('¿Revocar token?')
                ->modalDescription('La API dejará de funcionar hasta que generes uno nuevo.')
                ->action(function () {
                    $empresa = Filament::getTenant();

                    PersonalAccessToken::where('tokenable_type', \App\Models\Empresa::class)
                        ->where('tokenable_id', $empresa->id)
                        ->where('name', 'cms-api')
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
            ->where('name', 'cms-api')
            ->latest()
            ->first();

        return [
            'empresa'       => $empresa,
            'tieneToken'    => (bool) $token,
            'tokenCreadoEn' => $token?->created_at?->format('d/m/Y H:i'),
            'tokenUsadoEn'  => $token?->last_used_at?->format('d/m/Y H:i') ?? 'Nunca',
            'newToken'      => $this->newToken,
            'baseUrl'       => 'https://erp.mashaec.net/api/cms/' . $empresa->slug,
            'endpoints'     => self::endpoints(),
        ];
    }

    /**
     * Endpoints públicos de la API CMS (GET). Estructura de datos → la vista itera.
     *
     * @return array<int,array{metodo:string, ruta:string, desc:string, ejemplo:string}>
     */
    private static function endpoints(): array
    {
        return [
            [
                'metodo' => 'GET', 'ruta' => '/hero', 'desc' => 'Sección principal (título, subtítulo, CTA).',
                'ejemplo' => <<<'JSON'
{
  "titulo": "Logística global, sin fronteras",
  "subtitulo": "Importa y exporta con respaldo",
  "descripcion": "Texto del hero…",
  "imagen": "https://.../hero.jpg",
  "cta_texto": "Cotiza ahora",
  "cta_url": "/contacto"
}
JSON,
            ],
            [
                'metodo' => 'GET', 'ruta' => '/about', 'desc' => 'Sección "Nosotros": números, características y diferenciadores.',
                'ejemplo' => <<<'JSON'
{
  "titulo": "Sobre nosotros",
  "descripcion": "…",
  "imagen": "https://.../about.jpg",
  "por_que_nosotros": [{ "texto": "Cobertura mundial" }],
  "numeros": [{ "valor": "+10", "etiqueta": "Años" }],
  "caracteristicas": [{ "titulo": "Rapidez", "descripcion": "…" }]
}
JSON,
            ],
            [
                'metodo' => 'GET', 'ruta' => '/services', 'desc' => 'Listado de servicios publicados.',
                'ejemplo' => <<<'JSON'
[
  {
    "id": 1, "source": "cms",
    "titulo": "Transporte marítimo",
    "descripcion": "…",
    "caracteristicas": ["FCL", "LCL"],
    "icono": "ship", "imagen": "https://.../s1.jpg"
  }
]
JSON,
            ],
            [
                'metodo' => 'GET', 'ruta' => '/products', 'desc' => 'Listado de productos del catálogo CMS.',
                'ejemplo' => <<<'JSON'
[
  {
    "id": 1, "source": "cms",
    "nombre": "Producto A", "descripcion": "…",
    "precio": 99.0, "unidad_precio": "kg",
    "categoria": "General", "caracteristicas": [],
    "icono": null, "imagen": "https://.../p1.jpg"
  }
]
JSON,
            ],
            [
                'metodo' => 'GET', 'ruta' => '/team', 'desc' => 'Miembros del equipo.',
                'ejemplo' => <<<'JSON'
[{ "id": 1, "nombre": "Ana López", "cargo": "CEO", "bio": "…", "foto": "https://.../ana.jpg" }]
JSON,
            ],
            [
                'metodo' => 'GET', 'ruta' => '/clients', 'desc' => 'Logos de clientes.',
                'ejemplo' => <<<'JSON'
[{ "id": 1, "nombre": "Cliente X", "logo": "https://.../logo.png", "url": "https://cliente.com" }]
JSON,
            ],
            [
                'metodo' => 'GET', 'ruta' => '/testimonials', 'desc' => 'Testimonios de clientes.',
                'ejemplo' => <<<'JSON'
[{ "id": 1, "autor_nombre": "Juan", "autor_cargo": "Gerente", "autor_empresa": "ACME", "autor_foto": "https://…", "contenido": "Excelente servicio", "estrellas": 5 }]
JSON,
            ],
            [
                'metodo' => 'GET', 'ruta' => '/faq', 'desc' => 'Preguntas frecuentes.',
                'ejemplo' => <<<'JSON'
[{ "id": 1, "pregunta": "¿Cuánto tarda?", "respuesta": "Entre 5 y 7 días." }]
JSON,
            ],
            [
                'metodo' => 'GET', 'ruta' => '/contact', 'desc' => 'Datos de contacto y redes sociales.',
                'ejemplo' => <<<'JSON'
{
  "direccion": "Av. Principal 123",
  "telefono": "+593 99 999 9999",
  "email": "info@empresa.com",
  "whatsapp": "+593999999999",
  "mapa_embed": "<iframe…>",
  "redes": { "facebook": "https://…", "instagram": "https://…" }
}
JSON,
            ],
            [
                'metodo' => 'GET', 'ruta' => '/posts', 'desc' => 'Listado de publicaciones del blog (con extracto).',
                'ejemplo' => <<<'JSON'
[{ "id": 1, "titulo": "Novedades 2026", "slug": "novedades-2026", "imagen": "https://…", "publicado_en": "2026-06-01T00:00:00.000000Z", "extracto": "Resumen…" }]
JSON,
            ],
            [
                'metodo' => 'GET', 'ruta' => '/posts/{slug}', 'desc' => 'Detalle de una publicación por su slug.',
                'ejemplo' => <<<'JSON'
{ "id": 1, "titulo": "Novedades 2026", "slug": "novedades-2026", "contenido": "<p>HTML…</p>", "imagen": "https://…", "publicado_en": "2026-06-01T00:00:00.000000Z" }
JSON,
            ],
            [
                'metodo' => 'GET', 'ruta' => '/terminos', 'desc' => 'Términos y condiciones.',
                'ejemplo' => <<<'JSON'
{ "titulo": "Términos y condiciones", "contenido": "<p>…</p>", "ultima_actualizacion": "2026-06-01" }
JSON,
            ],
            [
                'metodo' => 'GET', 'ruta' => '/all', 'desc' => 'Todas las secciones en una sola llamada (optimiza la carga inicial).',
                'ejemplo' => <<<'JSON'
{
  "empresa": { "nombre": "…", "logo": "https://…" },
  "hero": { … }, "nosotros": { … }, "servicios": [ … ],
  "productos": [ … ], "equipo": [ … ], "clientes": [ … ],
  "testimonios": [ … ], "faq": [ … ], "contacto": { … },
  "noticias": [ … ], "terminos": { … }
}
JSON,
            ],
        ];
    }
}
