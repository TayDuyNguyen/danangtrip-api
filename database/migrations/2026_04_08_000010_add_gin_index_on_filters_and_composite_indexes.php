<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // GIN index for JSON filters (cast to jsonb for indexing)
        DB::statement('CREATE INDEX IF NOT EXISTS search_logs_filters_gin_idx ON search_logs USING GIN ((filters::jsonb) jsonb_path_ops)');

        // Composite indexes for reporting patterns
        DB::statement('CREATE INDEX IF NOT EXISTS ratings_status_created_at_index ON ratings (status, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS payments_status_created_at_index ON payments (payment_status, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS payments_gateway_created_at_index ON payments (payment_gateway, created_at)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS search_logs_filters_gin_idx');
        DB::statement('DROP INDEX IF EXISTS ratings_status_created_at_index');
        DB::statement('DROP INDEX IF EXISTS payments_status_created_at_index');
        DB::statement('DROP INDEX IF EXISTS payments_gateway_created_at_index');
    }
};
