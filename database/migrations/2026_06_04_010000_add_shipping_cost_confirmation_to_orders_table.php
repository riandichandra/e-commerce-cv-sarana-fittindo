<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('menunggu_konfirmasi_ongkir', 'belum_dibayar', 'menunggu_verifikasi_pembayaran', 'pembayaran_dikonfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'belum_dibayar'");
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_cost_status', 30)->default('fixed')->after('shipping_cost');
            $table->timestamp('shipping_cost_confirmed_at')->nullable()->after('shipping_cost_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_cost_status',
                'shipping_cost_confirmed_at',
            ]);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::table('orders')
                ->where('status', 'menunggu_konfirmasi_ongkir')
                ->update(['status' => 'belum_dibayar']);

            DB::statement("ALTER TABLE orders MODIFY status ENUM('belum_dibayar', 'menunggu_verifikasi_pembayaran', 'pembayaran_dikonfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'belum_dibayar'");
        }
    }
};
