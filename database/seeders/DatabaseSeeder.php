<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class, // Phải chạy đầu tiên để các bảng khác có FK user_id
            CategorySeeder::class,
            SubcategorySeeder::class,
            TagSeeder::class,
            LocationSeeder::class,
            AmenitySeeder::class,
            FavoriteSeeder::class,
            RatingSeeder::class,
            BlogSeeder::class,
            InteractionSeeder::class,
            TransactionalSeeder::class,
        ]);
    }
}
