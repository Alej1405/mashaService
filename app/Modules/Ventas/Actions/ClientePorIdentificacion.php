<?php

namespace App\Modules\Ventas\Actions;

use App\Models\Customer;

/**
 * El cliente de una factura emitida, creado si no estaba.
 *
 * Toda venta tiene cliente: el consumidor final es un cliente con la
 * identificación 9999999999999, nunca un customer_id nulo.
 */
final class ClientePorIdentificacion
{
    public const CONSUMIDOR_FINAL = '9999999999999';

    public function obtener(int $empresaId, string $identificacion, ?string $razonSocial): Customer
    {
        $existente = Customer::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('numero_identificacion', $identificacion)
            ->first();

        if ($existente) {
            return $existente;
        }

        $tipo = match (true) {
            $identificacion === self::CONSUMIDOR_FINAL => 'consumidor_final',
            strlen($identificacion) === 13            => 'ruc',
            strlen($identificacion) === 10            => 'cedula',
            default                                    => 'pasaporte',
        };

        return Customer::withoutGlobalScopes()->create([
            'empresa_id'            => $empresaId,
            'nombre'                => $razonSocial ?: ($tipo === 'consumidor_final' ? 'Consumidor final' : $identificacion),
            'razon_social'          => $razonSocial,
            'tipo_persona'          => $tipo === 'ruc' && ($identificacion[2] ?? '') >= '6' ? 'juridica' : 'natural',
            'tipo_identificacion'   => $tipo,
            'numero_identificacion' => $identificacion,
            'activo'                => true,
        ]);
    }
}
