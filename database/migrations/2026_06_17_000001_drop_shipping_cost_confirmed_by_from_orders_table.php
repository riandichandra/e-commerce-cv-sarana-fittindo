<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'shipping_cost_confirmed_by')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_cost_confirmed_by');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'shipping_cost_confirmed_by')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_cost_confirmed_by')
                ->nullable()
                ->after('shipping_cost_confirmed_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }
};
