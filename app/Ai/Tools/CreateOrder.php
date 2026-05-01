<?php

namespace App\Ai\Tools;

use App\Models\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateOrder implements Tool
{
    public function description(): Stringable|string
    {
        return 'Creates a new order with customer details and product list. Use this tool ONLY when all required information has been collected from the customer.';
    }

    public function handle(Request $request): Stringable|string
    {
        $order = Order::create([
            'full_name' => $request['full_name'],
            'phone_number' => $request['phone_number'],
            'products' => $request['products'],
            'address_state' => $request['address_state'],
            'address_city' => $request['address_city'],
            'address_neighborhood' => $request['address_neighborhood'],
            'address_street' => $request['address_street'],
        ]);

        return json_encode([
            'status' => 'success',
            'message' => 'Order created successfully.',
            'order_id' => $order->id,
            'order' => $order->toArray(),
        ], JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'full_name' => $schema->string()->required(),
            'phone_number' => $schema->string()->required(),
            'products' => $schema->array()
                ->items(
                    $schema->object(fn ($schema) => [
                        'name' => $schema->string()->required(),
                        'quantity' => $schema->integer()->required(),
                        'price' => $schema->number()->required(),
                    ])
                )
                ->required(),
            'address_state' => $schema->string()->required(),
            'address_city' => $schema->string()->required(),
            'address_neighborhood' => $schema->string()->required(),
            'address_street' => $schema->string()->required(),
        ];
    }
}
