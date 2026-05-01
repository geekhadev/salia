<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone_number' => '+56'.fake()->numerify('#########'),
            'products' => [
                [
                    'name' => 'Hamburguesa clásica',
                    'quantity' => 1,
                    'price' => 5990,
                ],
            ],
            'status' => Order::STATUS_PENDING,
            'address_state' => 'Región',
            'address_city' => 'Ciudad',
            'address_neighborhood' => 'Barrio',
            'address_street' => fake()->streetAddress(),
            'location' => null,
        ];
    }
}
