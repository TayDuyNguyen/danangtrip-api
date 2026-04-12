<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->createUniqueSlugIndexIfColumnExists('categories', 'categories_slug_unique');
        $this->createUniqueSlugIndexIfColumnExists('subcategories', 'subcategories_slug_unique');
        $this->createUniqueSlugIndexIfColumnExists('tags', 'tags_slug_unique');
        $this->createUniqueSlugIndexIfColumnExists('amenities', 'amenities_slug_unique');
        $this->createUniqueSlugIndexIfColumnExists('tours', 'tours_slug_unique');
        $this->createUniqueSlugIndexIfColumnExists('blog_categories', 'blog_categories_slug_unique');
        $this->createUniqueSlugIndexIfColumnExists('blog_posts', 'blog_posts_slug_unique');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS categories_slug_unique');
        DB::statement('DROP INDEX IF EXISTS subcategories_slug_unique');
        DB::statement('DROP INDEX IF EXISTS tags_slug_unique');
        DB::statement('DROP INDEX IF EXISTS amenities_slug_unique');
        DB::statement('DROP INDEX IF EXISTS tours_slug_unique');
        DB::statement('DROP INDEX IF EXISTS blog_categories_slug_unique');
        DB::statement('DROP INDEX IF EXISTS blog_posts_slug_unique');
    }

    private function createUniqueSlugIndexIfColumnExists(string $table, string $index): void
    {
        $sql = <<<SQL
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_name = '{$table}' AND column_name = 'slug'
    ) THEN
        CREATE UNIQUE INDEX IF NOT EXISTS {$index} ON {$table} (slug);
    END IF;
END $$;
SQL;
        DB::statement($sql);
    }
};
