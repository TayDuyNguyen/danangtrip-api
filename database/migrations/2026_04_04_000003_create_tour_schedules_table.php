<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('max_people')->default(0);
            $table->integer('booked_people')->default(0);
            $table->decimal('price_adult', 12, 0)->nullable()->comment('Override tour price');
            $table->decimal('price_child', 12, 0)->nullable();
            $table->decimal('price_infant', 12, 0)->nullable();
            $table->string('status', 20)->default('available')->index();
            $table->timestamps();

            $table->unique(['tour_id', 'start_date'], 'uq_tour_schedule');
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_schedules');
    }
};
