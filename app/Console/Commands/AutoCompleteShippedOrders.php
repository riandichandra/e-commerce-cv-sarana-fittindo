<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class AutoCompleteShippedOrders extends Command
{
    protected $signature = 'orders:auto-complete-shipped';

    protected $description = 'Automatically complete shipped orders after 3 days.';

    public function handle(): int
    {
        $completedCount = 0;
        $completedAt = now();

        Order::where('status', 'dikirim')
            ->whereNotNull('shipped_at')
            ->where('shipped_at', '<=', now()->subDays(3))
            ->whereNull('completed_at')
            ->chunkById(100, function ($orders) use (&$completedCount, $completedAt): void {
                foreach ($orders as $order) {
                    $order->update([
                        'status' => 'selesai',
                        'completed_at' => $completedAt,
                        'auto_completed_at' => $completedAt,
                        'completion_source' => 'system',
                        'completion_notes' => 'Otomatis selesai setelah 3 hari sejak dikirim.',
                    ]);

                    $completedCount++;
                }
            });

        $this->info("{$completedCount} pesanan dikirim otomatis diselesaikan.");

        return self::SUCCESS;
    }
}
