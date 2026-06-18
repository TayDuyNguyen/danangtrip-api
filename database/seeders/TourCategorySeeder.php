<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\ImportsSeederSql;
use Illuminate\Database\Seeder;

class TourCategorySeeder extends Seeder
{
    use ImportsSeederSql;

    public function run(): void
    {
        $this->importSeederSql('seeders_v2/base/05_tour_categories.sql');
        $this->importSeederSql('seeders_v2/base/06_blog_categories.sql');
    }
}
