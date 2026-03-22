<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->tinyInteger('score'); // 1-5 stars
            $table->text('comment')->nullable();
            $table->tinyInteger('image_count')->default(0);
            $table->integer('point_cost')->default(0);
            $table->string('status', 20)->default('pending')->index(); // pending, approved, rejected
            $table->string('rejected_reason', 255)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->integer('helpful_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'location_id'], 'uq_user_location_rating');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
