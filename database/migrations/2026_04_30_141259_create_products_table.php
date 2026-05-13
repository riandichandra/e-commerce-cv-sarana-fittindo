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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // PERBAIKAN: Tambahkan nama tabel 'product_categories' di dalam constrained()
            $table->foreignId('category_id')->constrained('product_categories')->onDelete('restrict');
            
            // PERBAIKAN: Tambahkan nama tabel 'product_brands' di dalam constrained()
            $table->foreignId('brand_id')->nullable()->constrained('product_brands')->onDelete('set null');
            
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->integer('stock')->default(0);
            $table->decimal('weight', 8, 2)->comment('gram');
            $table->string('thickness', 50)->nullable();
            $table->string('dimensions', 100)->nullable();
            $table->json('specifications')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('is_featured');
            $table->index('is_active');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
