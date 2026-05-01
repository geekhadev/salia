<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Parses Twilio inbound WhatsApp webhook input.
 *
 * Some reverse proxies / WAFs strip the form field `From` because it collides
 * with the HTTP "From" concept. Twilio still sends `WaId` for WhatsApp messages.
 */
final class TwilioWhatsAppPayload
{
    /**
     * E.164-ish address for the sender, e.g. {@code whatsapp:+15551234567}.
     */
    public static function senderAddress(Request $request): ?string
    {
        $from = self::nonEmptyStringFromBags($request, 'From');
        if ($from !== null) {
            return $from;
        }

        $waId = self::nonEmptyStringFromBags($request, 'WaId');
        if ($waId !== null) {
            $fromWaId = self::whatsappAddressFromWaId($waId);
            if ($fromWaId !== null) {
                return $fromWaId;
            }
        }

        $parsed = self::parsedUrlEncodedBody($request);
        if ($parsed !== []) {
            if (! empty($parsed['From']) && is_string($parsed['From'])) {
                return $parsed['From'];
            }
            if (! empty($parsed['WaId']) && is_string($parsed['WaId'])) {
                $fromParsed = self::whatsappAddressFromWaId($parsed['WaId']);

                if ($fromParsed !== null) {
                    return $fromParsed;
                }
            }
        }

        return null;
    }

    public static function messageBody(Request $request): string
    {
        if ($request->request->has('Body')) {
            $value = $request->request->get('Body');

            return is_string($value) ? $value : '';
        }

        if ($request->query->has('Body')) {
            $value = $request->query->get('Body');

            return is_string($value) ? $value : '';
        }

        $parsed = self::parsedUrlEncodedBody($request);

        return isset($parsed['Body']) && is_string($parsed['Body']) ? $parsed['Body'] : '';
    }

    private static function nonEmptyStringFromBags(Request $request, string $key): ?string
    {
        if ($request->request->has($key)) {
            $value = $request->request->get($key);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        if ($request->query->has($key)) {
            $value = $request->query->get($key);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private static function parsedUrlEncodedBody(Request $request): array
    {
        $content = $request->getContent();
        if ($content === '') {
            return [];
        }

        parse_str($content, $parsed);

        return is_array($parsed) ? $parsed : [];
    }

    private static function whatsappAddressFromWaId(string $waId): ?string
    {
        $digits = preg_replace('/\D+/', '', $waId) ?? '';

        return $digits === '' ? null : 'whatsapp:+'.$digits;
    }
}
