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
        Schema::create('rent_billings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rent_cycle_id')->constrained()->cascadeOnDelete();

            $table->date('billing_month'); // pakai tanggal 1 (YYYY-MM-01)
            $table->integer('amount');

            $table->date('due_date');
            $table->date('paid_at')->nullable();

            $table->enum('status', [
                'upcoming',
                'due',
                'overdue',
                'paid',
            ])->default('upcoming');

            $table->timestamps();

            $table->unique(['rent_cycle_id', 'billing_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_billings');
    }
};
