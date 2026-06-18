<?php

namespace Tests\Unit\Repositories;

use App\Models\Setting;
use Tests\TestCase;

class SettingRepositoryPublicFilterTest extends TestCase
{
    public function test_pgsql_public_filter_sql_uses_is_true_literal(): void
    {
        $sql = Setting::query()->whereRaw('is_public IS TRUE')->toSql();

        $this->assertStringContainsString('is_public IS TRUE', $sql);
    }

    public function test_non_pgsql_public_filter_sql_uses_parameterized_equality(): void
    {
        $sql = Setting::query()->where('is_public', true)->toSql();

        $this->assertStringContainsString('is_public', $sql);
        $this->assertStringNotContainsString('IS TRUE', $sql);
    }
}
