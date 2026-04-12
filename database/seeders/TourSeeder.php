<?php

namespace Database\Seeders;

use App\Models\Tour;
use App\Models\TourCategory;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TourSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');
        $categories = TourCategory::all();
        $admin = User::where('role', 'admin')->first();

        $toursData = [
            ['name' => 'Khám phá Sun World Ba Na Hills - Đường lên tiên cảnh', 'duration' => '1 ngày'],
            ['name' => 'Tour Ngũ Hành Sơn - Hội An: Di sản văn hóa', 'duration' => '6 tiếng'],
            ['name' => 'Lặn ngắm san hô tại Cù Lao Chàm', 'duration' => '1 ngày'],
            ['name' => 'City Tour Đà Nẵng: Bán đảo Sơn Trà - Chùa Linh Ứng', 'duration' => '4 tiếng'],
            ['name' => 'Thưởng thức ẩm thực Đà Thành về đêm', 'duration' => '4 tiếng'],
            ['name' => 'Tour Suối Khoáng Nóng Núi Thần Tài', 'duration' => '1 ngày'],
            ['name' => 'Ngắm rồng phun lửa trên du thuyền sông Hàn', 'duration' => '2 tiếng'],
            ['name' => 'Phượt đỉnh Đèo Hải Vân - Vịnh Lăng Cô', 'duration' => '1 ngày'],
        ];

        foreach ($toursData as $index => $data) {
            $name = $data['name'];
            $priceAdult = $faker->randomElement([500000, 800000, 1200000, 1500000]);

            Tour::create([
                'name' => $name,
                'slug' => Str::slug($name).'-'.uniqid(),
                'tour_category_id' => $categories->random()->id,
                'description' => "Trải nghiệm {$name} trọn gói với dịch vụ chất lượng cao, xe đưa đón tận nơi và hướng dẫn viên nhiệt tình.",
                'short_desc' => "Khám phá vẻ đẹp của Đà Nẵng qua tour {$name}.",
                'itinerary' => [
                    ['time' => '08:00', 'activity' => 'Xe và HDV đón khách tại khách sạn'],
                    ['time' => '09:00', 'activity' => 'Bắt đầu hành trình tham quan'],
                    ['time' => '12:00', 'activity' => 'Nghỉ ngơi và dùng bữa trưa tại nhà hàng địa phương'],
                    ['time' => '15:30', 'activity' => 'Kết thúc chương trình, xe đưa khách về lại điểm đón'],
                ],
                'inclusions' => ['Xe đưa đón đời mới', 'HDV tiếng Việt', 'Ăn trưa', 'Vé tham quan', 'Nước uống'],
                'exclusions' => ['VAT', 'Chi phí cá nhân', 'Tips'],
                'price_adult' => $priceAdult,
                'price_child' => $priceAdult * 0.7,
                'price_infant' => $priceAdult * 0.2,
                'discount_percent' => rand(0, 15),
                'duration' => $data['duration'],
                'start_time' => '08:30 AM',
                'meeting_point' => 'Điểm hẹn trung tâm hoặc Khách sạn của quý khách',
                'max_people' => rand(20, 50),
                'min_people' => 2,
                'available_from' => now()->startOfMonth(),
                'available_to' => now()->addMonths(6)->endOfMonth(),
                'thumbnail' => "https://picsum.photos/seed/tour{$index}/800/450",
                'images' => [
                    "https://picsum.photos/seed/tour{$index}a/800/600",
                    "https://picsum.photos/seed/tour{$index}b/800/600",
                    "https://picsum.photos/seed/tour{$index}c/800/600",
                ],
                'status' => 'available',
                'is_featured' => $faker->boolean(30),
                'is_hot' => $faker->boolean(20),
                'view_count' => rand(100, 1000),
                'booking_count' => rand(10, 100),
                'created_by' => $admin ? $admin->id : 1,
            ]);
        }
    }
}
