<?php

namespace Tests\Feature\Pelanggan;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_product_index_filters_by_price_range(): void
    {
        $this->makeProduct('Lem Murah', 'Perekat', 95000);
        $this->makeProduct('HPL Tengah', 'HPL', 250000);
        $this->makeProduct('Panel Mahal', 'Plywood', 650000);

        $response = $this->get(route('pelanggan.products.index', [
            'price_range' => 'under_100k',
        ]));

        $response->assertOk();
        $response->assertSee('Lem Murah');
        $response->assertDontSee('HPL Tengah');
        $response->assertDontSee('Panel Mahal');
    }

    public function test_customer_product_index_keeps_category_when_filtering_price(): void
    {
        $this->makeProduct('Perekat Murah', 'Perekat', 75000);
        $this->makeProduct('HPL Murah', 'HPL', 80000);
        $this->makeProduct('Perekat Sedang', 'Perekat', 175000);

        $category = ProductCategory::where('name', 'Perekat')->first();

        $response = $this->get(route('pelanggan.products.index', [
            'category' => $category->slug,
            'price_range' => 'under_100k',
        ]));

        $response->assertOk();
        $response->assertSee('Perekat Murah');
        $response->assertDontSee('HPL Murah');
        $response->assertDontSee('Perekat Sedang');
        $response->assertSee('price_range=under_100k', false);
        $response->assertSee('category=' . $category->slug, false);
    }

    private function makeProduct(string $name, string $categoryName, int $price): Product
    {
        $category = ProductCategory::firstOrCreate([
            'name' => $categoryName,
        ], [
            'is_active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'description' => 'Produk pengujian filter.',
            'price' => $price,
            'stock' => 10,
            'weight' => 1000,
            'is_active' => true,
        ]);
    }
}
