<?php

$namespaceMap = require __DIR__ . "/namespace.php";
// autoload.php
spl_autoload_register(function ($className) use ($namespaceMap) {

    $className = ltrim($className, '\\');

    foreach ($namespaceMap as $prefix => $directory) {
        if (strpos($className, $prefix) === 0) {
            $relativeClass = substr($className, strlen($prefix));
            $filePath = realpath(dirname(__DIR__ . '/', 1)) . '/' . $directory . str_replace('\\', '/', $relativeClass) . '.php';
            // $filePath = strtolower($filePath);


            if ($filePath && file_exists($filePath)) {
                require_once $filePath;
                return;
            } else {
                echo '<pre>'; print_r("File not found: $filePath"); exit;
            }
        }
    }

    echo '<pre>'; print_r("Class not found: $className"); exit;

});
