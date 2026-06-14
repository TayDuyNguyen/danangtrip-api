<?php

use DG\BypassFinals;

require __DIR__.'/../vendor/autoload.php';

if (class_exists(BypassFinals::class)) {
    $cacheDir = __DIR__.'/../storage/framework/cache/bypass_finals';
    if (! is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }
    BypassFinals::setCacheDirectory($cacheDir);
    BypassFinals::enable();
}
