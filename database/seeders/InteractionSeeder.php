<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\ImportsSeederSql;
use Illuminate\Database\Seeder;

class InteractionSeeder extends Seeder
{
    use ImportsSeederSql;

    public function run(): void
    {
        $this->importSeederSql('seeders_v2/demo/07_ratings.sql');
    }
}
