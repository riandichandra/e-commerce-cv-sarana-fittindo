<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasColumn('product_brands', 'logo')
            && DB::table('product_brands')->whereNotNull('logo')->exists()
        ) {
            throw new RuntimeException(
                'Kolom product_brands.logo masih memiliki data dan tidak dapat dihapus.'
            );
        }

        if (
            Schema::hasTable('payments')
            && DB::table('payments')
                ->select('order_id')
                ->groupBy('order_id')
                ->havingRaw('COUNT(*) > 1')
                ->exists()
        ) {
            throw new RuntimeException(
                'Unique index payments.order_id tidak dapat dibuat karena masih ada pembayaran ganda.'
            );
        }

        Schema::table('product_brands', function (Blueprint $table) {
            $table->dropColumn('logo');
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('icon');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_order_number_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unique('order_id');
        });

        if ($this->hasIndex('payments', 'payments_order_id_foreign')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('payments_order_id_foreign');
            });
        }
    }

    public function down(): void
    {
        if (! $this->hasIndex('payments', 'payments_order_id_foreign')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('order_id', 'payments_order_id_foreign');
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_order_id_unique');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('order_number');
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('icon', 255)->nullable()->after('instructions');
        });

        Schema::table('product_brands', function (Blueprint $table) {
            $table->string('logo', 255)->nullable()->after('description');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $definition) => $definition['name'] === $index);
    }
};
