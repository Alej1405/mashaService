<?php

/*
|--------------------------------------------------------------------------
| Catálogo canónico de módulos y features del ERP
|--------------------------------------------------------------------------
| Fuente única de verdad. El admin UI, PlanHelper y EmpresaFeaturesService
| leen de aquí. Para agregar una feature nueva, solo se edita este archivo.
|
| Estructura: módulo → sub-features (dot-notation para JSONB anidado)
| La clave 'activo' de cada módulo es especial: controla el módulo completo.
|
| Campos de documentación (solo CMS y Tienda implementados; resto se agrega por iteración):
|   descripcion_larga  → explicación detallada del módulo
|   alcance            → array: qué incluye y qué NO incluye
|   casos_uso          → array: ejemplos de uso típico
|   services           → array: services de Laravel que orquestan la lógica
|   queries_principales → array: queries/scopes clave con su descripción
|   algoritmos         → array: tipo de algoritmo y dónde se usa
*/

return [

    'finanzas' => [
        'label'       => 'Finanzas',
        'icon'        => 'heroicon-o-calculator',
        'color'       => 'violet',
        'descripcion' => 'Plan de cuentas, asientos contables e informes financieros',
        'features' => [
            'plan_cuentas'               => 'Plan de Cuentas',
            'asientos'                   => 'Asientos Contables',
            'mapeo'                      => 'Mapeo Contable',
            'costos_fijos'               => 'Costos Fijos',
            'informes.balance_general'   => 'Balance General',
            'informes.estado_resultados' => 'Estado de Resultados',
            'informes.flujo_caja'        => 'Flujo de Caja',
            'informes.libro_diario'      => 'Libro Diario',
            'informes.libro_mayor'       => 'Libro Mayor',
            'informes.supercias'         => 'Informe SUPERCIAS',
        ],
    ],

    'tesoreria' => [
        'label'       => 'Tesorería',
        'icon'        => 'heroicon-o-banknotes',
        'color'       => 'emerald',
        'descripcion' => 'Caja, cuentas bancarias, tarjetas y movimientos de efectivo',
        'features' => [
            'caja'                => 'Caja',
            'cuentas_bancarias'   => 'Cuentas Bancarias',
            'cajas_registradoras' => 'Cajas Registradoras',
            'sesiones_caja'       => 'Sesiones de Caja',
            'movimientos_caja'    => 'Movimientos de Caja',
            'tarjetas_credito'    => 'Tarjetas de Crédito',
            'deudas_prestamos'    => 'Deudas y Préstamos',
        ],
    ],

    'compras' => [
        'label'       => 'Compras',
        'icon'        => 'heroicon-o-shopping-cart',
        'color'       => 'amber',
        'descripcion' => 'Gestión de proveedores y registro de compras',
        'features' => [
            'proveedores'         => 'Proveedores',
            'registro_compras'    => 'Registro de Compras',
            'solicitudes_insumos' => 'Solicitudes de Insumos',
        ],
    ],

    'inventario' => [
        'label'       => 'Inventario',
        'icon'        => 'heroicon-o-cube',
        'color'       => 'blue',
        'descripcion' => 'Ítems, almacenes, unidades de medida y movimientos de stock',
        'features' => [
            'items'           => 'Ítems / Productos',
            'almacenes'       => 'Almacenes',
            'unidades_medida' => 'Unidades de Medida',
            'importar_csv'    => 'Importación CSV',
        ],
    ],

    'ventas' => [
        'label'       => 'Ventas',
        'icon'        => 'heroicon-o-currency-dollar',
        'color'       => 'green',
        'descripcion' => 'Registro de ventas y gestión de clientes',
        'features' => [
            'registro_ventas' => 'Registro de Ventas',
            'clientes'        => 'Clientes',
        ],
    ],

    'produccion' => [
        'label'       => 'Producción',
        'icon'        => 'heroicon-o-cog-8-tooth',
        'color'       => 'orange',
        'descripcion' => 'Órdenes de producción, planificación y diseño de productos',
        'features' => [
            'planificacion'        => 'Planificación',
            'produccion_operativa' => 'Producción Operativa',
            'ordenes'              => 'Órdenes de Producción',
            'diseno_productos'     => 'Diseño de Productos',
            'diseno_servicios'     => 'Diseño de Servicios',
        ],
    ],

    'marketing' => [
        'label'       => 'Marketing',
        'icon'        => 'heroicon-o-megaphone',
        'color'       => 'pink',
        'descripcion' => 'CMS web y campañas de mailing',

        'descripcion_larga' => 'Gestión completa de la presencia digital de la empresa: sitio web administrable (CMS) con secciones configurables, y campañas de correo masivo vía Mailgun con estadísticas en tiempo real.',

        'alcance' => [
            'incluye' => [
                'CMS con secciones: Hero, Nosotros, Servicios, Productos, Blog, Equipo, Testimonios, FAQ, Logos de Clientes, Contacto, Términos',
                'Mailing: campañas masivas, plantillas visuales, gestión de contactos y grupos',
                'Dashboard de estadísticas Mailgun (entregas, aperturas, clics)',
                'Configuración por empresa de dominio y credenciales Mailgun',
            ],
            'no_incluye' => [
                'Hosting del sitio web (el CMS genera contenido, no sirve el front)',
                'Automatizaciones de marketing (secuencias de correo, triggers)',
                'Integración con redes sociales',
            ],
        ],

        'casos_uso' => [
            'Empresa que necesita administrar el contenido de su landing page sin programar',
            'Negocio que envía newsletters o promociones mensuales a su base de clientes',
            'Plan básico: solo mailing sin CMS, para empresas que ya tienen sitio web externo',
        ],

        'services' => [
            'App\\Services\\EmpresaFeaturesService' => 'Activa/desactiva features de CMS y Mailing en el JSONB de la empresa',
            'App\\Mail\\*'                          => 'Envío de correos transaccionales relacionados con mailing',
        ],

        'queries_principales' => [
            'Empresa::hasFeature("marketing.cms.activo")'   => 'Verifica si el CMS está activo para la empresa actual — O(1) en memoria sobre array casted',
            'Empresa::hasFeature("marketing.mailing.activo")' => 'Verifica si Mailing está activo — O(1) en memoria',
            'EmpresaFeaturesService::setModule($empresa, "marketing", true)' => 'Activa el módulo completo con jsonb_set en PostgreSQL',
        ],

        'algoritmos' => [
            'Lookup O(1) por clave JSONB' => 'data_get() sobre array casted en Empresa::hasFeature() — acceso directo sin iteración',
            'jsonb_set PostgreSQL'         => 'Actualización atómica de sub-árbol JSONB sin reescribir el objeto completo',
            'Dual-write sync'              => 'Al modificar JSONB, se sincronizan booleanos legacy para mantener compatibilidad con EmpresaObserver',
        ],

        'features' => [
            'cms.activo'         => 'CMS (módulo completo)',
            'cms.hero'           => 'Inicio (Hero)',
            'cms.nosotros'       => 'Nosotros',
            'cms.contacto'       => 'Contacto',
            'cms.terminos'       => 'Términos y Condiciones',
            'cms.blog'           => 'Blog / Noticias',
            'cms.servicios'      => 'Servicios',
            'cms.productos'      => 'Productos CMS',
            'cms.equipo'         => 'Equipo',
            'cms.testimonios'    => 'Testimonios',
            'cms.faq'            => 'FAQ',
            'cms.logos_clientes' => 'Logos de Clientes',
            'mailing.activo'     => 'Mailing (módulo completo)',
            'mailing.dashboard'  => 'Dashboard Mailgun',
            'mailing.campanias'  => 'Campañas',
            'mailing.plantillas' => 'Plantillas de Correo',
            'mailing.contactos'  => 'Contactos',
            'mailing.grupos'     => 'Grupos de Contactos',
        ],
    ],

    'tienda' => [
        'label'       => 'Tienda',
        'icon'        => 'heroicon-o-shopping-bag',
        'color'       => 'cyan',
        'descripcion' => 'Portal de ventas online, catálogo y gestión de órdenes',

        'descripcion_larga' => 'Portal de comercio electrónico integrado al ERP: catálogo de productos con imágenes y precios, carrito de compras, gestión de órdenes de clientes, portal de autoservicio para clientes, cupones de descuento y contratos de servicio recurrentes.',

        'alcance' => [
            'incluye' => [
                'Catálogo de productos con categorías, imágenes, precios y SKU',
                'Órdenes de clientes con estados (pendiente, pagado, entregado)',
                'Portal de clientes: historial de compras, contratos, facturas',
                'Cupones de descuento (porcentaje o monto fijo)',
                'Contratos de servicio recurrentes y cargos adicionales',
                'Documentación API pública del catálogo',
                'Liberación automática a producción al confirmar orden',
            ],
            'no_incluye' => [
                'Pasarela de pago integrada (los pagos se verifican manualmente)',
                'Shipping y logística de entrega',
                'Facturación electrónica (SRI)',
            ],
        ],

        'casos_uso' => [
            'Empresa manufacturera que vende sus productos directamente a clientes finales',
            'Proveedor de servicios que ofrece paquetes recurrentes con contratos mensuales',
            'Negocio que necesita un catálogo público con API para integrarlo a su sitio web',
        ],

        'services' => [
            'App\\Services\\EmpresaFeaturesService' => 'Activa/desactiva features de Tienda en el JSONB de la empresa',
            'App\\Services\\EmpresaStatsService'    => 'Estadísticas agregadas de órdenes y clientes de la tienda',
        ],

        'queries_principales' => [
            'StoreProduct::where("empresa_id", ...)->activos()'    => 'Scope que filtra productos activos con stock disponible — índice compuesto (empresa_id, activo)',
            'Customer::whereHas("orders", ...)'                    => 'Clientes con órdenes en período — usa índice en orders.customer_id',
            'Empresa::hasFeature("tienda.productos")'              => 'Verifica acceso a catálogo — O(1) en memoria',
            'EmpresaFeaturesService::setModule($empresa, "tienda", true)' => 'Activa módulo tienda completo vía jsonb_set',
        ],

        'algoritmos' => [
            'Lookup O(1) por clave JSONB'   => 'data_get() sobre array casted en Empresa::hasFeature() — acceso directo sin iteración',
            'Índice compuesto PostgreSQL'    => 'índice (empresa_id, activo) en store_products para filtrado eficiente por tenant',
            'jsonb_set PostgreSQL'           => 'Actualización atómica de sub-árbol JSONB para activar/desactivar tienda',
            'Eager loading con whereHas'     => 'Evita N+1 al listar clientes con sus órdenes usando whereHas + with()',
            'GIN jsonb_path_ops'             => 'Índice GIN en empresas.features para queries @> (containment) en filtros del admin',
        ],

        'features' => [
            'productos'          => 'Catálogo de Productos',
            'categorias'         => 'Categorías',
            'ordenes'            => 'Órdenes de Clientes',
            'clientes'           => 'Portal de Clientes',
            'cupones'            => 'Cupones de Descuento',
            'contratos_servicio' => 'Contratos de Servicio',
            'cargos_adicionales' => 'Cargos Adicionales',
            'api_docs'           => 'Documentación API',
        ],
    ],

    'logistica' => [
        'label'       => 'Logística',
        'icon'        => 'heroicon-o-truck',
        'color'       => 'slate',
        'descripcion' => 'Facturación, cobros y comercio exterior',
        'features' => [
            'facturas_pagar'    => 'Facturas por Pagar',
            'ordenes_cobrar'    => 'Órdenes por Cobrar',
            'verificar_cobros'  => 'Verificación de Cobros',
            'comercio_exterior' => 'Comercio Exterior',
        ],
    ],

];
