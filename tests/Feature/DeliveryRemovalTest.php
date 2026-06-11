<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeliveryRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_obsolete_deliveries_table_is_not_present(): void
    {
        $this->assertFalse(Schema::hasTable('deliveries'));
        $this->assertTrue(Schema::hasTable('products'));
        $this->assertTrue(Schema::hasTable('order_items'));
    }
}
