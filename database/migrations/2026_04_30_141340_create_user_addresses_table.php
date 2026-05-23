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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('label', 50)->comment('Rumah, Kantor, dll');
            $table->string('receiver_name', 100);
            $table->string('receiver_phone', 20);
            $table->text('full_address');

            $table->char('province_id', 2);
            $table->char('regency_id', 4);
            $table->char('district_id', 7);
            $table->char('village_id', 10);

            // FOREIGN KEY
            $table->foreign('province_id')
                ->references('id')
                ->on('provinces');

            $table->foreign('regency_id')
                ->references('id')
                ->on('regencies');

            $table->foreign('district_id')
                ->references('id')
                ->on('districts');

            $table->foreign('village_id')
                ->references('id')
                ->on('villages');

            $table->string('postal_code', 10);
            $table->boolean('is_main')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
