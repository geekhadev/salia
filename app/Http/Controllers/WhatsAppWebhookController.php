<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use App\Support\TwilioWhatsAppPayload;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsappService
    ) {}

    public function handle(Request $request)
    {
        $from = TwilioWhatsAppPayload::senderAddress($request);
        $body = TwilioWhatsAppPayload::messageBody($request);

        if ($from === null || $from === '') {
            return response('Missing sender (From or WaId)', 400);
        }

        $response = $this->whatsappService->processMessage($from, $body);

        $this->whatsappService->sendMessage($from, $response);

        return response('', 204);
    }
}
