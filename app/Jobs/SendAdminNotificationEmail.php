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

            Mail::to($this->email)->send(new AdminNotificationMail(
                title: $this->title,
                content: $this->content,
                type: $this->type,
                data: $this->data,
                recipientName: $this->recipientName
            ));

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

    private function debugContext(): array
    {
        $smtpConfig = (array) config('mail.mailers.smtp', []);

        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'type' => $this->type,
            'title' => $this->title,
            'queue_connection' => (string) config('queue.default'),
            'mail_default' => (string) config('mail.default'),
            'smtp' => [
                'host' => $smtpConfig['host'] ?? null,
                'port' => $smtpConfig['port'] ?? null,
                'scheme' => $smtpConfig['scheme'] ?? null,
                'username' => $smtpConfig['username'] ?? null,
                'password_set' => ! empty($smtpConfig['password']),
                'timeout' => $smtpConfig['timeout'] ?? null,
                'local_domain' => $smtpConfig['local_domain'] ?? null,
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
