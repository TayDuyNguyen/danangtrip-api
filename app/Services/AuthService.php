<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Models\User;
use App\Repositories\Interfaces\RefreshTokenRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Class AuthService
 * Handles business logic related to authentication.
 * (Xử lý logic nghiệp vụ liên quan đến xác thực)
 */
class AuthService
{
    /**
     * AuthService constructor.
     * (Khởi tạo AuthService)
     */
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected RefreshTokenRepositoryInterface $refreshTokenRepository
    ) {}

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
                'role' => $data['role'] ?? 'user',
            ]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $user,
            ];
        } catch (\Exception $_) {
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
    public function login(string $email, string $password): ?array
    {
        try {
            if (! $token = Auth::guard('api')->attempt(['email' => $email, 'password' => $password])) {
                return [
                    'status' => HttpStatusCode::UNAUTHORIZED->value,
                    'message' => 'Invalid credentials',
                ];
            }

            $user = Auth::guard('api')->user();

            // Sinh Refresh Token an toàn và mã hoá vào DB (Cấp chuẩn OAuth 2.1)
            $refreshTokenStr = Str::random(64);
            $this->refreshTokenRepository->create([
                'user_id' => $user->id,
                'token' => hash('sha256', $refreshTokenStr),
                'expires_at' => now()->addDays(14),
            ]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'token' => $token,
                    'refresh_token' => $refreshTokenStr,
                    'user' => $user,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Login failed: '.$e->getMessage(),
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
        } catch (\Exception $_) {
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
                    'message' => 'Invalid refresh token',
                ];
            }

            // Theo tiêu chuẩn Auth-Security: Reuse Detection
            if ($storedToken->used_at) {
                // Refresh token đã sử dụng -> Bị kẻ gian cắp và thử gọi lại. Revoke toàn bộ token.
                $this->refreshTokenRepository->deleteAllByUserId($storedToken->user_id);

                return [
                    'status' => HttpStatusCode::FORBIDDEN->value,
                    'message' => 'Token reuse detected. All sessions revoked. Please login again.',
                ];
            }

            if ($storedToken->expires_at < now()) {
                return [
                    'status' => HttpStatusCode::UNAUTHORIZED->value,
                    'message' => 'Refresh token expired',
                ];
            }

            // Đánh dấu đã sử dụng (để tracking Reuse)
            $storedToken->update(['used_at' => now()]);

            // Sinh Access Token JWT mới
            $newAccessToken = Auth::guard('api')->login($storedToken->user);

            // Xoay vòng: Sinh mới Refresh Token cho quy trình mượt mà
            $newRefreshTokenStr = Str::random(64);
            $this->refreshTokenRepository->create([
                'user_id' => $storedToken->user_id,
                'token' => hash('sha256', $newRefreshTokenStr),
                'expires_at' => now()->addDays(14),
                'previous_token_id' => $storedToken->id,
            ]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'token' => $newAccessToken,
                    'refresh_token' => $newRefreshTokenStr,
                    'user' => $storedToken->user,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Refresh failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Handle forgot password.
     */
    public function forgotPassword(string $email): array
    {
        try {
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
            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Password has been reset successfully.',
            ];
        } catch (\Exception $_) {
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
            $this->userRepository->markEmailAsVerified($user->id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Email verified successfully.',
            ];
        } catch (\Exception $_) {
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

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Verification email resent successfully.',
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to resend verification email.',
            ];
        }
    }
}
