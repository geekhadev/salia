<?php

namespace App\Services;

use App\Ai\Agents\SalesAgent;
use App\Models\Order;
use App\Support\TwilioWhatsAppPayload;
use Illuminate\Http\Request;
use Twilio\Rest\Client;

class WhatsAppService
{
    protected Client $twilio;

    protected string $fromNumber;

    public function __construct(
        protected TwilioWhatsAppAudioTranscriptionService $audioTranscription,
    ) {
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

    /**
     * Build user-visible text for the agent from an inbound webhook (text, transcribed audio, or location pin).
     */
    public function inboundTextForAgent(Request $request): string
    {
        return match (TwilioWhatsAppPayload::effectiveMessageType($request)) {
            'audio' => $this->transcribeInboundAudio($request),
            'location' => TwilioWhatsAppPayload::locationAsAgentPrompt($request),
            default => TwilioWhatsAppPayload::messageBody($request),
        };
    }

    /**
     * @throws \RuntimeException
     */
    public function transcribeInboundAudio(Request $request): string
    {
        $url = TwilioWhatsAppPayload::firstMediaUrl($request);
        if ($url === null) {
            throw new \RuntimeException('Audio message missing MediaUrl0.');
        }

        $mime = TwilioWhatsAppPayload::firstMediaContentType($request);

        return $this->audioTranscription->transcribeHostedMedia($url, $mime);
    }

    public function processMessage(string $from, string $message, ?string $customerPhoneE164 = null): string
    {
        $agent = new SalesAgent;

        $user = (object) ['id' => $from];

        $agent->continueLastConversation($user);

        $prompt = $message;
        if ($customerPhoneE164 !== null && $customerPhoneE164 !== '') {
            $prompt = <<<TXT
[Datos automáticos del chat de WhatsApp]
Número de teléfono del cliente en este chat: {$customerPhoneE164}
Úselo como \`phone_number\` en CreateOrder después de confirmar con el cliente que es correcto (si ya lo confirmó verbalmente igual a este número, no vuelva a pedírselo).

{$message}
TXT;
        }

        $response = $agent->prompt($prompt);

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
