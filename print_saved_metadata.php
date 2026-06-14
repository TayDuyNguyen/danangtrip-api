<?php

use App\Models\ChatMessage;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$msg = ChatMessage::latest()->first();
if ($msg) {
    echo "Metadata:\n";
    print_r($msg->metadata);
}
