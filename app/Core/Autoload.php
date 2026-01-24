<?php
/**
 * Autoloader minimal (sans framework) pour nouvelles classes sous namespace CheckMaster\.
 * Note: on garde Composer pour vendor/ 
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'CheckMaster\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    $baseDir = dirname(__DIR__); // app/
    $file = $baseDir . DIRECTORY_SEPARATOR . $relativePath;

    if (is_file($file)) {
        require_once $file;
    }
});

