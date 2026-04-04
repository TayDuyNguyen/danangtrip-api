<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->foreignId('tour_category_id')->constrained('tour_categories')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->string('short_desc', 500)->nullable();
            $table->json('itinerary')->nullable();
            $table->json('inclusions')->nullable();
            $table->json('exclusions')->nullable();
            $table->decimal('price_adult', 12, 0);
            $table->decimal('price_child', 12, 0)->default(0);
            $table->decimal('price_infant', 12, 0)->default(0);
            $table->integer('discount_percent')->default(0);
            $table->string('duration', 50)->nullable();
            $table->string('start_time', 50)->nullable();
            $table->string('meeting_point', 255)->nullable();
            $table->integer('max_people')->default(0);
            $table->integer('min_people')->default(1);
            $table->date('available_from')->nullable();
            $table->date('available_to')->nullable();
            $table->string('thumbnail', 255)->nullable();
            $table->json('images')->nullable();
            $table->string('video_url', 255)->nullable();
            $table->json('location_ids')->nullable();
            $table->string('status', 20)->default('available')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_hot')->default(false)->index();
            $table->integer('view_count')->default(0);
            $table->integer('booking_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('price_adult');
            $table->index(['available_from', 'available_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
