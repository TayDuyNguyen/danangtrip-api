<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Repositories\Interfaces\SettingRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingPublicConfigDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('PDO sqlite extension is required for in-memory database tests.');
        }

        parent::setUp();

        $this->withoutMiddleware();
        Cache::forget('public_config');
    }

    public function test_public_config_endpoint_reads_public_settings_from_database(): void
    {
        Setting::query()->create([
            'key' => 'general.hotline',
            'value' => '0909123456',
            'value_type' => 'string',
            'is_public' => true,
        ]);
        Setting::query()->create([
            'key' => 'internal.secret',
            'value' => 'hidden-token',
            'value_type' => 'string',
            'is_public' => false,
        ]);

        $response = $this->getJson('/api/v1/config');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.general.hotline', '0909123456')
            ->assertJsonMissingPath('data.internal');
    }

    public function test_get_public_settings_excludes_non_public_rows(): void
    {
        Setting::query()->create([
            'key' => 'payment.sepay',
            'value' => 'true',
            'value_type' => 'boolean',
            'is_public' => true,
        ]);
        Setting::query()->create([
            'key' => 'chatbot.api_key',
            'value' => 'sk-test',
            'value_type' => 'string',
            'is_public' => false,
        ]);

        $settings = app(SettingRepositoryInterface::class)->getPublicSettings();

        $this->assertCount(1, $settings);
        $this->assertSame('payment.sepay', $settings->first()->key);
    }

    /**
     * Regression: PostgreSQL rejects `boolean = 1` (integer binding).
     */
    public function test_public_settings_query_uses_pgsql_boolean_syntax_when_driver_is_pgsql(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL connection required for driver-specific assertion.');
        }

        Setting::query()->create([
            'key' => 'general.email',
            'value' => 'support@danangtrip.com',
            'value_type' => 'string',
            'is_public' => true,
        ]);

        Cache::forget('public_config');

        $response = $this->getJson('/api/v1/config');

        $response
            ->assertOk()
            ->assertJsonPath('data.general.email', 'support@danangtrip.com');
    }
}
