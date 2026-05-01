<?php

namespace App\Services;

use App\Ai\Agents\SalesAgent;
use App\Models\Order;
use Twilio\Rest\Client;

class WhatsAppService
{
    protected Client $twilio;
    protected string $fromNumber;

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );
        $this->fromNumber = 'whatsapp:' . config('services.twilio.whatsapp_number');
    }

    public function sendMessage(string $to, string $message): void
    {
        $this->twilio->messages->create(
            'whatsapp:' . $to,
            [
                'from' => $this->fromNumber,
                'body' => $message,
            ]
        );
    }

    public function processMessage(string $from, string $message): string
    {
        $agent = new SalesAgent;

        $user = (object) ['id' => $from];

        $agent->continueLastConversation($user);

        $response = $agent->prompt($message);

        $lastOrder = $this->getLastOrderForSession($from);

        return $response->content();
    }

    protected function getLastOrderForSession(string $from): ?Order
    {
        return Order::where('phone_number', $from)
            ->latest()
            ->first();
    }

    public function closeOrder(int $orderId): Order
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => Order::STATUS_CLOSED]);

        return $order;
    }

    public function confirmOrder(int $orderId): Order
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => Order::STATUS_CONFIRMED]);

        return $order;
    }
}
