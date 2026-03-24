<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_indirect_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_design_id')->constrained('product_designs')->cascadeOnDelete();
            $table->string('tipo'); // diseño_marca, publicidad, otro
            $table->string('descripcion')->nullable();
            $table->decimal('monto_mensual', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_indirect_costs');
    }
};
