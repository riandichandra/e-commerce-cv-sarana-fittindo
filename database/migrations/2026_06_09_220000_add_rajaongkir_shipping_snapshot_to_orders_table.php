<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_cost_source', 30)->nullable()->after('shipping_cost_status');
            $table->string('shipping_origin_district_id', 32)->nullable()->after('shipping_cost_source');
            $table->string('shipping_destination_district_id', 32)->nullable()->after('shipping_origin_district_id');
            $table->unsignedInteger('shipping_weight_gram')->nullable()->after('shipping_destination_district_id');
            $table->string('shipping_courier_code', 30)->nullable()->after('shipping_weight_gram');
            $table->string('shipping_courier_name', 100)->nullable()->after('shipping_courier_code');
            $table->string('shipping_service', 100)->nullable()->after('shipping_courier_name');
            $table->string('shipping_service_description', 150)->nullable()->after('shipping_service');
            $table->string('shipping_etd', 50)->nullable()->after('shipping_service_description');
            $table->json('shipping_rate_snapshot')->nullable()->after('shipping_etd');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_cost_source',
                'shipping_origin_district_id',
                'shipping_destination_district_id',
                'shipping_weight_gram',
                'shipping_courier_code',
                'shipping_courier_name',
                'shipping_service',
                'shipping_service_description',
                'shipping_etd',
                'shipping_rate_snapshot',
            ]);
        });
    }
};
