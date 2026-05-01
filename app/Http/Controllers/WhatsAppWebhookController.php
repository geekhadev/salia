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
        $customerPhone = TwilioWhatsAppPayload::customerPhoneE164($request);
        $type = TwilioWhatsAppPayload::effectiveMessageType($request);

        if ($from === null || $from === '') {
            return response('Missing sender (From or WaId)', 400);
        }

        if ($type === 'unsupported_media') {
            $this->whatsappService->sendMessage(
                $from,
                'Por ahora solo puedo leer mensajes de texto, notas de voz y ubicaciones compartidas. ¿Podría escribirnos o enviar un audio?'
            );

            return response('', 204);
        }

        try {
            $body = $this->whatsappService->inboundTextForAgent($request);
        } catch (\Throwable) {
            $this->whatsappService->sendMessage(
                $from,
                'No pude entender el audio. ¿Podría intentarlo de nuevo o escribir su mensaje?'
            );

            return response('', 204);
        }

        if ($type === 'text' && trim($body) === '') {
            return response('', 204);
        }

        if ($type === 'audio' && trim($body) === '') {
            $this->whatsappService->sendMessage(
                $from,
                'El audio quedó vacío tras transcribirlo. ¿Podría repetirlo un poco más cerca del micrófono?'
            );

            return response('', 204);
        }

        $response = $this->whatsappService->processMessage($from, $body, $customerPhone);

        $this->whatsappService->sendMessage($from, $response);

        return response('', 204);
    }
}
