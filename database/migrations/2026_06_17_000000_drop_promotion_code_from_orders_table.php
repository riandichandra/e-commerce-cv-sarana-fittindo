<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'promotion_code')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('promotion_code');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'promotion_code')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('promotion_code', 50)->nullable()->after('promotion_id');
        });
    }
};
