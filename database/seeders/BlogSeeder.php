<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');
        $admins = User::where('role', 'admin')->get();

        $categories = [
            'Cẩm nang du lịch',
            'Ẩm thực Đà Nẵng',
            'Sự kiện & Lễ hội',
            'Kinh nghiệm đặt phòng',
            'Lịch trình gợi ý',
        ];

        $categoryModels = [];
        foreach ($categories as $name) {
            $categoryModels[] = BlogCategory::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "Các bài viết thuộc danh mục {$name}.",
            ]);
        }

        $titles = [
            'Top 10 quán hải sản ngon rẻ nhất Đà Nẵng 2026',
            'Kinh nghiệm du lịch Bà Nà Hills trọn gói trong 1 ngày',
            'Lịch trình khám phá Sơn Trà bằng xe máy cho dân phượt',
            'Những bãi biển đẹp nhất Đà Nẵng bạn không nên bỏ lỡ',
            'Ăn gì ở chợ Cồn? Thiên đường ẩm thực giá rẻ Đà Thành',
            'Review các Homestay view biển cực chill tại Ngũ Hành Sơn',
            'Hướng dẫn xem Cầu Rồng phun lửa và phun nước mới nhất',
            'Top 5 quán cà phê làm việc yên tĩnh tại quận Hải Châu',
            'Hành trình 3 ngày 2 đêm tại Đà Nẵng cho cặp đôi lãng mạn',
            'Bí kíp mua sắm đặc sản Đà Nẵng làm quà chuẩn không cần chỉnh',
        ];

        foreach ($titles as $index => $title) {
            $post = BlogPost::create([
                'title' => $title,
                'slug' => Str::slug($title).'-'.uniqid(),
                'excerpt' => $faker->sentence(15),
                'content' => $faker->paragraphs(10, true),
                'featured_image' => 'https://picsum.photos/seed/blog'.$index.'/1200/800',
                'author_id' => $admins->random()->id,
                'view_count' => $faker->numberBetween(500, 10000),
                'status' => 'published',
                'published_at' => now()->subDays(rand(1, 30)),
            ]);

            // Gán 1-2 danh mục ngẫu nhiên
            $post->categories()->attach(
                collect($categoryModels)->random(rand(1, 2))->pluck('id')
            );
        }
    }
}
