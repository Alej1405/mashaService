<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Paso 2/4: Copia los campos de portal de store_customers → customers.
 *
 * Usa el FK customer_id ya existente en store_customers (puesto por el observer).
 * Solo actualiza campos nuevos; nunca toca codigo, numero_identificacion,
 * tipo_persona, tipo_identificacion ni campos contables.
 *
 * Único caso especial: SC#1 (Margorie) tiene email distinto entre tablas —
 * el correo del portal (store_customers) es el correcto para autenticación,
 * el de customers era un error de carga (tenía el email del admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        $storeCustomers = DB::table('store_customers')
            ->whereNotNull('customer_id')
            ->get();

        foreach ($storeCustomers as $sc) {
            $customer = DB::table('customers')->where('id', $sc->customer_id)->first();
            if (! $customer) {
                continue;
            }

            $update = [
                'apellido'          => $sc->apellido,
                'razon_social'      => $sc->razon_social,
                'password'          => $sc->password,
                'email_verified_at' => $sc->email_verified_at,
                'is_super_admin'    => $sc->is_super_admin,
            ];

            // Si los emails difieren, el del portal es el correcto para autenticación
            if ($sc->email !== $customer->email) {
                $update['email'] = $sc->email;
            }

            DB::table('customers')->where('id', $sc->customer_id)->update($update);
        }
    }

    public function down(): void
    {
        // No revertimos datos porque no podemos distinguir qué cambió
        // Restaurar desde backup si es necesario.
    }
};
