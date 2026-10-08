<?php

namespace App\Modules\Compras\Queries;

use App\Models\ProductoProveedor;
use App\Models\Purchase;
use App\Shared\Attributes\Documentado;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lo que espera a alguien: compras electrónicas en borrador (Contabilidad) y
 * productos de proveedor que el inventario no conoce (Inventario). Un solo
 * sitio para contarlas, así el badge del menú y la pantalla dicen lo mismo.
 */
#[Documentado(
    grupo: 'Compras',
    descripcion: 'Compras electrónicas por completar y productos de proveedor por configurar de una empresa.',
    tipo: 'query',
)]
final class ComprasElectronicasPendientes
{
    public function compras(int $empresaId): Builder
    {
        return Purchase::withoutGlobalScopes()->where('empresa_id', $empresaId)
            ->where('status', Purchase::BORRADOR)
            ->where('origen', '!=', Purchase::ORIGEN_MANUAL);
    }

    public function productos(int $empresaId): Builder
    {
        return ProductoProveedor::withoutGlobalScopes()->where('empresa_id', $empresaId)
            ->whereNull('inventory_item_id')->whereNull('tipo_gasto_id');
    }
}
