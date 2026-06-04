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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->string('order_number', 50)->unique();
            $table->enum('status', [
                'belum_dibayar',
                'menunggu_verifikasi_pembayaran',
                'pembayaran_dikonfirmasi',
                'diproses',
                'dikirim',
                'selesai',
                'dibatalkan'
            ])->default('belum_dibayar');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');
            
            // Shipping address fields (denormalized)
            $table->string('shipping_name', 100);
            $table->string('shipping_phone', 20);
            $table->text('shipping_address');
            $table->string('shipping_province', 100);
            $table->string('shipping_city', 100);
            $table->string('shipping_district', 100);
            $table->string('shipping_village', 100)->nullable();
            $table->string('shipping_postal_code', 10)->nullable();
            
            $table->text('notes')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('order_number');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
