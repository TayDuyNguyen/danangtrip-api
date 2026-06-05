<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\ImportsSeederSql;
use Illuminate\Database\Seeder;

class CrawlerSeeder extends Seeder
{
    use ImportsSeederSql;

    /**
     * Seed crawler staging tables, real Overpass data, quality review status,
     * and duplicate metadata. Image enrichment is intentionally not imported
     * because external image candidates must be reviewed before use.
     */
    public function run(): void
    {
        $this->importSeederSql('11_crawl_staging_tables.sql');
        $this->importSeederSql('12_overpass_danang_pois_seed.sql');
        $this->importSeederSql('13_overpass_quality_review_seed.sql');
        $this->importSeederSql('15_crawl_duplicate_matching_seed.sql');
    }
}
