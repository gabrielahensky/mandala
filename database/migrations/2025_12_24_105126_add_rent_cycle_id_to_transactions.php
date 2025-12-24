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
            $table->foreignId('rent_cycle_id')
                ->nullable()
                ->after('category')
                ->constrained('rent_cycles')
                ->nullOnDelete();

            $table->index('rent_cycle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['rent_cycle_id']);
            $table->dropIndex(['rent_cycle_id']);
            $table->dropColumn('rent_cycle_id');
        });
    }
};
