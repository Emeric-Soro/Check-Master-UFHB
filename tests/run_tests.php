<?php
/**
 * Mini harnais de tests pour CheckMaster (sans PHPUnit).
 * Usage : php tests/run_tests.php [--filter=...] [--verbose]
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);
require $root . '/app/Core/Autoload.php';

use CheckMaster\Core\Bootstrap;

Bootstrap::init();

// ─── Récupération des arguments ─────────────────────────────
$filter = null;
$verbose = false;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--filter=')) {
        $filter = substr($arg, strlen('--filter='));
    } elseif ($arg === '--verbose') {
        $verbose = true;
    }
}

// ─── Registre des tests ─────────────────────────────────────
$GLOBALS['__TESTS__'] = [];

/**
 * Enregistrer un test.
 * @param string $name
 * @param callable():void $fn
 */
function test(string $name, callable $fn): void
{
    $GLOBALS['__TESTS__'][] = ['name' => $name, 'fn' => $fn];
}

/**
 * Assertions minimales.
 */
function assertTrue(bool $cond, string $msg = ''): void
{
    if (!$cond) {
        throw new RuntimeException('assertTrue failed' . ($msg !== '' ? ': ' . $msg : ''));
    }
}

function assertFalse(bool $cond, string $msg = ''): void
{
    if ($cond) {
        throw new RuntimeException('assertFalse failed' . ($msg !== '' ? ': ' . $msg : ''));
    }
}

function assertEquals($expected, $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        $e = is_scalar($expected) || $expected === null ? var_export($expected, true) : gettype($expected);
        $a = is_scalar($actual) || $actual === null ? var_export($actual, true) : gettype($actual);
        throw new RuntimeException(
            'assertEquals failed' . ($msg !== '' ? ': ' . $msg : '')
            . ' — expected ' . $e . ', got ' . $a
        );
    }
}

function assertContains(string $needle, string $haystack, string $msg = ''): void
{
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException(
            'assertContains failed' . ($msg !== '' ? ': ' . $msg : '')
            . ' — needle "' . $needle . '" not found in haystack'
        );
    }
}

function assertInstanceOf(string $class, $obj, string $msg = ''): void
{
    if (!$obj instanceof $class) {
        throw new RuntimeException(
            'assertInstanceOf failed' . ($msg !== '' ? ': ' . $msg : '')
            . ' — expected ' . $class . ', got ' . (is_object($obj) ? get_class($obj) : gettype($obj))
        );
    }
}

// ─── Chargement des fichiers de tests ───────────────────────
$testFiles = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
foreach ($it as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && $file->getFilename() !== 'run_tests.php') {
        $testFiles[] = $file->getPathname();
    }
}
sort($testFiles);
foreach ($testFiles as $file) {
    require_once $file;
}

// ─── Exécution ──────────────────────────────────────────────
$pass = 0;
$fail = 0;
$failures = [];
foreach ($GLOBALS['__TESTS__'] as $t) {
    if ($filter !== null && !str_contains($t['name'], $filter)) {
        continue;
    }
    $start = microtime(true);
    try {
        $t['fn']();
        $pass++;
        if ($verbose) {
            printf("  PASS  %-70s (%.1fms)\n", $t['name'], (microtime(true) - $start) * 1000);
        }
    } catch (\Throwable $e) {
        $fail++;
        $failures[] = $t['name'] . ' :: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine();
    }
}

printf("\n%d tests, %d passés, %d échecs\n", $pass + $fail, $pass, $fail);
foreach ($failures as $f) {
    echo "  FAIL  " . $f . "\n";
}
exit($fail > 0 ? 1 : 0);
