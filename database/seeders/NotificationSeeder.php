<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\ImportsSeederSql;
use Illuminate\Database\Seeder;

final class NotificationSeeder extends Seeder
{
    use ImportsSeederSql;

    public function run(): void
    {
        $this->importSeederSql('seeders_v2/demo/08_notifications_activity.sql');
    }
}
