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
*/

return [

    'finanzas' => [
        'label' => 'Finanzas',
        'icon'  => 'heroicon-o-calculator',
        'color' => 'violet',
        'descripcion' => 'Plan de cuentas, asientos contables e informes financieros',
        'features' => [
            'plan_cuentas'              => 'Plan de Cuentas',
            'asientos'                  => 'Asientos Contables',
            'mapeo'                     => 'Mapeo Contable',
            'costos_fijos'              => 'Costos Fijos',
            'informes.balance_general'   => 'Balance General',
            'informes.estado_resultados' => 'Estado de Resultados',
            'informes.flujo_caja'        => 'Flujo de Caja',
            'informes.libro_diario'      => 'Libro Diario',
            'informes.libro_mayor'       => 'Libro Mayor',
            'informes.supercias'         => 'Informe SUPERCIAS',
        ],
    ],

    'tesoreria' => [
        'label' => 'Tesorería',
        'icon'  => 'heroicon-o-banknotes',
        'color' => 'emerald',
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
        'label' => 'Compras',
        'icon'  => 'heroicon-o-shopping-cart',
        'color' => 'amber',
        'descripcion' => 'Gestión de proveedores y registro de compras',
        'features' => [
            'proveedores'         => 'Proveedores',
            'registro_compras'    => 'Registro de Compras',
            'solicitudes_insumos' => 'Solicitudes de Insumos',
        ],
    ],

    'inventario' => [
        'label' => 'Inventario',
        'icon'  => 'heroicon-o-cube',
        'color' => 'blue',
        'descripcion' => 'Ítems, almacenes, unidades de medida y movimientos de stock',
        'features' => [
            'items'            => 'Ítems / Productos',
            'almacenes'        => 'Almacenes',
            'unidades_medida'  => 'Unidades de Medida',
            'importar_csv'     => 'Importación CSV',
        ],
    ],

    'ventas' => [
        'label' => 'Ventas',
        'icon'  => 'heroicon-o-currency-dollar',
        'color' => 'green',
        'descripcion' => 'Registro de ventas y gestión de clientes',
        'features' => [
            'registro_ventas' => 'Registro de Ventas',
            'clientes'        => 'Clientes',
        ],
    ],

    'produccion' => [
        'label' => 'Producción',
        'icon'  => 'heroicon-o-cog-8-tooth',
        'color' => 'orange',
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
        'label' => 'Marketing',
        'icon'  => 'heroicon-o-megaphone',
        'color' => 'pink',
        'descripcion' => 'CMS web y campañas de mailing',
        'features' => [
            'cms.activo'          => 'CMS (módulo completo)',
            'cms.hero'            => 'Inicio (Hero)',
            'cms.nosotros'        => 'Nosotros',
            'cms.contacto'        => 'Contacto',
            'cms.terminos'        => 'Términos y Condiciones',
            'cms.blog'            => 'Blog / Noticias',
            'cms.servicios'       => 'Servicios',
            'cms.productos'       => 'Productos CMS',
            'cms.equipo'          => 'Equipo',
            'cms.testimonios'     => 'Testimonios',
            'cms.faq'             => 'FAQ',
            'cms.logos_clientes'  => 'Logos de Clientes',
            'mailing.activo'      => 'Mailing (módulo completo)',
            'mailing.dashboard'   => 'Dashboard Mailgun',
            'mailing.campanias'   => 'Campañas',
            'mailing.plantillas'  => 'Plantillas de Correo',
            'mailing.contactos'   => 'Contactos',
            'mailing.grupos'      => 'Grupos de Contactos',
        ],
    ],

    'tienda' => [
        'label' => 'Tienda',
        'icon'  => 'heroicon-o-shopping-bag',
        'color' => 'cyan',
        'descripcion' => 'Portal de ventas online, catálogo y gestión de órdenes',
        'features' => [
            'productos'           => 'Catálogo de Productos',
            'categorias'          => 'Categorías',
            'ordenes'             => 'Órdenes de Clientes',
            'clientes'            => 'Portal de Clientes',
            'cupones'             => 'Cupones de Descuento',
            'contratos_servicio'  => 'Contratos de Servicio',
            'cargos_adicionales'  => 'Cargos Adicionales',
            'api_docs'            => 'Documentación API',
        ],
    ],

    'logistica' => [
        'label' => 'Logística',
        'icon'  => 'heroicon-o-truck',
        'color' => 'slate',
        'descripcion' => 'Facturación, cobros y comercio exterior',
        'features' => [
            'facturas_pagar'    => 'Facturas por Pagar',
            'ordenes_cobrar'    => 'Órdenes por Cobrar',
            'verificar_cobros'  => 'Verificación de Cobros',
            'comercio_exterior' => 'Comercio Exterior',
        ],
    ],

];
