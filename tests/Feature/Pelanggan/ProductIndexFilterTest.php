<?php

namespace Tests\Feature\Pelanggan;

use App\Models\Product;
use App\Models\ProductBrand;
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
            'category' => $category->id,
            'price_range' => 'under_100k',
        ]));

        $response->assertOk();
        $response->assertSee('Perekat Murah');
        $response->assertDontSee('HPL Murah');
        $response->assertDontSee('Perekat Sedang');
        $response->assertSee('price_range=under_100k', false);
        $response->assertSee('category=' . $category->id, false);
    }

    public function test_customer_product_index_hides_products_when_category_or_brand_is_inactive(): void
    {
        $this->makeProduct('Produk Terlihat', 'Kategori Aktif', 250000);

        $inactiveCategory = ProductCategory::create([
            'name' => 'Kategori Nonaktif',
            'is_active' => false,
        ]);

        Product::create([
            'category_id' => $inactiveCategory->id,
            'name' => 'Produk Kategori Nonaktif',
            'description' => 'Produk tidak boleh tampil.',
            'price' => 250000,
            'stock' => 10,
            'weight' => 1000,
            'is_active' => true,
        ]);

        $activeCategory = ProductCategory::create([
            'name' => 'Kategori Brand Nonaktif',
            'is_active' => true,
        ]);

        $inactiveBrand = ProductBrand::create([
            'name' => 'Brand Nonaktif',
            'is_active' => false,
        ]);

        Product::create([
            'category_id' => $activeCategory->id,
            'brand_id' => $inactiveBrand->id,
            'name' => 'Produk Brand Nonaktif',
            'description' => 'Produk tidak boleh tampil.',
            'price' => 250000,
            'stock' => 10,
            'weight' => 1000,
            'is_active' => true,
        ]);

        $response = $this->get(route('pelanggan.products.index'));

        $response->assertOk();
        $response->assertSee('Produk Terlihat');
        $response->assertDontSee('Produk Kategori Nonaktif');
        $response->assertDontSee('Produk Brand Nonaktif');
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
