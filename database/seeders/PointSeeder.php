<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\ImportsSeederSql;
use Illuminate\Database\Seeder;

final class PointSeeder extends Seeder
{
    use ImportsSeederSql;

    public function run(): void
    {
        $this->importSeederSql('63_point_rules_seed.sql');
        $this->importSeederSql('64_point_rewards_seed.sql');
        $this->importSeederSql('65_user_points_demo_seed.sql');
    }
}
