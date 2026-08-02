<?php
/**
 * Autoloader central du projet CheckMaster.
 *
 * 1. Charge le Composer autoloader (PSR-4 : App\ -> src/ + app/, vendor/).
 * 2. Fallback SPL pour le namespace CheckMaster\ (mapping legacy -> app/).
 */

// --- Composer autoloader (gère App\, vendor/ et PSR-4) ---
$composerAutoload = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

// --- Fallback SPL pour les classes sous namespace CheckMaster\ ---
spl_autoload_register(function (string $class): void {
    $prefix = 'CheckMaster\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    
    // Split the relative path to handle lowercase directories with capitalized class filenames
    $parts = explode('\\', $relative);
    $className = array_pop($parts);
    $subDirs = array_map('strtolower', $parts);
    
    $subPath = implode(DIRECTORY_SEPARATOR, $subDirs);
    if ($subPath !== '') {
        $subPath .= DIRECTORY_SEPARATOR;
    }
    
    $baseDir = dirname(__DIR__); // app/
    $file = $baseDir . DIRECTORY_SEPARATOR . $subPath . $className . '.php';

    if (is_file($file)) {
        require_once $file;
        return;
    }

    // Fallback 1: Exact case matching
    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    $fileAlt = $baseDir . DIRECTORY_SEPARATOR . $relativePath;
    if (is_file($fileAlt)) {
        require_once $fileAlt;
        return;
    }

    // Fallback 2: Full lowercase
    $fileLower = $baseDir . DIRECTORY_SEPARATOR . strtolower($relativePath);
    if (is_file($fileLower)) {
        require_once $fileLower;
        return;
    }
});
