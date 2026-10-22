<?php

/**
 * Palabras con que el clasificador de productos reconoce qué es lo que se compró
 * (App\Services\ClasificadorProductos). Sin IA: diccionario + lo que ya se
 * clasificó antes. Se edita aquí; una frase de varias palabras pesa más que una suelta.
 *
 * Todo en minúsculas y sin tildes: el texto de la factura se normaliza igual.
 */
return [

    // Señales de que la línea es un SERVICIO (va a gasto, nunca al inventario).
    'servicio' => [
        'servicio', 'servicios', 'por concepto de', 'honorario', 'honorarios', 'asesoria', 'consultoria',
        'mano de obra', 'mantenimiento', 'reparacion', 'instalacion', 'control de plagas', 'fumigacion',
        'transporte', 'flete', 'arriendo', 'arrendamiento', 'alquiler', 'capacitacion', 'curso', 'suscripcion',
        'licencia', 'comision', 'auditoria', 'diseno', 'desarrollo', 'soporte', 'certificado de firma',
        'firma electronica', 'firma elec',
    ],

    // Señales de que la línea es un BIEN físico (unidades, envases, medidas).
    'bien' => [
        'ml', 'mm', 'cc', 'kg', 'gr', 'lt', 'litro', 'litros', 'galon', 'unidad', 'unidades', 'caja', 'paquete',
        'botella', 'frasco', 'tapa', 'liner', 'envase', 'etiqueta', 'funda', 'saco', 'rollo', 'pet',
    ],

    // Entre los bienes nuevos: qué es materia prima (va en la fórmula); el resto, insumo.
    'materia_prima' => [
        'sabor', 'esencia', 'extracto', 'acido', 'azucar', 'glucosa', 'colorante', 'alcohol', 'aguardiente',
        'pulpa', 'fruta', 'mortino', 'sunfo', 'goma', 'pectina', 'sorbato', 'benzoato', 'maltodextrina',
    ],

    // Tipo de gasto (por su código, que es estable entre empresas) => palabras.
    'gastos' => [
        'mantenimiento-y-reparaciones' => [
            'mantenimiento', 'reparacion', 'arreglo', 'control de plagas', 'plagas', 'fumigacion', 'desinfeccion',
            'limpieza', 'jardineria', 'plomeria', 'electricista', 'pintura', 'servicio tecnico', 'servicios tecnicos',
            'calibracion', 'revision tecnica', 'repuesto', 'repuestos',
        ],
        'transporte-y-movilizacion' => [
            'transporte', 'flete', 'envio', 'encomienda', 'courier', 'taxi', 'movilizacion', 'peaje', 'mudanza',
            'carga', 'logistica', 'logisticos',
        ],
        'servicios-profesionales-de-sociedades' => [
            'asesoria', 'consultoria', 'auditoria', 'servicios profesionales', 'contable', 'contabilidad', 'legal',
            'juridico', 'desarrollo de software', 'software', 'diseno',
        ],
        'honorarios-profesionales' => [
            'honorario', 'honorarios', 'servicios profesionales', 'asesoria', 'consultoria',
        ],
        'arriendo-de-inmuebles' => ['arriendo', 'arrendamiento', 'alquiler de local', 'alquiler', 'canon'],
        'combustible' => ['gasolina', 'diesel', 'combustible', 'extra', 'super', 'ecopais', 'galones'],
        'suministros-de-oficina' => [
            'papel', 'resma', 'tinta', 'toner', 'esfero', 'boligrafo', 'carpeta', 'cuaderno', 'utiles', 'grapas',
        ],
        'publicidad-y-comunicacion' => [
            'publicidad', 'pauta', 'anuncio', 'volantes', 'redes sociales', 'marketing', 'rotulo', 'letrero',
            'banner', 'impresion', 'fotografia',
        ],
        'firma-electronica-y-certificados-digitales' => [
            'firma electronica', 'firma elec', 'certificado de firma', 'certificado digital', 'firma digital',
            'security data', 'renovacion de firma',
        ],
        'seguros' => ['seguro', 'poliza', 'prima de seguro'],
        'servicios-basicos' => [
            'energia electrica', 'luz electrica', 'agua potable', 'internet', 'telefonia', 'plan celular', 'recarga',
        ],
        'impuestos-tasas-y-contribuciones' => [
            'tasa', 'impuesto', 'patente', 'permiso de funcionamiento', 'contribucion', 'matricula', 'arcsa',
            'registro sanitario', 'bomberos', 'municipio',
        ],
        'capacitacion' => ['capacitacion', 'curso', 'taller', 'seminario', 'formacion', 'certificacion'],
        'alimentacion' => ['almuerzo', 'refrigerio', 'comida', 'alimentacion', 'restaurante', 'cafeteria', 'desayuno'],
        'gastos-bancarios-y-financieros' => [
            'comision bancaria', 'mantenimiento de cuenta', 'interes', 'intereses', 'costo de transferencia',
        ],
    ],
];
