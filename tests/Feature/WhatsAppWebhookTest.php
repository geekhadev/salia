<?php

namespace Tests\Feature;

use App\Services\WhatsAppService;
use Mockery;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_webhook_accepts_twilio_payload_with_wa_id_only(): void
    {
        $this->partialMock(WhatsAppService::class, function ($mock): void {
            $mock->shouldReceive('processMessage')
                ->once()
                ->with('whatsapp:+15551234567', 'hello', '+15551234567')
                ->andReturn('reply text');
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with('whatsapp:+15551234567', 'reply text');
        });

        $response = $this->post('/api/webhooks/whatsapp', [
            'WaId' => '15551234567',
            'Body' => 'hello',
        ]);

        $response->assertNoContent();
    }

    public function test_webhook_returns_400_when_sender_cannot_be_resolved(): void
    {
        $this->mock(WhatsAppService::class, function ($mock): void {
            $mock->shouldNotReceive('processMessage');
            $mock->shouldNotReceive('sendMessage');
        });

        $response = $this->post('/api/webhooks/whatsapp', [
            'Body' => 'no sender',
        ]);

        $response->assertStatus(400);
        $response->assertSee('Missing sender', false);
    }

    public function test_webhook_includes_coordinates_in_prompt_for_shared_location(): void
    {
        $this->partialMock(WhatsAppService::class, function ($mock): void {
            $mock->shouldReceive('processMessage')
                ->once()
                ->withArgs(function (string $from, string $body, ?string $phone): bool {
                    return $from === 'whatsapp:+56937263654'
                        && str_contains($body, '-36.8921132')
                        && str_contains($body, '-73.1322204')
                        && $phone === '+56937263654';
                })
                ->andReturn('reply');
            $mock->shouldReceive('sendMessage')->once();
        });

        $response = $this->post('/api/webhooks/whatsapp', [
            'WaId' => '56937263654',
            'From' => 'whatsapp:+56937263654',
            'MessageType' => 'location',
            'Latitude' => '-36.8921132',
            'Longitude' => '-73.1322204',
            'Body' => '',
        ]);

        $response->assertNoContent();
    }

    public function test_webhook_rejects_non_supported_media_without_calling_agent(): void
    {
        $this->partialMock(WhatsAppService::class, function ($mock): void {
            $mock->shouldReceive('sendMessage')->once();
            $mock->shouldNotReceive('processMessage');
            $mock->shouldNotReceive('inboundTextForAgent');
        });

        $response = $this->post('/api/webhooks/whatsapp', [
            'WaId' => '56937263654',
            'NumMedia' => '1',
            'MediaUrl0' => 'https://example.com/img.jpg',
            'MediaContentType0' => 'image/jpeg',
            'Body' => '',
        ]);

        $response->assertNoContent();
    }
}
