<?php

namespace App\Filament\App\Resources\ProductDesignResource\Pages;

use App\Filament\App\Resources\ProductDesignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductDesign extends CreateRecord
{
    protected static string $resource = ProductDesignResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['pvp_venta']       = $data['_plan_pvp_venta'] ?? null;
        $data['pvp_incluye_iva'] = $data['_plan_pvp_incluye_iva'] ?? false;
        $data['margen_venta']    = $data['_plan_margen_venta'] ?? null;
        $data['dias_venta']      = $data['_plan_dias_venta'] ?? null;
        $data['meta_ganancia']   = $data['_plan_meta_ganancia'] ?? 5;
        $data['aplica_ice']      = $data['_plan_aplica_ice'] ?? false;
        $data['ice_categoria']   = $data['_plan_ice_categoria'] ?? null;
        $data['ice_porcentaje']  = $data['_plan_ice_porcentaje'] ?? null;

        unset(
            $data['_plan_pvp_venta'],
            $data['_plan_pvp_incluye_iva'],
            $data['_plan_margen_venta'],
            $data['_plan_dias_venta'],
            $data['_plan_meta_ganancia'],
            $data['_plan_aplica_ice'],
            $data['_plan_ice_categoria'],
            $data['_plan_ice_porcentaje'],
        );

        return $data;
    }
}
