<?php

namespace App\Hub\Widgets;

use App\Models\Empresa;

/**
 * Contrato de un widget de módulo en el hub de inicio.
 *
 * Cada módulo del catálogo (config/erp_features) puede tener un widget que
 * resume su actividad. El hub muestra el widget solo si el usuario ve ese
 * módulo (intersección plan ∩ rol). Las métricas son SIEMPRE agregados
 * (count/sum), nunca colecciones, para no cargar el flujo de datos.
 */
interface HubWidget
{
    /** Clave del módulo (config/erp_features) al que pertenece el widget. */
    public static function module(): string;

    /**
     * Metadatos de presentación.
     *
     * @return array{titulo:string, icono:string, color:string, path:string}
     *   path = segmento de URL del panel destino (ej. 'store', 'cms').
     */
    public static function meta(): array;

    /**
     * KPIs del módulo para la empresa dada. Solo agregados.
     *
     * @return array<int,array{label:string, value:int|float, money?:bool}>
     */
    public static function metrics(Empresa $empresa): array;
}
