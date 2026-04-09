<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX IF NOT EXISTS search_logs_query_trgm_idx ON search_logs USING GIN (query gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS search_logs_query_trgm_idx');
        // Do not drop extension automatically to avoid affecting other objects
    }
};
