<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('deliveries');
    }

    public function down(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('address_id')->constrained('user_addresses')->restrictOnDelete();
            $table->string('courier', 100)->nullable()->comment('Internal driver/team');
            $table->string('tracking_number', 100)->nullable();
            $table->enum('status', ['dikemas', 'dikirim', 'dalam_perjalanan', 'terkirim'])->default('dikemas');
            $table->date('estimated_arrival')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('received_by', 100)->nullable();
            $table->text('shipping_notes')->nullable();
            $table->timestamps();
        });
    }
};
