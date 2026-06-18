<?php

namespace Tests\Unit;

use App\Models\Rating;
use App\Support\BooleanColumn;
use Tests\TestCase;

class BooleanColumnTest extends TestCase
{
    public function test_where_generates_is_true_sql_on_pgsql_driver(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL connection required for driver-specific assertion.');
        }

        $sql = BooleanColumn::where(Rating::query(), 'is_new', true)->toSql();

        $this->assertStringContainsString('is_new IS TRUE', $sql);
    }

    public function test_prepare_attributes_uses_raw_boolean_literals_on_pgsql(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL connection required for driver-specific assertion.');
        }

        $attributes = BooleanColumn::prepareAttributes([
            'is_active' => false,
            'title' => 'Demo',
        ]);

        $this->assertSame('false', (string) $attributes['is_active']);
        $this->assertSame('Demo', $attributes['title']);
    }

    public function test_apply_wheres_handles_boolean_tuple_conditions(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL connection required for driver-specific assertion.');
        }

        $query = Rating::query();
        BooleanColumn::applyWheres($query, [
            ['is_new', '=', true],
            ['status', '=', 'pending'],
        ]);

        $sql = $query->toSql();

        $this->assertStringContainsString('is_new IS TRUE', $sql);
        $this->assertStringContainsString('"status" = ?', $sql);
    }
}
