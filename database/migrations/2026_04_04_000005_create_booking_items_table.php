<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->foreignId('tour_schedule_id')->constrained('tour_schedules')->cascadeOnDelete();
            $table->string('item_type', 30)->default('tour');
            $table->string('item_name', 200);
            $table->date('travel_date');
            $table->integer('quantity_adult')->default(0);
            $table->integer('quantity_child')->default(0);
            $table->integer('quantity_infant')->default(0);
            $table->decimal('unit_price_adult', 12, 0);
            $table->decimal('unit_price_child', 12, 0);
            $table->decimal('unit_price_infant', 12, 0);
            $table->decimal('subtotal', 12, 0);
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->index(['booking_id', 'tour_id']);
            $table->index('travel_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
