<?php

use App\Models\BlogPost;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$posts = BlogPost::all();
foreach ($posts as $p) {
    echo "ID: {$p->id} | Title: {$p->title} | Slug: {$p->slug}\n";
}
