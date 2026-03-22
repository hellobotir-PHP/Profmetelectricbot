<?php

spl_autoload_register(function ($class) {
    $classPath = str_replace('\\', '/', $class) . '.php';
    $file = __DIR__ . '/' . $classPath;

    if (file_exists($file)) {
        require $file;
    } else {
        error_log("Autoload not found: $file");
    }
});
