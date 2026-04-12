<?php

namespace Database\Seeders;

use App\Models\TourCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TourCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Tour Bà Nà Hills', 'icon' => 'mountain'],
            ['name' => 'Tour Ngũ Hành Sơn - Hội An', 'icon' => 'temple'],
            ['name' => 'Tour Cù Lao Chàm', 'icon' => 'island'],
            ['name' => 'Tour Ẩm thực Đà Nẵng', 'icon' => 'food'],
            ['name' => 'Tour Sinh thái - Cộng đồng', 'icon' => 'leaf'],
            ['name' => 'Tour Du thuyền Sông Hàn', 'icon' => 'ship'],
        ];

        foreach ($categories as $index => $cat) {
            TourCategory::create([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']),
                'description' => "Các tour thuộc nhóm {$cat['name']} hấp dẫn nhất Đà Nẵng.",
                'icon' => $cat['icon'],
                'sort_order' => $index,
                'status' => 'active',
            ]);
        }
    }
}
