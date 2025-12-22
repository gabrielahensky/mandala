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
        Schema::create('rent_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable(); // NULL = masih aktif

            $table->integer('monthly_rent')->nullable(); // optional, bisa dipakai nanti
            $table->text('note')->nullable();

            $table->timestamps();

            // constraint logis (tidak teknis)
            // satu unit TIDAK BOLEH punya >1 rent cycle aktif
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_cycles');
    }
};
