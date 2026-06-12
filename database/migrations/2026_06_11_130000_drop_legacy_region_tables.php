<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_addresses')) {
            $incompleteAddressCount = DB::table('user_addresses')
                ->whereNull('region_source')
                ->orWhere('region_source', '!=', 'rajaongkir')
                ->orWhereNull('province_id')
                ->orWhere('province_id', '')
                ->orWhereNull('regency_id')
                ->orWhere('regency_id', '')
                ->orWhereNull('district_id')
                ->orWhere('district_id', '')
                ->orWhereNull('village_id')
                ->orWhere('village_id', '')
                ->orWhereNull('province_name')
                ->orWhere('province_name', '')
                ->orWhereNull('city_name')
                ->orWhere('city_name', '')
                ->orWhereNull('district_name')
                ->orWhere('district_name', '')
                ->orWhereNull('village_name')
                ->orWhere('village_name', '')
                ->count();

            if ($incompleteAddressCount > 0) {
                throw new RuntimeException(
                    'Tabel wilayah lokal tidak dapat dihapus karena masih ada alamat tanpa snapshot RajaOngkir lengkap.'
                );
            }
        }

        Schema::dropIfExists('shipping_costs');
        Schema::dropIfExists('villages');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regencies');
        Schema::dropIfExists('provinces');
    }

    public function down(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->char('id', 2)->primary();
            $table->string('name');
        });

        Schema::create('regencies', function (Blueprint $table) {
            $table->char('id', 4)->primary();
            $table->char('province_id', 2);
            $table->string('name', 50);
            $table->foreign('province_id')
                ->references('id')
                ->on('provinces')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->char('id', 7)->primary();
            $table->char('regency_id', 4);
            $table->string('name', 50);
            $table->foreign('regency_id')
                ->references('id')
                ->on('regencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::create('villages', function (Blueprint $table) {
            $table->char('id', 10)->primary();
            $table->char('district_id', 7);
            $table->string('name', 50);
            $table->foreign('district_id')
                ->references('id')
                ->on('districts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::create('shipping_costs', function (Blueprint $table) {
            $table->id();
            $table->char('province_id', 2);
            $table->char('regency_id', 4);
            $table->foreign('province_id')->references('id')->on('provinces');
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->decimal('base_cost', 10, 2);
            $table->decimal('cost_per_kg', 10, 2)->default(0);
            $table->integer('estimated_days')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['province_id', 'regency_id']);
        });
    }
};
