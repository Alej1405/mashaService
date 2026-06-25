<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->jsonb('features')->nullable()->after('tiene_comercio_exterior');
        });

        // Poblar features desde los booleanos existentes (dual-write inicial)
        DB::statement("
            UPDATE empresas SET features = jsonb_build_object(
                'finanzas', jsonb_build_object(
                    'activo',          true,
                    'plan_cuentas',    true,
                    'asientos',        true,
                    'mapeo',           true,
                    'costos_fijos',    true,
                    'informes', jsonb_build_object(
                        'balance_general',   true,
                        'estado_resultados', true,
                        'flujo_caja',        true,
                        'libro_diario',      true,
                        'libro_mayor',       true,
                        'supercias',         true
                    )
                ),
                'tesoreria', jsonb_build_object(
                    'activo',             true,
                    'caja',               true,
                    'cuentas_bancarias',  true,
                    'cajas_registradoras',true,
                    'sesiones_caja',      true,
                    'movimientos_caja',   true,
                    'tarjetas_credito',   true,
                    'deudas_prestamos',   true
                ),
                'compras', jsonb_build_object(
                    'activo',             true,
                    'proveedores',        true,
                    'registro_compras',   true,
                    'solicitudes_insumos',true
                ),
                'inventario', jsonb_build_object(
                    'activo',        COALESCE(tipo_operacion_productos, false),
                    'items',         COALESCE(tipo_operacion_productos, false),
                    'almacenes',     COALESCE(tipo_operacion_productos, false),
                    'unidades_medida',COALESCE(tipo_operacion_productos, false),
                    'importar_csv',  COALESCE(tipo_operacion_productos, false)
                ),
                'ventas', jsonb_build_object(
                    'activo',          COALESCE(tipo_operacion_productos, false),
                    'registro_ventas', COALESCE(tipo_operacion_productos, false),
                    'clientes',        COALESCE(tipo_operacion_productos, false)
                ),
                'produccion', jsonb_build_object(
                    'activo',               COALESCE(tipo_operacion_manufactura, false),
                    'planificacion',         COALESCE(tipo_operacion_manufactura, false),
                    'produccion_operativa',  COALESCE(tipo_operacion_manufactura, false),
                    'ordenes',               COALESCE(tipo_operacion_manufactura, false),
                    'diseno_productos',      COALESCE(tipo_operacion_manufactura, false),
                    'diseno_servicios',      COALESCE(tipo_operacion_servicios, false)
                ),
                'marketing', jsonb_build_object(
                    'activo', (COALESCE(servicio_mailing_activo, false) OR COALESCE(servicio_cms_activo, false)),
                    'cms', jsonb_build_object(
                        'activo',         COALESCE(servicio_cms_activo, false),
                        'hero',           COALESCE(servicio_cms_activo, false),
                        'nosotros',       COALESCE(servicio_cms_activo, false),
                        'contacto',       COALESCE(servicio_cms_activo, false),
                        'terminos',       COALESCE(servicio_cms_activo, false),
                        'blog',           COALESCE(servicio_cms_activo, false),
                        'servicios',      COALESCE(servicio_cms_activo, false),
                        'productos',      COALESCE(servicio_cms_activo, false),
                        'equipo',         COALESCE(servicio_cms_activo, false),
                        'testimonios',    COALESCE(servicio_cms_activo, false),
                        'faq',            COALESCE(servicio_cms_activo, false),
                        'logos_clientes', COALESCE(servicio_cms_activo, false)
                    ),
                    'mailing', jsonb_build_object(
                        'activo',      COALESCE(servicio_mailing_activo, false),
                        'dashboard',   COALESCE(servicio_mailing_activo, false),
                        'campanias',   COALESCE(servicio_mailing_activo, false),
                        'plantillas',  COALESCE(servicio_mailing_activo, false),
                        'contactos',   COALESCE(servicio_mailing_activo, false),
                        'grupos',      COALESCE(servicio_mailing_activo, false)
                    )
                ),
                'tienda', jsonb_build_object(
                    'activo',             false,
                    'productos',          false,
                    'categorias',         false,
                    'ordenes',            false,
                    'clientes',           false,
                    'cupones',            false,
                    'contratos_servicio', false,
                    'cargos_adicionales', false,
                    'api_docs',           false
                ),
                'logistica', jsonb_build_object(
                    'activo',            COALESCE(tiene_logistica, false),
                    'facturas_pagar',    COALESCE(tiene_logistica, false),
                    'ordenes_cobrar',    COALESCE(tiene_logistica, false),
                    'verificar_cobros',  COALESCE(tiene_logistica, false),
                    'comercio_exterior', COALESCE(tiene_comercio_exterior, false)
                )
            )
        ");

        // Índice GIN con jsonb_path_ops (40% más rápido para @> / containment queries)
        DB::statement('CREATE INDEX idx_empresas_features ON empresas USING gin (features jsonb_path_ops)');

        // Índice parcial: empresas activas por plan — la mayoría de queries lo necesitan
        DB::statement('CREATE INDEX idx_empresas_activo_plan ON empresas (plan, id) WHERE activo = true');

        // Resuelve el N+1 de "último login por empresa" en el dashboard
        DB::statement("
            CREATE INDEX idx_users_empresa_login ON users (empresa_id, last_login_at DESC NULLS LAST)
            WHERE last_login_at IS NOT NULL
        ");

        // Resuelve el N+1 de "sesiones activas por empresa" en la tabla de servicios
        DB::statement("
            CREATE INDEX idx_sessions_user_activity ON sessions (user_id, last_activity)
            WHERE user_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_sessions_user_activity');
        DB::statement('DROP INDEX IF EXISTS idx_users_empresa_login');
        DB::statement('DROP INDEX IF EXISTS idx_empresas_activo_plan');
        DB::statement('DROP INDEX IF EXISTS idx_empresas_features');

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('features');
        });
    }
};
