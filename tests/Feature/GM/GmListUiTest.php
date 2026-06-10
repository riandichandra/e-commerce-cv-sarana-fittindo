<?php

namespace Tests\Feature\GM;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GmListUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_gm_dashboard_list_sections_use_clean_table_labels(): void
    {
        $gm = $this->makeUserWithRole('gm');
        $order = $this->makeOrderWithItem();

        $response = $this->actingAs($gm)->get(route('gm.dashboard'));

        $response->assertOk();
        $response->assertSee('Top Pelanggan');
        $response->assertSee('Detail Pesanan');
        $response->assertSee('Produk Teratas');
        $response->assertSee('No.');
        $response->assertSee($order->order_number);
        $response->assertSee('Produk Test');
        $response->assertDontSee('>#<', false);
    }

    public function test_gm_dashboard_order_summary_uses_selected_period(): void
    {
        $gm = $this->makeUserWithRole('gm');
        $thisMonthOrder = $this->makeOrderWithItem([
            'status' => 'selesai',
            'created_at' => now()->startOfMonth()->addDay(),
            'updated_at' => now()->startOfMonth()->addDay(),
        ], 'Produk Bulan Ini');
        $lastMonthOrder = $this->makeOrderWithItem([
            'status' => 'selesai',
            'created_at' => now()->subMonthNoOverflow()->startOfMonth()->addDay(),
            'updated_at' => now()->subMonthNoOverflow()->startOfMonth()->addDay(),
        ], 'Produk Bulan Lalu');

        $response = $this->actingAs($gm)->get(route('gm.dashboard', [
            'card_order_start_date' => now()->startOfMonth()->toDateString(),
            'card_order_end_date' => now()->endOfMonth()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Periode: ' . now()->startOfMonth()->format('d M Y') . ' - ' . now()->endOfMonth()->format('d M Y'));
        $response->assertSee('1 pesanan semua status');
        $response->assertSee($thisMonthOrder->order_number);
        $response->assertSee($lastMonthOrder->order_number);
    }

    public function test_gm_dashboard_card_can_filter_by_status_without_filtering_detail_orders(): void
    {
        $gm = $this->makeUserWithRole('gm');
        $dikirimOrder = $this->makeOrderWithItem(['status' => 'dikirim'], 'Produk Dikirim');
        $selesaiOrder = $this->makeOrderWithItem(['status' => 'selesai'], 'Produk Selesai');

        $response = $this->actingAs($gm)->get(route('gm.dashboard', [
            'card_order_start_date' => now()->startOfMonth()->toDateString(),
            'card_order_end_date' => now()->endOfMonth()->toDateString(),
            'card_order_status' => 'dikirim',
        ]));

        $response->assertOk();
        $response->assertSee('value="dikirim" selected', false);
        $response->assertSee('1 pesanan dikirim');
        $response->assertSee($dikirimOrder->order_number);
        $response->assertSee($selesaiOrder->order_number);
    }

    public function test_gm_dashboard_card_can_filter_by_date_range_without_filtering_detail_orders(): void
    {
        $gm = $this->makeUserWithRole('gm');
        $matchedOrder = $this->makeOrderWithItem([
            'created_at' => '2026-03-02 10:00:00',
            'updated_at' => '2026-03-02 10:00:00',
        ], 'Produk Custom');
        $outsideOrder = $this->makeOrderWithItem([
            'created_at' => '2026-03-10 10:00:00',
            'updated_at' => '2026-03-10 10:00:00',
        ], 'Produk Di Luar');

        $response = $this->actingAs($gm)->get(route('gm.dashboard', [
            'card_order_start_date' => '2026-03-01',
            'card_order_end_date' => '2026-03-03',
        ]));

        $response->assertOk();
        $response->assertSee('Periode: 01 Mar 2026 - 03 Mar 2026');
        $response->assertSee($matchedOrder->order_number);
        $response->assertSee($outsideOrder->order_number);
    }

    public function test_gm_dashboard_card_filter_does_not_filter_status_distribution(): void
    {
        $gm = $this->makeUserWithRole('gm');
        $mayOrder = $this->makeOrderWithItem([
            'status' => 'selesai',
            'created_at' => '2026-05-10 10:00:00',
            'updated_at' => '2026-05-10 10:00:00',
        ], 'Produk Mei');
        $juneOrder = $this->makeOrderWithItem([
            'status' => 'selesai',
            'created_at' => '2026-06-10 10:00:00',
            'updated_at' => '2026-06-10 10:00:00',
        ], 'Produk Juni');

        $response = $this->actingAs($gm)->get(route('gm.dashboard', [
            'card_order_start_date' => '2026-05-01',
            'card_order_end_date' => '2026-05-31',
            'card_order_status' => 'selesai',
        ]));

        $response->assertOk();
        $response->assertSee('Periode: 01 May 2026 - 31 May 2026');
        $response->assertSee($mayOrder->order_number);
        $response->assertSee($juneOrder->order_number);
        $response->assertSee('Distribusi status order saat ini.');
    }

    public function test_gm_dashboard_top_sections_use_selected_date_range(): void
    {
        $gm = $this->makeUserWithRole('gm');
        $this->makeOrderWithItem([
            'created_at' => '2026-06-10 10:00:00',
            'updated_at' => '2026-06-10 10:00:00',
        ], 'Produk Dalam Periode', 'Pelanggan Dalam Periode');
        $this->makeOrderWithItem([
            'created_at' => '2026-05-10 10:00:00',
            'updated_at' => '2026-05-10 10:00:00',
        ], 'Produk Luar Periode', 'Pelanggan Luar Periode');

        $response = $this->actingAs($gm)->get(route('gm.dashboard', [
            'top_start_date' => '2026-06-01',
            'top_end_date' => '2026-06-30',
        ]));

        $response->assertOk();
        $response->assertSee('dari 01 Jun 2026 sampai 30 Jun 2026');
        $response->assertSee('Pelanggan Dalam Periode');
        $response->assertSee('Produk Dalam Periode');
        $response->assertDontSee('Produk Luar Periode');
    }

    public function test_gm_dashboard_top_products_include_end_date_until_end_of_day(): void
    {
        $gm = $this->makeUserWithRole('gm');
        $this->makeOrderWithItem([
            'created_at' => '2026-06-30 23:00:00',
            'updated_at' => '2026-06-30 23:00:00',
        ], 'Produk Akhir Hari', 'Pelanggan Akhir Hari');

        $response = $this->actingAs($gm)->get(route('gm.dashboard', [
            'top_start_date' => '2026-06-01',
            'top_end_date' => '2026-06-30',
        ]));

        $response->assertOk();
        $response->assertSee('Produk Akhir Hari');
    }

    public function test_gm_report_list_uses_no_label_and_keeps_order_data(): void
    {
        $gm = $this->makeUserWithRole('gm');
        $order = $this->makeOrderWithItem();

        $response = $this->actingAs($gm)->get(route('gm.reports.index'));

        $response->assertOk();
        $response->assertSee('LAPORAN ORDER');
        $response->assertSee('No.');
        $response->assertSee($order->order_number);
        $response->assertSee('Pelanggan Test');
        $response->assertDontSee('Semua Konsumen');
        $response->assertDontSee('Semua Karyawan');
        $response->assertDontSee('Semua Nama Barang');
        $response->assertDontSee('>#<', false);
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

    private function makeOrderWithItem(array $attributes = [], string $productName = 'Produk Test', string $customerName = 'Pelanggan Test'): Order
    {
        $customer = $this->makeUserWithRole('pelanggan', [
            'name' => $customerName,
            'phone' => '08123456789',
        ]);

        $category = ProductCategory::firstOrCreate(
            ['name' => 'Kategori Test'],
            ['is_active' => true]
        );

        $product = Product::create([
            'category_id' => $category->id,
            'name' => $productName,
            'description' => 'Produk untuk test list GM.',
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
            'product_name' => $productName,
            'product_price' => 100000,
            'quantity' => 2,
            'subtotal' => 200000,
        ]);

        return $order;
    }
}
