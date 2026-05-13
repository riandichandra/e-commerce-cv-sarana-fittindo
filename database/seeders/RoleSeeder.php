<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $roles = [
        ['name' => 'pelanggan', 'guard_name' => 'pelanggan'],
        ['name' => 'admin', 'guard_name' => 'admin'],
        ['name' => 'marketing', 'guard_name' => 'marketing'],
        ['name' => 'gm', 'guard_name' => 'gm'],
        ['name' => 'direktur', 'guard_name' => 'direktur'],
    ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}