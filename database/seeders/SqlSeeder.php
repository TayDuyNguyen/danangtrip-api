<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\ImportsSeederSql;
use Illuminate\Database\Seeder;

class SqlSeeder extends Seeder
{
    use ImportsSeederSql;

    /**
     * Seed technical/system tables SQL.
     */
    public function run(): void
    {
        $this->importSeederSql('10_system_tables.sql');
    }
}
