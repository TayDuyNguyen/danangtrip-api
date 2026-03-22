<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Location;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        Amenity::query()->delete();
        $amenities = [
            ['name' => 'WiFi miễn phí', 'icon' => 'fa-wifi', 'category' => 'connectivity'],
            ['name' => 'Bãi đỗ xe ô tô', 'icon' => 'fa-car', 'category' => 'parking'],
            ['name' => 'Bãi đỗ xe máy', 'icon' => 'fa-motorcycle', 'category' => 'parking'],
            ['name' => 'Điều hòa', 'icon' => 'fa-wind', 'category' => 'comfort'],
            ['name' => 'Khu vực hút thuốc', 'icon' => 'fa-smoking', 'category' => 'comfort'],
            ['name' => 'Thanh toán thẻ', 'icon' => 'fa-credit-card', 'category' => 'payment'],
            ['name' => 'Ví điện tử (Momo/ZaloPay)', 'icon' => 'fa-wallet', 'category' => 'payment'],
            ['name' => 'Có thang máy', 'icon' => 'fa-elevator', 'category' => 'comfort'],
            ['name' => 'Cho phép mang thú cưng', 'icon' => 'fa-paw', 'category' => 'comfort'],
        ];

        foreach ($amenities as $item) {
            $amenity = Amenity::create($item);

            // Gán ngẫu nhiên cho một số địa điểm
            $locations = Location::inRandomOrder()->take(rand(10, 30))->get();
            $amenity->locations()->attach($locations->pluck('id'));
        }
    }
}
