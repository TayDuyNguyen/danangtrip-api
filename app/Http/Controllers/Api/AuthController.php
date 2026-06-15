<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthenticatedActionRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Class AuthController
 * Handles user authentication requests.
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
        }

        return $this->error('User registered failed', $result['status']);
    }

    /**
     * Authenticate a user and return an access token plus HttpOnly refresh cookie.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $remember = (bool) ($validated['remember'] ?? false);
        $result = $this->authService->login($validated['email'], $validated['password'], $remember);

        if ($result['status'] == HttpStatusCode::SUCCESS->value) {
            $data = $result['data'];
            $refreshToken = $data['refresh_token'];
            unset($data['refresh_token']);

            return $this->success($data, 'Login successful')
                ->withCookie($this->makeRefreshTokenCookie($refreshToken, $remember));
        }

        if ($result['status'] == HttpStatusCode::FORBIDDEN->value) {
            return $this->forbidden($result['message']);
        }

        return $this->unauthorized($result['message']);
    }

    /**
     * Invalidate user token (Logout).
     */
    public function logout(AuthenticatedActionRequest $request): JsonResponse
    {
        $refreshToken = $request->cookie($this->refreshCookieName());
        $result = $this->authService->logout($refreshToken);

        if ($result['status'] == HttpStatusCode::SUCCESS->value) {
            return $this->success(null, 'Logged out successfully')
                ->withCookie($this->expireRefreshTokenCookie());
        }

        return $this->unauthorized($result['message']);
    }

    /**
     * Refresh an expired access token using the HttpOnly refresh cookie.
     */
    public function refresh(AuthenticatedActionRequest $request): JsonResponse
    {
        $refreshToken = $request->cookie($this->refreshCookieName());

        if (! $refreshToken) {
            return $this->unauthorized('Refresh token is required in cookie');
        }

        $result = $this->authService->refresh($refreshToken);

        if ($result['status'] == HttpStatusCode::SUCCESS->value) {
            $data = $result['data'];
            $newRefreshToken = $data['refresh_token'];
            $remember = (bool) ($data['remember'] ?? false);
            unset($data['refresh_token'], $data['remember']);

            return $this->success($data, 'Token refreshed successfully')
                ->withCookie($this->makeRefreshTokenCookie($newRefreshToken, $remember));
        }

        return $this->error(
            $result['message'],
            $result['status'],
            isset($result['error']) ? ['error' => $result['error']] : null
        )->withCookie($this->expireRefreshTokenCookie());
    }

    /**
     * Get authenticated user.
     */
    public function me(AuthenticatedActionRequest $request): JsonResponse
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
        }

        return $this->error($result['message'], $result['status']);
    }

    /**
     * Reset password.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->resetPassword($request->validated());

        if ($result['status'] == HttpStatusCode::SUCCESS->value) {
            return $this->success(null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    /**
     * Verify email.
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $result = $this->authService->verifyEmail($request->user(), $request->validated('otp'));

        if ($result['status'] == HttpStatusCode::SUCCESS->value) {
            return $this->success(null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    /**
     * Resend verification email.
     */
    public function resendVerification(AuthenticatedActionRequest $request): JsonResponse
    {
        $result = $this->authService->resendVerification($request->user());

        if ($result['status'] == HttpStatusCode::SUCCESS->value) {
            return $this->success(null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    private function refreshCookieName(): string
    {
        return (string) config('auth_tokens.refresh_cookie.name', 'refresh_token');
    }

    private function makeRefreshTokenCookie(string $refreshToken, bool $remember = false): Cookie
    {
        $ttl = $remember ? (int) config('auth_tokens.refresh_cookie.ttl', 20160) : 0;

        return cookie(
            $this->refreshCookieName(),
            $refreshToken,
            $ttl,
            (string) config('auth_tokens.refresh_cookie.path', '/'),
            config('auth_tokens.refresh_cookie.domain'),
            (bool) config('auth_tokens.refresh_cookie.secure', false),
            true,
            false,
            (string) config('auth_tokens.refresh_cookie.same_site', 'lax')
        );
    }

    private function expireRefreshTokenCookie(): Cookie
    {
        return cookie()->forget(
            $this->refreshCookieName(),
            (string) config('auth_tokens.refresh_cookie.path', '/'),
            config('auth_tokens.refresh_cookie.domain')
        );
    }
}
