<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

echo "All Cafes in Database:\n";
$cafes = DB::table('locations')
    ->where('name', 'like', '%cà phê%')
    ->orWhere('name', 'like', '%cafe%')
    ->orWhere('name', 'like', '%coffee%')
    ->get(['id', 'name', 'address', 'description']);

foreach ($cafes as $c) {
    echo " - ID: {$c->id} | Name: {$c->name} | Address: {$c->address}\n";
    echo '   Desc: '.mb_substr(strip_tags($c->description), 0, 100)."...\n\n";
}

echo "Searching for cafes with 'biển' or 'sea':\n";
$seaCafes = DB::table('locations')
    ->where(function ($query) {
        $query->where('name', 'like', '%cà phê%')
            ->orWhere('name', 'like', '%cafe%')
            ->orWhere('name', 'like', '%coffee%');
    })
    ->where(function ($query) {
        $query->where('description', 'like', '%biển%')
            ->orWhere('description', 'like', '%sea%')
            ->orWhere('address', 'like', '%Võ Nguyên Giáp%')
            ->orWhere('address', 'like', '%Trường Sa%');
    })
    ->get(['id', 'name', 'address']);

foreach ($seaCafes as $sc) {
    echo " - ID: {$sc->id} | Name: {$sc->name} | Address: {$sc->address}\n";
}
