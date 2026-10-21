<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\CashRegister;
use App\Models\CreditCard;

/**
 * Cómo se pagó (o cobró) un documento, en una sola elección.
 *
 * El XML del SRI trae la forma de pago de su tabla 24 ("otros con utilización
 * del sistema financiero"), que no dice de qué caja o de qué cuenta salió el
 * dinero. Eso es lo que decide la cuenta del asiento, así que se pregunta:
 *
 *   credito                 → queda por pagar (o por cobrar)
 *   efectivo:<caja>         → contra la cuenta de esa caja
 *   transferencia:<cuenta>  → contra esa cuenta bancaria
 *   tarjeta:<tarjeta>       → contra el pasivo de esa tarjeta
 *
 * Lo usan las compras, los gastos y las ventas que salen de la bandeja del portal.
 */
class MedioPago
{
    /** @return array<string, string> valor => etiqueta, para un select */
    public static function opciones(int $empresaId, bool $esVenta = false): array
    {
        $opciones = ['credito' => $esVenta ? 'A crédito (por cobrar)' : 'A crédito (por pagar)'];

        foreach (BankAccount::withoutGlobalScopes()->where('empresa_id', $empresaId)->where('activo', true)->get() as $b) {
            $opciones["transferencia:{$b->id}"] = 'Transferencia · ' . ucfirst((string) $b->tipo_cuenta)
                . ' ·' . substr((string) $b->numero_cuenta, -4);
        }

        foreach (CashRegister::withoutGlobalScopes()->where('empresa_id', $empresaId)->where('activo', true)->get() as $c) {
            $opciones["efectivo:{$c->id}"] = 'Efectivo · ' . $c->nombre;
        }

        if (! $esVenta) {
            foreach (CreditCard::withoutGlobalScopes()->where('empresa_id', $empresaId)->where('activo', true)->get() as $t) {
                $opciones["tarjeta:{$t->id}"] = 'Tarjeta · ' . $t->nombre . ' ·' . $t->ultimos_digitos;
            }
        }

        return $opciones;
    }

    /**
     * De la elección a las columnas del documento.
     *
     * @return array{forma_pago: string, bank_account_id: ?int, cash_register_id: ?int, credit_card_id: ?int}
     */
    public static function atributos(?string $eleccion): array
    {
        [$forma, $id] = array_pad(explode(':', (string) $eleccion, 2), 2, null);
        $id = $id ? (int) $id : null;

        return match ($forma) {
            'efectivo'      => ['forma_pago' => 'efectivo', 'bank_account_id' => null, 'cash_register_id' => $id, 'credit_card_id' => null],
            'transferencia' => ['forma_pago' => 'transferencia', 'bank_account_id' => $id, 'cash_register_id' => null, 'credit_card_id' => null],
            'tarjeta'       => ['forma_pago' => 'tarjeta', 'bank_account_id' => null, 'cash_register_id' => null, 'credit_card_id' => $id],
            default         => ['forma_pago' => 'credito', 'bank_account_id' => null, 'cash_register_id' => null, 'credit_card_id' => null],
        };
    }

    /** De las columnas de un documento a la elección (formato de este servicio). */
    public static function de(object $doc): string
    {
        return match ($doc->forma_pago) {
            'efectivo'      => "efectivo:{$doc->cash_register_id}",
            'transferencia' => "transferencia:{$doc->bank_account_id}",
            'tarjeta'       => "tarjeta:{$doc->credit_card_id}",
            default         => 'credito',
        };
    }

    public static function esCredito(?string $eleccion): bool
    {
        return self::atributos($eleccion)['forma_pago'] === 'credito';
    }
}
