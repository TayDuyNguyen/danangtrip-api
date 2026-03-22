<?php

namespace Database\Seeders;

use App\Models\Favorite;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'user')->get();
        $locations = Location::all();

        foreach ($users as $user) {
            // Mỗi user có 5-15 địa điểm yêu thích
            $favLocations = $locations->random(rand(5, 15));

            foreach ($favLocations as $location) {
                Favorite::create([
                    'user_id' => $user->id,
                    'location_id' => $location->id,
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }
}
