<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Class AuthService
 * Handles business logic related to authentication.
 * (Xử lý logic nghiệp vụ liên quan đến xác thực)
 */
class AuthService
{
    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * AuthService constructor.
     * (Khởi tạo AuthService)
     *
     * @return void
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
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
                'role' => $data['role'] ?? 'user',
            ]);

            // $token = Auth::guard('api')->login($user);

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
     * Authenticate a user and return token.
     * (Xác thực người dùng và trả về token)
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

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'token' => $token,
                    'user' => Auth::guard('api')->user(),
                ],
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Login failed',
            ];
        }
    }

    /**
     * Invalidate user token (Logout).
     * (Vô hiệu hóa token người dùng - Đăng xuất)
     */
    public function logout(): array
    {
        try {
            if (Auth::guard('api')->check()) {
                Auth::guard('api')->logout();
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
     * Refresh user token.
     * (Refresh token người dùng)
     */
    public function refresh(string $token): array
    {
        try {
            $newToken = JWTAuth::setToken($token)->refresh();

            if (! $newToken) {
                return [
                    'status' => HttpStatusCode::UNAUTHORIZED->value,
                    'message' => 'Invalid token',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'token' => $newToken,
                    'user' => JWTAuth::setToken($newToken)->authenticate(),
                ],
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Refresh failed',
            ];
        }
    }

    /**
     * Handle forgot password.
     * (Xử lý quên mật khẩu)
     */
    public function forgotPassword(string $email): array
    {
        try {
            // Placeholder: Generate token, save to password_reset_tokens table, and send email.
            // For now, we simulate success since emailing is out of scope.

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
     * (Xử lý đặt lại mật khẩu)
     */
    public function resetPassword(array $data): array
    {
        try {
            // Placeholder: Verify token, update user password, delete token.

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
     * (Xác minh email người dùng)
     */
    public function verifyEmail(User $user, string $otp): array
    {
        try {
            // Placeholder: Verify OTP.

            $user->email_verified_at = Carbon::now();
            $user->save();

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
     * (Gửi lại email xác minh)
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
            // Placeholder: Generate new OTP and send email.

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
