<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update views table
        Schema::table('views', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->change();
            if (! Schema::hasColumn('views', 'tour_id')) {
                $table->foreignId('tour_id')->nullable()->after('location_id')->constrained('tours')->nullOnDelete();
                $table->index('tour_id');
            }
        });

        // 2. Update favorites table
        // Drop old unique constraint (actual name from DB)
        DB::statement('ALTER TABLE favorites DROP CONSTRAINT IF EXISTS uq_user_location_fav');
        DB::statement('DROP INDEX IF EXISTS uq_user_location_fav');
        DB::statement('DROP INDEX IF EXISTS favorites_user_location_unique');
        DB::statement('DROP INDEX IF EXISTS favorites_user_tour_unique');

        Schema::table('favorites', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->change();
            if (! Schema::hasColumn('favorites', 'tour_id')) {
                $table->foreignId('tour_id')->nullable()->after('location_id')->constrained('tours')->nullOnDelete();
                $table->index('tour_id');
            }

            // New indexes (use standard Laravel names if possible, or explicit)
            $table->unique(['user_id', 'location_id'], 'favorites_user_location_unique');
            $table->unique(['user_id', 'tour_id'], 'favorites_user_tour_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropUnique('favorites_user_tour_unique');
            $table->dropUnique('favorites_user_location_unique');
            $table->dropConstrainedForeignId('tour_id');
            $table->foreignId('location_id')->nullable(false)->change();
            $table->unique(['user_id', 'location_id'], 'uq_user_location_fav');
        });

        Schema::table('views', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tour_id');
            $table->foreignId('location_id')->nullable(false)->change();
        });
    }
};
