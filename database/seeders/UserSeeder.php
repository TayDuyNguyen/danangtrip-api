<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\ImportsSeederSql;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use ImportsSeederSql;

    public function run(): void
    {
        $this->importSeederSql('seeders_v2/base/08_admin_users.sql');
        $this->importSeederSql('seeders_v2/demo/01_demo_users.sql');
    }
}
