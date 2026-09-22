<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\PeriodoContable;
use Illuminate\Support\Facades\DB;

/**
 * Kardex valorado con promedio ponderado.
 *
 * Es la única puerta por la que se mueve inventario. Nadie incrementa
 * stock_actual a mano: el saldo se deriva de los movimientos, y cada movimiento
 * deja escrito el saldo que produjo.
 *
 * Reglas que implementa (NIC 2 · Sección 13 NIIF para PYMES):
 *   - el promedio se recalcula SOLO en las entradas
 *   - las salidas toman el promedio vigente y no lo alteran
 *   - ninguna salida puede dejar el saldo en negativo
 *   - ningún movimiento entra en un periodo cerrado
 *   - el traslado entre ubicaciones mueve stock pero no toca el costo
 */
class KardexService
{
    /** Motivos que suman al saldo. El resto resta, salvo el traslado. */
    private const ENTRADAS = ['compra', 'ingreso_produccion', 'ajuste_sobrante', 'saldo_inicial'];

    /**
     * Salidas que se valoran al costo que se indica, no al promedio vigente, y
     * que por eso lo recalculan: son entradas en negativo. Es el caso de la
     * devolución o anulación de una compra, que debe salir al costo con el que
     * entró (NIC 2 · Sección 13).
     */
    private const REVERSAS = ['devolucion_compra'];

    /**
     * Registra un movimiento y devuelve la fila del kardex ya valorada.
     *
     * @param  array{
     *   item: InventoryItem, motivo: string, cantidad: float, fecha: string,
     *   costo_unitario?: float, almacen_id?: int, ubicacion_id?: int,
     *   ubicacion_destino_id?: int, documento?: string, referencia_tipo?: string,
     *   referencia_id?: int, lote?: string, acta?: array{numero: string, fecha: string, archivo?: string}
     * }  $datos
     */
    public function registrar(array $datos): InventoryMovement
    {
        $item     = $datos['item'];
        $motivo   = $datos['motivo'];
        $cantidad = round((float) $datos['cantidad'], 4);
        $fecha    = $datos['fecha'];

        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad de un movimiento debe ser mayor que cero.');
        }

        $this->exigirPeriodoAbierto($item->empresa_id, $fecha);

        if ($motivo === 'baja' && empty($datos['acta']['numero'])) {
            throw new \InvalidArgumentException(
                'Una baja de inventario necesita número de acta: sin ella el gasto no es deducible.'
            );
        }

        $datos['almacen_id'] = $datos['almacen_id']
            ?? $this->almacenDeLaUbicacion($datos['ubicacion_id'] ?? null);

        return DB::transaction(function () use ($item, $motivo, $cantidad, $fecha, $datos) {
            $bloqueado = InventoryItem::withoutGlobalScopes()->lockForUpdate()->findOrFail($item->id);

            if ($motivo === 'traslado') {
                return $this->trasladar($bloqueado, $cantidad, $fecha, $datos);
            }

            $esEntrada = in_array($motivo, self::ENTRADAS, true);
            $esReversa = in_array($motivo, self::REVERSAS, true);

            $cantidadAnterior = (float) $bloqueado->stock_actual;
            $valorAnterior    = (float) $bloqueado->saldo_valorado;
            $promedioAnterior = (float) $bloqueado->costo_promedio;

            if ($esEntrada) {
                $costoUnitario = round((float) ($datos['costo_unitario'] ?? 0), 4);

                if ($costoUnitario <= 0) {
                    throw new \InvalidArgumentException(
                        'Una entrada sin costo unitario rompe el kardex: no hay valor que asignar a esas unidades.'
                    );
                }

                $cantidadNueva = round($cantidadAnterior + $cantidad, 4);

                if ($valorAnterior <= 0 && $cantidadAnterior > 0) {
                    // Hay stock pero sin valor: es el histórico que quedó sin
                    // costear. Promediar contra cero lo único que hace es
                    // inventar un costo más bajo del real, así que esta entrada
                    // pone el costo de todo el saldo.
                    $promedioNuevo = $costoUnitario;
                    $valorNuevo    = round($cantidadNueva * $costoUnitario, 4);
                } else {
                    $valorNuevo    = round($valorAnterior + ($cantidad * $costoUnitario), 4);
                    // El promedio solo se mueve aquí.
                    $promedioNuevo = $cantidadNueva > 0 ? round($valorNuevo / $cantidadNueva, 4) : 0.0;
                }
            } else {
                if ($cantidad > $cantidadAnterior) {
                    throw new \RuntimeException(sprintf(
                        'No hay stock suficiente de %s: hay %s y se piden %s.',
                        $bloqueado->nombre, $cantidadAnterior, $cantidad
                    ));
                }

                if ($esReversa) {
                    // Sale al costo con el que entró: el promedio se recalcula
                    // sobre lo que queda, como si esa compra no hubiera existido.
                    $costoUnitario = round((float) ($datos['costo_unitario'] ?? 0), 4);

                    if ($costoUnitario <= 0) {
                        throw new \InvalidArgumentException(
                            'Una devolución de compra necesita el costo con el que entró la mercadería.'
                        );
                    }

                    $cantidadNueva = round($cantidadAnterior - $cantidad, 4);
                    $valorNuevo    = round(max($valorAnterior - ($cantidad * $costoUnitario), 0), 4);
                    $promedioNuevo = $cantidadNueva > 0 ? round($valorNuevo / $cantidadNueva, 4) : 0.0;
                } else {
                    // La salida se valora al promedio vigente y no lo modifica.
                    $costoUnitario = $promedioAnterior;
                    $cantidadNueva = round($cantidadAnterior - $cantidad, 4);
                    $valorNuevo    = round($valorAnterior - ($cantidad * $costoUnitario), 4);
                    $promedioNuevo = $cantidadNueva > 0 ? $promedioAnterior : 0.0;
                }
            }

            $movimiento = InventoryMovement::create([
                'empresa_id'           => $bloqueado->empresa_id,
                'inventory_item_id'    => $bloqueado->id,
                'type'                 => $esEntrada ? 'entrada' : 'salida',
                'motivo'               => $motivo,
                'almacen_id'           => $datos['almacen_id'] ?? null,
                'ubicacion_almacen_id' => $datos['ubicacion_id'] ?? null,
                'quantity'             => $cantidad,
                'unit_price'           => $costoUnitario,
                'total'                => round($cantidad * $costoUnitario, 4),
                'saldo_cantidad'       => $cantidadNueva,
                'costo_promedio'       => $promedioNuevo,
                'saldo_valor'          => $valorNuevo,
                'lote'                 => $datos['lote'] ?? null,
                'acta_numero'          => $datos['acta']['numero']  ?? null,
                'acta_fecha'           => $datos['acta']['fecha']   ?? null,
                'acta_archivo'         => $datos['acta']['archivo'] ?? null,
                'journal_entry_id'     => $datos['asiento_id'] ?? null,
                'reference_type'       => $datos['referencia_tipo'] ?? null,
                'reference_id'         => $datos['referencia_id']   ?? null,
                'description'          => $datos['documento'] ?? null,
                'date'                 => $fecha,
                'user_id'              => auth()->id(),
            ]);

            $bloqueado->forceFill([
                'stock_actual'   => $cantidadNueva,
                'costo_promedio' => $promedioNuevo,
                'saldo_valorado' => $valorNuevo,
            ])->save();

            $this->moverStockDeUbicacion(
                $bloqueado,
                $datos['almacen_id'] ?? null,
                $datos['ubicacion_id'] ?? null,
                $esEntrada ? $cantidad : -$cantidad,
            );

            return $movimiento;
        });
    }

    /**
     * Traslado entre ubicaciones: cambia dónde está, no cuánto vale.
     * Por eso no genera asiento contable ni toca el promedio.
     */
    private function trasladar(InventoryItem $item, float $cantidad, string $fecha, array $datos): InventoryMovement
    {
        $origen  = $datos['ubicacion_id'] ?? null;
        $destino = $datos['ubicacion_destino_id'] ?? null;

        if (! $origen || ! $destino || $origen === $destino) {
            throw new \InvalidArgumentException('Un traslado necesita una ubicación de origen y otra distinta de destino.');
        }

        $disponible = (float) InventoryStock::withoutGlobalScopes()
            ->where('inventory_item_id', $item->id)
            ->where('ubicacion_almacen_id', $origen)
            ->value('cantidad');

        if ($cantidad > $disponible) {
            throw new \RuntimeException("En esa ubicación solo hay {$disponible} y se piden {$cantidad}.");
        }

        $this->moverStockDeUbicacion($item, $datos['almacen_id'] ?? null, $origen, -$cantidad);
        $this->moverStockDeUbicacion($item, $datos['almacen_destino_id'] ?? ($datos['almacen_id'] ?? null), $destino, $cantidad);

        return InventoryMovement::create([
            'empresa_id'           => $item->empresa_id,
            'inventory_item_id'    => $item->id,
            'type'                 => 'salida',
            'motivo'               => 'traslado',
            'almacen_id'           => $datos['almacen_id'] ?? null,
            'ubicacion_almacen_id' => $origen,
            'quantity'             => $cantidad,
            'unit_price'           => (float) $item->costo_promedio,
            'total'                => 0,
            'saldo_cantidad'       => (float) $item->stock_actual,
            'costo_promedio'       => (float) $item->costo_promedio,
            'saldo_valor'          => (float) $item->saldo_valorado,
            'description'          => $datos['documento'] ?? 'Traslado interno',
            'date'                 => $fecha,
            'user_id'              => auth()->id(),
        ]);
    }

    private function moverStockDeUbicacion(InventoryItem $item, ?int $almacenId, ?int $ubicacionId, float $delta): void
    {
        $almacenId ??= $this->almacenDeLaUbicacion($ubicacionId);

        if (! $almacenId) {
            return;
        }

        $fila = InventoryStock::withoutGlobalScopes()->firstOrCreate(
            [
                'inventory_item_id'    => $item->id,
                'almacen_id'           => $almacenId,
                'ubicacion_almacen_id' => $ubicacionId,
            ],
            ['empresa_id' => $item->empresa_id, 'cantidad' => 0],
        );

        $fila->cantidad = round((float) $fila->cantidad + $delta, 4);
        $fila->save();
    }

    /**
     * Una compra no dice bodega, pero el ítem sí sabe en qué gaveta vive: de ahí
     * se deduce el almacén y el stock por ubicación deja de estar vacío.
     */
    private function almacenDeLaUbicacion(?int $ubicacionId): ?int
    {
        if (! $ubicacionId) {
            return null;
        }

        return \App\Models\UbicacionAlmacen::withoutGlobalScopes()
            ->with('zona')
            ->find($ubicacionId)?->zona?->almacen_id;
    }

    private function exigirPeriodoAbierto(int $empresaId, string $fecha): void
    {
        $momento = \Illuminate\Support\Carbon::parse($fecha);

        $cerrado = PeriodoContable::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('anio', $momento->year)
            ->where('mes', $momento->month)
            ->whereNotNull('cerrado_en')
            ->exists();

        if ($cerrado) {
            throw new \RuntimeException(
                "El periodo {$momento->month}/{$momento->year} está cerrado: no admite movimientos nuevos."
            );
        }
    }
}
