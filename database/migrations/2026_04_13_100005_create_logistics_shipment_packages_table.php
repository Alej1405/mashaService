<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_shipment_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('logistics_shipments')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('logistics_packages')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['shipment_id', 'package_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_shipment_packages');
    }
};
