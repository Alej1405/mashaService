<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_shipment_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')
                  ->constrained('logistics_shipments')
                  ->cascadeOnDelete();
            $table->unsignedBigInteger('empresa_id');
            $table->string('descripcion');
            $table->decimal('monto', 10, 2);
            $table->unsignedTinyInteger('iva_pct')->default(15);
            $table->timestamps();

            $table->index('shipment_id');
            $table->index('empresa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_shipment_charges');
    }
};
