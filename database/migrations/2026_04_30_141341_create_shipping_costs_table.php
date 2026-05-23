<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_costs', function (Blueprint $table) {
            $table->id();

            $table->char('province_id', 2);
            $table->char('regency_id', 4);

// FOREIGN KEY
            $table->foreign('province_id')
                ->references('id')
                ->on('provinces');

            $table->foreign('regency_id')
                ->references('id')
                ->on('regencies');

            $table->decimal('base_cost', 10, 2);
            $table->decimal('cost_per_kg', 10, 2)->default(0);
            $table->integer('estimated_days')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['province_id', 'regency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_costs');
    }
};
