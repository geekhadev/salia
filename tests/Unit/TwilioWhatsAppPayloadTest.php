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
}
