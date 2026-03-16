<?php

namespace App\Observers;

use App\Models\Sale;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SaleObserver
{
    public function updated(Sale $sale): void
    {
        // Solo actuar si el estado cambia a 'confirmado'
        if (!$sale->isDirty('estado') || $sale->estado !== 'confirmado') {
            return;
        }

        // Evitar duplicidad si ya tiene asiento
        if ($sale->journal_entry_id) {
            return;
        }

        try {
            // Paso 5: El método generarAsientoVenta será implementado a continuación
            $journalEntry = app(AccountingService::class)
                ->generarAsientoVenta($sale);
            
            $sale->updateQuietly([
                'journal_entry_id' => $journalEntry->id,
                'confirmado_por'   => auth()->id(),
                'confirmado_at'    => now(),
            ]);

            // --- Lógica de Tesorería ---
            if ($sale->forma_pago === 'efectivo' && $sale->cash_register_id) {
                $sesion = \App\Models\CashSession::where('cash_register_id', $sale->cash_register_id)
                    ->where('estado', 'abierta')
                    ->latest()->first();

                \App\Models\CashMovement::create([
                    'empresa_id'       => $sale->empresa_id,
                    'cash_register_id' => $sale->cash_register_id,
                    'cash_session_id'  => $sesion?->id,
                    'tipo'             => 'ingreso',
                    'origen'           => 'venta',
                    'referencia_tipo'  => 'sale',
                    'referencia_id'    => $sale->id,
                    'journal_entry_id' => $journalEntry->id,
                    'monto'            => $sale->total,
                    'descripcion'      => 'Venta ' . $sale->referencia,
                    'fecha'            => $sale->fecha,
                ]);
            }

            if ($sale->forma_pago === 'tarjeta' && $sale->credit_card_id) {
                // Las ventas con tarjeta suelen ser abonos a la tarjeta si es cobro? 
                // No, en ventas con tarjeta el dinero va al banco (vía procesador). 
                // Sin embargo, si el usuario selecciona una tarjeta de crédito en Venta, 
                // asumimos que es un registro de cobro vía tarjeta que debe afectar Tesorería.
                // Actualmente CreditCardMovement es 'cargo' o 'pago'. 
                // Para una venta, si usara una tarjeta propia (poco común), sería un cargo.
                // Pero lo más común es que sea un cobro.
                // Ajustamos CreditCardMovement en el modelo si es necesario, 
                // por ahora seguimos el esquema de cargos para compras.
                // En ventas, usualmente el dinero va a Banco. 
                // Si el usuario eligió bank_account_id, no generamos movimiento de caja/tarjeta manual aquí 
                // ya que va directo al asiento.
            }

            // Webhook n8n
            $webhookUrl = config('services.n8n.webhook_venta');
            if ($webhookUrl) {
                try {
                    Http::timeout(5)->post($webhookUrl, [
                        'sale_id' => $sale->id,
                        'referencia' => $sale->referencia,
                        'total' => $sale->total,
                        'customer' => $sale->customer->nombre,
                        'fecha' => $sale->fecha->toDateString(),
                        'tipo_venta' => $sale->tipo_venta,
                    ]);
                } catch (\Exception $webhookEx) {
                    Log::warning('Error enviando webhook n8n para venta', [
                        'sale_id' => $sale->id,
                        'error' => $webhookEx->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error generando asiento de venta', [
                'sale_id' => $sale->id,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
