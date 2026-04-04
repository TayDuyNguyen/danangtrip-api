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
            UserSeeder::class,
            CategorySeeder::class,
            SubcategorySeeder::class,
            TagSeeder::class,
            LocationSeeder::class,
            AmenitySeeder::class,
            TourCategorySeeder::class, // New
            TourSeeder::class,         // New
            TourScheduleSeeder::class, // New
            BookingSeeder::class,      // New
            FavoriteSeeder::class,
            RatingSeeder::class,       // Updated for Tours
            BlogSeeder::class,
            InteractionSeeder::class,
            ContactSeeder::class,      // New
        ]);
    }
}
