<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubcategorySeeder extends Seeder
{
    public function run(): void
    {
        Subcategory::query()->delete();

        $categories = Category::query()->orderBy('sort_order')->get(['id']);
        if ($categories->isEmpty()) {
            return;
        }

        $names = [
            'Hải sản',
            'Quán cà phê',
            'Nhà hàng',
            'Buffet',
            'Street food',
            'Món chay',
            'Bánh ngọt',
            'Trà sữa',
            'Bar',
            'Karaoke',
            'Homestay',
            'Resort',
            'Hostel',
            'Căn hộ',
            'Bãi biển',
            'Núi',
            'Bảo tàng',
            'Công viên',
            'Chợ',
            'Trung tâm thương mại',
        ];

        $usedSlugs = [];
        foreach ($names as $index => $name) {
            $category = $categories[$index % $categories->count()];

            $baseSlug = Str::slug($name);
            $slug = $baseSlug;
            $i = 2;
            while (isset($usedSlugs[$slug])) {
                $slug = $baseSlug.'-'.$i;
                $i++;
            }
            $usedSlugs[$slug] = true;

            Subcategory::create([
                'category_id' => $category->id,
                'name' => $name,
                'slug' => $slug,
                'description' => null,
                'sort_order' => $index + 1,
                'status' => 'active',
            ]);
        }
    }
}
