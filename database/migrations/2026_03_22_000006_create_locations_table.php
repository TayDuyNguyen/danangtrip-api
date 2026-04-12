<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('subcategories')->nullOnDelete();
            $table->text('description');
            $table->string('short_description', 500);
            $table->string('address', 255);
            $table->string('district', 50)->index();
            $table->string('ward', 50)->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 255)->nullable();
            $table->json('opening_hours')->nullable();
            $table->decimal('price_min', 12, 0)->nullable();
            $table->decimal('price_max', 12, 0)->nullable();
            $table->unsignedTinyInteger('price_level')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0)->index();
            $table->unsignedInteger('review_count')->default(0);
            $table->unsignedInteger('view_count')->default(0)->index();
            $table->unsignedInteger('favorite_count')->default(0);
            $table->string('thumbnail', 255)->nullable();
            $table->json('images')->nullable();
            $table->string('video_url', 255)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('category_id');
            $table->index('subcategory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
