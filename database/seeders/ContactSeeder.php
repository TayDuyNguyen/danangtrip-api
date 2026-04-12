<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');
        $admins = User::where('role', 'admin')->get();

        foreach (range(1, 20) as $index) {
            $isReplied = $faker->boolean(60);

            Contact::create([
                'name' => $faker->name,
                'email' => $faker->email,
                'phone' => $faker->phoneNumber,
                'subject' => $faker->sentence(6),
                'message' => $faker->paragraph(3),
                'status' => $isReplied ? 'replied' : $faker->randomElement(['new', 'processing']),
                'replied_by' => $isReplied ? $admins->random()->id : null,
                'replied_at' => $isReplied ? now()->subDays(rand(1, 5)) : null,
                'reply' => $isReplied ? $faker->paragraph(2) : null,
                'created_at' => $faker->dateTimeBetween('-1 month', 'now'),
            ]);
        }
    }
}
