<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['tour_id', 'location_id'], 'tour_locations_tour_location_unique');
            $table->index('tour_id');
            $table->index('location_id');
        });

        // Optional backfill from legacy tours.location_ids (if column still exists).
        DB::statement("
            DO $$
            BEGIN
                IF EXISTS (
                    SELECT 1
                    FROM information_schema.columns
                    WHERE table_name = 'tours' AND column_name = 'location_ids'
                ) THEN
                    INSERT INTO tour_locations (tour_id, location_id, created_at)
                    SELECT
                        t.id,
                        (jsonb_array_elements_text(t.location_ids::jsonb))::bigint as location_id,
                        NOW()
                    FROM tours t
                    WHERE t.location_ids IS NOT NULL
                    ON CONFLICT (tour_id, location_id) DO NOTHING;
                END IF;
            END $$;
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_locations');
    }
};
