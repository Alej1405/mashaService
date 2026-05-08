<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_shipment_bills', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')
                ->nullable()
                ->after('notas')
                ->constrained('journal_entries')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('logistics_shipment_bills', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\JournalEntry::class);
            $table->dropColumn('journal_entry_id');
        });
    }
};
