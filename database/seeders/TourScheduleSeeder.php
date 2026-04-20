<?php

namespace Database\Seeders;

use App\Enums\TourScheduleStatus;
use App\Models\Tour;
use App\Models\TourSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TourScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $tours = Tour::all(); // Lấy toàn bộ tour đã seed trước đó để tạo lịch khởi hành tương ứng.

        foreach ($tours as $tour) {
            // Bắt đầu từ vài ngày sau hiện tại để frontend luôn có lịch tương lai để hiển thị.
            $currentDate = Carbon::now()->addDays(rand(2, 5));

            // Mỗi tour có nhiều lịch để mô phỏng dữ liệu thực tế và tiện test booking/filter.
            $numDays = rand(8, 12);

            for ($i = 0; $i < $numDays; $i++) {
                TourSchedule::updateOrCreate(
                    [
                        'tour_id' => $tour->id, // Lịch này thuộc tour nào.
                        'start_date' => $currentDate->format('Y-m-d'), // Cặp (tour_id, start_date) là khóa unique ở DB.
                    ],
                    [
                        'end_date' => $currentDate->copy()->addDays(rand(0, 2))->format('Y-m-d'), // Tour kéo dài 1-3 ngày.
                        'max_people' => $tour->max_people ?: 40, // Nếu tour gốc chưa set sức chứa thì dùng mốc mặc định.
                        'booked_people' => 0, // Seed ban đầu chưa có khách đặt.
                        'price_adult' => $tour->price_adult, // Giá lịch mặc định kế thừa từ bảng tours.
                        'price_child' => $tour->price_child,
                        'price_infant' => $tour->price_infant,
                        'status' => TourScheduleStatus::AVAILABLE->value, // Trạng thái mở bán mặc định.
                    ]
                );

                // Giãn lịch 3-7 ngày để không tạo các chuyến trùng ngày liên tiếp.
                $currentDate->addDays(rand(3, 7));
            }
        }
    }
}
