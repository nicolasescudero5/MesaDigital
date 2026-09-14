<?php
// mesa/tests/bootstrap.php

$rootAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($rootAutoload)) {
    require_once $rootAutoload;
}

spl_autoload_register(function ($class) {
    if (strncmp('App\\', $class, 4) === 0) {
        $relativeClass = substr($class, 4);
        $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    if (strncmp('Tests\\', $class, 6) === 0) {
        $relativeClass = substr($class, 6);
        $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

if (file_exists(dirname(__DIR__) . '/src/Support/Helpers.php')) {
    require_once dirname(__DIR__) . '/src/Support/Helpers.php';
}
