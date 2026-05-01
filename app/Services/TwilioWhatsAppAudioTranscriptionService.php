<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Laravel\Ai\Transcription;
use RuntimeException;
use Throwable;

class TwilioWhatsAppAudioTranscriptionService
{
    /**
     * Download media from Twilio (Basic auth) and transcribe via the default AI transcription provider.
     */
    public function transcribeHostedMedia(string $mediaUrl, ?string $mimeTypeHint = null): string
    {
        $sid = config('services.twilio.account_sid');
        $token = config('services.twilio.auth_token');

        if (! is_string($sid) || $sid === '' || ! is_string($token) || $token === '') {
            throw new RuntimeException('Twilio credentials are not configured.');
        }

        $response = Http::withBasicAuth($sid, $token)
            ->timeout(120)
            ->get($mediaUrl);

        if (! $response->successful()) {
            throw new RuntimeException('Could not download voice note from Twilio (HTTP '.$response->status().').');
        }

        $mime = $mimeTypeHint;
        if ($mime === null || $mime === '') {
            $mime = str($response->header('Content-Type', ''))->before(';')->trim()->toString();
        }
        $mime = $mime === '' ? null : $mime;

        try {
            $text = Transcription::fromBase64(base64_encode($response->body()), $mime)
                ->language('es')
                ->timeout(120)
                ->generate()
                ->text;
        } catch (Throwable $e) {
            throw new RuntimeException('Transcription failed: '.$e->getMessage(), previous: $e);
        }

        return trim($text);
    }
}
