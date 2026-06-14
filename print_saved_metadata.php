<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$msg = App\Models\ChatMessage::latest()->first();
if ($msg) {
    echo "Metadata:\n";
    print_r($msg->metadata);
}
