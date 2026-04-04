<?php

namespace Database\Seeders;

use App\Models\Tour;
use App\Models\TourSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TourScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $tours = Tour::all();

        foreach ($tours as $tour) {
            // Ngày khởi hành đầu tiên cách hôm nay 2-5 ngày
            $currentDate = Carbon::now()->addDays(rand(2, 5));

            // Mỗi tour có 8-12 lịch khởi hành trong tương lai
            $numDays = rand(8, 12);

            for ($i = 0; $i < $numDays; $i++) {
                TourSchedule::create([
                    'tour_id' => $tour->id,
                    'start_date' => $currentDate->format('Y-m-d'),
                    'end_date' => $currentDate->copy()->addDays(rand(0, 2))->format('Y-m-d'),
                    'max_people' => $tour->max_people ?: 40,
                    'booked_people' => 0,
                    'price_adult' => $tour->price_adult,
                    'price_child' => $tour->price_child,
                    'price_infant' => $tour->price_infant,
                    'status' => 'available',
                ]);

                // Cách 3-7 ngày mới có chuyến tiếp theo để tránh trùng
                $currentDate->addDays(rand(3, 7));
            }
        }
    }
}
