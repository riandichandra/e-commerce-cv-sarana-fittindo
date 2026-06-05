<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingCostConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_confirm_manual_shipping_cost(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('pelanggan');
        $paymentMethod = $this->makePaymentMethod();
        $order = $this->makeWaitingShippingCostOrder($customer, $paymentMethod);

        $response = $this->actingAs($admin)->patch(route('admin.orders.shipping-cost.update', $order), [
            'shipping_cost' => 45000,
        ]);

        $response->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'belum_dibayar',
            'shipping_cost_status' => 'confirmed',
            'shipping_cost' => 45000,
            'total_amount' => 345000,
            'shipping_cost_confirmed_by' => $admin->id,
        ]);

        $this->assertNotNull($order->fresh()->shipping_cost_confirmed_at);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 345000,
            'status' => 'menunggu',
        ]);
    }

    public function test_admin_confirmed_shipping_cost_keeps_order_discount_in_total(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('pelanggan');
        $paymentMethod = $this->makePaymentMethod();
        $order = $this->makeWaitingShippingCostOrder($customer, $paymentMethod, [
            'discount_amount' => 50000,
            'total_amount' => 250000,
        ]);
        $order->payment->update(['amount' => 250000]);

        $response = $this->actingAs($admin)->patch(route('admin.orders.shipping-cost.update', $order), [
            'shipping_cost' => 45000,
        ]);

        $response->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'discount_amount' => 50000,
            'shipping_cost' => 45000,
            'total_amount' => 295000,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 295000,
        ]);
    }

    public function test_admin_order_list_can_filter_waiting_shipping_cost_status(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('pelanggan');
        $paymentMethod = $this->makePaymentMethod();
        $waitingOrder = $this->makeWaitingShippingCostOrder($customer, $paymentMethod);
        $paidReadyOrder = $this->makeWaitingShippingCostOrder($customer, $paymentMethod, [
            'order_number' => 'SF' . now()->format('Ymd') . '9999',
            'status' => 'belum_dibayar',
            'shipping_cost_status' => 'fixed',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.index', [
            'status' => 'menunggu_konfirmasi_ongkir',
        ]));

        $response->assertOk();
        $response->assertSee($waitingOrder->order_number);
        $response->assertDontSee($paidReadyOrder->order_number);
    }

    private function makeUser(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['guard_name' => 'web']
        );

        $user = User::factory()->create([
            'phone' => '081111111111',
        ]);

        $user->assignRole($role->name);

        return $user;
    }

    private function makePaymentMethod(): PaymentMethod
    {
        return PaymentMethod::create([
            'name' => 'Transfer Bank BCA',
            'code' => 'bca_test_' . PaymentMethod::count(),
            'account_number' => '1234567890',
            'account_name' => 'CV Sarana Fittindo',
            'bank_name' => 'BCA',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function makeWaitingShippingCostOrder(User $customer, PaymentMethod $paymentMethod, array $overrides = []): Order
    {
        $order = Order::create(array_merge([
            'user_id' => $customer->id,
            'order_number' => 'SF' . now()->format('Ymd') . str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'status' => 'menunggu_konfirmasi_ongkir',
            'subtotal' => 300000,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'shipping_cost_status' => 'waiting_admin',
            'total_amount' => 300000,
            'payment_method_id' => $paymentMethod->id,
            'shipping_name' => $customer->name,
            'shipping_phone' => $customer->phone,
            'shipping_address' => 'Jl. Merdeka No. 10',
            'shipping_province' => 'Jawa Barat',
            'shipping_city' => 'Bandung',
            'shipping_district' => 'Coblong',
            'shipping_village' => 'Dago',
            'shipping_postal_code' => '40135',
        ], $overrides));

        Payment::create([
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => $order->total_amount,
            'status' => 'menunggu',
        ]);

        return $order;
    }
}
