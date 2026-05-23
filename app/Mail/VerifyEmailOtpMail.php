<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class VerifyEmailOtpMail extends Mailable
{
    public function __construct(
        public readonly string $otp,
        public readonly ?string $recipientName = null
    ) {}

    public function build(): self
    {
        $appName = (string) config('app.name', 'Da Nang Trip');
        $safeName = e($this->recipientName ?: 'traveler');
        $safeOtp = e($this->otp);

        return $this
            ->subject("Mã xác thực email {$appName}")
            ->html(<<<HTML
                <!doctype html>
                <html lang="vi">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                </head>
                <body style="margin:0;padding:0;background:#f5efe7">
                <div style="margin:0;padding:0;background:#f5efe7;font-family:Arial,Helvetica,sans-serif;color:#1f1712">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5efe7;padding:32px 12px">
                        <tr>
                            <td align="center">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#100c09;border-radius:28px;overflow:hidden;box-shadow:0 24px 70px rgba(63,38,20,.22)">
                                    <tr>
                                        <td style="padding:0;background:linear-gradient(135deg,#8b5a2b 0%,#2a1710 48%,#080604 100%)">
                                            <div style="padding:34px 32px 30px">
                                                <div style="display:inline-block;padding:8px 13px;border-radius:999px;background:rgba(255,255,255,.12);color:#f7d7aa;font-size:12px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase">
                                                    {$appName}
                                                </div>
                                                <h1 style="margin:24px 0 10px;color:#fff7ed;font-size:30px;line-height:1.15;font-weight:800">
                                                    Xác thực email của bạn
                                                </h1>
                                                <p style="margin:0;color:#e7c7a2;font-size:15px;line-height:1.7">
                                                    Xin chào {$safeName}, hãy dùng mã một lần bên dưới để hoàn tất xác thực tài khoản {$appName}.
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:34px 32px 12px;background:#100c09">
                                            <div style="background:#fff8ef;border-radius:22px;padding:28px 24px;text-align:center;border:1px solid #ead6bd">
                                                <p style="margin:0 0 14px;color:#8b5a2b;font-size:12px;font-weight:800;letter-spacing:2px;text-transform:uppercase">
                                                    Mã xác thực của bạn
                                                </p>
                                                <div style="font-size:42px;line-height:1;font-weight:900;letter-spacing:10px;color:#1f1712">
                                                    {$safeOtp}
                                                </div>
                                                <p style="margin:18px 0 0;color:#7a6556;font-size:13px;line-height:1.6">
                                                    Mã này có hiệu lực trong 10 phút.
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:14px 32px 34px;background:#100c09">
                                            <p style="margin:0;color:#b9a18e;font-size:13px;line-height:1.7">
                                                Nếu bạn không yêu cầu mã này, có thể bỏ qua email này. Không chia sẻ mã xác thực với bất kỳ ai.
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
