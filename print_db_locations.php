<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$total = DB::table('locations')->count();
echo "Total locations: $total\n\n";

$first10 = DB::table('locations')->take(10)->get(['id', 'name', 'address']);
foreach ($first10 as $l) {
    echo " - ID: {$l->id} | Name: {$l->name} | Address: {$l->address}\n";
}
