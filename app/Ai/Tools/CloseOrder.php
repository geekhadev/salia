<?php

namespace App\Ai\Tools;

use App\Models\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CloseOrder implements Tool
{
    public function description(): Stringable|string
    {
        return 'Closes and confirms an order after the customer has provided all required information. Use this tool when the customer confirms they want to finalize their order.';
    }

    public function handle(Request $request): Stringable|string
    {
        $order = Order::findOrFail($request['order_id']);

        if ($order->status === Order::STATUS_CLOSED) {
            return json_encode([
                'status' => 'error',
                'message' => 'Order is already closed.',
                'order_id' => $order->id,
            ], JSON_PRETTY_PRINT);
        }

        $order->update([
            'status' => Order::STATUS_CLOSED,
        ]);

        return json_encode([
            'status' => 'success',
            'message' => 'Order closed successfully. The customer will receive their order soon.',
            'order_id' => $order->id,
            'order' => $order->toArray(),
        ], JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_id' => $schema->integer()->required(),
        ];
    }
}
