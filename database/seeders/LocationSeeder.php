<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');
        $districts = ['Hải Châu', 'Sơn Trà', 'Ngũ Hành Sơn', 'Cẩm Lệ', 'Thanh Khê', 'Liên Chiểu'];

        $categories = Category::all();
        $subcategories = Subcategory::all();
        $tags = Tag::all();
        $admin = User::where('role', 'admin')->first();

        if ($categories->isEmpty()) {
            $this->command->error('Vui lòng chạy CategorySeeder trước!');

            return;
        }

        foreach (range(1, 150) as $index) {
            $district = $faker->randomElement($districts);
            $category = $categories->random();
            $subcategory = $subcategories->where('category_id', $category->id)->random() ?? null;
            $name = $this->generateName($category->name, $faker);

            $location = Location::create([
                'name' => $name,
                'slug' => Str::slug($name).'-'.uniqid(),
                'category_id' => $category->id,
                'subcategory_id' => $subcategory ? $subcategory->id : null,
                'description' => $faker->paragraph(3),
                'short_description' => $faker->sentence(10),
                'address' => $faker->streetAddress,
                'district' => $district,
                'ward' => 'Phường '.$faker->numberBetween(1, 10),
                'latitude' => $faker->latitude(15.9, 16.1),
                'longitude' => $faker->longitude(108.1, 108.3),
                'phone' => $faker->phoneNumber,
                'email' => $faker->companyEmail,
                'website' => $faker->url,
                'opening_hours' => [
                    'mon' => '08:00-22:00',
                    'tue' => '08:00-22:00',
                    'wed' => '08:00-22:00',
                    'thu' => '08:00-22:00',
                    'fri' => '08:00-23:00',
                    'sat' => '08:00-23:00',
                    'sun' => '08:00-22:00',
                ],
                'price_min' => $faker->randomElement([20000, 50000, 100000, 200000]),
                'price_max' => $faker->randomElement([150000, 300000, 1000000, 5000000]),
                'price_level' => $faker->numberBetween(1, 4),
                'avg_rating' => $faker->randomFloat(2, 3, 5),
                'review_count' => $faker->numberBetween(10, 500),
                'view_count' => $faker->numberBetween(100, 5000),
                'favorite_count' => $faker->numberBetween(5, 200),
                'thumbnail' => 'https://picsum.photos/seed/location'.$index.'/400/300',
                'images' => [
                    'https://picsum.photos/seed/loc'.$index.'a/800/600',
                    'https://picsum.photos/seed/loc'.$index.'b/800/600',
                    'https://picsum.photos/seed/loc'.$index.'c/800/600',
                ],
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'status' => 'active',
                'is_featured' => $faker->boolean(20),
                'created_by' => $admin ? $admin->id : 1,
            ]);

            // Gán 1-3 tag ngẫu nhiên
            $randomTags = $tags->random(rand(1, 3))->pluck('id');
            $location->tags()->attach($randomTags, ['created_at' => now()]);
        }
    }

    private function generateName($categoryName, $faker)
    {
        $prefixes = [
            'Ăn uống' => ['Nhà hàng', 'Quán ăn', 'Tiệm ăn', 'Buffet'],
            'Khách sạn' => ['Khách sạn', 'Hotel', 'Resort', 'Homestay'],
            'Cà phê' => ['Cửa hàng Cà phê', 'Tiệm Cà phê', 'Coffee', 'The'],
            'Du lịch' => ['Khu du lịch', 'Điểm tham quan', 'Bảo tàng', 'Công viên'],
        ];

        $prefix = isset($prefixes[$categoryName]) ? $faker->randomElement($prefixes[$categoryName]) : 'Địa điểm';

        return $prefix.' '.$faker->company;
    }
}
