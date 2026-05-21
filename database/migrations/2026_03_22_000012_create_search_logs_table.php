<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 100)->index();
            $table->string('query', 255)->index();
            $table->integer('results_count')->default(0);
            $table->json('filters')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('created_at');
        });

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX search_logs_query_trgm_idx ON search_logs USING GIN (query gin_trgm_ops)');
        DB::statement('CREATE INDEX search_logs_filters_gin_idx ON search_logs USING GIN ((filters::jsonb) jsonb_path_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
