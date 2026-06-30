<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('cart_items')) {
            return;
        }

        DB::statement(<<<'SQL'
            SELECT setval(
                pg_get_serial_sequence('cart_items', 'id'),
                COALESCE((SELECT MAX(id) FROM cart_items), 1),
                EXISTS (SELECT 1 FROM cart_items)
            )
        SQL);
    }

    public function down(): void
    {
        // Sequence synchronization is a data repair and should not be reversed.
    }
};
