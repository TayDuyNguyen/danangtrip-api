<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            SubcategorySeeder::class,
        ]);

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'full_name' => 'Admin',
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        User::query()
            ->where('email', '!=', 'admin@example.com')
            ->delete();

        for ($i = 1; $i <= 19; $i++) {
            User::updateOrCreate(
                ['email' => 'user'.$i.'@example.com'],
                [
                    'username' => 'user'.$i,
                    'email' => 'user'.$i.'@example.com',
                    'password' => Hash::make('password'),
                    'full_name' => 'User '.$i,
                    'role' => $i % 10 === 0 ? 'partner' : 'user',
                    'status' => 'active',
                ]
            );
        }
    }
}
