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
        $this->fromNumber = 'whatsapp:'.config('services.twilio.whatsapp_number');
    }

    public function sendMessage(string $to, string $message): void
    {
        $toAddress = $this->normalizeWhatsAppRecipient($to);

        $this->twilio->messages->create(
            $toAddress,
            [
                'from' => $this->fromNumber,
                'body' => $message,
            ]
        );
    }

    /**
     * Twilio inbound `From` values look like `whatsapp:+E164`; avoid duplicating the `whatsapp:` prefix.
     */
    protected function normalizeWhatsAppRecipient(string $to): string
    {
        if (str_starts_with($to, 'whatsapp:')) {
            return $to;
        }

        $digits = preg_replace('/\D+/', '', $to) ?? '';

        return 'whatsapp:+'.ltrim($digits, '+');
    }

    public function processMessage(string $from, string $message): string
    {
        $agent = new SalesAgent;

        $user = (object) ['id' => $from];

        $agent->continueLastConversation($user);

        $response = $agent->prompt($message);

        return $response->text;
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
