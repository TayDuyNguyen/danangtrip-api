<?php

namespace App\Jobs;

use App\Mail\ContactReplyMail;
use App\Services\BrevoMailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendContactReplyEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(
        public readonly string $email,
        public readonly string $originalSubject,
        public readonly string $originalMessage,
        public readonly string $replyContent,
        public readonly ?string $recipientName = null
    ) {}

    public function handle(): void
    {
        $startedAt = microtime(true);
        $debugContext = [
            'email' => $this->email,
            'subject' => $this->originalSubject,
            'recipient_name' => $this->recipientName,
        ];

        try {
            Log::info('MAIL_DEBUG starting contact reply email delivery.', $debugContext);

            app(BrevoMailService::class)->sendMailable(
                email: $this->email,
                name: $this->recipientName,
                mailable: new ContactReplyMail(
                    originalSubject: $this->originalSubject,
                    originalMessage: $this->originalMessage,
                    replyContent: $this->replyContent,
                    recipientName: $this->recipientName
                ),
                context: [
                    'mail_type' => 'contact_reply',
                    'email' => $this->email,
                ],
            );

            Log::info('MAIL_DEBUG contact reply email delivered successfully.', [
                ...$debugContext,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
        } catch (Throwable $e) {
            Log::warning('MAIL_DEBUG failed to send contact reply email.', [
                ...$debugContext,
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            throw $e;
        }
    }
}
