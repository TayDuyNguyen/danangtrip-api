<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Repositories\Interfaces\SettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Test public GET /config returns correct nested structure.
     */
    public function test_public_config_endpoint_returns_nested_public_settings(): void
    {
        $mockRepo = Mockery::mock(SettingRepositoryInterface::class);
        $mockRepo->shouldReceive('getPublicSettings')
            ->once()
            ->andReturn(new Collection([
                new Setting([
                    'key' => 'general.hotline',
                    'value' => '1900 1800',
                    'value_type' => 'string',
                    'is_public' => true,
                ]),
                new Setting([
                    'key' => 'payment.payos',
                    'value' => 'true',
                    'value_type' => 'boolean',
                    'is_public' => true,
                ]),
            ]));

        $this->app->instance(SettingRepositoryInterface::class, $mockRepo);

        $response = $this->getJson('/api/v1/config');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.general.hotline', '1900 1800')
            ->assertJsonPath('data.payment.payos', true);
    }

    /**
     * Test admin GET /settings.
     */
    public function test_admin_settings_index_endpoint_returns_all_settings(): void
    {
        $mockRepo = Mockery::mock(SettingRepositoryInterface::class);
        $mockRepo->shouldReceive('getAdminSettings')
            ->once()
            ->andReturn(new Collection([
                new Setting([
                    'key' => 'general.hotline',
                    'value' => '1900 1800',
                    'value_type' => 'string',
                    'is_public' => true,
                ]),
                new Setting([
                    'key' => 'payment.payos',
                    'value' => 'true',
                    'value_type' => 'boolean',
                    'is_public' => true,
                ]),
            ]));

        $this->app->instance(SettingRepositoryInterface::class, $mockRepo);

        $response = $this->getJson('/api/v1/admin/settings');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.general.hotline', '1900 1800')
            ->assertJsonPath('data.payment.payos', true);
    }

    /**
     * Test admin PUT /settings successfully updates settings.
     */
    public function test_admin_settings_update_endpoint_validates_and_saves_settings(): void
    {
        $payload = [
            'settings' => [
                'general' => [
                    'hotline' => '0912345678', // valid phone format
                    'email' => 'admin@danangtrip.com',
                    'address' => '456 Bach Dang, Da Nang',
                    'support_hours' => '24/7 Support',
                ],
                'brand' => [
                    'website_name' => 'DaNangTrip Modified',
                    'logo' => 'https://res.cloudinary.com/logo.png',
                    'favicon' => 'https://res.cloudinary.com/favicon.ico',
                ],
                'social' => [
                    'facebook' => 'https://facebook.com/new',
                    'instagram' => 'https://instagram.com/new',
                    'youtube' => 'https://youtube.com/new',
                    'tiktok' => 'https://tiktok.com/@new',
                    'zalo' => 'https://zalo.me/new',
                ],
                'payment' => [
                    'payos' => true,
                    'cod' => false,
                    'vnpay' => false,
                    'momo' => false,
                    'zalopay' => false,
                ],
                'policy' => [
                    'terms' => 'https://danangtrip.com/terms',
                    'privacy' => 'https://danangtrip.com/privacy',
                    'data_protection' => 'https://danangtrip.com/data-protection',
                ],
                'seo' => [
                    'meta_title' => 'DaNangTrip - New Title SEO Page',
                    'meta_description' => 'Great experiences with DaNangTrip travel and tourism.',
                    'og_image' => 'https://res.cloudinary.com/og_image.png',
                ],
            ],
        ];

        $mockRepo = Mockery::mock(SettingRepositoryInterface::class);
        $mockRepo->shouldReceive('saveSettings')
            ->once()
            ->with($payload['settings'])
            ->andReturn(true);

        $this->app->instance(SettingRepositoryInterface::class, $mockRepo);

        $response = $this->putJson('/api/v1/admin/settings', $payload);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'Website configuration updated successfully.');
    }

    /**
     * Test validation reject when email format is invalid.
     */
    public function test_admin_settings_update_fails_when_email_format_is_invalid(): void
    {
        $payload = [
            'settings' => [
                'general' => [
                    'hotline' => '0912345678',
                    'email' => 'invalid-email',
                    'address' => '456 Bach Dang, Da Nang',
                    'support_hours' => '24/7 Support',
                ],
            ],
        ];

        $response = $this->putJson('/api/v1/admin/settings', $payload);

        $response->assertStatus(422);
    }

    /**
     * Test public config endpoint returns 500 when repository fails.
     */
    public function test_public_config_endpoint_returns_500_when_service_fails(): void
    {
        $mockRepo = Mockery::mock(SettingRepositoryInterface::class);
        $mockRepo->shouldReceive('getPublicSettings')
            ->once()
            ->andThrow(new \Exception('Database Connection Failed'));

        $this->app->instance(SettingRepositoryInterface::class, $mockRepo);

        // Make sure cache doesn't bypass this by clearing cache first
        Cache::forget('public_config');

        $response = $this->getJson('/api/v1/config');

        $response
            ->assertStatus(500)
            ->assertJsonPath('code', 500)
            ->assertJsonPath('message', 'Failed to retrieve public configuration.');
    }

    /**
     * Test admin settings index endpoint returns 500 when repository fails.
     */
    public function test_admin_settings_index_endpoint_returns_500_when_service_fails(): void
    {
        $mockRepo = Mockery::mock(SettingRepositoryInterface::class);
        $mockRepo->shouldReceive('getAdminSettings')
            ->once()
            ->andThrow(new \Exception('Database Connection Failed'));

        $this->app->instance(SettingRepositoryInterface::class, $mockRepo);

        $response = $this->getJson('/api/v1/admin/settings');

        $response
            ->assertStatus(500)
            ->assertJsonPath('code', 500)
            ->assertJsonPath('message', 'Failed to retrieve admin settings.');
    }

    /**
     * Test admin settings update endpoint returns 500 when repository fails.
     */
    public function test_admin_settings_update_endpoint_returns_500_when_service_fails(): void
    {
        $payload = [
            'settings' => [
                'general' => [
                    'hotline' => '0912345678',
                    'email' => 'admin@danangtrip.com',
                    'address' => '456 Bach Dang, Da Nang',
                    'support_hours' => '24/7 Support',
                ],
                'brand' => [
                    'website_name' => 'DaNangTrip Modified',
                    'logo' => 'https://res.cloudinary.com/logo.png',
                    'favicon' => 'https://res.cloudinary.com/favicon.ico',
                ],
                'social' => [
                    'facebook' => 'https://facebook.com/new',
                    'instagram' => 'https://instagram.com/new',
                    'youtube' => 'https://youtube.com/new',
                    'tiktok' => 'https://tiktok.com/@new',
                    'zalo' => 'https://zalo.me/new',
                ],
                'payment' => [
                    'payos' => true,
                    'cod' => false,
                    'vnpay' => false,
                    'momo' => false,
                    'zalopay' => false,
                ],
                'policy' => [
                    'terms' => 'https://danangtrip.com/terms',
                    'privacy' => 'https://danangtrip.com/privacy',
                    'data_protection' => 'https://danangtrip.com/data-protection',
                ],
                'seo' => [
                    'meta_title' => 'DaNangTrip - New Title SEO Page',
                    'meta_description' => 'Great experiences with DaNangTrip travel and tourism.',
                    'og_image' => 'https://res.cloudinary.com/og_image.png',
                ],
            ],
        ];

        $mockRepo = Mockery::mock(SettingRepositoryInterface::class);
        $mockRepo->shouldReceive('saveSettings')
            ->once()
            ->with($payload['settings'])
            ->andThrow(new \Exception('Failed to save to database'));

        $this->app->instance(SettingRepositoryInterface::class, $mockRepo);

        $response = $this->putJson('/api/v1/admin/settings', $payload);

        $response
            ->assertStatus(500)
            ->assertJsonPath('code', 500)
            ->assertJsonPath('message', 'Failed to update website configuration.');
    }
}
