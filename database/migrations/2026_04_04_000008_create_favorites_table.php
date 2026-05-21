<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('tour_id')->nullable()->constrained('tours')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('location_id');
            $table->index('tour_id');
            $table->index('created_at');
        });

        DB::statement('CREATE UNIQUE INDEX favorites_user_location_unique ON favorites (user_id, location_id) WHERE location_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX favorites_user_tour_unique ON favorites (user_id, tour_id) WHERE tour_id IS NOT NULL');
        DB::statement('ALTER TABLE favorites ADD CONSTRAINT favorites_exactly_one_target_chk CHECK (num_nonnulls(location_id, tour_id) = 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
