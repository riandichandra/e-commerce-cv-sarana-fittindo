<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isMySql = Schema::getConnection()->getDriverName() === 'mysql';

        if ($isMySql) {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending_payment', 'waiting_payment_confirmation', 'payment_confirmed', 'processing', 'shipped', 'completed', 'cancelled', 'belum_dibayar', 'menunggu_verifikasi_pembayaran', 'pembayaran_dikonfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'belum_dibayar'");
            DB::statement("ALTER TABLE payments MODIFY status ENUM('pending', 'verified', 'rejected', 'menunggu', 'terverifikasi', 'ditolak') NOT NULL DEFAULT 'menunggu'");
            DB::statement("ALTER TABLE deliveries MODIFY status ENUM('packed', 'shipped', 'in_transit', 'delivered', 'dikemas', 'dikirim', 'dalam_perjalanan', 'terkirim') NOT NULL DEFAULT 'dikemas'");
        }

        $this->translateOrderStatuses();
        $this->translatePaymentStatuses();
        $this->translateDeliveryStatuses();

        if ($isMySql) {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('belum_dibayar', 'menunggu_verifikasi_pembayaran', 'pembayaran_dikonfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'belum_dibayar'");
            DB::statement("ALTER TABLE payments MODIFY status ENUM('menunggu', 'terverifikasi', 'ditolak') NOT NULL DEFAULT 'menunggu'");
            DB::statement("ALTER TABLE deliveries MODIFY status ENUM('dikemas', 'dikirim', 'dalam_perjalanan', 'terkirim') NOT NULL DEFAULT 'dikemas'");
        }
    }

    public function down(): void
    {
        $isMySql = Schema::getConnection()->getDriverName() === 'mysql';

        if ($isMySql) {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending_payment', 'waiting_payment_confirmation', 'payment_confirmed', 'processing', 'shipped', 'completed', 'cancelled', 'belum_dibayar', 'menunggu_verifikasi_pembayaran', 'pembayaran_dikonfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'pending_payment'");
            DB::statement("ALTER TABLE payments MODIFY status ENUM('pending', 'verified', 'rejected', 'menunggu', 'terverifikasi', 'ditolak') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE deliveries MODIFY status ENUM('packed', 'shipped', 'in_transit', 'delivered', 'dikemas', 'dikirim', 'dalam_perjalanan', 'terkirim') NOT NULL DEFAULT 'packed'");
        }

        $this->restoreOrderStatuses();
        $this->restorePaymentStatuses();
        $this->restoreDeliveryStatuses();

        if ($isMySql) {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending_payment', 'waiting_payment_confirmation', 'payment_confirmed', 'processing', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'pending_payment'");
            DB::statement("ALTER TABLE payments MODIFY status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE deliveries MODIFY status ENUM('packed', 'shipped', 'in_transit', 'delivered') NOT NULL DEFAULT 'packed'");
        }
    }

    private function translateOrderStatuses(): void
    {
        DB::table('orders')->where('status', 'pending_payment')->update(['status' => 'belum_dibayar']);
        DB::table('orders')->where('status', 'waiting_payment_confirmation')->update(['status' => 'menunggu_verifikasi_pembayaran']);
        DB::table('orders')->where('status', 'payment_confirmed')->update(['status' => 'pembayaran_dikonfirmasi']);
        DB::table('orders')->where('status', 'processing')->update(['status' => 'diproses']);
        DB::table('orders')->where('status', 'shipped')->update(['status' => 'dikirim']);
        DB::table('orders')->where('status', 'completed')->update(['status' => 'selesai']);
        DB::table('orders')->where('status', 'cancelled')->update(['status' => 'dibatalkan']);
    }

    private function translatePaymentStatuses(): void
    {
        DB::table('payments')->where('status', 'pending')->update(['status' => 'menunggu']);
        DB::table('payments')->where('status', 'verified')->update(['status' => 'terverifikasi']);
        DB::table('payments')->where('status', 'rejected')->update(['status' => 'ditolak']);
    }

    private function translateDeliveryStatuses(): void
    {
        DB::table('deliveries')->where('status', 'packed')->update(['status' => 'dikemas']);
        DB::table('deliveries')->where('status', 'shipped')->update(['status' => 'dikirim']);
        DB::table('deliveries')->where('status', 'in_transit')->update(['status' => 'dalam_perjalanan']);
        DB::table('deliveries')->where('status', 'delivered')->update(['status' => 'terkirim']);
    }

    private function restoreOrderStatuses(): void
    {
        DB::table('orders')->where('status', 'belum_dibayar')->update(['status' => 'pending_payment']);
        DB::table('orders')->where('status', 'menunggu_verifikasi_pembayaran')->update(['status' => 'waiting_payment_confirmation']);
        DB::table('orders')->where('status', 'pembayaran_dikonfirmasi')->update(['status' => 'payment_confirmed']);
        DB::table('orders')->where('status', 'diproses')->update(['status' => 'processing']);
        DB::table('orders')->where('status', 'dikirim')->update(['status' => 'shipped']);
        DB::table('orders')->where('status', 'selesai')->update(['status' => 'completed']);
        DB::table('orders')->where('status', 'dibatalkan')->update(['status' => 'cancelled']);
    }

    private function restorePaymentStatuses(): void
    {
        DB::table('payments')->where('status', 'menunggu')->update(['status' => 'pending']);
        DB::table('payments')->where('status', 'terverifikasi')->update(['status' => 'verified']);
        DB::table('payments')->where('status', 'ditolak')->update(['status' => 'rejected']);
    }

    private function restoreDeliveryStatuses(): void
    {
        DB::table('deliveries')->where('status', 'dikemas')->update(['status' => 'packed']);
        DB::table('deliveries')->where('status', 'dikirim')->update(['status' => 'shipped']);
        DB::table('deliveries')->where('status', 'dalam_perjalanan')->update(['status' => 'in_transit']);
        DB::table('deliveries')->where('status', 'terkirim')->update(['status' => 'delivered']);
    }
};
