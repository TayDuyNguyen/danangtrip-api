<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BrevoMailService
{
    public function sendMailable(string $email, ?string $name, Mailable $mailable, array $context = []): void
    {
        $apiKey = trim((string) config('services.brevo.key', ''));

        if ($apiKey === '') {
            throw new RuntimeException('BREVO_API_KEY is not configured.');
        }

        $mailable->build();

        $payload = [
            'sender' => [
                'name' => (string) config('mail.from.name'),
                'email' => (string) config('mail.from.address'),
            ],
            'to' => [
                [
                    'email' => $email,
                    'name' => $name ?: $email,
                ],
            ],
            'subject' => (string) $mailable->subject,
            'htmlContent' => $mailable->render(),
        ];

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $apiKey,
            'content-type' => 'application/json',
        ])
            ->timeout((int) config('services.brevo.timeout', 15))
            ->post((string) config('services.brevo.url'), $payload);

        Log::info('BREVO_MAIL response.', [
            ...$context,
            'email' => $email,
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ]);

        $response->throw();
    }
}
