<?php

namespace Tests\Feature\GM;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GmReportExcelTest extends TestCase
{
    use RefreshDatabase;

    public function test_gm_report_excel_download_shows_new_header_with_indonesian_period(): void
    {
        $gm = $this->makeUserWithRole('gm');
        $order = $this->makeOrderWithItem([
            'created_at' => '2026-03-02 10:00:00',
            'updated_at' => '2026-03-02 10:00:00',
        ]);

        $response = $this->actingAs($gm)->get(route('gm.reports.download', [
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-02',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('Laporan Kuantitas Penjualan', $content);
        $this->assertStringContainsString('Cabang CV. SARANA FITTINDO', $content);
        $this->assertStringContainsString('Periode: 2 Maret 2026 - 2 Maret 2026', $content);
        $this->assertStringContainsString('Status Pesanan : Semua', $content);
        $this->assertStringContainsString('Status Pembayaran : Semua', $content);
        $this->assertStringContainsString($order->order_number, $content);
        $this->assertStringContainsString('Pelanggan Test', $content);
        $this->assertStringContainsString('Produk Test (2)', $content);
    }

    public function test_gm_report_excel_download_shows_all_period_when_dates_are_empty(): void
    {
        $gm = $this->makeUserWithRole('gm');

        $response = $this->actingAs($gm)->get(route('gm.reports.download'));

        $response->assertOk();

        $this->assertStringContainsString('Periode: Semua', $response->streamedContent());
    }

    public function test_gm_report_excel_header_uses_selected_order_and_payment_status_filters(): void
    {
        $gm = $this->makeUserWithRole('gm');
        $employee = $this->makeUserWithRole('admin', ['name' => 'Admin Verifikator']);
        $order = $this->makeOrderWithItem(['status' => 'diproses']);
        $paymentMethod = PaymentMethod::create([
            'name' => 'Transfer Bank',
            'code' => 'transfer-bank',
            'is_active' => true,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => $order->total_amount,
            'status' => 'terverifikasi',
            'verified_by' => $employee->id,
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($gm)->get(route('gm.reports.download', [
            'status' => 'diproses',
            'payment_status' => 'terverifikasi',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('Status Pesanan : Diproses', $content);
        $this->assertStringContainsString('Status Pembayaran : Terverifikasi', $content);
        $this->assertStringContainsString($order->order_number, $content);
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

    private function makeOrderWithItem(array $attributes = []): Order
    {
        $customer = $this->makeUserWithRole('pelanggan', [
            'name' => 'Pelanggan Test',
            'phone' => '08123456789',
        ]);

        $category = ProductCategory::create([
            'name' => 'Kategori Test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Test',
            'description' => 'Produk untuk test laporan GM.',
            'price' => 100000,
            'stock' => 10,
            'weight' => 1000,
            'is_active' => true,
        ]);

        $createdAt = $attributes['created_at'] ?? null;
        $updatedAt = $attributes['updated_at'] ?? null;
        unset($attributes['created_at'], $attributes['updated_at']);

        $order = Order::create(array_merge([
            'user_id' => $customer->id,
            'order_number' => 'GM-' . strtoupper(uniqid()),
            'status' => 'selesai',
            'subtotal' => 200000,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'total_amount' => 200000,
            'shipping_name' => $customer->name,
            'shipping_phone' => '08123456789',
            'shipping_address' => 'Jl. Test',
            'shipping_province' => 'Sumatera Selatan',
            'shipping_city' => 'Palembang',
            'shipping_district' => 'Ilir Timur',
            'shipping_village' => 'Test',
            'shipping_postal_code' => '30111',
        ], $attributes));

        if ($createdAt || $updatedAt) {
            $order->forceFill([
                'created_at' => $createdAt ?? $order->created_at,
                'updated_at' => $updatedAt ?? $order->updated_at,
            ])->save();
        }

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => 100000,
            'quantity' => 2,
            'subtotal' => 200000,
        ]);

        return $order;
    }
}
