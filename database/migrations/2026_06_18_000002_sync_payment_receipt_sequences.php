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

        foreach (['payments', 'payment_receipts', 'refund_requests'] as $table) {
            DB::statement("
                SELECT setval(
                    pg_get_serial_sequence('{$table}', 'id'),
                    COALESCE((SELECT MAX(id) FROM {$table}), 1),
                    true
                )
            ");
        }
    }

    public function down(): void
    {
        // no-op
    }
};
