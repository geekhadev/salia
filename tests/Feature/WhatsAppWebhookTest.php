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
        $this->mock(WhatsAppService::class, function ($mock): void {
            $mock->shouldReceive('processMessage')
                ->once()
                ->with('whatsapp:+15551234567', 'hello')
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
}
