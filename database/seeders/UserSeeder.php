<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@gmail.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Pelanggan User',
                'email' => 'pelanggan@gmail.com',
                'role' => 'pelanggan',
            ],
            [
                'name' => 'Marketing User',
                'email' => 'marketing@gmail.com',
                'role' => 'marketing',
            ],
            [
                'name' => 'GM User',
                'email' => 'gm@gmail.com',
                'role' => 'gm',
            ],
            [
                'name' => 'Direktur User',
                'email' => 'direktur@gmail.com',
                'role' => 'direktur',
            ],
        ];

        foreach ($users as $seedUser) {
            $user = User::updateOrCreate(
                ['email' => $seedUser['email']],
                [
                    'name' => $seedUser['name'],
                    'password' => bcrypt('password'),
                    'phone' => '081234567890',
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$seedUser['role']]);
        }
    }
}
