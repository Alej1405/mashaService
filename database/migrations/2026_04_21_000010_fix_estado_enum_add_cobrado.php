<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE logistics_billing_requests MODIFY COLUMN estado ENUM('pendiente','aceptado','rechazado','facturado','cobrado') NOT NULL DEFAULT 'pendiente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE logistics_billing_requests MODIFY COLUMN estado ENUM('pendiente','aceptado','rechazado','facturado') NOT NULL DEFAULT 'pendiente'");
    }
};
