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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('restrict');
            
            // PERBAIKAN: Arahkan constrained ke tabel 'user_addresses'
            $table->foreignId('address_id')->constrained('user_addresses')->onDelete('restrict');
            
            $table->string('courier', 100)->nullable()->comment('Internal driver/team');
            $table->string('tracking_number', 100)->nullable();
            $table->enum('status', ['packed', 'shipped', 'in_transit', 'delivered'])->default('packed');
            $table->date('estimated_arrival')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('received_by', 100)->nullable();
            $table->text('shipping_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
