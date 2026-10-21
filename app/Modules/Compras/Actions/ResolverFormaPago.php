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
 * El XML trae la forma de pago del SRI (Tabla 24): "sin sistema financiero" es
 * efectivo, "otros con sistema financiero" es transferencia, y así. Eso manda:
 * nunca se supone crédito ni se aplica una forma general a varias facturas. Lo
 * que el XML no dice es de qué caja o banco salió: se carga solo si la empresa
 * tiene un único medio de ese tipo; si no, se pregunta.
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

    /** La forma del ERP que corresponde al código de la Tabla 24, o null si no se sabe. */
    public static function formaDelSri(?string $codigo): ?string
    {
        return self::DESDE_SRI[(string) $codigo] ?? null;
    }

    /**
     * Lo que dice el XML, con la caja o el banco si no hay dudas; null si falta
     * elegir de cuál salió. $cuentas: la elegida por forma ('efectivo' => 'efectivo:2').
     */
    public function sugerida(int $empresaId, ?string $codigoSri, array $cuentas = []): ?string
    {
        $forma = self::formaDelSri($codigoSri);
        if (! $forma) {
            return null;
        }
        if (! empty($cuentas[$forma])) {
            return $cuentas[$forma];
        }

        $medios = $this->medios($empresaId, $forma);

        return count($medios) === 1 ? $forma . ':' . array_key_first($medios) : null;
    }

    /** @return array<string,mixed> columnas de la compra */
    public function desdeXml(Purchase $compra, array $pago): array
    {
        // El código del SRI manda sobre el plazo: muchos emisores ponen un plazo
        // por defecto aunque se haya pagado en efectivo.
        if ($sugerida = $this->sugerida($compra->empresa_id, $pago['codigo'] ?? null)) {
            [$forma, $medio] = explode(':', $sugerida, 2);

            return $this->contado($forma, (int) $medio);
        }

        if (! self::formaDelSri($pago['codigo'] ?? null) && (int) ($pago['plazo'] ?? 0) > 0) {
            return $this->credito($compra, (int) $pago['plazo']);
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
