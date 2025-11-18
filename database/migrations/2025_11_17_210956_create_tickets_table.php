<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the tickets table with columns:
     * - id, type (VIP, Standard, etc.), price, quantity, event_id (foreign key to events)
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // VIP, Standard, Premium, etc.
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->timestamps();

            // Indexes to improve query performance
            $table->index('event_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
