<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Twilio\Security\RequestMethodValidator;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsappService
    ) {
    }

    public function handle(Request $request)
    {
        $from = $request->input('From');
        $body = $request->input('Body', '');

        if (!$from) {
            return response('Missing From parameter', 400);
        }

        $response = $this->whatsappService->processMessage($from, $body);

        $this->whatsappService->sendMessage($from, $response);

        return response('', 204);
    }
}
