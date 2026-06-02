<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock_quantity')->default(0)->after('price');
            $table->enum('status', ['tersedia', 'tidak tersedia'])->default('tidak tersedia')->after('stock_quantity');
        });

        DB::table('products')->update([
            'stock_quantity' => DB::raw('CASE WHEN stock = 1 THEN 1 ELSE 0 END'),
            'status' => DB::raw('CASE WHEN stock = 1 THEN \'tersedia\' ELSE \'tidak tersedia\' END'),
        ]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stock');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock')->default(0)->after('price');
        });

        DB::table('products')->update([
            'stock' => DB::raw('stock_quantity'),
        ]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stock_quantity');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock_quantity')->default(0)->after('price');
        });

        DB::table('products')->update([
            'stock_quantity' => DB::raw('stock'),
        ]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stock');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('stock')->default(true)->comment('1 tersedia, 0 tidak tersedia')->after('price');
        });

        DB::table('products')->update([
            'stock' => DB::raw('CASE WHEN stock_quantity > 0 THEN 1 ELSE 0 END'),
        ]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stock_quantity');
        });
    }
};
