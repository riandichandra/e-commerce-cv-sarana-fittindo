<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::insert([
            "role_id" => 2,
            "name" => "Admin",
            "email" => "admin@gmail.com",
            "password" => bcrypt("password"),
            "phone" => "081234567890",
            "is_active" => 1,
            "created_at" => now(),
            "updated_at" => now(),
        ]);
    }
}
