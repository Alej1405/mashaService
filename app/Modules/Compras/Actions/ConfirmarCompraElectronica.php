<?php

namespace App\Modules\Compras\Actions;

use App\Models\ProductoProveedor;
use App\Models\Purchase;
use App\Models\TipoGasto;
use App\Services\ContabilidadService;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Lo que falta para que una compra electrónica quede registrada: la forma de pago
 * y qué es cada producto. Cada vez que se completa algo se intenta confirmar; al
 * confirmar, el PurchaseObserver genera el asiento y la entrada al kardex.
 */
#[Documentado(
    grupo: 'Compras',
    descripcion: 'Completa la forma de pago o el producto de una compra electrónica y la confirma cuando ya tiene todo.',
    tipo: 'action',
)]
final class ConfirmarCompraElectronica
{
    public function __construct(private readonly ResolverFormaPago $formas) {}

    /** Confirma si ya tiene todo. Devuelve true si quedó con su asiento. */
    public function intentar(Purchase $compra): bool
    {
        if (! $compra->listaParaConfirmar()) {
            return false;
        }

        // Un mes declarado o un año cerrado no admiten asientos: ni el de inventario
        // ni el de gasto. La compra espera en borrador con el motivo a la vista.
        try {
            app(ContabilidadService::class)->exigirEjercicioAbierto($compra->empresa_id, $compra->date->toDateString());
        } catch (\RuntimeException $e) {
            $compra->updateQuietly(['error_contable' => true, 'error_contable_msg' => $e->getMessage()]);

            return false;
        }

        $compra->update(['status' => Purchase::CONFIRMADO, 'error_contable' => false, 'error_contable_msg' => null]);
        $compra->refresh();

        if ($compra->journal_entry_id) {
            return true;
        }

        // Sin asiento no hay compra: vuelve a borrador con el motivo a la vista.
        $compra->updateQuietly(['status' => Purchase::BORRADOR]);

        return false;
    }

    public function formaPago(Purchase $compra, string $forma, ?int $medioId, ?int $plazoDias = null): bool
    {
        $this->exigirBorrador($compra);
        $compra->update($this->formas->elegida($compra, $forma, $medioId, $plazoDias));

        return $this->intentar($compra->refresh());
    }

    /**
     * Dice qué es un producto del proveedor. Vale para esta y para las compras que
     * vengan: todas las líneas en borrador con ese producto se actualizan.
     *
     * @return int compras que quedaron confirmadas
     */
    public function producto(ProductoProveedor $producto, ?int $itemId, ?int $tipoGastoId, ?int $usuarioId): int
    {
        if (! $itemId === ! $tipoGastoId) {
            throw ValidationException::withMessages(['inventory_item_id' => 'Elige un ítem de inventario o un tipo de gasto.']);
        }
        if ($tipoGastoId && ! TipoGasto::find($tipoGastoId)) {
            throw ValidationException::withMessages(['tipo_gasto_id' => 'Ese tipo de gasto no existe.']);
        }

        $compras = DB::transaction(function () use ($producto, $itemId, $tipoGastoId, $usuarioId) {
            $producto->update([
                'inventory_item_id' => $itemId,
                'tipo_gasto_id'     => $tipoGastoId,
                'configurado_en'    => now(),
                'configurado_por'   => $usuarioId,
            ]);

            $pendientes = Purchase::withoutGlobalScopes()->where('status', Purchase::BORRADOR)
                ->whereHas('items', fn ($q) => $q->where('producto_proveedor_id', $producto->id))->get();

            foreach ($pendientes as $compra) {
                $compra->items()->where('producto_proveedor_id', $producto->id)->get()
                    ->each(fn ($linea) => $linea->update(['inventory_item_id' => $itemId]));
            }

            return $pendientes;
        });

        return $compras->filter(fn (Purchase $c) => $this->intentar($c))->count();
    }

    private function exigirBorrador(Purchase $compra): void
    {
        if ($compra->status !== Purchase::BORRADOR) {
            throw ValidationException::withMessages(['compra' => 'Esa compra ya está confirmada.']);
        }
    }
}
