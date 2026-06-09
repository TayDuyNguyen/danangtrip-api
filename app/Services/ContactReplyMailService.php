<?php

namespace App\Services;

use App\Mail\ContactReplyMail;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ContactReplyMailService
{
    public function __construct(
        protected BrevoMailService $brevoMailService
    ) {}

    public function send(Contact $contact, string $reply): void
    {
        if (empty($contact->email)) {
            return;
        }

        $startedAt = microtime(true);
        $debugContext = [
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'subject' => $contact->subject,
            'recipient_name' => $contact->name,
        ];

        try {
            Log::info('MAIL_DEBUG starting contact reply email delivery.', $debugContext);

            $this->brevoMailService->sendMailable(
                email: (string) $contact->email,
                name: $contact->name,
                mailable: new ContactReplyMail(
                    originalSubject: (string) $contact->subject,
                    originalMessage: (string) $contact->message,
                    replyContent: $reply,
                    recipientName: $contact->name
                ),
                context: [
                    'mail_type' => 'contact_reply',
                    'contact_id' => $contact->id,
                    'email' => $contact->email,
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
