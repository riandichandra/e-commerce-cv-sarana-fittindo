<?php

namespace Tests\Feature\Pelanggan;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_detail_page_shows_customer_friendly_product_information(): void
    {
        $customer = $this->makeUserWithRole('pelanggan');
        [$product, $related] = $this->makeProductSet();

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/main.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($customer)->get(route('pelanggan.products.show', $product));

        $response->assertOk();
        $response->assertSee($product->name);
        $response->assertSee('Rp 250.000');
        $response->assertSee('Detail Produk');
        $response->assertSee('Berat');
        $response->assertSee('Ketebalan');
        $response->assertSee('Dimensi');
        $response->assertDontSee('Finishing');
        $response->assertSee('Tambah ke Keranjang');
        $response->assertSee('name="quantity"', false);
        $response->assertSee($related->name);
        $response->assertSee(route('pelanggan.cart.store', $related), false);
    }

    public function test_product_detail_page_has_clean_fallback_when_product_has_no_image(): void
    {
        [$product] = $this->makeProductSet([
            'name' => 'Produk Tanpa Gambar',
        ]);

        $response = $this->get(route('pelanggan.products.show', $product));

        $response->assertOk();
        $response->assertSee('Produk Tanpa Gambar');
        $response->assertSee('Gambar produk belum tersedia');
        $response->assertDontSee('âˆ’');
    }

    private function makeUserWithRole(string $roleName, array $attributes = []): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['guard_name' => 'web']
        );

        $user = User::factory()->create($attributes);
        $user->assignRole($role->name);

        return $user;
    }

    private function makeProductSet(array $overrides = []): array
    {
        $category = ProductCategory::create([
            'name' => 'HPL Premium',
            'is_active' => true,
        ]);

        $brand = ProductBrand::create([
            'name' => 'Sarana Panel',
            'is_active' => true,
        ]);

        $product = Product::create(array_merge([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'HPL Kayu Natural',
            'description' => 'Material interior untuk kabinet dan panel dinding.',
            'price' => 250000,
            'stock' => 12,
            'weight' => 1800,
            'thickness' => '0.8 mm',
            'dimensions' => '122 x 244 cm',
            'specifications' => [
                'finishing' => 'Doff',
                'motif' => 'Kayu',
            ],
            'is_active' => true,
        ], $overrides));

        $related = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'HPL Abu Elegan',
            'description' => 'Produk terkait untuk rekomendasi.',
            'price' => 220000,
            'stock' => 8,
            'weight' => 1700,
            'thickness' => '0.8 mm',
            'dimensions' => '122 x 244 cm',
            'is_active' => true,
        ]);

        return [$product, $related];
    }
}
