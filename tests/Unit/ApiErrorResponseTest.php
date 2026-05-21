<?php

namespace Tests\Unit;

use App\Support\ApiErrorResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiErrorResponseTest extends TestCase
{
    public function test_it_builds_localized_validation_error_payload(): void
    {
        app()->instance('request', Request::create(
            '/api/test',
            'POST',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'vi']
        ));

        $payload = ApiErrorResponse::make(422, 'Validation failed', [
            'email' => ['The email field is required. (Email khong duoc de trong.)'],
        ]);

        $this->assertSame(422, $payload['code']);
        $this->assertSame('validation.failed', $payload['error_key']);
        $this->assertSame('Email khong duoc de trong.', $payload['user_message']);
        $this->assertArrayHasKey('errors', $payload);
    }

    public function test_it_hides_internal_server_messages_from_users(): void
    {
        app()->instance('request', Request::create(
            '/api/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en']
        ));

        $payload = ApiErrorResponse::make(500, 'Failed to create payment link');

        $this->assertSame(500, $payload['code']);
        $this->assertSame('server.error', $payload['error_key']);
        $this->assertSame('The system is temporarily busy. Please try again later.', $payload['user_message']);
        $this->assertSame('Failed to create payment link', $payload['message']);
    }
}
