<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('shipped_at')->nullable()->after('received_image');
            $table->timestamp('completed_at')->nullable()->after('shipped_at');
            $table->timestamp('auto_completed_at')->nullable()->after('completed_at');
            $table->string('completion_source', 30)->nullable()->after('auto_completed_at');
            $table->text('completion_notes')->nullable()->after('completion_source');
            $table->index(['status', 'shipped_at'], 'orders_status_shipped_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_shipped_at_index');
            $table->dropColumn([
                'shipped_at',
                'completed_at',
                'auto_completed_at',
                'completion_source',
                'completion_notes',
            ]);
        });
    }
};
