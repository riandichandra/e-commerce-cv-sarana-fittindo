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
        $legacySlugs = [
            'pelapis' => 'laminate',
            'perekat' => 'adhesives',
        ];

        foreach ($legacySlugs as $legacySlug => $currentSlug) {
            if (
                DB::table('product_categories')->where('slug', $legacySlug)->exists()
                && ! DB::table('product_categories')->where('slug', $currentSlug)->exists()
            ) {
                DB::table('product_categories')
                    ->where('slug', $legacySlug)
                    ->update([
                        'slug' => $currentSlug,
                        'updated_at' => now(),
                    ]);
            }
        }

        $categories = [
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
                "name" => "Pelapis",
                "slug" => "laminate",
                "description" => "Pelapis",
                "is_active" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Perekat",
                "slug" => "adhesives",
                "description" => "Perekat",
                "is_active" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ];

        foreach ($categories as $category) {
            DB::table('product_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
