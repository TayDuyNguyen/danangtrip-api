<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rating_helpful_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_id')->constrained('ratings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['rating_id', 'user_id'], 'uq_rating_helpful_vote_user');
            $table->index(['user_id', 'created_at'], 'rating_helpful_votes_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_helpful_votes');
    }
};
