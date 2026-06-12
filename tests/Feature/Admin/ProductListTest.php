<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_product_list_is_rendered_with_clean_table_labels_and_product_details(): void
    {
        $admin = $this->makeAdmin();
        $category = ProductCategory::create([
            'name' => 'HPL',
            'description' => 'Material finishing',
            'is_active' => true,
        ]);
        $brand = ProductBrand::create([
            'name' => 'Sarana HPL',
            'description' => 'Brand HPL',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'HPL Walnut Premium',
            'description' => 'Produk pengujian',
            'price' => 325000,
            'stock' => 12,
            'weight' => 1200,
            'thickness' => '18mm',
            'dimensions' => '122x244 cm',
            'is_featured' => true,
            'is_active' => true,
        ]);

        $product->images()->create([
            'image_path' => 'products/test-product.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.products.index'));

        $response->assertOk();
        $response->assertSee('No.');
        $response->assertDontSee('<th class="w-16 px-5 py-4">#</th>', false);
        $response->assertSee('HPL Walnut Premium');
        $response->assertSee('Sarana HPL');
        $response->assertSee('Rp 325.000');
        $response->assertSee('12 stok');
        $response->assertSee('Tersedia');
        $response->assertSee('Aktif');
        $response->assertSee('Unggulan');
        $response->assertDontSee('storage/products/test-product.jpg');
    }

    public function test_admin_can_store_structured_product_specifications(): void
    {
        $admin = $this->makeAdmin();
        $category = ProductCategory::create([
            'name' => 'Panel',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Panel Spesifikasi',
            'description' => 'Produk dengan spesifikasi terstruktur.',
            'price' => 175000,
            'stock' => 5,
            'weight' => 900,
            'specifications_text' => "Material: HPL\nFinishing: Matte\nCocok untuk interior",
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Panel Spesifikasi')->firstOrFail();

        $this->assertSame([
            'Material' => 'HPL',
            'Finishing' => 'Matte',
            0 => 'Cocok untuk interior',
        ], $product->specifications);
    }

    private function makeAdmin(): User
    {
        $role = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole($role->name);

        return $admin;
    }
}
