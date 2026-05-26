<?php

namespace App\Filament\App\Resources\ProductDesignResource\Pages;

use App\Filament\App\Resources\ProductDesignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductDesign extends EditRecord
{
    protected static string $resource = ProductDesignResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['_plan_pvp_venta']       = $data['pvp_venta'] ?? null;
        $data['_plan_pvp_incluye_iva'] = $data['pvp_incluye_iva'] ?? false;
        $data['_plan_margen_venta']    = $data['margen_venta'] ?? null;
        $data['_plan_dias_venta']      = $data['dias_venta'] ?? null;
        $data['_plan_meta_ganancia']   = $data['meta_ganancia'] ?? 5;
        $data['_plan_aplica_ice']      = $data['aplica_ice'] ?? false;
        $data['_plan_ice_categoria']   = $data['ice_categoria'] ?? null;
        $data['_plan_ice_porcentaje']  = $data['ice_porcentaje'] ?? null;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['pvp_venta']      = $data['_plan_pvp_venta'] ?? null;
        $data['pvp_incluye_iva'] = $data['_plan_pvp_incluye_iva'] ?? false;
        $data['margen_venta']   = $data['_plan_margen_venta'] ?? null;
        $data['dias_venta']     = $data['_plan_dias_venta'] ?? null;
        $data['meta_ganancia']  = $data['_plan_meta_ganancia'] ?? 5;
        $data['aplica_ice']     = $data['_plan_aplica_ice'] ?? false;
        $data['ice_categoria']  = $data['_plan_ice_categoria'] ?? null;
        $data['ice_porcentaje'] = $data['_plan_ice_porcentaje'] ?? null;

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
