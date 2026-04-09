<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS payments_transaction_code_unique ON payments (transaction_code)');
        DB::statement('CREATE INDEX IF NOT EXISTS payments_booking_id_index ON payments (booking_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS payments_payment_status_index ON payments (payment_status)');
        DB::statement('CREATE INDEX IF NOT EXISTS payments_payment_gateway_index ON payments (payment_gateway)');
        DB::statement('CREATE INDEX IF NOT EXISTS payments_created_at_index ON payments (created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS payments_paid_at_index ON payments (paid_at)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS payments_transaction_code_unique');
        DB::statement('DROP INDEX IF EXISTS payments_booking_id_index');
        DB::statement('DROP INDEX IF EXISTS payments_payment_status_index');
        DB::statement('DROP INDEX IF EXISTS payments_payment_gateway_index');
        DB::statement('DROP INDEX IF EXISTS payments_created_at_index');
        DB::statement('DROP INDEX IF EXISTS payments_paid_at_index');
    }
};
