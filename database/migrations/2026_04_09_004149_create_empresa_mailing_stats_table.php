<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_mailing_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('delivered')->default(0);
            $table->unsignedBigInteger('opened')->default(0);
            $table->unsignedBigInteger('clicked')->default(0);
            $table->unsignedBigInteger('bounced')->default(0);
            $table->unsignedBigInteger('complained')->default(0);
            $table->unsignedBigInteger('unsubscribed')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_mailing_stats');
    }
};
