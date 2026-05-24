<?php

namespace App\Jobs;

use App\Mail\AdminNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAdminNotificationEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 20;

    public function __construct(
        public readonly string $email,
        public readonly string $title,
        public readonly string $content,
        public readonly string $type,
        public readonly ?array $data = null,
        public readonly ?string $recipientName = null,
        public readonly ?int $userId = null,
    ) {}

    public function handle(): void
    {
        try {
            Log::info('Starting notification email delivery.', [
                'user_id' => $this->userId,
                'email' => $this->email,
                'type' => $this->type,
                'title' => $this->title,
            ]);

            Mail::to($this->email)->send(new AdminNotificationMail(
                title: $this->title,
                content: $this->content,
                type: $this->type,
                data: $this->data,
                recipientName: $this->recipientName
            ));

            Log::info('Notification email delivered successfully.', [
                'user_id' => $this->userId,
                'email' => $this->email,
                'type' => $this->type,
                'title' => $this->title,
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to send notification email.', [
                'user_id' => $this->userId,
                'email' => $this->email,
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('Notification email job failed.', [
            'user_id' => $this->userId,
            'email' => $this->email,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $e->getMessage(),
            'exception' => $e::class,
        ]);
    }
}
