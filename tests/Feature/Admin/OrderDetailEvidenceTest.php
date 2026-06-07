<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDetailEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_order_detail_shows_payment_and_received_evidence(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create(['name' => 'Budi Pelanggan']);
        $paymentMethod = $this->makePaymentMethod();
        $order = $this->makeOrder($customer, $paymentMethod, [
            'status' => 'selesai',
            'received_image' => 'received-product-photos/test-received.jpg',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 150000,
            'proof_image' => 'payment-proofs/test-proof.jpg',
            'transfer_date' => now()->toDateString(),
            'sender_name' => 'Budi Transfer',
            'status' => 'terverifikasi',
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Bukti Pesanan');
        $response->assertSee('Bukti Pembayaran');
        $response->assertSee('storage/payment-proofs/test-proof.jpg');
        $response->assertSee('Budi Transfer');
        $response->assertSee('Bukti Barang Diterima');
        $response->assertSee('storage/received-product-photos/test-received.jpg');
        $response->assertSee('Budi Pelanggan');
    }

    public function test_admin_order_detail_shows_empty_states_when_evidence_is_missing(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create();
        $paymentMethod = $this->makePaymentMethod();
        $order = $this->makeOrder($customer, $paymentMethod);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Bukti pembayaran belum diunggah.');
        $response->assertSee('Bukti barang diterima belum tersedia.');
    }

    private function makeAdmin(): User
    {
        $role = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole($role->name);

        return $admin;
    }

    private function makePaymentMethod(): PaymentMethod
    {
        return PaymentMethod::create([
            'name' => 'Transfer Bank BCA',
            'code' => 'bca_test',
            'account_number' => '1234567890',
            'account_name' => 'CV Sarana Fittindo',
            'bank_name' => 'BCA',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function makeOrder(User $customer, PaymentMethod $paymentMethod, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $customer->id,
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'status' => 'belum_dibayar',
            'subtotal' => 150000,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'total_amount' => 150000,
            'payment_method_id' => $paymentMethod->id,
            'shipping_name' => $customer->name,
            'shipping_phone' => '08123456789',
            'shipping_address' => 'Jl. Test',
            'shipping_province' => 'Sumatera Selatan',
            'shipping_city' => 'Palembang',
            'shipping_district' => 'Ilir Timur',
            'shipping_village' => 'Test',
            'shipping_postal_code' => '30111',
        ], $overrides));
    }
}
