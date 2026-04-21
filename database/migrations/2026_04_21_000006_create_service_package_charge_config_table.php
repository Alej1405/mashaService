<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_package_charge_config', function (Blueprint $table) {
            $table->unsignedBigInteger('service_package_id');
            $table->unsignedBigInteger('service_charge_config_id');

            $table->primary(['service_package_id', 'service_charge_config_id']);

            $table->foreign('service_package_id')
                  ->references('id')->on('service_packages')
                  ->cascadeOnDelete();

            $table->foreign('service_charge_config_id')
                  ->references('id')->on('service_charge_configs')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_package_charge_config');
    }
};
