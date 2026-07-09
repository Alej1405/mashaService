<?php

/*
|--------------------------------------------------------------------------
| Configuración operativa del ERP
|--------------------------------------------------------------------------
| Ajustes de comportamiento del panel/app que NO son catálogo de módulos
| (para eso está config/erp_features.php).
*/

return [

    /*
    | Modo AISLAR PRODUCTO (temporal, para pruebas).
    | Cuando está activo, el panel oculta TODO menos "Diseño de Productos"
    | para poder probar el módulo Producto sin ruido. No elimina nada:
    | basta poner ERP_AISLAR_PRODUCTO=false (o quitarlo) para reactivar todo.
    | Lee PlanHelper::aislarProducto() como fuente única.
    */
    'aislar_producto' => env('ERP_AISLAR_PRODUCTO', false),

];
