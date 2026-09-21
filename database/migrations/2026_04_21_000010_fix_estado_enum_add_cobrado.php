<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MODIFY COLUMN … ENUM es sintaxis de MySQL: en Postgres y SQLite
        // la columna ya es texto y la sentencia no aplica.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE logistics_billing_requests MODIFY COLUMN estado ENUM('pendiente','aceptado','rechazado','facturado','cobrado') NOT NULL DEFAULT 'pendiente'");
        }
    }

    public function down(): void
    {
        // MODIFY COLUMN … ENUM es sintaxis de MySQL: en Postgres y SQLite
        // la columna ya es texto y la sentencia no aplica.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE logistics_billing_requests MODIFY COLUMN estado ENUM('pendiente','aceptado','rechazado','facturado') NOT NULL DEFAULT 'pendiente'");
        }
    }
};
