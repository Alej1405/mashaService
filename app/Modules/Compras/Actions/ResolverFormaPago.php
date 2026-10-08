<?php

namespace App\Modules\Compras\Actions;

use App\Models\BankAccount;
use App\Models\CashRegister;
use App\Models\CreditCard;
use App\Models\Purchase;
use App\Shared\Attributes\Documentado;
use Illuminate\Validation\ValidationException;

/**
 * Cómo se pagó una compra que llegó como factura electrónica.
 *
 * El XML trae la forma de pago del SRI (Tabla 24), que dice "con tarjeta" o "sin
 * sistema financiero", no de qué caja o banco salió el dinero. Se carga sola solo
 * cuando no hay dudas: crédito con plazo, o un único medio activo de ese tipo en
 * la empresa. En otro caso queda pendiente y se pregunta.
 */
#[Documentado(
    grupo: 'Compras',
    descripcion: 'Deduce la forma de pago de una factura electrónica o aplica la que elige el usuario.',
    tipo: 'action',
)]
final class ResolverFormaPago
{
    /** Tabla 24 → forma de pago del ERP. Lo que no está aquí se pregunta. */
    private const DESDE_SRI = [
        '01' => 'efectivo',        // sin utilización del sistema financiero
        '16' => 'transferencia',   // tarjeta de débito
        '19' => 'tarjeta',         // tarjeta de crédito
        '20' => 'transferencia',   // otros con utilización del sistema financiero
    ];

    public const ETIQUETAS = [
        'efectivo'      => 'Efectivo',
        'transferencia' => 'Transferencia / Débito',
        'tarjeta'       => 'Tarjeta de crédito',
        'credito'       => 'Crédito (por pagar)',
    ];

    /** @return array<string,mixed> columnas de la compra */
    public function desdeXml(Purchase $compra, array $pago): array
    {
        $plazo = (int) ($pago['plazo'] ?? 0);
        if ($plazo > 0) {
            return $this->credito($compra, $plazo);
        }

        $forma = self::DESDE_SRI[$pago['codigo'] ?? ''] ?? null;
        $medios = $forma ? $this->medios($compra->empresa_id, $forma) : [];

        if (count($medios) === 1) {
            return $this->contado($forma, (int) array_key_first($medios));
        }

        return ['requiere_forma_pago' => true];
    }

    /**
     * La forma que elige el usuario, por Telegram o en el panel.
     *
     * @return array<string,mixed>
     */
    public function elegida(Purchase $compra, string $forma, ?int $medioId, ?int $plazoDias = null): array
    {
        if (! array_key_exists($forma, self::ETIQUETAS)) {
            throw ValidationException::withMessages(['forma_pago' => 'Esa forma de pago no existe.']);
        }
        if ($forma === 'credito') {
            return $this->credito($compra, $plazoDias ?: (int) $compra->plazo_dias ?: 30);
        }

        $medios = $this->medios($compra->empresa_id, $forma);
        $medioId ??= count($medios) === 1 ? (int) array_key_first($medios) : null;

        if (! $medioId || ! array_key_exists($medioId, $medios)) {
            throw ValidationException::withMessages(['medio_id' => 'Elige de qué ' . $this->queMedio($forma) . ' salió el pago.']);
        }

        return $this->contado($forma, $medioId);
    }

    /**
     * Los medios activos de un tipo, id => nombre. Es lo que se ofrece al preguntar.
     *
     * @return array<int,string>
     */
    public function medios(int $empresaId, string $forma): array
    {
        return match ($forma) {
            'efectivo'      => CashRegister::withoutGlobalScopes()->where('empresa_id', $empresaId)
                ->where('activo', true)->pluck('nombre', 'id')->all(),
            'transferencia' => BankAccount::withoutGlobalScopes()->where('empresa_id', $empresaId)
                ->where('activo', true)->get()
                ->mapWithKeys(fn ($b) => [$b->id => trim(($b->bank?->nombre ?? 'Banco') . ' ' . $b->numero_cuenta)])->all(),
            'tarjeta'       => CreditCard::withoutGlobalScopes()->where('empresa_id', $empresaId)
                ->where('activo', true)->pluck('nombre', 'id')->all(),
            default         => [],
        };
    }

    private function queMedio(string $forma): string
    {
        return match ($forma) {
            'efectivo' => 'caja',
            'tarjeta'  => 'tarjeta',
            default    => 'cuenta bancaria',
        };
    }

    private function credito(Purchase $compra, int $plazo): array
    {
        return [
            'forma_pago'          => 'credito',
            'tipo_pago'           => 'credito_local',
            'plazo_dias'          => $plazo,
            'fecha_vencimiento'   => $compra->date->copy()->addDays($plazo),
            'cash_register_id'    => null,
            'bank_account_id'     => null,
            'credit_card_id'      => null,
            'requiere_forma_pago' => false,
        ];
    }

    private function contado(string $forma, int $medioId): array
    {
        return [
            'forma_pago'          => $forma,
            'tipo_pago'           => 'contado',
            'fecha_vencimiento'   => null,
            'cash_register_id'    => $forma === 'efectivo' ? $medioId : null,
            'bank_account_id'     => $forma === 'transferencia' ? $medioId : null,
            'credit_card_id'      => $forma === 'tarjeta' ? $medioId : null,
            'requiere_forma_pago' => false,
        ];
    }
}
