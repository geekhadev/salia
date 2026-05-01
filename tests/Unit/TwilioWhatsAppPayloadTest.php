<?php

namespace Tests\Unit;

use App\Support\TwilioWhatsAppPayload;
use Illuminate\Http\Request;
use Tests\TestCase;

class TwilioWhatsAppPayloadTest extends TestCase
{
    public function test_sender_address_uses_from_when_present(): void
    {
        $request = Request::create('/api/webhooks/whatsapp', 'POST', [
            'From' => 'whatsapp:+15559876543',
            'Body' => 'Hola',
        ]);

        $this->assertSame('whatsapp:+15559876543', TwilioWhatsAppPayload::senderAddress($request));
        $this->assertSame('Hola', TwilioWhatsAppPayload::messageBody($request));
    }

    public function test_sender_address_derives_from_wa_id_when_from_missing(): void
    {
        $request = Request::create('/api/webhooks/whatsapp', 'POST', [
            'WaId' => '15559876543',
            'Body' => 'Hola',
        ]);

        $this->assertSame('whatsapp:+15559876543', TwilioWhatsAppPayload::senderAddress($request));
        $this->assertSame('Hola', TwilioWhatsAppPayload::messageBody($request));
    }

    public function test_sender_address_prefers_non_empty_from_over_wa_id(): void
    {
        $request = Request::create('/api/webhooks/whatsapp', 'POST', [
            'From' => 'whatsapp:+19998887777',
            'WaId' => '15550001111',
        ]);

        $this->assertSame('whatsapp:+19998887777', TwilioWhatsAppPayload::senderAddress($request));
    }

    public function test_sender_address_reads_url_encoded_raw_body_when_post_bag_empty(): void
    {
        $request = Request::create(
            '/api/webhooks/whatsapp',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            'WaId=573001234567&Body=ping'
        );

        $this->assertSame('whatsapp:+573001234567', TwilioWhatsAppPayload::senderAddress($request));
        $this->assertSame('ping', TwilioWhatsAppPayload::messageBody($request));
    }

    public function test_sender_address_returns_null_when_no_sender_fields(): void
    {
        $request = Request::create('/api/webhooks/whatsapp', 'POST', [
            'Body' => 'orphan',
        ]);

        $this->assertNull(TwilioWhatsAppPayload::senderAddress($request));
    }

    public function test_customer_phone_e164_prefers_from_when_present(): void
    {
        $request = Request::create('/api/webhooks/whatsapp', 'POST', [
            'From' => 'whatsapp:+56937263654',
            'WaId' => '56911111111',
        ]);

        $this->assertSame('+56937263654', TwilioWhatsAppPayload::customerPhoneE164($request));
    }

    public function test_customer_phone_e164_derives_from_wa_id_when_from_missing(): void
    {
        $request = Request::create('/api/webhooks/whatsapp', 'POST', [
            'WaId' => '56937263654',
        ]);

        $this->assertSame('+56937263654', TwilioWhatsAppPayload::customerPhoneE164($request));
    }

    public function test_effective_message_type_handles_explicit_audio(): void
    {
        $request = Request::create('/api/webhooks/whatsapp', 'POST', [
            'MessageType' => 'audio',
            'MediaUrl0' => 'https://api.twilio.com/media',
            'MediaContentType0' => 'audio/ogg',
        ]);

        $this->assertSame('audio', TwilioWhatsAppPayload::effectiveMessageType($request));
    }

    public function test_effective_message_type_detects_audio_from_media_without_message_type(): void
    {
        $request = Request::create('/api/webhooks/whatsapp', 'POST', [
            'NumMedia' => '1',
            'MediaUrl0' => 'https://api.twilio.com/media',
            'MediaContentType0' => 'audio/ogg',
        ]);

        $this->assertSame('audio', TwilioWhatsAppPayload::effectiveMessageType($request));
    }

    public function test_location_prompt_contains_coordinates_and_maps_link(): void
    {
        $request = Request::create('/api/webhooks/whatsapp', 'POST', [
            'Latitude' => '-36.8921132',
            'Longitude' => '-73.1322204',
        ]);

        $text = TwilioWhatsAppPayload::locationAsAgentPrompt($request);

        $this->assertStringContainsString('-36.8921132', $text);
        $this->assertStringContainsString('-73.1322204', $text);
        $this->assertStringContainsString('google.com/maps', $text);
    }
}
