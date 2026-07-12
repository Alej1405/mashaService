<?php

namespace App\Modules\N8n;

use App\Models\CmsAbout;
use App\Models\CmsClientLogo;
use App\Models\CmsContact;
use App\Models\CmsFaq;
use App\Models\CmsHero;
use App\Models\CmsPost;
use App\Models\CmsService;
use App\Models\CmsTeamMember;
use App\Models\CmsTerminos;
use App\Models\CmsTestimonial;
use App\Models\StoreCategory;
use App\Models\StoreCoupon;
use App\Models\StoreProduct;

/**
 * Catálogo de entidades gestionables por n8n (CMS y Tienda). El controlador
 * genérico N8nRecursoController se maneja SOLO con esta config: por eso agregar
 * un recurso nuevo es agregar una entrada aquí, no escribir métodos.
 *
 * Claves por recurso:
 *  - model      : clase Eloquent (usa HasEmpresa; se ancla empresa_id explícito)
 *  - label      : nombre legible
 *  - singleton  : true = una por empresa (hero/about/contact/terminos)
 *  - slug_from  : campo del que se genera el slug único por empresa (o ausente)
 *  - rules      : validación de campos escalares (store). En update se relajan.
 *  - imagenes   : [campo => carpeta] campos de imagen (subida individual)
 *  - galeria    : [campo => carpeta, max] galería múltiple (solo producto)
 *  - upper      : campos a mayúsculas (código de cupón)
 *  - unico      : campos únicos por empresa (código de cupón)
 *  - crea_inventario : producto → auto-crea inventory_item ligado
 *  - lista      : columnas del index
 *  - orden      : [columna, dir] del index
 */
class RecursoRegistry
{
    public static function modulos(): array
    {
        return [
            'cms' => ['modulo' => 'marketing', 'recursos' => self::cms()],
            'store' => ['modulo' => 'tienda', 'recursos' => self::store()],
        ];
    }

    /** Config de un recurso o null si no existe en ese módulo. */
    public static function config(string $modulo, string $recurso): ?array
    {
        return self::modulos()[$modulo]['recursos'][$recurso] ?? null;
    }

    /** Módulo (marketing/tienda) requerido para un grupo de recursos. */
    public static function moduloRequerido(string $modulo): ?string
    {
        return self::modulos()[$modulo]['modulo'] ?? null;
    }

    private static function cms(): array
    {
        return [
            'hero' => [
                'model' => CmsHero::class, 'label' => 'Hero', 'singleton' => true,
                'rules' => [
                    'titulo' => 'required|string|max:255', 'subtitulo' => 'nullable|string|max:255',
                    'descripcion' => 'nullable|string', 'cta_texto' => 'nullable|string|max:120',
                    'cta_url' => 'nullable|string|max:255', 'activo' => 'nullable|boolean',
                ],
                'imagenes' => ['imagen' => 'cms/hero'], 'lista' => ['id', 'titulo', 'activo'],
            ],
            'about' => [
                'model' => CmsAbout::class, 'label' => 'Nosotros', 'singleton' => true,
                'rules' => [
                    'titulo' => 'nullable|string|max:255', 'descripcion' => 'nullable|string',
                    'por_que_nosotros' => 'nullable|string', 'numeros' => 'nullable|array',
                    'caracteristicas' => 'nullable|array', 'activo' => 'nullable|boolean',
                ],
                'imagenes' => ['imagen' => 'cms/about'], 'lista' => ['id', 'titulo', 'activo'],
            ],
            'contacto' => [
                'model' => CmsContact::class, 'label' => 'Contacto', 'singleton' => true,
                'rules' => [
                    'direccion' => 'nullable|string|max:255', 'telefono' => 'nullable|string|max:60',
                    'email' => 'nullable|email|max:150', 'whatsapp' => 'nullable|string|max:60',
                    'mapa_embed' => 'nullable|string', 'facebook' => 'nullable|string|max:255',
                    'instagram' => 'nullable|string|max:255', 'linkedin' => 'nullable|string|max:255',
                    'youtube' => 'nullable|string|max:255', 'tiktok' => 'nullable|string|max:255',
                    'activo' => 'nullable|boolean',
                ],
                'imagenes' => [], 'lista' => ['id', 'email', 'telefono', 'activo'],
            ],
            'terminos' => [
                'model' => CmsTerminos::class, 'label' => 'Términos', 'singleton' => true,
                'rules' => [
                    'titulo' => 'nullable|string|max:255', 'contenido' => 'nullable|string',
                    'ultima_actualizacion' => 'nullable|date', 'activo' => 'nullable|boolean',
                ],
                'imagenes' => [], 'lista' => ['id', 'titulo', 'activo'],
            ],
            'posts' => [
                'model' => CmsPost::class, 'label' => 'Publicación', 'slug_from' => 'titulo',
                'rules' => [
                    'titulo' => 'required|string|max:255', 'contenido' => 'nullable|string',
                    'publicado_en' => 'nullable|date', 'activo' => 'nullable|boolean',
                ],
                'imagenes' => ['imagen' => 'cms/posts'], 'lista' => ['id', 'titulo', 'activo', 'publicado_en'],
                'orden' => ['id', 'desc'],
            ],
            'services' => [
                'model' => CmsService::class, 'label' => 'Servicio',
                'rules' => [
                    'titulo' => 'required|string|max:255', 'descripcion' => 'nullable|string',
                    'caracteristicas' => 'nullable|array', 'sort_order' => 'nullable|integer',
                    'activo' => 'nullable|boolean',
                ],
                'imagenes' => ['imagen' => 'cms/services', 'icono' => 'cms/services'],
                'lista' => ['id', 'titulo', 'activo'], 'orden' => ['sort_order', 'asc'],
            ],
            'faq' => [
                'model' => CmsFaq::class, 'label' => 'FAQ',
                'rules' => [
                    'pregunta' => 'required|string|max:255', 'respuesta' => 'required|string',
                    'sort_order' => 'nullable|integer', 'activo' => 'nullable|boolean',
                ],
                'imagenes' => [], 'lista' => ['id', 'pregunta', 'activo'], 'orden' => ['sort_order', 'asc'],
            ],
            'testimonios' => [
                'model' => CmsTestimonial::class, 'label' => 'Testimonio',
                'rules' => [
                    'autor_nombre' => 'required|string|max:150', 'autor_cargo' => 'nullable|string|max:150',
                    'autor_empresa' => 'nullable|string|max:150', 'contenido' => 'required|string',
                    'estrellas' => 'nullable|integer|min:1|max:5', 'sort_order' => 'nullable|integer',
                    'activo' => 'nullable|boolean',
                ],
                'imagenes' => ['autor_foto' => 'cms/testimonials'], 'lista' => ['id', 'autor_nombre', 'activo'],
                'orden' => ['sort_order', 'asc'],
            ],
            'equipo' => [
                'model' => CmsTeamMember::class, 'label' => 'Miembro del equipo',
                'rules' => [
                    'nombre' => 'required|string|max:150', 'cargo' => 'nullable|string|max:150',
                    'bio' => 'nullable|string', 'sort_order' => 'nullable|integer', 'activo' => 'nullable|boolean',
                ],
                'imagenes' => ['foto' => 'cms/team'], 'lista' => ['id', 'nombre', 'cargo', 'activo'],
                'orden' => ['sort_order', 'asc'],
            ],
            'logos' => [
                'model' => CmsClientLogo::class, 'label' => 'Logo de cliente',
                'rules' => [
                    'nombre' => 'nullable|string|max:150', 'url' => 'nullable|string|max:255',
                    'sort_order' => 'nullable|integer', 'activo' => 'nullable|boolean',
                ],
                'imagenes' => ['logo' => 'cms/logos'], 'lista' => ['id', 'nombre', 'activo'],
                'orden' => ['sort_order', 'asc'],
            ],
        ];
    }

    private static function store(): array
    {
        return [
            'products' => [
                'model' => StoreProduct::class, 'label' => 'Producto', 'slug_from' => 'nombre',
                'rules' => [
                    'nombre' => 'required|string|max:255', 'sku' => 'nullable|string|max:100',
                    'descripcion' => 'nullable|string', 'precio_venta' => 'required|numeric|min:0',
                    'precio_distribuidor' => 'nullable|numeric|min:0',
                    'unidad_precio' => 'nullable|string|max:80', 'caracteristicas' => 'nullable|array',
                    'meta_titulo' => 'nullable|string|max:200', 'meta_descripcion' => 'nullable|string|max:300',
                    'publicado' => 'nullable|boolean', 'destacado' => 'nullable|boolean',
                    'orden' => 'nullable|integer', 'store_category_id' => 'nullable|integer',
                ],
                'imagenes' => ['imagen_principal' => 'store/products'],
                'galeria' => ['galeria' => 'store/products/gallery', 'max' => 5],
                'lista' => ['id', 'nombre', 'precio_venta', 'publicado'], 'orden' => ['id', 'desc'],
            ],
            'categories' => [
                'model' => StoreCategory::class, 'label' => 'Categoría', 'slug_from' => 'nombre',
                'rules' => [
                    'nombre' => 'required|string|max:255', 'descripcion' => 'nullable|string',
                    'parent_id' => 'nullable|integer', 'publicado' => 'nullable|boolean',
                    'orden' => 'nullable|integer', 'meta_titulo' => 'nullable|string|max:200',
                    'meta_descripcion' => 'nullable|string|max:300', 'contenido' => 'nullable|string',
                    'destacada' => 'nullable|boolean',
                ],
                'imagenes' => ['imagen' => 'store/categories', 'banner' => 'store/categories/banners'],
                'lista' => ['id', 'nombre', 'publicado'], 'orden' => ['orden', 'asc'],
            ],
            'coupons' => [
                'model' => StoreCoupon::class, 'label' => 'Cupón', 'upper' => ['codigo'], 'unico' => ['codigo'],
                'rules' => [
                    'codigo' => 'required|string|max:50', 'tipo' => 'required|in:porcentaje,monto_fijo',
                    'valor' => 'required|numeric|min:0', 'minimo_compra' => 'nullable|numeric|min:0',
                    'maximo_usos' => 'nullable|integer|min:1', 'activo' => 'nullable|boolean',
                    'fecha_inicio' => 'nullable|date', 'fecha_fin' => 'nullable|date',
                ],
                'imagenes' => [], 'lista' => ['id', 'codigo', 'tipo', 'valor', 'activo'], 'orden' => ['id', 'desc'],
            ],
        ];
    }
}
