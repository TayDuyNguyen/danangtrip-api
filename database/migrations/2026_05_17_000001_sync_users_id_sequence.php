<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('users', 'id'),
                COALESCE((SELECT MAX(id) FROM users), 1),
                true
            )
        ");
    }

    public function down(): void
    {
        // no-op: sequence sync is a one-way data repair
    }
};
