<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\ImportsSeederSql;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    use ImportsSeederSql;

    public function run(): void
    {
        $this->importSeederSql('seeders_v2/base/07_system_settings.sql');
    }
}
