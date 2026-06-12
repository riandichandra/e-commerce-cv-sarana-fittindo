<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_unused_columns_are_removed_and_business_columns_remain(): void
    {
        $this->assertFalse(Schema::hasColumn('product_brands', 'logo'));
        $this->assertFalse(Schema::hasColumn('payment_methods', 'icon'));

        $this->assertTrue(Schema::hasColumn('products', 'specifications'));
        $this->assertTrue(Schema::hasColumn('orders', 'notes'));
        $this->assertTrue(Schema::hasColumn('orders', 'shipping_rate_snapshot'));
    }

    public function test_each_order_can_only_have_one_payment(): void
    {
        $indexes = collect(Schema::getIndexes('payments'));

        $this->assertTrue(
            $indexes->contains(fn (array $index) => $index['name'] === 'payments_order_id_unique')
        );
    }
}
