<?php

declare(strict_types=1);

/**
 * Sync schema/data across Primary (Tokyo) and Standby (Singapore) PostgreSQL.
 *
 * Canonical data source: DATN_Tài liệu/database-seeders/seeders_v2
 * Applied via Laravel seeders in database/seeders (php artisan db:seed).
 *
 * Usage:
 *   php sync_both_databases.php --mode=migrate
 *   php sync_both_databases.php --mode=full
 *   php sync_both_databases.php --target=both --mode=full
 */

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

$target = 'both';
$mode = 'migrate';

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--target=')) {
        $target = substr($arg, 9);
    }
    if (str_starts_with($arg, '--mode=')) {
        $mode = substr($arg, 7);
    }
}

if (! in_array($target, ['primary', 'standby', 'both'], true)) {
    fwrite(STDERR, "Invalid --target. Use primary, standby, or both.\n");
    exit(1);
}

if (! in_array($mode, ['migrate', 'full'], true)) {
    fwrite(STDERR, "Invalid --mode. Use migrate or full.\n");
    exit(1);
}
$password = (string) config('database.connections.pgsql.password');
if ($password === '') {
    fwrite(STDERR, "Missing DB_PASSWORD in environment.\n");
    exit(1);
}

$configs = [
    'primary' => [
        'name' => 'Primary Database (Tokyo Pooler)',
        'host' => 'aws-1-ap-northeast-1.pooler.supabase.com',
        'port' => '5432',
        'database' => 'postgres',
        'username' => 'postgres.bucmucgvsuawrpompyvu',
        'password' => $password,
    ],
    'standby' => [
        'name' => 'Standby Database (Singapore Pooler)',
        'host' => 'aws-1-ap-southeast-1.pooler.supabase.com',
        'port' => '5432',
        'database' => 'postgres',
        'username' => 'postgres.aevuyguxwlcglpxcuwbe',
        'password' => $password,
    ],
];

$targetsToRun = $target === 'both' ? ['primary', 'standby'] : [$target];

echo "=== DanangTrip Dual-Database Sync ===\n";
echo "Mode: {$mode}\n";
echo "Target: {$target}\n";
echo "Seed source: database/seeders -> seeders_v2\n\n";
function connectDatabase(array $dbConfig): void
{
    Config::set('database.connections.pgsql.host', $dbConfig['host']);
    Config::set('database.connections.pgsql.port', $dbConfig['port']);
    Config::set('database.connections.pgsql.database', $dbConfig['database']);
    Config::set('database.connections.pgsql.username', $dbConfig['username']);
    Config::set('database.connections.pgsql.password', $dbConfig['password']);
    DB::purge('pgsql');
    DB::reconnect('pgsql');
}

function runArtisan(string $command, array $parameters = []): void
{
    $exitCode = Artisan::call($command, $parameters);
    $output = trim(Artisan::output());
    if ($output !== '') {
        echo $output.PHP_EOL;
    }
    if ($exitCode !== 0) {
        throw new RuntimeException("Command failed: php artisan {$command} (exit {$exitCode})");
    }
}

function runSeedersV2(): void
{
    echo "Running Laravel db:seed (seeders_v2 via database/seeders)...\n";
    runArtisan('db:seed', ['--force' => true]);
}

foreach ($targetsToRun as $key) {
    $dbConfig = $configs[$key];
    echo str_repeat('-', 72).PHP_EOL;
    echo "TARGET: {$dbConfig['name']}".PHP_EOL;
    echo "Host: {$dbConfig['host']}".PHP_EOL;
    echo str_repeat('-', 72).PHP_EOL;

    try {
        connectDatabase($dbConfig);
        echo 'Testing connection... ';
        DB::connection()->getPdo();
        echo "OK\n\n";

        if ($mode === 'full') {
            echo "Running migrate:fresh...\n";
            runArtisan('migrate:fresh', ['--force' => true]);
            runSeedersV2();
            echo "Syncing tour schedule availability...\n";
            runArtisan('tour-schedules:sync-availability');
        } elseif ($mode === 'migrate') {
            echo "Running migrate...\n";
            runArtisan('migrate', ['--force' => true]);
        }
        echo PHP_EOL."=== {$dbConfig['name']} synced successfully ===".PHP_EOL.PHP_EOL;
    } catch (Throwable $e) {
        fwrite(STDERR, PHP_EOL."ERROR on {$dbConfig['name']}: {$e->getMessage()}".PHP_EOL);
        exit(1);
    }
}

echo "=== All selected databases synced successfully ===\n";
