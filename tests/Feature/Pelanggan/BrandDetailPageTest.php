<?php

namespace Tests\Feature\Pelanggan;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandDetailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_detail_page_shows_brand_information_and_visible_products(): void
    {
        $category = ProductCategory::create([
            'name' => 'HPL Premium',
            'description' => 'Kategori HPL pilihan.',
            'is_active' => true,
        ]);

        $brand = ProductBrand::create([
            'name' => 'Sarana Panel',
            'description' => 'Merek panel interior.',
            'is_active' => true,
        ]);

        $visibleProduct = $this->makeProduct('HPL Kayu Natural', $category, $brand);
        $otherBrand = ProductBrand::create([
            'name' => 'Merek Lain',
            'is_active' => true,
        ]);
        $otherBrandProduct = $this->makeProduct('Produk Merek Lain', $category, $otherBrand);
        $inactiveProduct = $this->makeProduct('Produk Nonaktif', $category, $brand, ['is_active' => false]);
        $inactiveCategory = ProductCategory::create([
            'name' => 'Kategori Nonaktif',
            'is_active' => false,
        ]);
        $hiddenCategoryProduct = $this->makeProduct('Produk Kategori Nonaktif', $inactiveCategory, $brand);

        $response = $this->get(route('pelanggan.brands.show', $brand));

        $response->assertOk();
        $response->assertSee($brand->name);
        $response->assertSee('Merek panel interior.');
        $response->assertSee($visibleProduct->name);
        $response->assertSee(route('pelanggan.products.show', $visibleProduct), false);
        $response->assertDontSee($otherBrandProduct->name);
        $response->assertDontSee($inactiveProduct->name);
        $response->assertDontSee($hiddenCategoryProduct->name);
    }

    public function test_inactive_brand_detail_page_returns_not_found(): void
    {
        $brand = ProductBrand::create([
            'name' => 'Brand Disembunyikan',
            'is_active' => false,
        ]);

        $this->get(route('pelanggan.brands.show', $brand))
            ->assertNotFound();
    }

    private function makeProduct(string $name, ProductCategory $category, ProductBrand $brand, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => $name,
            'description' => 'Produk pengujian halaman brand.',
            'price' => 250000,
            'stock' => 10,
            'weight' => 1000,
            'is_active' => true,
        ], $overrides));
    }
}
