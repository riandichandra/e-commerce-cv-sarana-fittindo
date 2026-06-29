<?php

namespace Tests\Feature\Pelanggan;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryDetailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_detail_page_shows_category_information_and_visible_products(): void
    {
        $category = ProductCategory::create([
            'name' => 'HPL Premium',
            'description' => 'Kategori HPL pilihan.',
            'is_active' => true,
        ]);

        $brand = ProductBrand::create([
            'name' => 'Sarana Panel',
            'is_active' => true,
        ]);

        $visibleProduct = $this->makeProduct('HPL Kayu Natural', $category, $brand);
        $otherCategory = ProductCategory::create([
            'name' => 'Plywood',
            'is_active' => true,
        ]);
        $otherCategoryProduct = $this->makeProduct('Produk Kategori Lain', $otherCategory, $brand);
        $inactiveProduct = $this->makeProduct('Produk Nonaktif', $category, $brand, ['is_active' => false]);
        $inactiveBrand = ProductBrand::create([
            'name' => 'Brand Nonaktif',
            'is_active' => false,
        ]);
        $hiddenBrandProduct = $this->makeProduct('Produk Brand Nonaktif', $category, $inactiveBrand);

        $response = $this->get(route('pelanggan.categories.show', $category));

        $response->assertOk();
        $response->assertSee($category->name);
        $response->assertSee('Kategori HPL pilihan.');
        $response->assertSee($visibleProduct->name);
        $response->assertSee(route('pelanggan.products.show', $visibleProduct), false);
        $response->assertDontSee($otherCategoryProduct->name);
        $response->assertDontSee($inactiveProduct->name);
        $response->assertDontSee($hiddenBrandProduct->name);
    }

    public function test_inactive_category_detail_page_returns_not_found(): void
    {
        $category = ProductCategory::create([
            'name' => 'Kategori Disembunyikan',
            'is_active' => false,
        ]);

        $this->get(route('pelanggan.categories.show', $category))
            ->assertNotFound();
    }

    private function makeProduct(string $name, ProductCategory $category, ?ProductBrand $brand = null, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'brand_id' => $brand?->id,
            'name' => $name,
            'description' => 'Produk pengujian halaman kategori.',
            'price' => 250000,
            'stock' => 10,
            'weight' => 1000,
            'is_active' => true,
        ], $overrides));
    }
}
