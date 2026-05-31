<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\ImportsSeederSql;
use Illuminate\Database\Seeder;

class CrawlerSeeder extends Seeder
{
    use ImportsSeederSql;

    /**
     * Seed crawler staging tables and pending-review crawled data.
     */
    public function run(): void
    {
        $this->importSeederSql('11_crawl_staging_tables.sql');
        $this->importSeederSql('12_overpass_danang_pois_seed.sql');
    }
}
