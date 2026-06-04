<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $this->seedBrands();
        $this->seedPaymentMethods();
        $products = $this->seedProducts($faker);
        $customers = $this->seedCustomers($faker);
        $this->seedOrdersAndPayments($faker, $customers, $products);
    }

    protected function seedBrands(): void
    {
        $brands = [
            'Sarana HPL',
            'Fittindo Plywood',
            'Prime Laminate',
            'Ultra Adhesives',
            'Sarana Woodworks',
        ];

        foreach ($brands as $brand) {
            ProductBrand::firstOrCreate([
                'name' => $brand,
            ], [
                'description' => "Brand resmi $brand untuk material finishing.",
                'is_active' => true,
            ]);
        }
    }

    protected function seedPaymentMethods(): void
    {
        $methods = [
            [
                'name' => 'Bank Transfer BCA',
                'code' => 'bca',
                'account_number' => '1234567890',
                'account_name' => 'CV Sarana Fittindo',
                'bank_name' => 'BCA',
                'instructions' => 'Transfer ke rekening BCA kami dan upload bukti pembayaran.',
                'icon' => 'mdi:bank',
            ],
            [
                'name' => 'Bank Transfer Mandiri',
                'code' => 'mandiri',
                'account_number' => '0987654321',
                'account_name' => 'CV Sarana Fittindo',
                'bank_name' => 'Mandiri',
                'instructions' => 'Transfer ke rekening Mandiri kami dan upload bukti pembayaran.',
                'icon' => 'mdi:bank',
            ],
            [
                'name' => 'e-Wallet OVO',
                'code' => 'ovo',
                'account_number' => '081234567890',
                'account_name' => 'CV Sarana Fittindo',
                'bank_name' => 'OVO',
                'instructions' => 'Bayar menggunakan aplikasi OVO dengan kode merchant kami.',
                'icon' => 'mdi:wallet',
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate([
                'code' => $method['code'],
            ], array_merge($method, [
                'is_active' => true,
                'sort_order' => 0,
            ]));
        }
    }

    protected function seedProducts($faker)
    {
        $categories = ProductCategory::pluck('id', 'slug')->toArray();
        $brands = ProductBrand::where('is_active', true)->pluck('id', 'name')->toArray();

        $items = [
            ['name' => 'HPL Wood Grain Walnut', 'slug' => 'hpl-wood-grain-walnut', 'category' => 'hpl', 'brand' => 'Sarana HPL', 'price' => 325000],
            ['name' => 'HPL Concrete Matte', 'slug' => 'hpl-concrete-matte', 'category' => 'hpl', 'brand' => 'Sarana HPL', 'price' => 295000],
            ['name' => 'Plywood Marine Grade 18mm', 'slug' => 'plywood-marine-grade-18mm', 'category' => 'plywood', 'brand' => 'Fittindo Plywood', 'price' => 450000],
            ['name' => 'Plywood Meranti 12mm', 'slug' => 'plywood-meranti-12mm', 'category' => 'plywood', 'brand' => 'Fittindo Plywood', 'price' => 375000],
            ['name' => 'Laminate Oak Texture', 'slug' => 'laminate-oak-texture', 'category' => 'laminate', 'brand' => 'Prime Laminate', 'price' => 220000],
            ['name' => 'Laminate Ash Stone', 'slug' => 'laminate-ash-stone', 'category' => 'laminate', 'brand' => 'Prime Laminate', 'price' => 235000],
            ['name' => 'Contact Adhesive 1L', 'slug' => 'contact-adhesive-1l', 'category' => 'adhesives', 'brand' => 'Ultra Adhesives', 'price' => 88000],
            ['name' => 'Super Glue Sealant', 'slug' => 'super-glue-sealant', 'category' => 'adhesives', 'brand' => 'Ultra Adhesives', 'price' => 76000],
        ];

        $products = collect();

        foreach ($items as $item) {
            $products->push(Product::updateOrCreate([
                'slug' => $item['slug'],
            ], [
                'category_id' => $categories[$item['category']] ?? null,
                'brand_id' => $brands[$item['brand']] ?? null,
                'name' => $item['name'],
                'description' => $faker->sentence(12),
                'price' => $item['price'],
                'stock' => true,
                'status' => Product::STATUS_AVAILABLE,
                'weight' => $faker->randomFloat(2, 800, 3500),
                'thickness' => $faker->randomElement(['12mm', '15mm', '18mm', '25mm']),
                'dimensions' => $faker->randomElement(['120x240 cm', '90x180 cm', '122x244 cm']),
                'specifications' => [
                    'surface' => $faker->randomElement(['glossy', 'matte', 'textured']),
                    'usage' => $faker->randomElement(['indoor', 'outdoor', 'commercial']),
                ],
                'is_featured' => $faker->boolean(20),
                'is_active' => true,
            ]));
        }

        return $products;
    }

    protected function seedCustomers($faker)
    {
        $customers = collect();

        foreach (range(1, 60) as $index) {
            $user = User::factory()->create([
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'phone' => $faker->phoneNumber(),
                'is_active' => true,
                'email_verified_at' => $faker->boolean(90) ? now() : null,
            ]);

            $user->assignRole('pelanggan');
            $customers->push($user);
        }

        return $customers;
    }

    protected function seedOrdersAndPayments($faker, $customers, $products): void
    {
        $paymentMethodIds = PaymentMethod::where('is_active', true)->pluck('id')->toArray();
        $verifiers = User::role('admin')->get()->pluck('id')->toArray();

        $months = collect(range(5, 0))->map(fn ($monthAgo) => now()->subMonths($monthAgo));

        foreach ($months as $month) {
            $orderCount = $faker->numberBetween(20, 30);

            foreach (range(1, $orderCount) as $sequence) {
                $createdAt = $faker->dateTimeBetween($month->copy()->startOfMonth(), $month->copy()->endOfMonth());
                $customer = $customers->random();
                $selectedProducts = $products->random($faker->numberBetween(1, 3));
                $items = collect();
                $subtotal = 0;

                foreach ($selectedProducts as $product) {
                    $quantity = $faker->numberBetween(1, 4);
                    $productSubtotal = $product->price * $quantity;

                    $items->push([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_price' => $product->price,
                        'quantity' => $quantity,
                        'subtotal' => $productSubtotal,
                    ]);

                    $subtotal += $productSubtotal;
                }

                $discount = $faker->boolean(25) ? round($subtotal * $faker->randomFloat(2, 0.03, 0.12), 2) : 0;
                $shipping = $faker->randomElement([15000, 25000, 30000]);
                $total = max(0, $subtotal - $discount + $shipping);

                $status = $this->pickOrderStatus($faker);
                $cancelledAt = null;
                $cancellationReason = null;

                if ($status === 'dibatalkan') {
                    $cancelledAt = now()->createFromTimestamp($createdAt->getTimestamp())->addDays($faker->numberBetween(1, 3));
                    $cancellationReason = $faker->randomElement([
                        'Pelanggan mengubah pesanan',
                        'Stok produk habis',
                        'Pembayaran tidak diterima tepat waktu',
                    ]);
                }

                $paymentMethodId = Arr::random($paymentMethodIds);
                $orderNumber = $this->generateOrderNumber($createdAt);

                $order = Order::create([
                    'user_id' => $customer->id,
                    'order_number' => $orderNumber,
                    'status' => $status,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'shipping_cost' => $shipping,
                    'total_amount' => $total,
                    'payment_method_id' => $paymentMethodId,
                    'shipping_name' => $customer->name,
                    'shipping_phone' => $customer->phone,
                    'shipping_address' => $faker->address(),
                    'shipping_province' => $faker->state(),
                    'shipping_city' => $faker->city(),
                    'shipping_district' => $faker->citySuffix(),
                    'shipping_village' => $faker->word(),
                    'shipping_postal_code' => $faker->postcode(),
                    'notes' => $faker->boolean(30) ? $faker->sentence() : null,
                    'cancelled_by' => $status === 'dibatalkan' ? Arr::random($verifiers) : null,
                    'cancellation_reason' => $cancellationReason,
                    'cancelled_at' => $cancelledAt,
                ]);

                $order->timestamps = false;
                $order->created_at = $createdAt;
                $order->updated_at = $createdAt;
                $order->save();

                foreach ($items as $item) {
                    $orderItem = OrderItem::create(array_merge($item, [
                        'order_id' => $order->id,
                    ]));
                    $orderItem->timestamps = false;
                    $orderItem->created_at = $createdAt;
                    $orderItem->updated_at = $createdAt;
                    $orderItem->save();
                }

                $paymentStatus = $this->determinePaymentStatus($status, $faker);
                $verifiedAt = null;
                $verifiedBy = null;

                if ($paymentStatus === 'terverifikasi') {
                    $verifiedAt = now()->createFromTimestamp($createdAt->getTimestamp())->addDays($faker->numberBetween(1, 3));
                    $verifiedBy = Arr::random($verifiers);
                }

                $payment = Payment::create([
                    'order_id' => $order->id,
                    'payment_method_id' => $paymentMethodId,
                    'amount' => $total,
                    'proof_image' => null,
                    'transfer_date' => $createdAt->format('Y-m-d'),
                    'sender_name' => $customer->name,
                    'status' => $paymentStatus,
                    'verified_by' => $verifiedBy,
                    'verified_at' => $verifiedAt,
                    'notes' => $paymentStatus === 'ditolak' ? 'Bukti transfer tidak sesuai.' : null,
                ]);

                $payment->timestamps = false;
                $payment->created_at = $createdAt;
                $payment->updated_at = $createdAt;
                $payment->save();
            }
        }
    }

    protected function pickOrderStatus($faker): string
    {
        $chance = $faker->numberBetween(1, 100);

        return match (true) {
            $chance <= 50 => 'selesai',
            $chance <= 70 => $faker->randomElement(['dikirim', 'diproses']),
            $chance <= 80 => 'pembayaran_dikonfirmasi',
            $chance <= 90 => 'menunggu_verifikasi_pembayaran',
            $chance <= 95 => 'belum_dibayar',
            default => 'dibatalkan',
        };
    }

    protected function determinePaymentStatus(string $orderStatus, $faker): string
    {
        return match ($orderStatus) {
            'selesai', 'dikirim', 'diproses', 'pembayaran_dikonfirmasi' => 'terverifikasi',
            'menunggu_verifikasi_pembayaran' => 'menunggu',
            'belum_dibayar' => $faker->boolean(70) ? 'menunggu' : 'ditolak',
            'dibatalkan' => $faker->boolean(40) ? 'menunggu' : 'ditolak',
            default => 'menunggu',
        };
    }

    protected function generateOrderNumber($date): string
    {
        return 'SF' . $date->format('Ymd') . Str::upper(Str::random(6));
    }
}
