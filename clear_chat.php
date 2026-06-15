<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Chỉ xóa chat_cache và chat_messages, KHÔNG xóa chat_knowledge_base
$targets = ['chat_cache', 'chat_messages'];

echo '=== CLEARING CHAT CACHE & MESSAGES ==='.PHP_EOL;

foreach ($targets as $table) {
    try {
        $before = DB::table($table)->count();
        $deleted = DB::table($table)->delete();
        echo '✓ Cleared '.$table.': '.$deleted.' rows deleted (was '.$before.')'.PHP_EOL;
    } catch (Throwable $e) {
        echo '✗ Failed: '.$table.' — '.$e->getMessage().PHP_EOL;
    }
}

$kbCount = DB::table('chat_knowledge_base')->count();
echo PHP_EOL.'✓ chat_knowledge_base UNTOUCHED: '.$kbCount.' rows still intact'.PHP_EOL;
echo PHP_EOL.'Done!'.PHP_EOL;
