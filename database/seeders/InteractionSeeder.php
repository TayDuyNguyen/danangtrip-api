<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\SearchLog;
use App\Models\User;
use App\Models\View;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InteractionSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');
        $users = User::all();
        $locations = Location::all();

        // 1. Tạo 500 records cho Views
        foreach (range(1, 500) as $i) {
            View::create([
                'user_id' => $faker->boolean(70) ? $users->random()->id : null,
                'location_id' => $locations->random()->id,
                'session_id' => Str::random(40),
                'time_spent' => rand(10, 300),
                'created_at' => $faker->dateTimeBetween('-1 month', 'now'),
            ]);
        }

        // 2. Tạo 200 records cho Search Logs
        $searchQueries = ['Hải sản Hải Châu', 'Khách sạn view biển', 'Cà phê Yên', 'Mì Quảng ngon', 'Bà Nà Hills', 'Chợ Cồn', 'Cầu Rồng'];
        foreach (range(1, 200) as $i) {
            SearchLog::create([
                'user_id' => $faker->boolean(50) ? $users->random()->id : null,
                'session_id' => Str::random(40),
                'query' => $faker->randomElement($searchQueries),
                'results_count' => rand(0, 50),
                'filters' => ['district' => $faker->randomElement(['Hải Châu', 'Sơn Trà', 'Thanh Khê'])],
                'created_at' => $faker->dateTimeBetween('-1 month', 'now'),
            ]);
        }
    }
}
