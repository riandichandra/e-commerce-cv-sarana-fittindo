<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                "name" => "HPL",
                "description" => "HPL",
                "is_active" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Plywood",
                "description" => "Plywood",
                "is_active" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Pelapis",
                "description" => "Pelapis",
                "is_active" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Perekat",
                "description" => "Perekat",
                "is_active" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ];

        foreach ($categories as $category) {
            DB::table('product_categories')->updateOrInsert(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
