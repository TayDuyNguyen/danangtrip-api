<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->decimal('price_adult', 12, 2);
            $table->decimal('price_child', 12, 2)->default(0);
            $table->decimal('price_infant', 12, 2)->default(0);
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
            $table->string('status', 20)->default('active')->index();
            $table->string('booking_availability', 20)->default('open')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_hot')->default(false)->index();
            $table->integer('view_count')->default(0);
            $table->integer('booking_count')->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('price_adult');
            $table->index(['available_from', 'available_to']);
            $table->fullText(['name', 'description', 'itinerary', 'inclusions', 'exclusions'], 'tours_search_fulltext');
        });

        DB::statement('CREATE INDEX IF NOT EXISTS tours_list_idx ON tours (status, booking_availability, tour_category_id, price_adult, created_at DESC)');
        DB::statement('
            ALTER TABLE tours
            ADD CONSTRAINT tours_people_chk
            CHECK (min_people >= 1 AND max_people >= 0 AND min_people <= max_people)
        ');
        DB::statement('
            ALTER TABLE tours
            ADD CONSTRAINT tours_price_chk
            CHECK (price_adult >= 0 AND price_child >= 0 AND price_infant >= 0 AND discount_percent BETWEEN 0 AND 100)
        ');
        DB::statement("
            ALTER TABLE tours
            ADD CONSTRAINT tours_booking_availability_chk
            CHECK (booking_availability IN ('open','sold_out'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
