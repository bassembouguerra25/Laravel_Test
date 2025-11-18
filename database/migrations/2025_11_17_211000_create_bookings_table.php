<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the bookings table with columns:
     * - id, user_id (foreign key to users), ticket_id (foreign key to tickets),
     *   quantity, status (pending, confirmed, cancelled)
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->integer('quantity');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamps();

            // Indexes to improve query performance
            $table->index('user_id');
            $table->index('ticket_id');
            $table->index('status');
            $table->index(['user_id', 'ticket_id', 'status']); // Composite index to prevent double bookings
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
