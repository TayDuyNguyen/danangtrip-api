<?php

namespace App\Jobs;

use App\Mail\AdminNotificationMail;
use App\Services\BrevoMailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAdminNotificationEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

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
        $startedAt = microtime(true);
        $debugContext = $this->debugContext();

        try {
            $this->debugLog('info', 'MAIL_DEBUG starting notification email delivery.', $debugContext);

            app(BrevoMailService::class)->sendMailable(
                email: $this->email,
                name: $this->recipientName,
                mailable: $this->makeMailable(),
                context: [
                    'mail_type' => 'admin_notification',
                    'user_id' => $this->userId,
                    'type' => $this->type,
                    'title' => $this->title,
                ],
            );

            $this->debugLog('info', 'MAIL_DEBUG notification email delivered successfully.', [
                ...$debugContext,
                'duration_ms' => $this->elapsedMs($startedAt),
            ]);
        } catch (Throwable $e) {
            $this->debugLog('warning', 'MAIL_DEBUG failed to send notification email.', [
                ...$debugContext,
                ...$this->exceptionContext($e),
                'duration_ms' => $this->elapsedMs($startedAt),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $this->debugLog('error', 'MAIL_DEBUG notification email job failed.', [
            ...$this->debugContext(),
            ...$this->exceptionContext($e),
        ]);
    }

    private function makeMailable(): AdminNotificationMail
    {
        return new AdminNotificationMail(
            title: $this->title,
            content: $this->content,
            type: $this->type,
            data: $this->data,
            recipientName: $this->recipientName
        );
    }

    private function debugContext(): array
    {
        $brevoApiKey = (string) config('services.brevo.key', '');

        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'type' => $this->type,
            'title' => $this->title,
            'queue_connection' => (string) config('queue.default'),
            'transport_strategy' => 'brevo_api',
            'brevo' => [
                'api_key_set' => $brevoApiKey !== '',
                'api_url' => (string) config('services.brevo.url'),
                'timeout' => (int) config('services.brevo.timeout', 15),
            ],
        ];
    }

    private function exceptionContext(Throwable $e): array
    {
        $previous = $e->getPrevious();

        return [
            'exception' => $e::class,
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'previous_exception' => $previous ? $previous::class : null,
            'previous_code' => $previous?->getCode(),
            'previous_message' => $previous?->getMessage(),
            'trace_head' => array_slice(explode("\n", $e->getTraceAsString()), 0, 8),
        ];
    }

    private function debugLog(string $level, string $message, array $context): void
    {
        Log::log($level, $message, $context);
        error_log($message.' '.json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
