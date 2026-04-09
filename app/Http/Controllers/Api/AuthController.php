<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class AuthController
 * Handles user authentication requests.
 * Đã thay thế custom Request Validator thành chuẩn FormRequest trực tiếp và Cắm HttpOnly Cookie.
 */
class AuthController extends Controller
{
    /**
     * AuthController constructor.
     */
    public function __construct(protected AuthService $authService) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        if ($result['status'] == HttpStatusCode::SUCCESS->value) {
            return $this->created($result['data'], 'User registered successfully');
        } else {
            return $this->error('User registered failed', $result['status']);
        }
    }

    /**
     * Authenticate a user and return token via JSON and HttpOnly refresh cookie.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->authService->login($validated['email'], $validated['password']);

        if ($result['status'] == HttpStatusCode::SUCCESS->value) {
            $data = $result['data'];
            $refreshToken = $data['refresh_token'];
            unset($data['refresh_token']);

            $secureCookie = env('APP_ENV') !== 'local';

            return $this->success($data, 'Login successful')
                ->cookie('refresh_token', $refreshToken, 20160, '/', null, $secureCookie, true, false, 'Lax');
        } else {
            return $this->unauthorized($result['message']);
        }
    }

    /**
     * Invalidate user token (Logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie('refresh_token');
        $result = $this->authService->logout($refreshToken);

        if ($result['status'] == HttpStatusCode::SUCCESS->value) {
            return $this->success(null, 'Logged out successfully')
                ->withoutCookie('refresh_token');
        } else {
            return $this->unauthorized($result['message']);
        }
    }

    /**
     * Refresh Token
     */
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie('refresh_token');

        if (! $refreshToken) {
            return $this->error('Refresh token is required in cookie', HttpStatusCode::UNAUTHORIZED->value);
        }

        $result = $this->authService->refresh($refreshToken);

        if ($result['status'] == HttpStatusCode::SUCCESS->value) {
            $data = $result['data'];
            $newRefreshToken = $data['refresh_token'];
            unset($data['refresh_token']);

            $secureCookie = env('APP_ENV') !== 'local';

            return $this->success($data, 'Token refreshed successfully')
                ->cookie('refresh_token', $newRefreshToken, 20160, '/', null, $secureCookie, true, false, 'Lax');
        } else {
            // Khi không hợp lệ do hết hạn hoặc Token Reuse Attack, revoke HttpOnly Cookie hiện tại
            return $this->error($result['message'], $result['status'])
                ->withoutCookie('refresh_token');
        }
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success($request->user());
    }

    /**
     * Send forgot password email.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->forgotPassword($request->validated('email'));

        if ($result['status'] == HttpStatusCode::SUCCESS->value) {
            return $this->success(null, $result['message']);
        } else {
            return $this->error($result['message'], $result['status']);
        }
    }

    /**
     * Reset password.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->resetPassword($request->validated());

        if ($result['status'] == HttpStatusCode::SUCCESS->value) {
            return $this->success(null, $result['message']);
        } else {
            return $this->error($result['message'], $result['status']);
        }
    }

    /**
     * Verify email.
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $result = $this->authService->verifyEmail($request->user(), $request->validated('otp'));

        if ($result['status'] == HttpStatusCode::SUCCESS->value) {
            return $this->success(null, $result['message']);
        } else {
            return $this->error($result['message'], $result['status']);
        }
    }

    /**
     * Resend verification email.
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $result = $this->authService->resendVerification($request->user());

        if ($result['status'] == HttpStatusCode::SUCCESS->value) {
            return $this->success(null, $result['message']);
        } else {
            return $this->error($result['message'], $result['status']);
        }
    }
}
