<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('note');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');

            // index penting untuk lookup idempotency
            $table->index(['source_type', 'source_id'], 'transactions_source_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_source_index');
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
