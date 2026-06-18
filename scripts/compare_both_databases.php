<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

$password = (string) config('database.connections.pgsql.password');
$configs = [
    'primary' => [
        'label' => 'Tokyo',
        'host' => 'aws-1-ap-northeast-1.pooler.supabase.com',
        'username' => 'postgres.bucmucgvsuawrpompyvu',
        'password' => $password,
    ],
    'standby' => [
        'label' => 'Singapore',
        'host' => 'aws-1-ap-southeast-1.pooler.supabase.com',
        'username' => 'postgres.aevuyguxwlcglpxcuwbe',
        'password' => $password,
    ],
];

$tables = ['users', 'locations', 'tours', 'bookings', 'payments', 'notifications', 'payment_receipts', 'tour_schedules'];
$counts = [];

foreach ($configs as $key => $cfg) {
    Config::set('database.connections.pgsql.host', $cfg['host']);
    Config::set('database.connections.pgsql.username', $cfg['username']);
    Config::set('database.connections.pgsql.password', $cfg['password']);
    DB::purge('pgsql');
    DB::reconnect('pgsql');

    foreach ($tables as $table) {
        $counts[$table][$key] = (int) DB::table($table)->count();
    }
}

echo str_pad('Table', 22).'Tokyo'.str_repeat(' ', 9).'Singapore'.str_repeat(' ', 5).'Match'.PHP_EOL;
echo str_repeat('-', 55).PHP_EOL;
foreach ($tables as $table) {
    $tokyo = $counts[$table]['primary'];
    $sg = $counts[$table]['standby'];
    $match = $tokyo === $sg ? 'OK' : 'DIFF';
    echo str_pad($table, 22).str_pad((string) $tokyo, 14).str_pad((string) $sg, 14).$match.PHP_EOL;
}
