<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->cascadeOnDelete();
            $table->foreignId('tour_id')->nullable()->constrained('tours')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->tinyInteger('score'); // 1-5 stars
            $table->text('comment')->nullable();
            $table->tinyInteger('image_count')->default(0);
            $table->string('status', 20)->default('approved')->index(); // pending, approved, rejected
            $table->string('rejected_reason', 255)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->integer('helpful_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'location_id'], 'uq_user_location_rating');
            $table->index('created_at');
        });

        DB::statement('CREATE UNIQUE INDEX ratings_user_tour_unique ON ratings (user_id, tour_id) WHERE tour_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX ratings_user_booking_unique ON ratings (user_id, booking_id) WHERE booking_id IS NOT NULL');
        DB::statement('CREATE INDEX ratings_tour_status_created_at_idx ON ratings (tour_id, status, created_at)');
        DB::statement('CREATE INDEX ratings_location_status_created_idx ON ratings (location_id, status, created_at DESC)');
        DB::statement('CREATE INDEX ratings_status_created_at_index ON ratings (status, created_at)');
        DB::statement('
            ALTER TABLE ratings
            ADD CONSTRAINT ratings_exactly_one_target_chk
            CHECK (num_nonnulls(location_id, tour_id, booking_id) = 1)
        ');
        DB::statement('ALTER TABLE ratings ADD CONSTRAINT ratings_score_chk CHECK (score BETWEEN 1 AND 5)');
        DB::statement('ALTER TABLE ratings ADD CONSTRAINT ratings_image_count_chk CHECK (image_count >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
