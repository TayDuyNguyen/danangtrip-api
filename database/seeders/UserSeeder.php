<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo Admin
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'full_name' => 'Hệ thống Admin',
                'avatar' => 'https://ui-avatars.com/api/?name=Admin&background=random',
                'role' => 'admin',
                'status' => 'active',
                'point_balance' => 1000,
            ]
        );

        // 2. Tạo 20 Users mẫu
        for ($i = 1; $i <= 20; $i++) {
            User::updateOrCreate(
                ['email' => "user{$i}@example.com"],
                [
                    'username' => "user{$i}",
                    'email' => "user{$i}@example.com",
                    'password' => Hash::make('password'),
                    'full_name' => "Người dùng {$i}",
                    'avatar' => "https://ui-avatars.com/api/?name=User+{$i}&background=random",
                    'role' => $i % 5 === 0 ? 'partner' : 'user',
                    'status' => 'active',
                    'point_balance' => rand(10, 100),
                ]
            );
        }
    }
}
