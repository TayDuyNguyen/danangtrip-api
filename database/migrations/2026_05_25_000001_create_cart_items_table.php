<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->foreignId('tour_schedule_id')->constrained('tour_schedules')->cascadeOnDelete();
            $table->integer('quantity_adult')->default(1);
            $table->integer('quantity_child')->default(0);
            $table->integer('quantity_infant')->default(0);
            $table->timestamps();

            // Prevent duplicate schedules for a user
            $table->unique(['user_id', 'tour_schedule_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
