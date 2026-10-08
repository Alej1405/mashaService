<?php

namespace App\Modules\Compras\Actions;

use App\Models\Supplier;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Str;

/**
 * El proveedor de un comprobante, por su RUC o cédula; si no existe, se crea.
 * Lo usan el clasificador del portal (solo trae nombre y RUC) y las compras por
 * correo (el XML trae además nombre comercial y dirección). Los campos que la
 * tabla exige y el SRI no da quedan "Por completar".
 */
#[Documentado(
    grupo: 'Compras',
    descripcion: 'Busca el proveedor de un comprobante por su identificación y lo crea si no existe.',
    tipo: 'action',
)]
final class ProveedorPorIdentificacion
{
    public function obtener(int $empresaId, string $identificacion, ?string $razonSocial, array $extra = []): Supplier
    {
        $existente = Supplier::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('numero_identificacion', $identificacion)
            ->first();

        if ($existente) {
            return $existente;
        }

        $esRuc = strlen($identificacion) === 13;

        return Supplier::withoutGlobalScopes()->create([
            'empresa_id'            => $empresaId,
            'nombre'                => $razonSocial ?: $identificacion,
            'nombre_comercial'      => $extra['nombre_comercial'] ?? null,
            'direccion'             => $extra['direccion'] ?? null,
            'tipo_persona'          => $esRuc && ($identificacion[2] ?? '') >= '6' ? 'juridica' : 'natural',
            'tipo_identificacion'   => $esRuc ? 'ruc' : 'cedula',
            'numero_identificacion' => $identificacion,
            'tipo_proveedor'        => 'bienes',
            'contacto_principal'    => 'Por completar',
            'telefono_principal'    => 'Por completar',
            'correo_principal'      => 'porcompletar@' . Str::slug($razonSocial ?: 'proveedor') . '.ec',
            'activo'                => true,
        ]);
    }
}
