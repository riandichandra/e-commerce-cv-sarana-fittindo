<?php

namespace Tests\Feature\Admin;

use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminValidationMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_product_required_validation_messages_are_indonesian(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->post(route('admin.products.store'), []);

        $response->assertSessionHasErrors([
            'category_id' => 'Kategori wajib diisi.',
            'name' => 'Nama wajib diisi.',
            'price' => 'Harga wajib diisi.',
            'stock' => 'Jumlah stok wajib diisi.',
            'weight' => 'Berat wajib diisi.',
        ]);

        $this->assertValidationDoesNotContainEnglishDefaults();
    }

    public function test_admin_product_image_validation_message_is_indonesian(): void
    {
        $category = ProductCategory::create([
            'name' => 'HPL',
            'description' => 'Material finishing',
            'is_active' => true,
        ]);

        ProductBrand::create([
            'name' => 'TACO',
            'description' => 'Brand produk',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->makeAdmin())
            ->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'Produk Uji Validasi',
                'price' => 100000,
                'stock' => 5,
                'weight' => 1000,
                'images' => [
                    UploadedFile::fake()->create('dokumen.pdf', 12, 'application/pdf'),
                ],
            ]);

        $response->assertSessionHasErrors([
            'images.0' => 'Gambar produk harus berupa gambar.',
        ]);

        $this->assertValidationDoesNotContainEnglishDefaults();
    }

    public function test_admin_payment_method_validation_messages_are_indonesian(): void
    {
        $response = $this->actingAs($this->makeAdmin())
            ->post(route('admin.payment-methods.store'), [
                'name' => '',
                'bank_name' => '',
                'account_number' => '',
                'account_name' => '',
                'sort_order' => 'paling-atas',
            ]);

        $response->assertSessionHasErrors([
            'name' => 'Nama wajib diisi.',
            'bank_name' => 'Nama bank wajib diisi.',
            'account_number' => 'Nomor rekening wajib diisi.',
            'account_name' => 'Nama rekening wajib diisi.',
            'sort_order' => 'Urutan harus berupa bilangan bulat.',
        ]);

        $this->assertValidationDoesNotContainEnglishDefaults();
    }

    private function makeAdmin(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );

        $admin = User::factory()->create();
        $admin->assignRole($role->name);

        return $admin;
    }

    private function assertValidationDoesNotContainEnglishDefaults(): void
    {
        $messages = session('errors')?->getBag('default')->all() ?? [];
        $messageText = implode(' ', $messages);

        $this->assertStringNotContainsString('The ', $messageText);
        $this->assertStringNotContainsString(' field is required', $messageText);
        $this->assertStringNotContainsString(' must be ', $messageText);
        $this->assertStringNotContainsString(' selected ', $messageText);
    }
}
