<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the payments table with columns:
     * - id, booking_id (foreign key to bookings, hasOne relationship),
     *   amount, status (success, failed, refunded)
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['success', 'failed', 'refunded'])->default('failed');
            $table->timestamps();

            // Indexes to improve query performance
            $table->index('booking_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
