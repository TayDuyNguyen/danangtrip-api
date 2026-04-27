<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('tour_id')->nullable()->constrained('tours')->nullOnDelete();
            $table->string('session_id', 100)->index();
            $table->integer('time_spent')->default(0); // in seconds
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('location_id');
            $table->index('tour_id');
            $table->index('created_at');
        });

        DB::statement('ALTER TABLE views ADD CONSTRAINT views_exactly_one_target_chk CHECK (num_nonnulls(location_id, tour_id) = 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('views');
    }
};
