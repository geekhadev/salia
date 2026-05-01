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
        $from = self::nonEmptyString($request, 'From');
        if ($from !== null) {
            return $from;
        }

        $waId = self::nonEmptyString($request, 'WaId');
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

    /**
     * Customer phone in E.164 form (digits with a leading {@code +}), e.g. {@code +56937263654}.
     * Prefer {@code From} when present; otherwise derive from {@code WaId}.
     */
    public static function customerPhoneE164(Request $request): ?string
    {
        $from = self::nonEmptyString($request, 'From');
        if ($from !== null && str_starts_with($from, 'whatsapp:')) {
            $normalized = self::e164FromWhatsappStyleAddress(substr($from, strlen('whatsapp:')));

            if ($normalized !== null) {
                return $normalized;
            }
        }

        $waId = self::nonEmptyString($request, 'WaId');
        if ($waId !== null) {
            return self::e164FromWaIdOrDigits($waId);
        }

        $parsed = self::parsedUrlEncodedBody($request);
        if ($parsed !== []) {
            if (! empty($parsed['From']) && is_string($parsed['From'])
                && str_starts_with($parsed['From'], 'whatsapp:')) {
                $normalized = self::e164FromWhatsappStyleAddress(substr($parsed['From'], strlen('whatsapp:')));
                if ($normalized !== null) {
                    return $normalized;
                }
            }
            if (! empty($parsed['WaId']) && is_string($parsed['WaId'])) {
                return self::e164FromWaIdOrDigits($parsed['WaId']);
            }
        }

        return null;
    }

    public static function messageBody(Request $request): string
    {
        $value = self::valueFromRequest($request, 'Body');

        return is_string($value) ? $value : '';
    }

    /**
     * Twilio {@code MessageType} when present (e.g. text, audio, location), lowercased.
     */
    public static function messageType(Request $request): ?string
    {
        $raw = self::nonEmptyString($request, 'MessageType');

        return $raw !== null ? strtolower($raw) : null;
    }

    /**
     * Resolved channel type for routing: {@code text}, {@code audio}, {@code location}, or {@code unsupported_media}.
     */
    public static function effectiveMessageType(Request $request): string
    {
        $explicit = self::messageType($request);
        if ($explicit !== null && $explicit !== '') {
            return $explicit;
        }

        $numMedia = (int) (self::scalarString($request, 'NumMedia') ?? '0');
        if ($numMedia < 1) {
            return 'text';
        }

        $mime = strtolower(self::scalarString($request, 'MediaContentType0') ?? '');
        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        return 'unsupported_media';
    }

    public static function firstMediaUrl(Request $request): ?string
    {
        return self::nonEmptyString($request, 'MediaUrl0');
    }

    public static function firstMediaContentType(Request $request): ?string
    {
        return self::nonEmptyString($request, 'MediaContentType0');
    }

    /**
     * Text segment to send to the sales agent when the user shares a live location pin.
     */
    public static function locationAsAgentPrompt(Request $request): string
    {
        $lat = self::scalarString($request, 'Latitude');
        $lng = self::scalarString($request, 'Longitude');
        if ($lat === null || $lat === '' || $lng === null || $lng === '') {
            return '[El cliente compartió una ubicación pero el mensaje no incluye coordenadas legibles.]';
        }

        $mapsUrl = 'https://www.google.com/maps?q='.rawurlencode($lat.','.$lng);

        return <<<PROMPT
[Ubicación en vivo compartida por WhatsApp]
Coordenadas: latitud {$lat}, longitud {$lng}
Mapa: {$mapsUrl}

Si el cliente confirmó que está en el punto de entrega (o que este pin es su domicilio de entrega), guarda estas coordenadas en el campo opcional \`location\` al llamar CreateOrder. No insistas en la ubicación si aún está eligiendo productos o no está en el sitio de entrega.
PROMPT;
    }

    private static function nonEmptyString(Request $request, string $key): ?string
    {
        $value = self::valueFromRequest($request, $key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Like {@see nonEmptyString} but returns non-trimmed empty strings too (used for numeric string fields).
     */
    private static function scalarString(Request $request, string $key): ?string
    {
        $value = self::valueFromRequest($request, $key);
        if (! is_string($value)) {
            return null;
        }

        return $value;
    }

    private static function valueFromRequest(Request $request, string $key): mixed
    {
        if ($request->request->has($key)) {
            return $request->request->get($key);
        }

        if ($request->query->has($key)) {
            return $request->query->get($key);
        }

        $parsed = self::parsedUrlEncodedBody($request);

        return $parsed[$key] ?? null;
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

    private static function e164FromWaIdOrDigits(string $waId): ?string
    {
        $digits = preg_replace('/\D+/', '', $waId) ?? '';

        return $digits === '' ? null : '+'.$digits;
    }

    private static function e164FromWhatsappStyleAddress(string $address): ?string
    {
        $digits = preg_replace('/\D+/', '', $address) ?? '';

        return $digits === '' ? null : '+'.$digits;
    }
}
