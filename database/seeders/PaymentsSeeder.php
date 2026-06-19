<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class PaymentsSeeder extends Seeder
{
    private const SEED_PREFIX = 'SFDM';

    public function run(): void
    {
        $adminIds = User::role('admin')
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id');

        if ($adminIds->isEmpty()) {
            $this->command?->warn('PaymentsSeeder dihentikan: tidak ditemukan user aktif dengan role admin.');

            return;
        }

        $paymentMethodIds = PaymentMethod::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id');

        if ($paymentMethodIds->isEmpty()) {
            $this->command?->warn('PaymentsSeeder dihentikan: tidak ditemukan metode pembayaran aktif.');

            return;
        }

        $orders = Order::query()
            ->with('payment')
            ->where('order_number', 'like', self::SEED_PREFIX.'%')
            ->where('status', '!=', 'belum_dibayar')
            ->whereDoesntHave('payment')
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            $this->command?->info('Tidak ada order dummy baru yang membutuhkan payment.');

            return;
        }

        foreach ($orders as $order) {
            $sequence = $this->sequenceFromOrderNumber($order->order_number);
            $paymentStatus = $this->paymentStatusFor($order->status, $sequence);
            $verifiedAt = $paymentStatus === 'terverifikasi'
                ? $order->created_at->copy()->addDay()
                : null;
            $verifiedBy = $paymentStatus === 'terverifikasi'
                ? $this->auditUserFor($adminIds, $sequence)
                : null;

            $payment = new Payment([
                'order_id' => $order->id,
                'payment_method_id' => $order->payment_method_id ?: $this->paymentMethodFor($paymentMethodIds, $sequence),
                'amount' => $order->total_amount,
                'proof_image' => $paymentStatus === 'menunggu' || $paymentStatus === 'terverifikasi'
                    ? 'payment-proofs/dummy-transfer-'.$order->order_number.'.jpg'
                    : null,
                'transfer_date' => $order->created_at->copy()->toDateString(),
                'sender_name' => $order->shipping_name,
                'status' => $paymentStatus,
                'verified_by' => $verifiedBy,
                'verified_at' => $verifiedAt,
                'rejection_reason' => $paymentStatus === 'ditolak' ? 'Bukti pembayaran tidak valid.' : null,
                'notes' => $this->noteFor($paymentStatus),
            ]);

            $payment->created_at = $order->created_at->copy()->addHours(2);
            $payment->updated_at = $verifiedAt ?: $payment->created_at;
            $payment->save();
        }

        $this->command?->info('Payment dummy ditambahkan untuk '.$orders->count().' order.');
    }

    private function sequenceFromOrderNumber(string $orderNumber): int
    {
        return max(1, (int) substr($orderNumber, -3));
    }

    private function paymentStatusFor(string $orderStatus, int $sequence): string
    {
        return match ($orderStatus) {
            'menunggu_verifikasi_pembayaran' => 'menunggu',
            'diproses', 'dikirim', 'selesai' => 'terverifikasi',
            'dibatalkan' => $sequence % 2 === 0 ? 'ditolak' : 'menunggu',
            default => 'menunggu',
        };
    }

    private function auditUserFor(Collection $adminIds, int $sequence): int
    {
        return $adminIds->values()[($sequence - 1) % $adminIds->count()];
    }

    private function paymentMethodFor(Collection $paymentMethodIds, int $sequence): int
    {
        return $paymentMethodIds->values()[($sequence - 1) % $paymentMethodIds->count()];
    }

    private function noteFor(string $paymentStatus): ?string
    {
        return match ($paymentStatus) {
            'menunggu' => 'Menunggu verifikasi pembayaran admin.',
            'terverifikasi' => 'Pembayaran sudah diverifikasi admin.',
            'ditolak' => 'Pembayaran ditolak karena bukti tidak sesuai.',
            default => null,
        };
    }
}
