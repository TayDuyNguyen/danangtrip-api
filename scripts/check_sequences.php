<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$tables = [
    'payments',
    'payment_receipts',
    'refund_requests',
    'notifications',
    'point_transactions',
    'bookings',
    'booking_items',
];

$host = config('database.connections.pgsql.host');
echo "DB host: {$host}\n\n";

foreach ($tables as $table) {
    try {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            echo "{$table}: TABLE MISSING\n";

            continue;
        }

        $maxId = (int) (DB::table($table)->max('id') ?? 0);
        $seqName = DB::selectOne('SELECT pg_get_serial_sequence(?, ?) AS seq', [$table, 'id'])->seq ?? null;
        if (! $seqName) {
            echo "{$table}: max_id={$maxId} (no serial sequence)\n";

            continue;
        }
        $row = DB::selectOne('SELECT last_value, is_called FROM '.str_replace("'", "''", $seqName).' AS seq');

        $lastValue = (int) ($row->last_value ?? 0);
        $drift = $maxId > $lastValue;
        $status = $drift ? 'DRIFT (sequence behind!)' : 'ok';

        echo sprintf(
            "%s: max_id=%d seq_last=%d %s\n",
            $table,
            $maxId,
            $lastValue,
            $status
        );
    } catch (Throwable $e) {
        echo "{$table}: ERROR - {$e->getMessage()}\n";
    }
}
