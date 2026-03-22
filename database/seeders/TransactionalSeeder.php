<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\PointTransaction;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TransactionalSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');
        $users = User::where('role', 'user')->get();

        foreach ($users as $user) {
            // 1. Tạo lịch sử giao dịch point cho mỗi user
            $numTransactions = rand(2, 5);
            $balance = $user->point_balance;

            for ($i = 0; $i < $numTransactions; $i++) {
                $type = $faker->randomElement(['purchase', 'bonus', 'spend']);
                $amount = $type === 'spend' ? -rand(2, 5) : rand(10, 50);

                $balanceBefore = $balance;
                $balance += $amount;
                $balanceAfter = $balance;

                PointTransaction::create([
                    'user_id' => $user->id,
                    'transaction_code' => strtoupper($type).'-'.Str::random(10),
                    'type' => $type,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'description' => "Giao dịch {$type} mô phỏng",
                    'status' => 'completed',
                    'created_at' => $faker->dateTimeBetween('-1 month', 'now'),
                ]);
            }

            // 2. Tạo thông báo cho mỗi user
            $numNotifications = rand(3, 8);
            for ($i = 0; $i < $numNotifications; $i++) {
                $isRead = $faker->boolean(40);
                Notification::create([
                    'user_id' => $user->id,
                    'type' => $faker->randomElement(['rating_approved', 'point_credited', 'system_alert']),
                    'title' => 'Thông báo hệ thống mẫu '.($i + 1),
                    'content' => $faker->sentence(10),
                    'is_read' => $isRead,
                    'read_at' => $isRead ? now() : null,
                    'created_at' => $faker->dateTimeBetween('-1 month', 'now'),
                ]);
            }
        }
    }
}
