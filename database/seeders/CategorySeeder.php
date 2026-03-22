<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::query()->delete();

        $names = [
            ['name' => 'Ăn uống', 'icon' => 'fa-utensils'],
            ['name' => 'Khách sạn', 'icon' => 'fa-hotel'],
            ['name' => 'Du lịch', 'icon' => 'fa-map-location-dot'],
            ['name' => 'Cà phê', 'icon' => 'fa-mug-hot'],
            ['name' => 'Giải trí', 'icon' => 'fa-icons'],
            ['name' => 'Mua sắm', 'icon' => 'fa-bag-shopping'],
            ['name' => 'Spa & Massage', 'icon' => 'fa-spa'],
            ['name' => 'Bar & Pub', 'icon' => 'fa-martini-glass-citrus'],
            ['name' => 'Bánh ngọt', 'icon' => 'fa-cake-candles'],
            ['name' => 'Trà sữa', 'icon' => 'fa-mug-saucer'],
            ['name' => 'Chợ đêm', 'icon' => 'fa-moon'],
            ['name' => 'Đặc sản', 'icon' => 'fa-pepper-hot'],
            ['name' => 'Nhà hàng chay', 'icon' => 'fa-seedling'],
            ['name' => 'BBQ', 'icon' => 'fa-fire'],
            ['name' => 'Món Hàn', 'icon' => 'fa-bowl-rice'],
            ['name' => 'Món Nhật', 'icon' => 'fa-fish-fins'],
            ['name' => 'View biển', 'icon' => 'fa-umbrella-beach'],
            ['name' => 'Cắm trại', 'icon' => 'fa-campground'],
            ['name' => 'Thể thao', 'icon' => 'fa-person-running'],
            ['name' => 'Sự kiện', 'icon' => 'fa-calendar-days'],
        ];

        $usedSlugs = [];
        foreach ($names as $index => $item) {
            $baseSlug = Str::slug($item['name']);
            $slug = $baseSlug;
            $i = 2;
            while (isset($usedSlugs[$slug])) {
                $slug = $baseSlug.'-'.$i;
                $i++;
            }
            $usedSlugs[$slug] = true;

            Category::create([
                'name' => $item['name'],
                'slug' => $slug,
                'icon' => $item['icon'],
                'description' => 'Danh mục: '.$item['name'].'.',
                'image' => null,
                'sort_order' => $index + 1,
                'status' => 'active',
            ]);
        }
    }
}
