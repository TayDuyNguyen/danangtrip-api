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
        $this->importSeederSql('17_promotions_seed.sql');
        $this->importSeederSql('18_landing_pages_seed.sql');
    }
}
