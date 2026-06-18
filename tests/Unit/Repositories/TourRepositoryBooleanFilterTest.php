<?php

namespace Tests\Unit\Repositories;

use App\Models\Tour;
use Tests\TestCase;

class TourRepositoryBooleanFilterTest extends TestCase
{
    public function test_pgsql_featured_filter_sql_uses_is_true_literal(): void
    {
        $sql = Tour::query()->whereRaw('is_featured IS TRUE')->toSql();

        $this->assertStringContainsString('is_featured IS TRUE', $sql);
    }

    public function test_pgsql_hot_filter_sql_uses_is_true_literal(): void
    {
        $sql = Tour::query()->whereRaw('is_hot IS TRUE')->toSql();

        $this->assertStringContainsString('is_hot IS TRUE', $sql);
    }
}
