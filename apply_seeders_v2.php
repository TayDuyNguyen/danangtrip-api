<?php

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

echo "=== DanangTrip Database Synchronizer & Seeder V2 ===\n\n";

// Get arguments
$target = 'both';
foreach ($argv as $arg) {
    if (strpos($arg, '--target=') === 0) {
        $target = substr($arg, 9);
    }
}

if (! in_array($target, ['primary', 'standby', 'both'])) {
    echo "Invalid target: '$target'. Must be 'primary', 'standby', or 'both'.\n";
    exit(1);
}

// Database configurations
$configs = [
    'primary' => [
        'name' => 'Primary Database (Tokyo Pooler)',
        'host' => 'aws-1-ap-northeast-1.pooler.supabase.com',
        'port' => '5432',
        'database' => 'postgres',
        'username' => 'postgres.bucmucgvsuawrpompyvu',
        'password' => 'taybkdn@2004',
    ],
    'standby' => [
        'name' => 'Standby Database (Singapore Pooler)',
        'host' => 'aws-1-ap-southeast-1.pooler.supabase.com',
        'port' => '5432',
        'database' => 'postgres',
        'username' => 'postgres.aevuyguxwlcglpxcuwbe',
        'password' => 'taybkdn@2004',
    ],
];

$targetsToRun = [];
if ($target === 'both') {
    $targetsToRun = ['primary', 'standby'];
} else {
    $targetsToRun = [$target];
}

foreach ($targetsToRun as $key) {
    $dbConfig = $configs[$key];
    echo "------------------------------------------------------------\n";
    echo "TARGET: {$dbConfig['name']}\n";
    echo "Host: {$dbConfig['host']}\n";
    echo "Database: {$dbConfig['database']}\n";
    echo "Username: {$dbConfig['username']}\n";
    echo "------------------------------------------------------------\n\n";

    // Dynamically set database connection details
    Config::set('database.connections.pgsql.host', $dbConfig['host']);
    Config::set('database.connections.pgsql.port', $dbConfig['port']);
    Config::set('database.connections.pgsql.database', $dbConfig['database']);
    Config::set('database.connections.pgsql.username', $dbConfig['username']);
    Config::set('database.connections.pgsql.password', $dbConfig['password']);

    // Purge PDO connection to force reconnection with new config
    DB::purge('pgsql');
    DB::reconnect('pgsql');

    try {
        // Test connection
        echo "Testing connection to {$dbConfig['name']}... ";
        DB::connection()->getPdo();
        echo "OK!\n\n";

        // Run Migrate Fresh
        echo "Running migrate:fresh on {$dbConfig['name']}...\n";
        $exitCode = Artisan::call('migrate:fresh', [
            '--force' => true,
        ]);
        echo Artisan::output();
        if ($exitCode !== 0) {
            throw new Exception("migrate:fresh failed with exit code $exitCode");
        }
        echo "Migration completed successfully.\n\n";

        // Run Db Seed
        echo "Running db:seed on {$dbConfig['name']}...\n";
        $exitCode = Artisan::call('db:seed', [
            '--force' => true,
        ]);
        echo Artisan::output();
        if ($exitCode !== 0) {
            throw new Exception("db:seed failed with exit code $exitCode");
        }
        echo "Seeding completed successfully.\n\n";

        echo "=== {$dbConfig['name']} synchronized successfully! ===\n\n";
    } catch (Throwable $e) {
        echo "\nERROR running on {$dbConfig['name']}: ".$e->getMessage()."\n";
        echo $e->getTraceAsString()."\n\n";
        echo "Halting execution.\n";
        exit(1);
    }
}

echo "=== All target databases synchronized and seeded successfully! ===\n";
