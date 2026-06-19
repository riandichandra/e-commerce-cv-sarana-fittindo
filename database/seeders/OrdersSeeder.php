<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class OrdersSeeder extends Seeder
{
    private const TARGET_ORDER_TOTAL = 30;

    private const STATUS_TARGETS = [
        'belum_dibayar' => 3,
        'menunggu_verifikasi_pembayaran' => 2,
        'diproses' => 5,
        'dikirim' => 5,
        'selesai' => 13,
        'dibatalkan' => 2,
    ];

    private const SEED_PREFIX = 'SFDM';

    public function run(): void
    {
        $existingOrderCount = Order::count();

        if ($existingOrderCount >= self::TARGET_ORDER_TOTAL) {
            $this->command?->info('Total orders sudah '.$existingOrderCount.'. Tidak ada order dummy yang ditambahkan.');

            return;
        }

        $customers = User::role('pelanggan')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($customers->isEmpty()) {
            $this->command?->warn('Seeder dihentikan: tidak ditemukan user aktif dengan role pelanggan.');

            return;
        }

        $adminIds = User::role('admin')
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id');

        if ($adminIds->isEmpty()) {
            $this->command?->warn('Seeder dihentikan: tidak ditemukan user aktif dengan role admin untuk field audit.');

            return;
        }

        $products = Product::query()
            ->where('is_active', true)
            ->where('status', Product::STATUS_AVAILABLE)
            ->where('stock', '>', 0)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'name', 'price', 'weight']);

        if ($products->isEmpty()) {
            $this->command?->warn('Seeder dihentikan: tidak ditemukan produk aktif dan tersedia.');

            return;
        }

        $paymentMethodIds = PaymentMethod::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id');

        if ($paymentMethodIds->isEmpty()) {
            $this->command?->warn('Seeder dihentikan: tidak ditemukan metode pembayaran aktif.');

            return;
        }

        $addresses = UserAddress::query()
            ->whereIn('user_id', $customers->pluck('id'))
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');

        $statuses = $this->statusesToCreate(self::TARGET_ORDER_TOTAL - $existingOrderCount);
        $nextSequence = $this->nextSeedSequence();

        foreach ($statuses as $offset => $status) {
            $sequence = $nextSequence + $offset;
            $createdAt = $this->createdAtFor($sequence, $status);
            $customer = $this->customerFor($customers, $sequence);
            $items = $this->plannedItems($products, $sequence);
            $subtotal = $items->sum('subtotal');
            $shippingWeight = (int) $items->sum('weight_total');
            $shippingCost = $this->shippingCostFor($shippingWeight, $sequence);
            $discount = 0;
            $total = max(0, $subtotal - $discount + $shippingCost);
            $paymentMethodId = $this->paymentMethodFor($paymentMethodIds, $sequence, $status);
            $shipping = $this->shippingSnapshotFor($customer, $addresses->get($customer->id, collect()), $sequence);

            $order = new Order([
                'user_id' => $customer->id,
                'order_number' => $this->orderNumberFor($createdAt, $sequence),
                'status' => $status,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'shipping_cost' => $shippingCost,
                'shipping_cost_status' => 'fixed',
                'shipping_cost_source' => 'dummy_snapshot',
                'shipping_destination_district_id' => $shipping['district_id'],
                'shipping_weight_gram' => $shippingWeight,
                'shipping_courier_code' => $this->courierCodeFor($sequence),
                'shipping_courier_name' => $this->courierNameFor($sequence),
                'shipping_service' => $this->shippingServiceFor($sequence),
                'shipping_service_description' => 'Estimasi pengiriman reguler',
                'shipping_etd' => $this->shippingEtdFor($sequence),
                'shipping_rate_snapshot' => [
                    'source' => 'dummy_snapshot',
                    'weight' => $shippingWeight,
                    'cost' => $shippingCost,
                ],
                'shipping_cost_confirmed_at' => $createdAt,
                'total_amount' => $total,
                'payment_method_id' => $paymentMethodId,
                'shipping_name' => $shipping['name'],
                'shipping_phone' => $shipping['phone'],
                'shipping_address' => $shipping['address'],
                'shipping_province' => $shipping['province'],
                'shipping_city' => $shipping['city'],
                'shipping_district' => $shipping['district'],
                'shipping_village' => $shipping['village'],
                'shipping_postal_code' => $shipping['postal_code'],
                'notes' => $this->noteFor($sequence),
                'cancelled_by' => $status === 'dibatalkan' ? $this->auditUserFor($adminIds, $sequence) : null,
                'cancellation_reason' => $status === 'dibatalkan' ? $this->cancellationReasonFor($sequence) : null,
                'cancelled_at' => $status === 'dibatalkan' ? $createdAt->copy()->addDays(1) : null,
                'shipped_at' => in_array($status, ['dikirim', 'selesai'], true) ? $createdAt->copy()->addDays(2) : null,
                'completed_at' => $status === 'selesai' ? $createdAt->copy()->addDays(5) : null,
                'completion_source' => $status === 'selesai' ? 'pelanggan' : null,
                'completion_notes' => $status === 'selesai' ? 'Pesanan diterima pelanggan.' : null,
                'stock_restored_at' => null,
                'stock_restored_by' => null,
            ]);

            $order->created_at = $createdAt;
            $order->updated_at = $this->updatedAtFor($createdAt, $status);
            $order->save();
        }

        $this->command?->info(count($statuses).' order dummy ditambahkan. Total orders sekarang: '.Order::count().'.');
    }

    private function statusesToCreate(int $needed): array
    {
        $existingByStatus = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = [];

        foreach (self::STATUS_TARGETS as $status => $target) {
            $deficit = max(0, $target - (int) $existingByStatus->get($status, 0));

            for ($i = 0; $i < $deficit && count($statuses) < $needed; $i++) {
                $statuses[] = $status;
            }
        }

        $fallback = ['selesai', 'diproses', 'dikirim', 'belum_dibayar', 'menunggu_verifikasi_pembayaran', 'dibatalkan'];
        $index = 0;

        while (count($statuses) < $needed) {
            $statuses[] = $fallback[$index % count($fallback)];
            $index++;
        }

        return $statuses;
    }

    private function nextSeedSequence(): int
    {
        $lastOrderNumber = Order::where('order_number', 'like', self::SEED_PREFIX.'%')
            ->orderByDesc('order_number')
            ->value('order_number');

        if (! $lastOrderNumber) {
            return 1;
        }

        return ((int) substr($lastOrderNumber, -3)) + 1;
    }

    private function orderNumberFor(Carbon $createdAt, int $sequence): string
    {
        return self::SEED_PREFIX.$createdAt->format('Ymd').str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    private function createdAtFor(int $sequence, string $status): Carbon
    {
        $base = match ($status) {
            'selesai' => now()->subDays(70),
            'dikirim' => now()->subDays(16),
            'diproses' => now()->subDays(9),
            'menunggu_verifikasi_pembayaran' => now()->subDays(4),
            'belum_dibayar' => now()->subDays(2),
            'dibatalkan' => now()->subDays(25),
            default => now()->subDays(12),
        };

        return $base->copy()
            ->addDays($sequence % 9)
            ->setTime(8 + ($sequence % 9), ($sequence * 7) % 60, 0);
    }

    private function updatedAtFor(Carbon $createdAt, string $status): Carbon
    {
        return match ($status) {
            'selesai' => $createdAt->copy()->addDays(5),
            'dikirim' => $createdAt->copy()->addDays(2),
            'diproses' => $createdAt->copy()->addDay(),
            'dibatalkan' => $createdAt->copy()->addDay(),
            default => $createdAt->copy(),
        };
    }

    private function customerFor(Collection $customers, int $sequence): User
    {
        return $customers->values()[($sequence - 1) % $customers->count()];
    }

    private function plannedItems(Collection $products, int $sequence): Collection
    {
        $itemCountPattern = [4, 3, 4, 2, 3, 4, 1, 3, 2, 4];
        $itemCount = min($itemCountPattern[($sequence - 1) % count($itemCountPattern)], $products->count());
        $start = ($sequence * 2) % $products->count();
        $items = collect();

        for ($i = 0; $i < $itemCount; $i++) {
            $product = $products[($start + $i) % $products->count()];
            $price = (float) $product->price;
            $quantity = $price >= 1000000 ? 1 : (($sequence + $i) % 3) + 1;
            $subtotal = $price * $quantity;
            $weight = max(1, (float) $product->weight);

            $items->push([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'weight_total' => $weight * $quantity,
            ]);
        }

        return $items;
    }

    private function shippingCostFor(int $weight, int $sequence): int
    {
        $base = [18000, 25000, 32000, 45000, 60000, 75000][$sequence % 6];
        $extra = max(0, (int) ceil(($weight - 5000) / 5000)) * 12000;

        return $base + $extra;
    }

    private function paymentMethodFor(Collection $paymentMethodIds, int $sequence, string $status): ?int
    {
        if ($status === 'belum_dibayar' && $sequence % 2 === 0) {
            return null;
        }

        return $paymentMethodIds->values()[($sequence - 1) % $paymentMethodIds->count()];
    }

    private function auditUserFor(Collection $adminIds, int $sequence): int
    {
        return $adminIds->values()[($sequence - 1) % $adminIds->count()];
    }

    private function shippingSnapshotFor(User $customer, Collection $addresses, int $sequence): array
    {
        $address = $addresses->first();

        if ($address) {
            return [
                'name' => $address->receiver_name,
                'phone' => $address->receiver_phone,
                'address' => $address->full_address,
                'province' => $address->province_name ?: 'Sumatera Selatan',
                'city' => $address->city_name ?: 'Palembang',
                'district' => $address->district_name ?: 'Ilir Timur I',
                'village' => $address->village_name,
                'postal_code' => $address->postal_code,
                'district_id' => $address->district_id,
            ];
        }

        $snapshots = [
            ['city' => 'Palembang', 'district' => 'Ilir Barat I', 'village' => 'Bukit Lama', 'postal_code' => '30139'],
            ['city' => 'Palembang', 'district' => 'Sukarami', 'village' => 'Kebun Bunga', 'postal_code' => '30152'],
            ['city' => 'Banyuasin', 'district' => 'Talang Kelapa', 'village' => 'Sukajadi', 'postal_code' => '30961'],
            ['city' => 'Ogan Ilir', 'district' => 'Indralaya', 'village' => 'Timbangan', 'postal_code' => '30862'],
        ];
        $snapshot = $snapshots[($sequence - 1) % count($snapshots)];

        return [
            'name' => $customer->name,
            'phone' => $customer->phone ?: '081234567890',
            'address' => 'Jl. Proyek Interior No. '.(10 + $sequence).', '.$snapshot['village'],
            'province' => 'Sumatera Selatan',
            'city' => $snapshot['city'],
            'district' => $snapshot['district'],
            'village' => $snapshot['village'],
            'postal_code' => $snapshot['postal_code'],
            'district_id' => null,
        ];
    }

    private function courierCodeFor(int $sequence): string
    {
        return ['jne', 'jnt', 'sicepat', 'internal'][($sequence - 1) % 4];
    }

    private function courierNameFor(int $sequence): string
    {
        return ['JNE', 'J&T Express', 'SiCepat', 'Tim Internal CV Sarana Fittindo'][($sequence - 1) % 4];
    }

    private function shippingServiceFor(int $sequence): string
    {
        return ['REG', 'EZ', 'BEST', 'DELIVERY'][($sequence - 1) % 4];
    }

    private function shippingEtdFor(int $sequence): string
    {
        return ['1-2 hari', '2-3 hari', '3-4 hari'][($sequence - 1) % 3];
    }

    private function noteFor(int $sequence): ?string
    {
        $notes = [
            null,
            'Mohon konfirmasi sebelum pengiriman.',
            'Pengiriman diterima oleh bagian gudang.',
            'Produk untuk kebutuhan proyek interior.',
        ];

        return $notes[$sequence % count($notes)];
    }

    private function cancellationReasonFor(int $sequence): string
    {
        return [
            'Pelanggan membatalkan karena perubahan kebutuhan proyek.',
            'Pembayaran tidak dilanjutkan oleh pelanggan.',
        ][$sequence % 2];
    }
}
