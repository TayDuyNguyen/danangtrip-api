<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Rating;
use App\Models\RatingImage;
use App\Models\Tour;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class RatingSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');
        $users = User::where('role', 'user')->get();
        $locations = Location::all();
        $tours = Tour::all();
        $admins = User::where('role', 'admin')->get();

        // 1. Rating cho Locations
        foreach ($locations as $location) {
            $this->seedRatings($location, 'location_id', $users, $admins, $faker);
        }

        // 2. Rating cho Tours
        foreach ($tours as $tour) {
            $this->seedRatings($tour, 'tour_id', $users, $admins, $faker);
        }
    }

    private function seedRatings($model, $foreignKey, $users, $admins, $faker)
    {
        $numRatings = rand(2, 5);
        $selectedUsers = $users->random(min($numRatings, $users->count()));

        foreach ($selectedUsers as $user) {
            $status = $faker->randomElement(['approved', 'approved', 'approved', 'pending', 'rejected']);
            $imageCount = $faker->numberBetween(0, 2);

            $rating = Rating::create([
                'user_id' => $user->id,
                $foreignKey => $model->id,
                'score' => $faker->numberBetween(3, 5),
                'comment' => $faker->paragraph,
                'image_count' => $imageCount,
                'status' => $status,
                'rejected_reason' => $status === 'rejected' ? 'Nội dung không phù hợp' : null,
                'approved_by' => $status !== 'pending' ? $admins->random()->id : null,
                'approved_at' => $status !== 'pending' ? now() : null,
                'helpful_count' => $faker->numberBetween(0, 50),
                'created_at' => $faker->dateTimeBetween('-3 months', 'now'),
            ]);

            if ($imageCount > 0) {
                for ($i = 0; $i < $imageCount; $i++) {
                    RatingImage::create([
                        'rating_id' => $rating->id,
                        'image_url' => "https://picsum.photos/seed/rating{$rating->id}_{$i}/800/600",
                        'sort_order' => $i,
                        'created_at' => $rating->created_at,
                    ]);
                }
            }
        }
    }
}
