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
        return 'Creates a new order in the system with shipping data and line items. Call ONLY after you have every required field from the customer (full name, phone, address parts, confirmed products with quantities/prices—typically from ListProducts). Do not call for simple menu or price questions.';
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
