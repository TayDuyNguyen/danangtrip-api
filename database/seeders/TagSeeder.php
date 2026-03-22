<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['name' => 'View biển', 'type' => 'feature'],
            ['name' => 'Giá rẻ', 'type' => 'feature'],
            ['name' => 'WiFi miễn phí', 'type' => 'service'],
            ['name' => 'Chỗ đậu xe', 'type' => 'service'],
            ['name' => 'Không gian xanh', 'type' => 'atmosphere'],
            ['name' => 'Phù hợp gia đình', 'type' => 'atmosphere'],
            ['name' => 'Sang trọng', 'type' => 'atmosphere'],
            ['name' => 'Mở cửa đêm', 'type' => 'feature'],
            ['name' => 'Gần trung tâm', 'type' => 'feature'],
            ['name' => 'Phong cách hiện đại', 'type' => 'atmosphere'],
            ['name' => 'Lãng mạn', 'type' => 'atmosphere'],
            ['name' => 'Có nhạc sống', 'type' => 'feature'],
            ['name' => 'Thích hợp làm việc', 'type' => 'feature'],
            ['name' => 'Cho phép mang thú cưng', 'type' => 'service'],
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(
                ['slug' => Str::slug($tag['name'])],
                [
                    'name' => $tag['name'],
                    'type' => $tag['type'],
                ]
            );
        }
    }
}
