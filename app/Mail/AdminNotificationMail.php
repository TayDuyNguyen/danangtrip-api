<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AdminNotificationMail extends Mailable
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly string $type,
        public readonly ?array $data = null,
        public readonly ?string $recipientName = null
    ) {}

    public function build(): self
    {
        $appName = (string) config('app.name', 'Da Nang Trip');
        $safeName = e($this->recipientName ?: 'ban');
        $safeTitle = e($this->title);
        $safeContent = nl2br(e($this->content));
        $safeType = e($this->type);
        $actionUrl = $this->resolveActionUrl($this->data['url'] ?? null);
        $actionButton = $actionUrl
            ? '<a href="'.e($actionUrl).'" style="display:inline-block;margin-top:22px;padding:13px 20px;border-radius:999px;background:#14b8a6;color:#06221f;text-decoration:none;font-size:14px;font-weight:800">Xem chi tiet</a>'
            : '';

        return $this
            ->subject("[{$appName}] {$this->title}")
            ->html(<<<HTML
                <!doctype html>
                <html lang="vi">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                </head>
                <body style="margin:0;padding:0;background:#f3f7fb">
                <div style="margin:0;padding:0;background:#f3f7fb;font-family:Arial,Helvetica,sans-serif;color:#0f172a">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f7fb;padding:32px 12px">
                        <tr>
                            <td align="center">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #dbe6f1">
                                    <tr>
                                        <td style="padding:30px 32px;background:#0f172a">
                                            <div style="display:inline-block;padding:7px 12px;border-radius:999px;background:rgba(20,184,166,.14);color:#7ddbd2;font-size:12px;font-weight:800;letter-spacing:1px;text-transform:uppercase">
                                                {$appName}
                                            </div>
                                            <h1 style="margin:20px 0 0;color:#ffffff;font-size:26px;line-height:1.25;font-weight:800">
                                                {$safeTitle}
                                            </h1>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:30px 32px 34px;background:#ffffff">
                                            <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.7">
                                                Xin chao {$safeName},
                                            </p>
                                            <div style="margin:0;color:#0f172a;font-size:16px;line-height:1.75">
                                                {$safeContent}
                                            </div>
                                            {$actionButton}
                                            <p style="margin:28px 0 0;color:#94a3b8;font-size:12px;line-height:1.6">
                                                Loai thong bao: {$safeType}
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
                </body>
                </html>
                HTML);
    }

    private function resolveActionUrl(mixed $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (preg_match('/^https?:\/\//i', $url) === 1) {
            return $url;
        }

        $frontendUrl = rtrim((string) config('app.frontend_url', ''), '/');

        return $frontendUrl !== ''
            ? $frontendUrl.'/'.ltrim($url, '/')
            : $url;
    }
}
