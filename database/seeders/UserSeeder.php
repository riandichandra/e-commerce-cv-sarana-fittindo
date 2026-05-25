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
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'),
            'phone' => '081234567890',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $pelanggan = User::create([
            'name' => 'Pelanggan User',
            'email' => 'pelanggan@gmail.com',
            'password' => bcrypt('password'),
            'phone' => '081234567890',
            'is_active' => true,
        ]);
        $pelanggan->assignRole('pelanggan');

        $marketing = User::create([
            'name' => 'Marketing User',
            'email' => 'marketing@gmail.com',
            'password' => bcrypt('password'),
            'phone' => '081234567890',
            'is_active' => true,
        ]);
        $marketing->assignRole('marketing');

         $gm = User::create([
            'name' => 'GM User',
            'email' => 'gm@gmail.com',
            'password' => bcrypt('password'),
            'phone' => '081234567890',
            'is_active' => true,
        ]);
        $gm->assignRole('gm');
        
         $direktur = User::create([
            'name' => 'Direktur User',
            'email' => 'direktur@gmail.com',
            'password' => bcrypt('password'),
            'phone' => '081234567890',
            'is_active' => true,
        ]);
        $direktur->assignRole('direktur');


    }
}
