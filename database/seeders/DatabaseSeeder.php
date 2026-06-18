<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\ImportsSeederSql;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use ImportsSeederSql;
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,          // base/01, base/02
            TagSeeder::class,               // base/03, base/04
            TourCategorySeeder::class,      // base/05, base/06
            SettingSeeder::class,           // PHP code
            UserSeeder::class,              // base/08, demo/01
            LocationSeeder::class,          // demo/03
            TourSeeder::class,              // demo/04
            BlogSeeder::class,              // demo/05
            BookingSeeder::class,           // demo/06
            InteractionSeeder::class,       // demo/07
            SqlSeeder::class,               // demo/02, base/10
            TestCheckoutSeeder::class,      // test/03
            PointSeeder::class,             // base/09, demo/08 (part 1)
            NotificationSeeder::class,      // demo/08 (part 2)
            ChatKnowledgeBaseSeeder::class, // demo/09 (Chứa vector)
            TestCartSeeder::class,          // test/01
        ]);

        // Reset all postgres sequences after hardcoding IDs in seeders
        try {
            $this->importSeederSql('67_reset_all_postgres_sequences.sql');
        } catch (\Throwable $e) {
            $this->command?->warn('Could not reset postgres sequences: '.$e->getMessage());
        }
    }
}
