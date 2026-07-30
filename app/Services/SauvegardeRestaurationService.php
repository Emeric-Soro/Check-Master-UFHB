<?php
namespace CheckMaster\Services;

use CheckMaster\Core\AppConfig;
use CheckMaster\Core\Messages;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AuditLog.php';

use AuditLog;
use Database;
use PDO;
use PDOException;
use Exception;

class SauvegardeRestaurationService
{
    private $backupDir;
    private $auditLog;

    public function __construct($db)
    {
        $this->backupDir = AppConfig::uploadPath() . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0750, true);
        }

        $this->auditLog = new AuditLog($db);
    }

    // -----------------------------------------------------------------
    //  Database configuration
    // -----------------------------------------------------------------

    public function getDbConfig(): array
    {
        return Database::getConfig();
    }

    // -----------------------------------------------------------------
    //  Backup listing
    // -----------------------------------------------------------------

    /**
     * Liste les sauvegardes existantes.
     * @return array
     */
    public function getBackups(): array
    {
        if (!is_dir($this->backupDir)) {
            return [];
        }

        $pattern = $this->backupDir . '*.sql';
        $files = glob($pattern);

        $backups = [];
        foreach ($files as $file) {
            if (is_file($file)) {
                $backups[] = [
                    'filename'   => basename($file),
                    'size'       => $this->humanFileSize(filesize($file)),
                    'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                    'type'       => 'Manuelle',
                ];
            }
        }

        usort($backups, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return $backups;
    }

    // -----------------------------------------------------------------
    //  Create backup
    // -----------------------------------------------------------------

    /**
     * Crée une sauvegarde de la base de données.
     *
     * @param string      $backupName  Nom personnalisé (ou null pour auto).
     * @param int         $userId      ID de l'utilisateur courant.
     * @return array{success: bool, redirect: string}
     */
    public function createBackup(?string $backupName, int $userId): array
    {
        $backupName = $backupName ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $backupName) : 'backup_' . date('Ymd_His');
        $filename   = $backupName . '_' . date('Ymd_His') . '.sql';
        $filepath   = $this->backupDir . $filename;

        // Essayer d'abord avec la méthode PHP (plus fiable)
        if ($this->createBackupWithPHP($filepath, $this->getDbConfig())) {
            $this->auditLog->logAction($userId, 'Sauvegarde', 'base_de_donnees', 'Succès');
            return ['success' => true, 'redirect' => '?page=sauvegarde_restauration&success=1'];
        }

        // Si PHP échoue, essayer avec Docker
        if ($this->isDockerAvailable()) {
            $containerName = $this->getDockerContainerName();

            if ($this->isContainerRunning($containerName)) {
                $dbConfig = $this->getDbConfig();
                $cmd = sprintf(
                    'docker exec -i %s mysqldump -h%s -u%s -p%s %s > %s 2>/dev/null',
                    escapeshellarg($containerName),
                    escapeshellarg($dbConfig['host']),
                    escapeshellarg($dbConfig['user']),
                    escapeshellarg($dbConfig['pass']),
                    escapeshellarg($dbConfig['db']),
                    escapeshellarg($filepath)
                );

                system($cmd, $retval);

                if ($retval === 0 && file_exists($filepath) && filesize($filepath) > 0) {
                    $this->auditLog->logAction($userId, 'Sauvegarde', 'base_de_donnees', 'Succès');
                    return ['success' => true, 'redirect' => '?page=sauvegarde_restauration&success=1'];
                }
            }
        }

        // Si toutes les méthodes échouent
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $this->auditLog->logAction($userId, 'Sauvegarde', 'base_de_donnees', 'Erreur');
        return ['success' => false, 'redirect' => '?page=sauvegarde_restauration&error=backup_failed'];
    }

    // -----------------------------------------------------------------
    //  Restore backup
    // -----------------------------------------------------------------

    /**
     * Restaure la base de données à partir d'un fichier SQL.
     *
     * @param string $filename Nom du fichier de sauvegarde.
     * @param int    $userId   ID de l'utilisateur courant.
     * @return array{success: bool, redirect: string}
     */
    public function restoreBackup(string $filename, int $userId): array
    {
        $filename = basename($filename);
        $filepath = $this->backupDir . $filename;

        if (!file_exists($filepath)) {
            error_log("Erreur: Fichier de sauvegarde introuvable: " . $filepath);
            return ['success' => false, 'redirect' => '?page=sauvegarde_restauration&error=1'];
        }

        $dbConfig = $this->getDbConfig();

        // Essayer d'abord avec PHP PDO
        if ($this->restoreBackupWithPHP($filepath, $dbConfig)) {
            $this->auditLog->logAction($userId, 'Restauration', 'base_de_donnees', 'Succès');
            return ['success' => true, 'redirect' => '?page=sauvegarde_restauration&restored=1'];
        }

        // Si PHP échoue, essayer avec Docker
        if ($this->isDockerAvailable()) {
            $containerName = $this->getDockerContainerName();

            if ($this->isContainerRunning($containerName)) {
                $cmd = sprintf(
                    'docker exec -i %s mysql -h%s -u%s -p%s %s < %s 2>/dev/null',
                    escapeshellarg($containerName),
                    escapeshellarg($dbConfig['host']),
                    escapeshellarg($dbConfig['user']),
                    escapeshellarg($dbConfig['pass']),
                    escapeshellarg($dbConfig['db']),
                    escapeshellarg($filepath)
                );

                system($cmd, $retval);
                if ($retval === 0) {
                    $this->auditLog->logAction($userId, 'Restauration', 'base_de_donnees', 'Succès');
                    return ['success' => true, 'redirect' => '?page=sauvegarde_restauration&restored=1'];
                } else {
                    error_log("Erreur Docker: La commande de restauration Docker a échoué avec le code de retour: " . $retval);
                }
            } else {
                error_log("Erreur Docker: Le conteneur Docker '" . $containerName . "' n'est pas en cours d'exécution.");
            }
        }

        // Si toutes les tentatives échouent
        $this->auditLog->logAction($userId, 'Restauration', 'base_de_donnees', 'Erreur');
        return ['success' => false, 'redirect' => '?page=sauvegarde_restauration&error=1'];
    }

    // -----------------------------------------------------------------
    //  Delete backup
    // -----------------------------------------------------------------

    /**
     * Supprime un fichier de sauvegarde.
     *
     * @param string $filename Nom du fichier.
     * @param int    $userId   ID de l'utilisateur courant.
     * @return array{success: bool, redirect: string}
     */
    public function deleteBackup(string $filename, int $userId): array
    {
        $filename = basename($filename);
        $filepath = $this->backupDir . $filename;

        if (file_exists($filepath)) {
            unlink($filepath);
            $this->auditLog->logAction($userId, 'Suppression', 'sauvegarde', 'Succès');
            return ['success' => true, 'redirect' => '?page=sauvegarde_restauration&deleted=1'];
        }

        $this->auditLog->logAction($userId, 'Suppression', 'sauvegarde', 'Erreur');
        return ['success' => false, 'redirect' => '?page=sauvegarde_restauration&error=1'];
    }

    // -----------------------------------------------------------------
    //  Download backup
    // -----------------------------------------------------------------

    /**
     * Prépare le téléchargement d'un fichier de sauvegarde.
     *
     * @param string $filename Nom du fichier.
     * @param int    $userId   ID de l'utilisateur courant.
     * @return array{success: bool, filepath?: string, filename?: string, filesize?: int, redirect?: string}
     */
    public function downloadBackup(string $filename, int $userId): array
    {
        $filename = basename($filename);
        $filepath = $this->backupDir . $filename;

        if (file_exists($filepath)) {
            $this->auditLog->logAction($userId, 'Téléchargement', 'sauvegarde', 'Succès');
            return [
                'success'  => true,
                'filepath' => $filepath,
                'filename' => $filename,
                'filesize' => filesize($filepath),
            ];
        }

        $this->auditLog->logAction($userId, 'Téléchargement', 'sauvegarde', 'Erreur');
        return ['success' => false, 'redirect' => '?page=sauvegarde_restauration&error=1'];
    }

    // -----------------------------------------------------------------
    //  Diagnostics
    // -----------------------------------------------------------------

    /**
     * Méthode de test pour diagnostiquer les problèmes de restauration.
     */
    public function testRestore(string $filepath): array
    {
        $diagnostic = [
            'success'  => false,
            'errors'   => [],
            'warnings' => [],
            'info'     => [],
        ];

        try {
            $dbConfig = $this->getDbConfig();
            $diagnostic['info']['db_config'] = $dbConfig;

            if (!file_exists($filepath)) {
                $diagnostic['errors'][] = "Fichier introuvable: $filepath";
                return $diagnostic;
            }

            $diagnostic['info']['file_size'] = filesize($filepath);
            $diagnostic['info']['file_path'] = $filepath;

            try {
                $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset=utf8";
                $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $diagnostic['info']['connection'] = 'success';

                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                $diagnostic['info']['existing_tables'] = count($tables);
            } catch (Exception $e) {
                $diagnostic['errors'][] = "Erreur de connexion: " . $e->getMessage();
                return $diagnostic;
            }

            $sql = file_get_contents($filepath);
            if ($sql === false) {
                $diagnostic['errors'][] = "Impossible de lire le fichier SQL";
                return $diagnostic;
            }

            $diagnostic['info']['sql_length'] = strlen($sql);

            $queries = $this->splitSQL($sql);
            $diagnostic['info']['parsed_queries'] = count($queries);

            if (count($queries) == 0) {
                $diagnostic['errors'][] = "Aucune requête SQL trouvée dans le fichier";
                return $diagnostic;
            }

            $diagnostic['info']['first_query'] = substr($queries[0], 0, 100) . '...';
            $diagnostic['info']['last_query']  = substr($queries[count($queries) - 1], 0, 100) . '...';

            $testQueries  = array_slice($queries, 0, 5);
            $testResults   = [];

            foreach ($testQueries as $index => $query) {
                try {
                    $result = $pdo->exec($query);
                    $testResults[$index] = [
                        'success'       => true,
                        'rows_affected' => $result,
                    ];
                } catch (PDOException $e) {
                    $testResults[$index] = [
                        'success' => false,
                        'error'   => $e->getMessage(),
                    ];
                    $diagnostic['warnings'][] = "Erreur dans la requête #$index: " . $e->getMessage();
                }
            }

            $diagnostic['info']['test_results'] = $testResults;
            $diagnostic['success'] = true;
        } catch (Exception $e) {
            $diagnostic['errors'][] = "Erreur générale: " . $e->getMessage();
        }

        return $diagnostic;
    }

    /**
     * Méthode de diagnostic spécifique pour analyser les requêtes INSERT.
     */
    public function diagnoseInsertQueries(string $filepath): array
    {
        $diagnostic = [
            'success'        => false,
            'insert_queries' => [],
            'problems'       => [],
            'statistics'     => [],
        ];

        try {
            $sql = file_get_contents($filepath);
            if ($sql === false) {
                $diagnostic['problems'][] = "Impossible de lire le fichier SQL";
                return $diagnostic;
            }

            $queries      = $this->splitSQL($sql);
            $insertQueries = [];
            $tableStats    = [];

            foreach ($queries as $index => $query) {
                if (preg_match('/^INSERT\s+INTO\s+`?(\w+)`?\s+VALUES/i', $query, $matches)) {
                    $tableName = $matches[1];

                    $valueCount = preg_match_all('/\([^)]+\)/', $query, $valueMatches);

                    $insertQueries[] = [
                        'index'       => $index,
                        'table'       => $tableName,
                        'query'       => $query,
                        'value_count' => $valueCount,
                        'first_value' => $valueMatches[0][0] ?? 'N/A',
                        'last_value'  => $valueMatches[0][$valueCount - 1] ?? 'N/A',
                    ];

                    if (!isset($tableStats[$tableName])) {
                        $tableStats[$tableName] = [
                            'query_count'  => 0,
                            'total_values' => 0,
                            'queries'      => [],
                        ];
                    }

                    $tableStats[$tableName]['query_count']++;
                    $tableStats[$tableName]['total_values'] += $valueCount;
                    $tableStats[$tableName]['queries'][] = $index;
                }
            }

            $diagnostic['insert_queries'] = $insertQueries;
            $diagnostic['statistics'] = [
                'total_insert_queries' => count($insertQueries),
                'tables_affected'      => count($tableStats),
                'table_statistics'     => $tableStats,
            ];

            foreach ($tableStats as $tableName => $stats) {
                if ($stats['query_count'] > 1) {
                    $diagnostic['problems'][] = "Table '$tableName' a {$stats['query_count']} requêtes INSERT - risque de fragmentation";
                }
                if ($stats['total_values'] < 1) {
                    $diagnostic['problems'][] = "Table '$tableName' n'a aucune valeur INSERT détectée";
                }
            }

            foreach ($insertQueries as $insert) {
                if ($insert['value_count'] == 0) {
                    $diagnostic['problems'][] = "Requête INSERT #{$insert['index']} pour table '{$insert['table']}' n'a aucune valeur détectée";
                }
                if (strlen($insert['query']) > 10000) {
                    $diagnostic['problems'][] = "Requête INSERT #{$insert['index']} pour table '{$insert['table']}' est très longue (" . strlen($insert['query']) . " caractères)";
                }
            }

            $diagnostic['success'] = true;
        } catch (Exception $e) {
            $diagnostic['problems'][] = "Erreur lors du diagnostic: " . $e->getMessage();
        }

        return $diagnostic;
    }

    // =================================================================
    //  Private helpers
    // =================================================================

    private function getDockerContainerName(): string
    {
        $cmd = "docker ps --filter 'ancestor=mysql:8.0' --format '{{.Names}}' 2>/dev/null";
        $containerName = shell_exec($cmd);

        if ($containerName === null || empty(trim($containerName))) {
            $containerName = 'projet_soutenance-db-1';
        } else {
            $containerName = trim($containerName);
        }

        return $containerName;
    }

    private function isDockerAvailable(): bool
    {
        $output = shell_exec('docker --version 2>/dev/null');
        if ($output !== null && strpos($output, 'Docker version') !== false) {
            return true;
        }

        $output = shell_exec('docker-compose --version 2>/dev/null');
        if ($output !== null && strpos($output, 'docker-compose version') !== false) {
            return true;
        }

        $output = shell_exec('docker.exe --version 2>/dev/null');
        if ($output !== null && strpos($output, 'Docker version') !== false) {
            return true;
        }

        return false;
    }

    private function isContainerRunning(string $containerName): bool
    {
        $cmd = sprintf('docker ps --filter "name=%s" --format "{{.Names}}" 2>/dev/null', escapeshellarg($containerName));
        $output = shell_exec($cmd);
        return $output !== null && !empty(trim($output));
    }

    private function createBackupWithPHP(string $filepath, array $dbConfig): bool
    {
        try {
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset=utf8";
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            $backup = "-- Sauvegarde générée par PHP\n";
            $backup .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";

            foreach ($tables as $table) {
                $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
                $backup .= "\n-- Structure de la table `$table`\n";
                $backup .= "DROP TABLE IF EXISTS `$table`;\n";
                $backup .= $createTable['Create Table'] . ";\n\n";

                $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    $backup .= "-- Données de la table `$table`\n";
                    foreach ($rows as $row) {
                        $values = array_map(function ($value) use ($pdo) {
                            if ($value === null) {
                                return 'NULL';
                            }
                            return $pdo->quote($value);
                        }, $row);
                        $backup .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $backup .= "\n";
                }
            }

            file_put_contents($filepath, $backup);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Restaure la base de données en utilisant PHP PDO.
     */
    public function restoreBackupWithPHP(string $filepath, array $dbConfig): bool
    {
        $pdo = null;

        try {
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset=utf8";
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            $this->truncateAllTables($pdo);

            $sql = file_get_contents($filepath);
            if ($sql === false) {
                error_log("Erreur: Impossible de lire le fichier de sauvegarde: $filepath");
                return false;
            }

            $queries = $this->splitSQL($sql);
            error_log("Nombre de requêtes parsées: " . count($queries));

            $successCount = 0;
            $errorCount   = 0;
            $insertCount  = 0;
            $createCount  = 0;

            foreach ($queries as $index => $query) {
                $query = trim($query);
                if (!empty($query) && !preg_match('/^(--|\/\*|#)/', $query)) {
                    try {
                        $result = $pdo->exec($query);
                        if ($result !== false) {
                            $successCount++;
                            if (preg_match('/^INSERT\s+INTO/i', $query)) {
                                $insertCount++;
                            } elseif (preg_match('/^CREATE\s+TABLE/i', $query)) {
                                $createCount++;
                            }
                        }
                    } catch (PDOException $e) {
                        $errorMsg = $e->getMessage();
                        if (
                            strpos($errorMsg, 'already exists') !== false ||
                            strpos($errorMsg, 'Duplicate entry') !== false ||
                            strpos($errorMsg, "doesn't exist") !== false
                        ) {
                            error_log("Requête ignorée (erreur attendue): " . substr($query, 0, 100) . "... - " . $errorMsg);
                            continue;
                        }
                        error_log("Erreur SQL à la requête #$index: " . $errorMsg);
                        error_log("Requête problématique: " . substr($query, 0, 200) . "...");
                        $errorCount++;
                    }
                }
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            error_log("Restauration terminée - Succès: $successCount, Erreurs: $errorCount, INSERT: $insertCount, CREATE: $createCount");
            return $successCount > 0;
        } catch (Exception $e) {
            error_log("Erreur générale lors de la restauration: " . $e->getMessage());
            if ($pdo) {
                try {
                    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                } catch (Exception $e2) {
                    // Ignorer les erreurs de réactivation
                }
            }
            return false;
        }
    }

    private function truncateAllTables($pdo): bool
    {
        try {
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `$table`");
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Divise une chaîne SQL en requêtes individuelles.
     */
    public function splitSQL(string $sql): array
    {
        $sql = trim($sql);

        $queries      = [];
        $currentQuery = '';
        $inString     = false;
        $stringChar   = '';
        $inComment    = false;
        $commentType  = '';
        $lineNumber   = 0;

        for ($i = 0; $i < strlen($sql); $i++) {
            $char     = $sql[$i];
            $nextChar = ($i < strlen($sql) - 1) ? $sql[$i + 1] : '';

            if ($char === "\n") {
                $lineNumber++;
            }

            // Gestion des commentaires
            if (!$inString && !$inComment) {
                if ($char === '-' && $nextChar === '-') {
                    $inComment   = true;
                    $commentType = 'line';
                    $i++;
                    continue;
                }
                if ($char === '#') {
                    $inComment   = true;
                    $commentType = 'line';
                    continue;
                }
                if ($char === '/' && $nextChar === '*') {
                    $inComment   = true;
                    $commentType = 'block';
                    $i++;
                    continue;
                }
            }

            if ($inComment) {
                if ($commentType === 'line' && $char === "\n") {
                    $inComment   = false;
                    $commentType = '';
                } elseif ($commentType === 'block' && $char === '*' && $nextChar === '/') {
                    $inComment   = false;
                    $commentType = '';
                    $i++;
                }
                continue;
            }

            // Gestion des chaînes de caractères
            if (!$inComment) {
                if (!$inString && ($char === "'" || $char === '"')) {
                    $inString   = true;
                    $stringChar = $char;
                } elseif ($inString && $char === $stringChar) {
                    if ($i > 0 && $sql[$i - 1] !== '\\') {
                        $inString   = false;
                        $stringChar = '';
                    }
                }
            }

            if (!$inComment) {
                $currentQuery .= $char;
            }

            if ($char === ';' && !$inString && !$inComment) {
                $currentQuery = trim($currentQuery);
                if (!empty($currentQuery) && !preg_match('/^\s*$/', $currentQuery)) {
                    $queries[] = $currentQuery;
                }
                $currentQuery = '';
            }
        }

        $currentQuery = trim($currentQuery);
        if (!empty($currentQuery) && !preg_match('/^\s*$/', $currentQuery)) {
            $queries[] = $currentQuery;
        }

        return $queries;
    }

    private function humanFileSize($size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unit  = 0;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        return round($size, $precision) . ' ' . $units[$unit];
    }
}