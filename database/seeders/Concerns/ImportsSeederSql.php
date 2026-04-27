<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

trait ImportsSeederSql
{
    protected function importSeederSql(string $fileName): void
    {
        $sqlPath = base_path('../DATN_Tài liệu/seeder'.DIRECTORY_SEPARATOR.$fileName);

        if (! File::exists($sqlPath)) {
            throw new RuntimeException("Missing seeder SQL file: {$sqlPath}");
        }

        $sql = trim((string) File::get($sqlPath));
        if ($sql === '') {
            return;
        }

        DB::unprepared($sql);
        $this->command?->info("Imported {$fileName}");
    }
}
