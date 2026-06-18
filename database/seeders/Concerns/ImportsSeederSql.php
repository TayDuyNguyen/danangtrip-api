<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

trait ImportsSeederSql
{
    protected function importSeederSql(string $fileName): void
    {
        $sqlPath = base_path('../DATN_Document/database-seeders'.DIRECTORY_SEPARATOR.$fileName);

        $fallbackSqlPath = base_path('../DATN_Tài liệu/database-seeders'.DIRECTORY_SEPARATOR.$fileName);

        if (! File::exists($sqlPath)) {
            $sqlPath = $fallbackSqlPath;
        }

        if (! File::exists($sqlPath)) {
            throw new RuntimeException("Missing seeder SQL file: {$fileName}");
        }

        $sql = trim((string) File::get($sqlPath));
        if ($sql === '') {
            return;
        }

        // Strip trigger queries to avoid Insufficient privilege errors on Cloud DB (Supabase)
        $sql = str_replace([
            'ALTER TABLE tours DISABLE TRIGGER ALL;',
            'ALTER TABLE tours ENABLE TRIGGER ALL;',
            'ALTER TABLE locations DISABLE TRIGGER ALL;',
            'ALTER TABLE locations ENABLE TRIGGER ALL;',
            'ALTER TABLE blog_posts DISABLE TRIGGER ALL;',
            'ALTER TABLE blog_posts ENABLE TRIGGER ALL;',
            'ALTER TABLE "tours" DISABLE TRIGGER ALL;',
            'ALTER TABLE "tours" ENABLE TRIGGER ALL;',
            'ALTER TABLE "locations" DISABLE TRIGGER ALL;',
            'ALTER TABLE "locations" ENABLE TRIGGER ALL;',
            'ALTER TABLE "blog_posts" DISABLE TRIGGER ALL;',
            'ALTER TABLE "blog_posts" ENABLE TRIGGER ALL;',
        ], '', $sql);

        DB::unprepared($sql);
        $this->command?->info("Imported {$fileName}");
    }
}
