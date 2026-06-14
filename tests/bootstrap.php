<?php

require __DIR__ . '/../vendor/autoload.php';

if (class_exists(DG\BypassFinals::class)) {
    $cacheDir = __DIR__ . '/../storage/framework/cache/bypass_finals';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }
    DG\BypassFinals::setCacheDirectory($cacheDir);
    DG\BypassFinals::enable();
}



