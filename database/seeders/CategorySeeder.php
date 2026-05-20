<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductCategory::insert([
            [
                "name" => "HPL",
                "slug" => "hpl",
                "description" => "HPL",
                "is_active" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Plywood",
                "slug" => "plywood",
                "description" => "Plywood",
                "is_active" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Laminate",
                "slug" => "laminate",
                "description" => "Laminate",
                "is_active" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Adhesives",
                "slug" => "adhesives",
                "description" => "Adhesives",
                "is_active" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ]);
    }
}
