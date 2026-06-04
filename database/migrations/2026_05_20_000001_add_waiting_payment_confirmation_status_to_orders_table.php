<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY status ENUM('belum_dibayar', 'menunggu_verifikasi_pembayaran', 'pembayaran_dikonfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'belum_dibayar'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('orders')
            ->where('status', 'menunggu_verifikasi_pembayaran')
            ->update(['status' => 'belum_dibayar']);

        DB::statement("ALTER TABLE orders MODIFY status ENUM('belum_dibayar', 'pembayaran_dikonfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'belum_dibayar'");
    }
};
