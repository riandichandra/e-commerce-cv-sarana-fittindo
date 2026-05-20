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

        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending_payment', 'waiting_payment_confirmation', 'payment_confirmed', 'processing', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'pending_payment'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('orders')
            ->where('status', 'waiting_payment_confirmation')
            ->update(['status' => 'pending_payment']);

        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending_payment', 'payment_confirmed', 'processing', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'pending_payment'");
    }
};
