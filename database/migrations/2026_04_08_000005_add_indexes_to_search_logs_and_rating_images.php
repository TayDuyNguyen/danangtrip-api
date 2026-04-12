<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // search_logs: indexes for analytics and quick lookup
        DB::statement('CREATE INDEX IF NOT EXISTS search_logs_user_id_index ON search_logs (user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS search_logs_session_id_index ON search_logs (session_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS search_logs_query_index ON search_logs (query)');
        DB::statement('CREATE INDEX IF NOT EXISTS search_logs_created_at_index ON search_logs (created_at)');

        // rating_images: indexes for relation and ordering
        DB::statement('CREATE INDEX IF NOT EXISTS rating_images_rating_id_index ON rating_images (rating_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS rating_images_created_at_index ON rating_images (created_at)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS search_logs_user_id_index');
        DB::statement('DROP INDEX IF EXISTS search_logs_session_id_index');
        DB::statement('DROP INDEX IF EXISTS search_logs_query_index');
        DB::statement('DROP INDEX IF EXISTS search_logs_created_at_index');

        DB::statement('DROP INDEX IF EXISTS rating_images_rating_id_index');
        DB::statement('DROP INDEX IF EXISTS rating_images_created_at_index');
    }
};
