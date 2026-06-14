<?php

use App\Models\ChatMessage;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$msg = ChatMessage::latest()->first();
if ($msg) {
    echo 'Saved Message ID: '.$msg->id."\n";
    echo 'Intent: '.$msg->intent."\n";
    echo 'Question: '.$msg->question."\n";
    echo "Metadata understanding:\n";
    print_r($msg->metadata['understanding'] ?? []);
}
