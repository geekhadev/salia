<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLocationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_persists_optional_location_coordinates(): void
    {
        $order = Order::factory()->create([
            'location' => [
                'latitude' => -36.8921132,
                'longitude' => -73.1322204,
            ],
        ]);

        $order->refresh();

        $this->assertNotNull($order->location);
        $this->assertEqualsWithDelta(-36.8921132, $order->location['latitude'], 0.000001);
        $this->assertEqualsWithDelta(-73.1322204, $order->location['longitude'], 0.000001);
    }

    public function test_order_location_may_remain_null(): void
    {
        $order = Order::factory()->create(['location' => null]);

        $this->assertNull($order->fresh()->location);
    }
}
