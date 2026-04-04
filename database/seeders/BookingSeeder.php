<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\TourSchedule;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');
        $users = User::where('role', 'user')->get();
        $schedules = TourSchedule::with('tour')->get();

        // Tạo 30 đơn đặt tour mẫu
        foreach (range(1, 30) as $index) {
            $user = $users->random();
            $schedule = $schedules->random();
            $tour = $schedule->tour;

            $qAdult = rand(1, 4);
            $qChild = rand(0, 2);
            $qInfant = rand(0, 1);

            $total = ($qAdult * $schedule->price_adult) + ($qChild * $schedule->price_child) + ($qInfant * $schedule->price_infant);
            $bookingCode = 'BK'.strtoupper(Str::random(8));

            $booking = Booking::create([
                'booking_code' => $bookingCode,
                'user_id' => $user->id,
                'customer_name' => $user->full_name,
                'customer_email' => $user->email,
                'customer_phone' => $faker->phoneNumber,
                'customer_address' => $faker->address,
                'customer_note' => $faker->sentence(10),
                'total_amount' => $total,
                'discount_amount' => 0,
                'final_amount' => $total,
                'deposit_amount' => $total * 0.3,
                'payment_method' => $faker->randomElement(['momo', 'vnpay', 'bank_transfer', 'cash']),
                'payment_status' => $faker->randomElement(['unpaid', 'paid', 'partially_paid']),
                'booking_status' => $faker->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
                'booked_at' => $faker->dateTimeBetween('-1 month', 'now'),
            ]);

            BookingItem::create([
                'booking_id' => $booking->id,
                'tour_id' => $tour->id,
                'tour_schedule_id' => $schedule->id,
                'item_type' => 'tour',
                'item_name' => $tour->name,
                'travel_date' => $schedule->start_date,
                'quantity_adult' => $qAdult,
                'quantity_child' => $qChild,
                'quantity_infant' => $qInfant,
                'unit_price_adult' => $schedule->price_adult,
                'unit_price_child' => $schedule->price_child,
                'unit_price_infant' => $schedule->price_infant,
                'subtotal' => $total,
                'status' => 'pending',
            ]);

            // Cập nhật số người đã đặt cho tour schedule
            $schedule->increment('booked_people', $qAdult + $qChild);
        }
    }
}
