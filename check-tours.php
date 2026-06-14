<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$msg = App\Models\ChatMessage::latest()->first();
if ($msg) {
    echo "Question: {$msg->question}\n";
    echo "Intent: {$msg->intent}\n";
    echo "Metadata: " . json_encode($msg->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "Context Count: " . count($msg->context) . "\n";
} else {
    echo "No chat messages found\n";
}
