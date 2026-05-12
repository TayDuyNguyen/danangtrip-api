<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * List admin UI maps null price_level to "—". Legacy/imported rows often left price_level NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('locations')->whereNull('price_level')->update(['price_level' => 1]);
    }

    public function down(): void
    {
        // Intentionally empty: cannot distinguish rows that were NULL before backfill.
    }
};
