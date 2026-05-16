<?php

namespace App\Support;

class ApiErrorResponse
{
    public static function make(int $code, string $message, mixed $errors = null): array
    {
        $locale = self::resolveLocale();
        $errorKey = self::resolveErrorKey($code, $message);

        $response = [
            'code' => $code,
            'message' => $message,
            'error_key' => $errorKey,
            'user_message' => self::resolveUserMessage($code, $message, $errors, $errorKey, $locale),
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return $response;
    }

    private static function resolveLocale(): string
    {
        $header = strtolower((string) request()->header('Accept-Language', ''));

        return str_starts_with($header, 'vi') ? 'vi' : 'en';
    }

    private static function resolveErrorKey(int $code, string $message): string
    {
        $normalized = strtolower(trim($message));

        if ($code === 422) {
            return 'validation.failed';
        }

        if (str_contains($normalized, 'invalid credentials')) {
            return 'auth.invalid_credentials';
        }

        if (
            str_contains($normalized, 'token expired') ||
            str_contains($normalized, 'refresh token expired') ||
            str_contains($normalized, 'session expired')
        ) {
            return 'auth.session_expired';
        }

        if (
            $code === 401 ||
            str_contains($normalized, 'unauthenticated') ||
            str_contains($normalized, 'unauthorized') ||
            str_contains($normalized, 'invalid token') ||
            str_contains($normalized, 'token not provided')
        ) {
            return 'auth.unauthenticated';
        }

        if (
            $code === 403 ||
            str_contains($normalized, 'forbidden') ||
            str_contains($normalized, 'access denied') ||
            str_contains($normalized, 'do not have permission')
        ) {
            return 'auth.forbidden';
        }

        if (
            $code === 404 ||
            str_contains($normalized, 'not found') ||
            str_contains($normalized, 'does not exist')
        ) {
            return 'resource.not_found';
        }

        if ($code === 405) {
            return 'request.method_not_allowed';
        }

        if ($code === 429) {
            return 'request.throttled';
        }

        if (
            str_contains($normalized, 'already been paid') ||
            str_contains($normalized, 'already processed') ||
            str_contains($normalized, 'already cancelled or completed') ||
            str_contains($normalized, 'already verified') ||
            str_contains($normalized, 'already been replied to')
        ) {
            return 'request.already_processed';
        }

        if (
            str_contains($normalized, 'already ') ||
            str_contains($normalized, 'already.') ||
            str_contains($normalized, 'already exists') ||
            str_contains($normalized, 'already taken') ||
            str_contains($normalized, 'already registered') ||
            str_contains($normalized, 'already in use') ||
            str_contains($normalized, 'already rated') ||
            str_contains($normalized, 'already in your favorites')
        ) {
            return 'request.conflict';
        }

        if (str_contains($normalized, 'cannot ')) {
            return 'request.invalid_state';
        }

        if ($code >= 500 || str_starts_with($normalized, 'failed to ')) {
            return 'server.error';
        }

        if ($code >= 400) {
            return 'request.bad_request';
        }

        return 'request.unknown';
    }

    private static function resolveUserMessage(
        int $code,
        string $message,
        mixed $errors,
        string $errorKey,
        string $locale
    ): string {
        if ($code === 422) {
            $firstValidationMessage = self::extractFirstValidationMessage($errors, $locale);

            return $firstValidationMessage ?: self::translate($errorKey, $locale);
        }

        if (self::shouldExposeOriginalMessage($message, $errorKey, $code)) {
            return self::normalizeBilingualMessage($message, $locale);
        }

        return self::translate($errorKey, $locale);
    }

    private static function extractFirstValidationMessage(mixed $errors, string $locale): ?string
    {
        if (! is_array($errors)) {
            return null;
        }

        foreach ($errors as $value) {
            if (is_array($value) && isset($value[0]) && is_string($value[0]) && trim($value[0]) !== '') {
                return self::normalizeBilingualMessage($value[0], $locale);
            }

            if (is_string($value) && trim($value) !== '') {
                return self::normalizeBilingualMessage($value, $locale);
            }
        }

        return null;
    }

    private static function shouldExposeOriginalMessage(string $message, string $errorKey, int $code): bool
    {
        $normalized = strtolower(trim($message));

        if ($normalized === '') {
            return false;
        }

        if ($code >= 500 || str_starts_with($normalized, 'failed to ') || str_contains($normalized, 'internal server error')) {
            return false;
        }

        return ! in_array($errorKey, [
            'auth.unauthenticated',
            'auth.forbidden',
            'request.method_not_allowed',
            'request.throttled',
            'request.bad_request',
            'request.unknown',
        ], true);
    }

    private static function normalizeBilingualMessage(string $message, string $locale): string
    {
        $trimmed = trim($message);

        if ($trimmed === '') {
            return $trimmed;
        }

        if (preg_match('/^(.*?)\s*\((.*?)\)\s*$/', $trimmed, $matches) === 1) {
            return trim($locale === 'vi' ? $matches[2] : $matches[1]);
        }

        return $trimmed;
    }

    private static function translate(string $errorKey, string $locale): string
    {
        $messages = [
            'en' => [
                'validation.failed' => 'Please check the information you entered and try again.',
                'auth.invalid_credentials' => 'The email or password you entered is incorrect.',
                'auth.session_expired' => 'Your session has expired. Please sign in again.',
                'auth.unauthenticated' => 'Please sign in to continue.',
                'auth.forbidden' => 'You do not have permission to perform this action.',
                'resource.not_found' => 'The requested information could not be found.',
                'request.method_not_allowed' => 'This action is not supported.',
                'request.throttled' => 'You are doing this too quickly. Please wait a moment and try again.',
                'request.already_processed' => 'This request has already been processed.',
                'request.conflict' => 'This information already exists in the system.',
                'request.invalid_state' => 'This action cannot be completed right now.',
                'request.bad_request' => 'The request could not be completed. Please review your information and try again.',
                'request.unknown' => 'The request could not be completed at this time.',
                'server.error' => 'The system is temporarily busy. Please try again later.',
            ],
            'vi' => [
                'validation.failed' => 'Vui long kiem tra lai thong tin da nhap va thu lai.',
                'auth.invalid_credentials' => 'Email hoac mat khau ban nhap khong dung.',
                'auth.session_expired' => 'Phien dang nhap da het han. Vui long dang nhap lai.',
                'auth.unauthenticated' => 'Vui long dang nhap de tiep tuc.',
                'auth.forbidden' => 'Ban khong co quyen thuc hien hanh dong nay.',
                'resource.not_found' => 'Khong tim thay thong tin ban yeu cau.',
                'request.method_not_allowed' => 'Hanh dong nay khong duoc ho tro.',
                'request.throttled' => 'Ban thao tac qua nhanh. Vui long doi it phut roi thu lai.',
                'request.already_processed' => 'Yeu cau nay da duoc xu ly truoc do.',
                'request.conflict' => 'Thong tin nay da ton tai trong he thong.',
                'request.invalid_state' => 'Khong the hoan tat hanh dong nay vao luc nay.',
                'request.bad_request' => 'Khong the xu ly yeu cau. Vui long kiem tra lai thong tin va thu lai.',
                'request.unknown' => 'Khong the hoan tat yeu cau luc nay.',
                'server.error' => 'He thong dang ban. Vui long thu lai sau.',
            ],
        ];

        return $messages[$locale][$errorKey] ?? $messages[$locale]['request.unknown'];
    }
}
