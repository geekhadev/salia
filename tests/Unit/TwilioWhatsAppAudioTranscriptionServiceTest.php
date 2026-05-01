<?php

namespace Tests\Unit;

use App\Services\TwilioWhatsAppAudioTranscriptionService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Transcription;
use Tests\TestCase;

class TwilioWhatsAppAudioTranscriptionServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Http::preventStrayRequests(false);

        parent::tearDown();
    }

    public function test_downloads_twilio_media_and_returns_transcription_text(): void
    {
        config([
            'services.twilio.account_sid' => 'ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'services.twilio.auth_token' => 'test_auth_token',
        ]);

        Http::preventStrayRequests();

        Http::fake([
            'api.twilio.com/*' => Http::response("\x00fake-audio-bytes", 200, ['Content-Type' => 'audio/ogg']),
        ]);

        Transcription::fake(['Pedido de dos hamburguesas por favor']);

        $service = app(TwilioWhatsAppAudioTranscriptionService::class);

        $text = $service->transcribeHostedMedia(
            'https://api.twilio.com/2010-04-01/Accounts/ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/Media/MEXXX'
        );

        $this->assertSame('Pedido de dos hamburguesas por favor', $text);

        Http::assertSent(function (Request $request): bool {
            return $request->hasHeader('Authorization')
                && str_contains((string) $request->url(), 'api.twilio.com');
        });
    }
}
