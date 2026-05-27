<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$posts = App\Models\BlogPost::all();
foreach ($posts as $p) {
    echo "ID: {$p->id} | Title: {$p->title} | Slug: {$p->slug}\n";
}
