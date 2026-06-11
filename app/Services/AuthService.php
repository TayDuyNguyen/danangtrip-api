<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Mail\VerifyEmailOtpMail;
use App\Models\User;
use App\Repositories\Interfaces\RefreshTokenRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Class AuthService
 * Handles business logic related to authentication.
 * (Xử lý logic nghiệp vụ liên quan đến xác thực)
 */
class AuthService
{
    protected BrevoMailService $brevoMailService;

    /**
     * AuthService constructor.
     * (Khởi tạo AuthService)
     */
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected RefreshTokenRepositoryInterface $refreshTokenRepository,
        ?BrevoMailService $brevoMailService = null
    ) {
        $this->brevoMailService = $brevoMailService ?? app(BrevoMailService::class);
    }

    /**
     * Register a new user.
     * (Đăng ký người dùng mới)
     */
    public function register(array $data): array
    {
        try {
            $data['password'] = Hash::make($data['password']);

            $user = $this->userRepository->create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'full_name' => $data['full_name'],
                'phone' => $data['phone'] ?? null,
                'birthdate' => $data['birthdate'] ?? null,
                'gender' => $data['gender'] ?? null,
                'city' => $data['city'] ?? null,
                'role' => 'user', // Force user role for registration
            ]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $user,
            ];
        } catch (\Exception $e) {
            Log::error('Auth register failed', [
                'email' => $data['email'] ?? null,
                'username' => $data['username'] ?? null,
                'exception' => $e->getMessage(),
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Register failed',
            ];
        }
    }

    /**
     * Authenticate a user and return token + refresh token.
     * (Xác thực người dùng và trả về token cùng với refresh token mới)
     */
    public function login(string $email, string $password, bool $remember = false): ?array
    {
        try {
            if (! $token = Auth::guard('api')->attempt(['email' => $email, 'password' => $password])) {
                return [
                    'status' => HttpStatusCode::UNAUTHORIZED->value,
                    'error' => 'INVALID_CREDENTIALS',
                    'message' => 'Invalid credentials',
                ];
            }

            $user = Auth::guard('api')->user();

            // Sinh Refresh Token an toàn và mã hoá vào DB (Cấp chuẩn OAuth 2.1)
            $refreshTokenStr = Str::random(64);
            $expiresInDays = $remember ? 14 : 1;
            $this->refreshTokenRepository->create([
                'user_id' => $user->id,
                'token' => hash('sha256', $refreshTokenStr),
                'expires_at' => now()->addDays($expiresInDays),
            ]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => (int) Config::get('auth_tokens.access_token_ttl_seconds', 900),
                    'refresh_token' => $refreshTokenStr,
                    'user' => $user,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Auth login failed', [
                'email' => $email,
                'exception' => $e->getMessage(),
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'error' => 'LOGIN_FAILED',
                'message' => 'Login failed',
            ];
        }
    }

    /**
     * Invalidate user token (Logout).
     * (Vô hiệu hóa token người dùng và xóa Refresh Token - Đăng xuất)
     */
    public function logout(?string $refreshTokenStr = null): array
    {
        try {
            if (Auth::guard('api')->check()) {
                Auth::guard('api')->logout();
            }

            if ($refreshTokenStr) {
                // Thu hồi Refresh Token
                $hashedToken = hash('sha256', $refreshTokenStr);
                $this->refreshTokenRepository->deleteByToken($hashedToken);
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Logout successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Auth logout failed', [
                'exception' => $e->getMessage(),
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Logout failed',
            ];
        }
    }

    /**
     * Refresh user token with Refresh Token rotation logic.
     * (Tạo lại token mới thay cho access_token cũ đã hết hạn thông qua hệ thống Refresh Token xoay vòng an toàn)
     */
    public function refresh(string $refreshTokenStr): array
    {
        try {
            $hashedToken = hash('sha256', $refreshTokenStr);
            $storedToken = $this->refreshTokenRepository->findByToken($hashedToken);

            if (! $storedToken) {
                return [
                    'status' => HttpStatusCode::UNAUTHORIZED->value,
                    'error' => 'REFRESH_TOKEN_INVALID',
                    'message' => 'Invalid refresh token',
                ];
            }

            // Theo tiêu chuẩn Auth-Security: Reuse Detection
            if ($storedToken->used_at) {
                // Refresh token đã sử dụng -> Bị kẻ gian cắp và thử gọi lại. Revoke toàn bộ token.
                $this->refreshTokenRepository->deleteAllByUserId($storedToken->user_id);

                return [
                    'status' => HttpStatusCode::FORBIDDEN->value,
                    'error' => 'REFRESH_TOKEN_REUSED',
                    'message' => 'Token reuse detected. All sessions revoked. Please login again.',
                ];
            }

            if ($storedToken->expires_at < now()) {
                return [
                    'status' => HttpStatusCode::UNAUTHORIZED->value,
                    'error' => 'REFRESH_TOKEN_EXPIRED',
                    'message' => 'Refresh token expired',
                ];
            }

            // Đánh dấu đã sử dụng (để tracking Reuse)
            $this->refreshTokenRepository->markUsedAtNow((int) $storedToken->id);

            // Sinh Access Token JWT mới
            $newAccessToken = (string) Auth::guard('api')->login($storedToken->user);

            // Check original token lifespan to persist the "remember" state
            $isRemembered = true;
            $expiresInDays = 14;
            if ($storedToken->created_at && $storedToken->expires_at) {
                $createdAt = \Carbon\Carbon::parse($storedToken->created_at);
                $expiresAt = \Carbon\Carbon::parse($storedToken->expires_at);
                if ($createdAt->diffInHours($expiresAt) <= 36) { // ~1 day
                    $isRemembered = false;
                    $expiresInDays = 1;
                }
            }

            // Xoay vòng: Sinh mới Refresh Token cho quy trình mượt mà
            $newRefreshTokenStr = Str::random(64);
            $this->refreshTokenRepository->create([
                'user_id' => $storedToken->user_id,
                'token' => hash('sha256', $newRefreshTokenStr),
                'expires_at' => now()->addDays($expiresInDays),
                'previous_token_id' => $storedToken->id,
            ]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'token' => $newAccessToken,
                    'token_type' => 'bearer',
                    'expires_in' => (int) Config::get('auth_tokens.access_token_ttl_seconds', 900),
                    'refresh_token' => $newRefreshTokenStr,
                    'user' => $storedToken->user,
                    'remember' => $isRemembered,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Auth refresh failed', [
                'exception' => $e->getMessage(),
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'error' => 'REFRESH_FAILED',
                'message' => 'Refresh failed',
            ];
        }
    }

    /**
     * Handle forgot password.
     */
    public function forgotPassword(string $email): array
    {
        try {
            $status = Password::broker()->sendResetLink(['email' => $email]);

            if ($status !== Password::RESET_LINK_SENT) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => __($status),
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Password reset link sent to your email.',
            ];
        } catch (\Exception $_) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to process forgot password request.',
            ];
        }
    }

    /**
     * Handle reset password.
     */
    public function resetPassword(array $data): array
    {
        try {
            $status = Password::broker()->reset(
                [
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'password_confirmation' => $data['password_confirmation'],
                    'token' => $data['token'],
                ],
                function (User $user, string $password): void {
                    $user->forceFill([
                        'password' => $password,
                    ])->save();

                    $this->refreshTokenRepository->deleteAllByUserId((int) $user->id);

                    event(new PasswordReset($user));
                }
            );

            if ($status !== Password::PASSWORD_RESET) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => __($status),
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Password has been reset successfully.',
            ];
        } catch (\Exception $e) {
            Log::error('Auth reset password failed', [
                'email' => $data['email'] ?? null,
                'exception' => $e->getMessage(),
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to reset password.',
            ];
        }
    }

    /**
     * Verify user email.
     */
    public function verifyEmail(User $user, string $otp): array
    {
        try {
            $cacheKey = "verify_otp:{$user->email}";
            $storedOtp = Cache::get($cacheKey);

            if (! $storedOtp || (string) $storedOtp !== $otp) {
                // Limit attempts logic could be added here
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'Invalid or expired OTP.',
                ];
            }

            Cache::forget($cacheKey);
            $this->userRepository->markEmailAsVerified($user->id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Email verified successfully.',
            ];
        } catch (\Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to verify email.',
            ];
        }
    }

    /**
     * Resend verification email.
     */
    public function resendVerification(User $user): array
    {
        try {
            if ($user->email_verified_at) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'Email is already verified.',
                ];
            }

            $otp = (string) random_int(100000, 999999);
            Cache::put("verify_otp:{$user->email}", $otp, now()->addMinutes(10));

            $this->brevoMailService->sendMailable(
                email: $user->email,
                name: $user->full_name ?: $user->username,
                mailable: new VerifyEmailOtpMail(
                    otp: $otp,
                    recipientName: $user->full_name ?: $user->username
                ),
                context: [
                    'mail_type' => 'verify_email_otp',
                    'user_id' => $user->id,
                ],
            );

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Verification email resent successfully.',
            ];
        } catch (\Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to resend verification email.',
            ];
        }
    }
}
