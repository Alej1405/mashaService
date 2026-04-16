<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE journal_entries MODIFY tipo ENUM('apertura','manual','compra','venta','manufactura','ajuste','cierre','depreciacion','cobro_logistico')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE journal_entries MODIFY tipo ENUM('apertura','manual','compra','venta','manufactura','ajuste','cierre','depreciacion')");
    }
};
