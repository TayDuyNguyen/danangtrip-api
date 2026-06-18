<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\ImportsSeederSql;
use Illuminate\Database\Seeder;

final class PointSeeder extends Seeder
{
    use ImportsSeederSql;

    public function run(): void
    {
        $this->importSeederSql('seeders_v2/base/09_point_rules.sql');
        $this->importSeederSql('seeders_v2/demo/08_notifications_activity.sql');
    }
}
