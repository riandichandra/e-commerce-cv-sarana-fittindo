<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class OrderItemsSeeder extends Seeder
{
    private const SEED_PREFIX = 'SFDM';

    public function run(): void
    {
        $products = Product::query()
            ->where('is_active', true)
            ->where('status', Product::STATUS_AVAILABLE)
            ->where('stock', '>', 0)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'name', 'price', 'weight']);

        if ($products->isEmpty()) {
            $this->command?->warn('OrderItemsSeeder dihentikan: tidak ditemukan produk aktif dan tersedia.');

            return;
        }

        $orders = Order::query()
            ->where('order_number', 'like', self::SEED_PREFIX.'%')
            ->whereDoesntHave('items')
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            $this->command?->info('Tidak ada order dummy baru yang membutuhkan order item.');

            return;
        }

        foreach ($orders as $order) {
            $sequence = $this->sequenceFromOrderNumber($order->order_number);
            $items = $this->plannedItems($products, $sequence);

            foreach ($items as $item) {
                $orderItem = new OrderItem([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_price' => $item['product_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);

                $orderItem->created_at = $order->created_at;
                $orderItem->updated_at = $order->created_at;
                $orderItem->save();
            }
        }

        $this->command?->info('Order item dummy ditambahkan untuk '.$orders->count().' order.');
    }

    private function sequenceFromOrderNumber(string $orderNumber): int
    {
        return max(1, (int) substr($orderNumber, -3));
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

            $items->push([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_price' => $price,
                'quantity' => $quantity,
                'subtotal' => $price * $quantity,
            ]);
        }

        return $items;
    }
}
