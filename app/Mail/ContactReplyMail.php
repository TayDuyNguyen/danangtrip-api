<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ContactReplyMail extends Mailable
{
    public function __construct(
        public readonly string $originalSubject,
        public readonly string $originalMessage,
        public readonly string $replyContent,
        public readonly ?string $recipientName = null
    ) {}

    public function build(): self
    {
        $appName = (string) config('app.name', 'Da Nang Trip');
        $safeName = e($this->recipientName ?: 'Khách hàng');
        $safeOriginalSubject = e($this->originalSubject);
        $safeOriginalMessage = nl2br(e($this->originalMessage));
        $safeReplyContent = nl2br(e($this->replyContent));

        return $this
            ->subject("[{$appName}] Phản hồi liên hệ: {$safeOriginalSubject}")
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
                                            <h1 style="margin:20px 0 0;color:#ffffff;font-size:24px;line-height:1.25;font-weight:800">
                                                Phản hồi liên hệ từ {$appName}
                                            </h1>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:30px 32px 34px;background:#ffffff">
                                            <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.7">
                                                Xin chào <strong>{$safeName}</strong>,
                                            </p>
                                            <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.7">
                                                Cảm ơn bạn đã liên hệ với chúng tôi. Dưới đây là phản hồi từ ban quản trị {$appName} dành cho câu hỏi của bạn:
                                            </p>
                                            
                                            <!-- Admin Reply -->
                                            <div style="margin:20px 0;padding:20px;background:#f0fdfa;border-left:4px solid #14b8a6;border-radius:0 12px 12px 0;font-size:15px;line-height:1.7;color:#0f766e">
                                                <strong style="display:block;margin-bottom:8px;color:#0d9488">Phản hồi của chúng tôi:</strong>
                                                {$safeReplyContent}
                                            </div>
                                            
                                            <hr style="border:0;border-top:1px solid #f1f5f9;margin:24px 0;">
                                            
                                            <!-- Original Message -->
                                            <div style="font-size:13px;line-height:1.6;color:#64748b">
                                                <strong style="color:#475569">Nội dung liên hệ ban đầu của bạn:</strong>
                                                <div style="margin-top:6px;font-style:italic">
                                                    "{$safeOriginalMessage}"
                                                </div>
                                            </div>
                                            
                                            <p style="margin:28px 0 0;color:#94a3b8;font-size:12px;line-height:1.6">
                                                Đây là email tự động từ hệ thống {$appName}. Vui lòng không trả lời trực tiếp email này.
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
}
