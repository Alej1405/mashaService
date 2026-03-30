<?php

namespace App\Services;

use App\Models\AccountingMap;
use App\Models\AccountPlan;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Purchase;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class AccountingService
{
    public function getCuentaPago($model): ?AccountPlan
    {
        // $model puede ser Sale o Purchase
        $formaPago = $model->forma_pago ?? 'efectivo';
        $empresaId = $model->empresa_id;

        return match ($formaPago) {
            'efectivo'      => $model->cashRegister?->accountPlan 
                               ?? AccountPlan::where('empresa_id', $empresaId)->where('code', '1.1.01.01')->first(),
            'transferencia',
            'cheque'        => $model->bankAccount?->accountPlan 
                               ?? AccountPlan::where('empresa_id', $empresaId)->where('code', '1.1.01.03')->first(),
            'tarjeta'       => $model instanceof \App\Models\Purchase 
                                ? ($model->creditCard?->accountPlan ?? $model->bankAccount?->accountPlan)
                                : ($model->bankAccount?->accountPlan ?? $model->cashRegister?->accountPlan), 
            'credito'       => self::getMapeo(
                                $empresaId, 
                                'global', 
                                $model instanceof \App\Models\Sale ? 'venta_credito' : 'compra_credito_local'
                               ),
            default         => null,
        };
    }

    /**
     * Obtiene el Plan de Cuentas mapeado según el tipo y movimiento.
     */
    public static function getMapeo(?int $empresaId, string $tipoItem, string $tipoMovimiento): AccountPlan
    {
        $mapa = AccountingMap::where('empresa_id', $empresaId)
            ->where('tipo_item', $tipoItem)
            ->where('tipo_movimiento', $tipoMovimiento)
            ->first();

        // Fallback a mapeo base si no encuentra en la empresa
        if (!$mapa) {
            $mapaBase = AccountingMap::withoutGlobalScopes()
                ->whereNull('empresa_id')
                ->where('tipo_item', $tipoItem)
                ->where('tipo_movimiento', $tipoMovimiento)
                ->first();
                
            if ($mapaBase) {
                // Buscar cuenta equivalente en la empresa usando el código
                $cuentaBase = AccountPlan::withoutGlobalScopes()
                    ->whereNull('empresa_id')
                    ->where('id', $mapaBase->account_plan_id)
                    ->first();
                
                if ($cuentaBase) {
                    $cuentaEmpresa = AccountPlan::where('empresa_id', $empresaId)
                        ->where('code', $cuentaBase->code)
                        ->first();

                    if ($cuentaEmpresa) {
                        return $cuentaEmpresa;
                    }
                }
            }
        }

        $cuenta = $mapa?->accountPlan;

        // Si la relación falla (por Scopes Globales en contextos de consola/job), intentar búsqueda manual
        if (!$cuenta && $mapa) {
            $cuenta = AccountPlan::withoutGlobalScopes()
                ->where('id', $mapa->account_plan_id)
                ->first();
        }

        if (!$cuenta) {
            // Intento final con el mapeo 'global' para ese movimiento
            if ($tipoItem !== 'global') {
                return self::getMapeo($empresaId, 'global', $tipoMovimiento);
            }
            throw new Exception("Sin mapeo contable definido para: [{$tipoItem} | {$tipoMovimiento}] en la empresa [{$empresaId}].");
        }

        return $cuenta;
    }

    /**
     * Genera un asiento contable automático para una Compra confirmada.
     */
    public function generarAsientoCompra(Purchase $purchase): JournalEntry
    {
        $purchase->refresh();
        $purchase->load(['items.inventoryItem', 'supplier', 'cashRegister', 'bankAccount', 'creditCard']);

        return DB::transaction(function () use ($purchase) {
            $journalEntry = JournalEntry::create([
                'empresa_id'      => $purchase->empresa_id,
                'fecha'           => $purchase->date,
                'descripcion'     => 'Compra ' . $purchase->number . ' - ' . ($purchase->supplier->nombre ?? 'S/N'),
                'tipo'            => 'compra',
                'origen'          => 'automatico',
                'referencia_tipo' => 'purchase',
                'referencia_id'   => $purchase->id,
                'status'          => 'confirmado',
                'total_debe'      => 0,
                'total_haber'     => 0,
                'esta_cuadrado'   => true,
                'confirmado_por'  => Auth::id(),
                'confirmado_at'   => now(),
            ]);

            $totalDebe  = 0;
            $orden      = 1;

            $tipoMovimiento = match($purchase->forma_pago) {
                'efectivo', 'transferencia', 'tarjeta_credito' => 'compra_contado',
                'credito' => 'compra_credito_local',
                default   => 'compra_contado',
            };

            foreach ($purchase->items as $item) {
                // Items sin inventory_item_id: gasto genérico sin seguimiento de stock
                $tipoItem = $item->inventoryItem?->type ?? 'global';
                $descItem = $item->inventoryItem?->nombre ?? ($item->descripcion ?? 'Gasto');

                // 1. Línea de Gasto/Inventario (Debe)
                $cuentaItem = self::getMapeo($purchase->empresa_id, $tipoItem, $tipoMovimiento);

                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_plan_id'  => $cuentaItem->id,
                    'descripcion'      => $descItem,
                    'debe'             => $item->subtotal,
                    'haber'            => 0,
                    'orden'            => $orden++,
                ]);
                $totalDebe += $item->subtotal;

                // 2. Línea de IVA (Debe) - Si aplica
                if ($item->aplica_iva && $item->iva_monto > 0) {
                    try {
                        $cuentaIva = self::getMapeo($purchase->empresa_id, $tipoItem, 'iva_compras');
                    } catch (Exception $e) {
                        $cuentaIva = self::getMapeo($purchase->empresa_id, 'global', 'iva_compras');
                    }

                    JournalEntryLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_plan_id'  => $cuentaIva->id,
                        'descripcion'      => 'IVA 15% - ' . $descItem,
                        'debe'             => $item->iva_monto,
                        'haber'            => 0,
                        'orden'            => $orden++,
                    ]);
                    $totalDebe += $item->iva_monto;
                }

                // 3. Registrar Movimiento de Inventario (solo si hay item de inventario vinculado)
                if ($item->inventoryItem) {
                    $factor   = (float) ($item->inventoryItem->conversion_factor ?? 1);
                    $stockQty = round($item->quantity * $factor, 6);

                    InventoryMovement::create([
                        'empresa_id'        => $purchase->empresa_id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'type'              => 'entrada',
                        'quantity'          => $stockQty,
                        'unit_price'        => $item->unit_price / $factor,
                        'total'             => $item->subtotal,
                        'reference_type'    => 'purchase',
                        'reference_id'      => $purchase->id,
                        'journal_entry_id'  => $journalEntry->id,
                        'notes'             => 'Compra ' . $purchase->number,
                        'date'              => $purchase->date,
                    ]);

                    $item->inventoryItem->increment('stock_actual', $stockQty);
                }
            }

            // 4. Línea de Pago (Haber)
            $cuentaHaber = $this->getCuentaPago($purchase);

            if (!$cuentaHaber || $cuentaHaber->type === 'ingreso') {
                // Fallback robusto: Banco si no hay cuenta o es de ingreso (error de mapeo)
                $cuentaHaber = AccountPlan::where('empresa_id', $purchase->empresa_id)
                    ->where('code', '1.1.01.03')
                    ->first() ?? self::getMapeo($purchase->empresa_id, 'global', $tipoMovimiento);
            }

            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_plan_id'  => $cuentaHaber->id,
                'descripcion'      => 'Pago compra ' . $purchase->number . ' (' . ucfirst($purchase->forma_pago) . ')',
                'debe'             => 0,
                'haber'            => $purchase->total,
                'orden'            => $orden++,
            ]);

            $totalHaber = $purchase->total;

            if (round((float)$totalDebe, 2) !== round((float)$totalHaber, 2)) {
                throw new Exception("Asiento descuadrado: DEBE={$totalDebe} HABER={$totalHaber}");
            }

            $journalEntry->update([
                'total_debe'  => $totalDebe,
                'total_haber' => $totalHaber,
            ]);

            return $journalEntry;
        });
    }

    /**
     * Anula un asiento confirmado creando uno inverso.
     */
    public static function revertirAsiento(JournalEntry $entry): JournalEntry
    {
        return DB::transaction(function () use ($entry) {
            if ($entry->status !== 'confirmado') {
                throw new Exception("Solo se pueden revertir asientos confirmados.");
            }

            // Crear cabecera del asiento inverso
            $reversal = $entry->replicate([
                'numero', 'status', 'confirmado_por', 'confirmado_at', 'anulado_por', 'anulado_at'
            ]);
            
            $reversal->tipo = 'ajuste';
            $reversal->descripcion = "Anulación de {$entry->numero}";
            $reversal->status = 'confirmado';
            $reversal->confirmado_por = Auth::id();
            $reversal->confirmado_at = now();
            $reversal->save();

            // Revertir líneas (Intercambiar Debe y Haber)
            foreach ($entry->lines as $line) {
                $reversal->lines()->create([
                    'account_plan_id' => $line->account_plan_id,
                    'descripcion' => "REVERSO: " . ($line->descripcion ?: $entry->descripcion),
                    'debe' => $line->haber,
                    'haber' => $line->debe,
                    'orden' => $line->orden,
                ]);
            }

            // Marcar original como anulado
            $entry->update([
                'status' => 'anulado',
                'anulado_por' => Auth::id(),
                'anulado_at' => now(),
            ]);

            return $reversal;
        });
    }

    /**
     * Obtiene el saldo [debe, haber, saldo] de una cuenta en un periodo.
     */
    public static function getSaldoCuenta($empresaId, $accountPlanId, $fechaInicio, $fechaFin)
    {
        $movimientos = JournalEntryLine::whereHas('journalEntry', function($q) use ($empresaId, $fechaInicio, $fechaFin) {
                $q->where('empresa_id', $empresaId)
                  ->where('status', 'confirmado')
                  ->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            })
            ->where('account_plan_id', $accountPlanId)
            ->selectRaw('SUM(debe) as total_debe, SUM(haber) as total_haber')
            ->first();

        $debe  = $movimientos->total_debe ?? 0;
        $haber = $movimientos->total_haber ?? 0;
        
        $cuenta = AccountPlan::find($accountPlanId);
        $saldo = in_array($cuenta->type, ['activo', 'costo', 'gasto']) 
            ? $debe - $haber 
            : $haber - $debe;

        return [
            'debe'  => $debe,
            'haber' => $haber,
            'saldo' => $saldo
        ];
    }

    /**
     * Genera un asiento para ajustes de inventario.
     */
    public static function generarAsientoAjuste(InventoryMovement $movement): JournalEntry
    {
        return DB::transaction(function () use ($movement) {
            $totalMonto = abs($movement->quantity) * ($movement->unit_price ?? $movement->inventoryItem->purchase_price ?? 0);
            $esEntrada = $movement->quantity > 0;
            $tipoMapeo = $esEntrada ? 'ajuste_sobrante' : 'ajuste_inventario';

            $entry = JournalEntry::create([
                'empresa_id'      => $movement->empresa_id,
                'fecha'           => $movement->date,
                'descripcion'     => "Ajuste Inventario: {$movement->inventoryItem->nombre} (" . ($esEntrada ? 'Sobrante' : 'Faltante') . ")",
                'tipo'            => 'ajuste',
                'origen'          => 'automatico',
                'referencia_tipo' => 'adjustment',
                'referencia_id'   => $movement->id,
                'status'          => 'confirmado',
                'total_debe'      => $totalMonto,
                'total_haber'     => $totalMonto,
                'esta_cuadrado'   => true,
                'confirmado_por'  => Auth::id(),
                'confirmado_at'   => now(),
            ]);

            $cuentaInv = self::getMapeo($movement->empresa_id, $movement->inventoryItem->type, 'compra_contado');
            $cuentaAjuste = self::getMapeo($movement->empresa_id, $movement->inventoryItem->type, $tipoMapeo);

            if ($esEntrada) {
                // Entrada (Sobrante): Debe Inventario, Haber Ingreso (Ajuste Sobrante)
                $entry->lines()->create(['account_plan_id' => $cuentaInv->id, 'debe' => $totalMonto, 'haber' => 0, 'orden' => 1]);
                $entry->lines()->create(['account_plan_id' => $cuentaAjuste->id, 'debe' => 0, 'haber' => $totalMonto, 'orden' => 2]);
            } else {
                // Salida (Faltante): Debe Gasto (Ajuste Inventario), Haber Inventario
                $entry->lines()->create(['account_plan_id' => $cuentaAjuste->id, 'debe' => $totalMonto, 'haber' => 0, 'orden' => 1]);
                $entry->lines()->create(['account_plan_id' => $cuentaInv->id, 'debe' => 0, 'haber' => $totalMonto, 'orden' => 2]);
            }

            return $entry;
        });
    }

    /**
     * Genera un asiento contable automático para una Venta confirmada.
     */
    public function generarAsientoVenta(\App\Models\Sale $sale): JournalEntry
    {
        // Asegurar que los datos estén frescos y las relaciones cargadas
        $sale->refresh();
        $sale->load(['items.inventoryItem', 'customer', 'cashRegister', 'bankAccount', 'creditCard']);

        return DB::transaction(function () use ($sale) {

            $journalEntry = JournalEntry::create([
                'empresa_id'      => $sale->empresa_id,
                'fecha'           => $sale->fecha,
                'descripcion'     => 'Venta ' . $sale->referencia
                                    . ' - ' . $sale->customer->nombre,
                'tipo'            => 'venta',
                'origen'          => 'automatico',
                'referencia_tipo' => 'sale',
                'referencia_id'   => $sale->id,
                'status'          => 'confirmado',
                'total_debe'      => 0,
                'total_haber'     => 0,
                'esta_cuadrado'   => true,
                'confirmado_por'  => Auth::id(),
                'confirmado_at'   => now(),
            ]);

            $totalDebe  = 0;
            $totalHaber = 0;
            $orden      = 1;

            $tipoMovimiento = match($sale->forma_pago) {
                'efectivo', 'transferencia', 'cheque', 'tarjeta' => 'venta_contado',
                'credito' => 'venta_credito',
                default   => 'venta_contado',
            };

            // === LÍNEAS HABER: ingresos por cada item ===
            foreach ($sale->items as $item) {
                $tipoItem = $item->tipo_item;

                $tipoItemMapa = match($tipoItem) {
                    'producto_terminado'     => 'producto_terminado',
                    'materia_prima'          => 'materia_prima',
                    'insumo'                 => 'insumo',
                    'servicio'               => 'servicio',
                    'activo_fijo_maquinaria' => 'activo_fijo_maquinaria',
                    'activo_fijo_computo'    => 'activo_fijo_computo',
                    'activo_fijo_vehiculo'   => 'activo_fijo_vehiculo',
                    'activo_fijo_muebles'    => 'activo_fijo_muebles',
                    default                  => 'producto_terminado',
                };

                // HABER: ingreso
                $cuentaIngreso = self::getMapeo($sale->empresa_id, $tipoItemMapa, $tipoMovimiento);

                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_plan_id'  => $cuentaIngreso->id,
                    'descripcion'      => $item->inventoryItem?->nombre
                                        ?? $item->descripcion_servicio,
                    'debe'             => 0,
                    'haber'            => (float)$item->subtotal,
                    'orden'            => $orden++,
                ]);
                $totalHaber += (float)$item->subtotal;

                // HABER: IVA ventas
                if ($item->aplica_iva && $item->iva_monto > 0) {
                    try {
                        $cuentaIva = self::getMapeo($sale->empresa_id, $tipoItemMapa, 'iva_ventas');
                    } catch (Exception $e) {
                        $cuentaIva = self::getMapeo($sale->empresa_id, 'global', 'iva_ventas');
                    }

                    JournalEntryLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_plan_id'  => $cuentaIva->id,
                        'descripcion'      => 'IVA 15% - ' .
                                            ($item->inventoryItem?->nombre
                                            ?? $item->descripcion_servicio),
                        'debe'             => 0,
                        'haber'            => (float)$item->iva_monto,
                        'orden'            => $orden++,
                    ]);
                    $totalHaber += (float)$item->iva_monto;
                }

                // DEBE/HABER: costo de venta para productos
                if ($item->tipo_item !== 'servicio' && $item->inventoryItem) {
                    $cuentaCosto = self::getMapeo($sale->empresa_id, $tipoItemMapa, 'costo_venta');
                    $cuentaInventario = self::getMapeo($sale->empresa_id, $tipoItemMapa, 'compra_contado');

                    // factor_empaque: cuántas unidades base por presentación (default 1)
                    $factorEmpaque = (float)($item->factor_empaque ?? 1);
                    $stockQty      = round((float)$item->cantidad * $factorEmpaque, 6);

                    $costoUnitario = (float)($item->inventoryItem->purchase_price ?? 0);
                    $costoTotal    = $costoUnitario * $stockQty;

                    if ($costoTotal > 0) {
                        // DEBE: costo de venta
                        JournalEntryLine::create([
                            'journal_entry_id' => $journalEntry->id,
                            'account_plan_id'  => $cuentaCosto->id,
                            'descripcion'      => 'Costo venta - ' . $item->inventoryItem->nombre,
                            'debe'             => $costoTotal,
                            'haber'            => 0,
                            'orden'            => $orden++,
                        ]);
                        $totalDebe += $costoTotal;

                        // HABER: salida de inventario
                        JournalEntryLine::create([
                            'journal_entry_id' => $journalEntry->id,
                            'account_plan_id'  => $cuentaInventario->id,
                            'descripcion'      => 'Salida inventario - ' . $item->inventoryItem->nombre,
                            'debe'             => 0,
                            'haber'            => $costoTotal,
                            'orden'            => $orden++,
                        ]);
                        $totalHaber += $costoTotal;
                    }

                    // Movimiento inventario en unidades base
                    \App\Models\InventoryMovement::create([
                        'empresa_id'        => $sale->empresa_id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'type'              => 'salida',
                        'quantity'          => $stockQty,
                        'unit_price'        => $costoUnitario,
                        'total'             => $costoTotal,
                        'reference_type'    => 'sale',
                        'reference_id'      => $sale->id,
                        'journal_entry_id'  => $journalEntry->id,
                        'notes'             => 'Venta ' . $sale->referencia,
                        'date'              => $sale->fecha,
                    ]);

                    // Validar stock en unidades base
                    if ((float)$item->inventoryItem->stock_actual < $stockQty) {
                        throw new \Exception("Stock insuficiente para " . $item->inventoryItem->nombre . ": disponible " . $item->inventoryItem->stock_actual . ", requerido " . $stockQty);
                    }
                    $item->inventoryItem->decrement('stock_actual', $stockQty);
                }
            }

            // === LÍNEA DEBE: cobro total ===
            // Determinar cuenta según forma de pago
            $cuentaCobro = $this->getCuentaPago($sale);

            if (!$cuentaCobro || $cuentaCobro->type === 'ingreso') {
                // Fallback robusto: Banco si no hay cuenta o es de ingreso (error de mapeo)
                $cuentaCobro = AccountPlan::where('empresa_id', $sale->empresa_id)
                    ->where('code', '1.1.01.03')
                    ->first() ?? self::getMapeo($sale->empresa_id, 'global', $tipoMovimiento);
            }

            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_plan_id'  => $cuentaCobro->id,
                'descripcion'      => 'Cobro venta ' . $sale->referencia . ' (' . ucfirst($sale->forma_pago) . ')',
                'debe'             => (float)$sale->total,
                'haber'            => 0,
                'orden'            => $orden++,
            ]);
            $totalDebe += (float)$sale->total;

            // Verificar cuadre
            if (round((float)$totalDebe, 2) !== round((float)$totalHaber, 2)) {
                throw new \Exception("Asiento de venta descuadrado: DEBE=" . round($totalDebe, 2) . " HABER=" . round($totalHaber, 2));
            }

            $journalEntry->update([
                'total_debe'    => $totalDebe,
                'total_haber'   => $totalHaber,
                'esta_cuadrado' => true,
                'status'        => 'confirmado',
            ]);

            return $journalEntry;
        });
    }

    /**
     * Genera el asiento contable al activar una Deuda (préstamo recibido).
     */
    public function generarAsientoDeuda(\App\Models\Debt $debt): JournalEntry
    {
        return DB::transaction(function () use ($debt) {
            $monto = (float) $debt->monto_original;

            $cuentaPasivo = $this->getCuentaPasivoDeuda($debt);
            $cuentaActivo = $this->getCuentaActivoDeuda($debt);

            $entry = JournalEntry::create([
                'empresa_id'      => $debt->empresa_id,
                'fecha'           => $debt->fecha_inicio,
                'descripcion'     => "Préstamo recibido: {$debt->acreedor} - {$debt->numero}",
                'tipo'            => 'ajuste',
                'origen'          => 'automatico',
                'referencia_tipo' => 'debt',
                'referencia_id'   => $debt->id,
                'status'          => 'confirmado',
                'total_debe'      => $monto,
                'total_haber'     => $monto,
                'esta_cuadrado'   => true,
                'confirmado_por'  => Auth::id(),
                'confirmado_at'   => now(),
            ]);

            // DR: Banco/Caja (activo aumenta)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_plan_id'  => $cuentaActivo->id,
                'descripcion'      => "Préstamo recibido {$debt->numero} - {$debt->acreedor}",
                'debe'             => $monto,
                'haber'            => 0,
                'orden'            => 1,
            ]);

            // CR: Préstamo por Pagar (pasivo aumenta)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_plan_id'  => $cuentaPasivo->id,
                'descripcion'      => "Préstamo por pagar - {$debt->acreedor}",
                'debe'             => 0,
                'haber'            => $monto,
                'orden'            => 2,
            ]);

            return $entry;
        });
    }

    /**
     * Genera el asiento contable para un Pago de Deuda.
     */
    public function generarAsientoPagoDeuda(\App\Models\DebtPayment $payment): JournalEntry
    {
        $debt = $payment->debt;

        return DB::transaction(function () use ($payment, $debt) {
            $totalDebe = (float) $payment->monto_capital
                + (float) $payment->monto_interes
                + (float) $payment->monto_mora;

            $cuentaPasivo = $this->getCuentaPasivoDeuda($debt);
            $cuentaPago   = $this->getCuentaPagoDebtPayment($payment);

            $desc = $payment->numero_cuota
                ? "Pago cuota #{$payment->numero_cuota} préstamo {$debt->numero} ({$payment->numero})"
                : "Pago préstamo {$debt->numero} ({$payment->numero})";

            $entry = JournalEntry::create([
                'empresa_id'      => $debt->empresa_id,
                'fecha'           => $payment->fecha_pago,
                'descripcion'     => $desc,
                'tipo'            => 'ajuste',
                'origen'          => 'automatico',
                'referencia_tipo' => 'debt_payment',
                'referencia_id'   => $payment->id,
                'status'          => 'confirmado',
                'total_debe'      => $totalDebe,
                'total_haber'     => $totalDebe,
                'esta_cuadrado'   => true,
                'confirmado_por'  => Auth::id(),
                'confirmado_at'   => now(),
            ]);

            $orden = 1;

            // DR: Préstamo por Pagar (reduce el pasivo)
            if ($payment->monto_capital > 0) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_plan_id'  => $cuentaPasivo->id,
                    'descripcion'      => "Capital abonado - {$debt->numero}",
                    'debe'             => (float) $payment->monto_capital,
                    'haber'            => 0,
                    'orden'            => $orden++,
                ]);
            }

            // DR: Gasto por Intereses
            if ($payment->monto_interes > 0) {
                $cuentaInteres = $this->getCuentaGastoFinanciero($debt->empresa_id, '5.3.01', 'Intereses');
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_plan_id'  => $cuentaInteres->id,
                    'descripcion'      => "Intereses pagados - {$debt->numero}",
                    'debe'             => (float) $payment->monto_interes,
                    'haber'            => 0,
                    'orden'            => $orden++,
                ]);
            }

            // DR: Gasto por Mora
            if ($payment->monto_mora > 0) {
                $cuentaMora = $this->getCuentaGastoFinanciero($debt->empresa_id, '5.3.02', 'Mora');
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_plan_id'  => $cuentaMora->id,
                    'descripcion'      => "Mora pagada - {$debt->numero}",
                    'debe'             => (float) $payment->monto_mora,
                    'haber'            => 0,
                    'orden'            => $orden++,
                ]);
            }

            // CR: Banco/Caja (activo disminuye)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_plan_id'  => $cuentaPago->id,
                'descripcion'      => "Pago desde cuenta - {$debt->acreedor}",
                'debe'             => 0,
                'haber'            => $totalDebe,
                'orden'            => $orden,
            ]);

            return $entry;
        });
    }

    private function getCuentaPasivoDeuda(\App\Models\Debt $debt): AccountPlan
    {
        // 1. Cuenta asignada manualmente en la deuda
        if ($debt->account_plan_id) {
            $cuenta = AccountPlan::withoutGlobalScopes()->where('id', $debt->account_plan_id)->first();
            if ($cuenta) return $cuenta;
        }

        $empresaId = $debt->empresa_id;

        // 2. Según clasificación y tipo, buscar cuenta más específica
        if ($debt->clasificacion === 'no_corriente') {
            // Largo plazo: 2.2.01.01 Préstamos bancarios LP
            $cuenta = AccountPlan::where('empresa_id', $empresaId)->where('code', '2.2.01.01')->first()
                ?? AccountPlan::where('empresa_id', $empresaId)->where('code', 'like', '2.2.01%')->where('accepts_movements', true)->first()
                ?? AccountPlan::where('empresa_id', $empresaId)->where('code', 'like', '2.2%')->where('accepts_movements', true)->first();
        } elseif ($debt->tipo === 'tarjeta_credito') {
            // Tarjetas de crédito: 2.1.02.03
            $cuenta = AccountPlan::where('empresa_id', $empresaId)->where('code', '2.1.02.03')->first()
                ?? AccountPlan::where('empresa_id', $empresaId)->where('code', 'like', '2.1.02%')->where('accepts_movements', true)->first();
        } else {
            // Corriente: 2.1.02.01 Préstamos bancarios CP
            $cuenta = AccountPlan::where('empresa_id', $empresaId)->where('code', '2.1.02.01')->first()
                ?? AccountPlan::where('empresa_id', $empresaId)->where('code', 'like', '2.1.02%')->where('accepts_movements', true)->first();
        }

        // 3. Fallback: cualquier pasivo con accepts_movements
        return $cuenta
            ?? AccountPlan::where('empresa_id', $empresaId)->where('type', 'pasivo')->where('accepts_movements', true)->first()
            ?? throw new Exception("Sin cuenta contable de pasivo para deuda {$debt->numero}");
    }

    private function getCuentaActivoDeuda(\App\Models\Debt $debt): AccountPlan
    {
        if ($debt->bank_account_id) {
            $cuenta = $debt->bankAccount?->accountPlan;
            if ($cuenta) return $cuenta;
        }

        if ($debt->credit_card_id) {
            $cuenta = $debt->creditCard?->accountPlan;
            if ($cuenta) return $cuenta;
        }

        $empresaId = $debt->empresa_id;

        return AccountPlan::where('empresa_id', $empresaId)->where('code', '1.1.01.03')->first()
            ?? AccountPlan::where('empresa_id', $empresaId)->where('code', 'like', '1.1.01%')->first()
            ?? AccountPlan::where('empresa_id', $empresaId)->where('type', 'activo')->where('accepts_movements', true)->first()
            ?? throw new Exception("Sin cuenta contable de activo para deuda {$debt->numero}");
    }

    private function getCuentaPagoDebtPayment(\App\Models\DebtPayment $payment): AccountPlan
    {
        if ($payment->metodo_pago === 'efectivo' && $payment->cash_register_id) {
            $cuenta = $payment->cashRegister?->accountPlan;
            if ($cuenta) return $cuenta;
        }

        if ($payment->bank_account_id) {
            $cuenta = $payment->bankAccount?->accountPlan;
            if ($cuenta) return $cuenta;
        }

        $empresaId = $payment->empresa_id;

        return AccountPlan::where('empresa_id', $empresaId)->where('code', '1.1.01.03')->first()
            ?? AccountPlan::where('empresa_id', $empresaId)->where('type', 'activo')->where('accepts_movements', true)->first()
            ?? throw new Exception("Sin cuenta contable de pago para {$payment->numero}");
    }

    private function getCuentaGastoFinanciero(int $empresaId, string $codigoPref, string $tipo): AccountPlan
    {
        // Buscar por código exacto primero
        $cuenta = AccountPlan::where('empresa_id', $empresaId)->where('code', $codigoPref)->first();
        if ($cuenta) return $cuenta;

        // Para intereses: 6.2.01 Intereses bancarios
        if ($tipo === 'Intereses') {
            $cuenta = AccountPlan::where('empresa_id', $empresaId)->where('code', '6.2.01')->first()
                ?? AccountPlan::where('empresa_id', $empresaId)->where('code', 'like', '6.2%')->where('accepts_movements', true)->first()
                ?? AccountPlan::where('empresa_id', $empresaId)->where('name', 'like', '%inter%')->where('type', 'gasto')->where('accepts_movements', true)->first();
        }

        // Para mora: 6.2.03 Intereses y multas, o fallback a 6.2.01
        if ($tipo === 'Mora') {
            $cuenta = AccountPlan::where('empresa_id', $empresaId)->where('code', '6.2.03')->first()
                ?? AccountPlan::where('empresa_id', $empresaId)->where('code', '6.2.01')->first()
                ?? AccountPlan::where('empresa_id', $empresaId)->where('code', 'like', '6.2%')->where('accepts_movements', true)->first()
                ?? AccountPlan::where('empresa_id', $empresaId)->where('name', 'like', '%multa%')->where('type', 'gasto')->where('accepts_movements', true)->first();
        }

        // Fallback final: cualquier gasto financiero o último recurso cualquier gasto
        return $cuenta
            ?? AccountPlan::where('empresa_id', $empresaId)->where('code', 'like', '6.2%')->where('accepts_movements', true)->first()
            ?? AccountPlan::where('empresa_id', $empresaId)->where('type', 'gasto')->where('accepts_movements', true)->orderBy('code', 'desc')->first()
            ?? throw new Exception("Sin cuenta de gasto financiero ({$tipo}) para empresa {$empresaId}");
    }

    /**
     * Genera un asiento contable para una Orden de Producción completada.
     */
    public function generarAsientoProduccion(\App\Models\ProductionOrder $order): JournalEntry
    {
        $order->load(['finishedProduct', 'materials.inventoryItem']);

        return DB::transaction(function () use ($order) {
            $journalEntry = JournalEntry::create([
                'empresa_id'      => $order->empresa_id,
                'fecha'           => $order->fecha,
                'descripcion'     => 'Producción ' . $order->referencia . ' - ' . ($order->finishedProduct->nombre ?? 'S/N'),
                'tipo'            => 'manufactura',
                'origen'          => 'automatico',
                'referencia_tipo' => 'production_order',
                'referencia_id'   => $order->id,
                'status'          => 'confirmado',
                'total_debe'      => 0,
                'total_haber'     => 0,
                'esta_cuadrado'   => true,
                'confirmado_por'  => Auth::id(),
                'confirmado_at'   => now(),
            ]);

            $totalDebe  = 0;
            $totalHaber = 0;
            $orden      = 1;

            // 1. Línea DEBE (Entrada de Producto Terminado)
            $tipoItemPT = $order->finishedProduct->type ?? 'producto_terminado';
            $cuentaPT = self::getMapeo($order->empresa_id, $tipoItemPT, 'entrada_produccion');

            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_plan_id'  => $cuentaPT->id,
                'descripcion'      => "Producción terminada - " . $order->finishedProduct->nombre,
                'debe'             => $order->costo_total,
                'haber'            => 0,
                'orden'            => $orden++,
            ]);
            $totalDebe += $order->costo_total;

            // 2. Líneas HABER (Salida de Materiales)
            foreach ($order->materials as $material) {
                $tipoItemMat = $material->inventoryItem->type ?? 'materia_prima';
                $cuentaMat = self::getMapeo($order->empresa_id, $tipoItemMat, 'salida_produccion');

                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_plan_id'  => $cuentaMat->id,
                    'descripcion'      => "Consumo producción - " . $material->inventoryItem->nombre,
                    'debe'             => 0,
                    'haber'            => $material->costo_total,
                    'orden'            => $orden++,
                ]);
                $totalHaber += $material->costo_total;
            }

            if (round($totalDebe, 2) !== round($totalHaber, 2)) {
                throw new Exception("Asiento de producción descuadrado: DEBE={$totalDebe} HABER={$totalHaber}");
            }

            $journalEntry->update([
                'total_debe'  => $totalDebe,
                'total_haber' => $totalHaber,
            ]);

            return $journalEntry;
        });
    }
}
