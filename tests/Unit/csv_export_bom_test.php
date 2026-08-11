<?php
/**
 * Régression : exports CSV sans BOM UTF-8 → accents cassés dans Excel.
 * Ticket : "Commissions & Archives (à corriger)" — le formatage des accents.
 *
 * Les méthodes d'export appellent exit() : le test lance un sous-processus CLI
 * (PHP_BINARY) qui exécute réellement l'export et capture les octets bruts.
 */

$GLOBALS['__csv_root__'] = dirname(__DIR__, 2);
$GLOBALS['__csv_runner__'] = sys_get_temp_dir() . '/checkmaster_csv_export_runner_' . getmypid() . '.php';

function csvRunnerContent(): string
{
    $root = $GLOBALS['__csv_root__'];
    return str_replace('{{ROOT}}', $root, <<<'PHP'
<?php
session_start();
$_SESSION['id_utilisateur'] = 5;
$_SESSION['id_GU'] = 14;
$_SESSION['login'] = 'kbrou';
$_SESSION['gu'] = 14;

require '{{ROOT}}\app\Core\Autoload.php';
\CheckMaster\Core\Bootstrap::init();

$target = $argv[1] ?? 'etudiants';
if ($target === 'cr') {
    require '{{ROOT}}\app\controllers\ArchivesCompteRenduController.php';
    $c = new ArchivesCompteRenduController();
    $c->exportArchives();
} else {
    require '{{ROOT}}\app\controllers\ArchiveEtudiantController.php';
    $c = new ArchiveEtudiantController();
    $c->exportCsv();
}
PHP);
}

function runCsvExport(string $target): array
{
    $runner = $GLOBALS['__csv_runner__'];
    file_put_contents($runner, csvRunnerContent());
    try {
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($runner) . ' ' . escapeshellarg($target);
        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);
    } finally {
        @unlink($runner);
    }
    return [$code, $out, $err];
}

test('CSV Export : ArchiveEtudiant::exportCsv démarre par le BOM UTF-8', function () {
    [$code, $out, $err] = runCsvExport('etudiants');
    assertTrue($code === 0, 'sous-processus en échec: ' . $err);
    assertContains('Matricule', $out, 'sortie CSV attendue (en-têtes)');
    assertTrue(substr($out, 0, 3) === "\xEF\xBB\xBF", 'BOM UTF-8 manquant en début de CSV — accents cassés dans Excel');
});

test('CSV Export : ArchivesCompteRendu::exportArchives démarre par le BOM UTF-8', function () {
    [$code, $out, $err] = runCsvExport('cr');
    assertTrue($code === 0, 'sous-processus en échec: ' . $err);
    assertContains('Nom CR', $out, 'sortie CSV attendue (en-têtes)');
    assertTrue(substr($out, 0, 3) === "\xEF\xBB\xBF", 'BOM UTF-8 manquant en début de CSV');
});
