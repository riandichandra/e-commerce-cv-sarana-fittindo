<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('stock_restored_at')->nullable()->after('received_image');
            $table->foreignId('stock_restored_by')
                ->nullable()
                ->after('stock_restored_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_restored_by');
            $table->dropColumn('stock_restored_at');
        });
    }
};
