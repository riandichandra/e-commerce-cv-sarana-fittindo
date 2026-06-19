<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('promotion_id')
                ->nullable()
                ->after('discount_amount')
                ->constrained('promotions')
                ->nullOnDelete();
            $table->string('promotion_name', 150)->nullable()->after('promotion_id');
            $table->string('promotion_type', 20)->nullable()->after('promotion_name');
            $table->decimal('promotion_value', 15, 2)->nullable()->after('promotion_type');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropColumn([
                'promotion_name',
                'promotion_type',
                'promotion_value',
            ]);
        });
    }
};
