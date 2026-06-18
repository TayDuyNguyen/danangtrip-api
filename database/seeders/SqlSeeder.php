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
        $this->importSeederSql('seeders_v2/demo/02_promotions.sql');
        $this->importSeederSql('seeders_v2/base/10_landing_pages.sql');
    }
}
