<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'Transfer Bank BCA',
                'code' => 'bca',
                'account_number' => '1234567890',
                'account_name' => 'CV Sarana Fittindo',
                'bank_name' => 'BCA',
                'instructions' => 'Transfer ke rekening BCA, lalu upload bukti pembayaran pada halaman pesanan.',
                'icon' => 'mdi:bank',
                'sort_order' => 1,
            ],
            [
                'name' => 'Transfer Bank Mandiri',
                'code' => 'mandiri',
                'account_number' => '0987654321',
                'account_name' => 'CV Sarana Fittindo',
                'bank_name' => 'Mandiri',
                'instructions' => 'Transfer ke rekening Mandiri, lalu upload bukti pembayaran pada halaman pesanan.',
                'icon' => 'mdi:bank',
                'sort_order' => 2,
            ],
            [
                'name' => 'Transfer Bank BRI',
                'code' => 'bri',
                'account_number' => '1122334455',
                'account_name' => 'CV Sarana Fittindo',
                'bank_name' => 'BRI',
                'instructions' => 'Transfer ke rekening BRI, lalu upload bukti pembayaran pada halaman pesanan.',
                'icon' => 'mdi:bank',
                'sort_order' => 3,
            ],
            [
                'name' => 'Transfer Bank BNI',
                'code' => 'bni',
                'account_number' => '5566778899',
                'account_name' => 'CV Sarana Fittindo',
                'bank_name' => 'BNI',
                'instructions' => 'Transfer ke rekening BNI, lalu upload bukti pembayaran pada halaman pesanan.',
                'icon' => 'mdi:bank',
                'sort_order' => 4,
            ],
        ];

        foreach ($paymentMethods as $paymentMethod) {
            PaymentMethod::updateOrCreate(
                ['code' => $paymentMethod['code']],
                array_merge($paymentMethod, ['is_active' => true])
            );
        }
    }
}
