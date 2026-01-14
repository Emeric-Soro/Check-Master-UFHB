<?php

namespace App\Controllers;

use PDO;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * SauvegardeRestaurationController - Backup/Restore de la base de données
 * 
 * Ce contrôleur gère les opérations de sauvegarde et restauration :
 * - Création de sauvegardes manuelles
 * - Restauration de la base de données
 * - Suppression et téléchargement des sauvegardes
 * 
 * @package App\Controllers
 */
class SauvegardeRestaurationController
{
    private string $backupDir;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        // Utiliser un chemin absolu pour le dossier de sauvegarde
        $this->backupDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
        
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Vérification centralisée des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'sauvegarde_restauration', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur sauvegarde_restauration"
            );
            
            $GLOBALS['messageErreur'] = "Vous n'avez pas les droits nécessaires pour effectuer cette action.";
            
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                http_response_code(403);
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            
            return false;
        }
        
        return true;
    }

    /**
     * Obtient la configuration de la base de données
     */
    public function getDbConfig(): array
    {
        return [
            'host' => 'db',
            'db'   => 'soutenance_manager',
            'user' => 'root',
            'pass' => 'password',
        ];
    }

    /**
     * Détecte automatiquement le nom du conteneur Docker
     */
    private function getDockerContainerName(): string
    {
        // Essayer de détecter le conteneur MySQL
        $cmd = "docker ps --filter 'ancestor=mysql:8.0' --format '{{.Names}}' 2>/dev/null";
        $containerName = shell_exec($cmd);
        
        // Gérer le cas où shell_exec retourne null
        if ($containerName === null || empty(trim($containerName))) {
            // Fallback : essayer avec le nom par défaut
            $containerName = 'projet_soutenance-db-1';
        } else {
            $containerName = trim($containerName);
        }
        
        return $containerName;
    }

    /**
     * Vérifie si Docker est disponible
     */
    private function isDockerAvailable(): bool
    {
        // Essayer plusieurs méthodes pour détecter Docker
        $output = shell_exec('docker --version 2>/dev/null');
        if ($output !== null && strpos($output, 'Docker version') !== false) {
            return true;
        }
        
        // Essayer avec docker-compose
        $output = shell_exec('docker-compose --version 2>/dev/null');
        if ($output !== null && strpos($output, 'docker-compose version') !== false) {
            return true;
        }
        
        // Essayer avec docker.exe (Windows)
        $output = shell_exec('docker.exe --version 2>/dev/null');
        if ($output !== null && strpos($output, 'Docker version') !== false) {
            return true;
        }
        
        return false;
    }

    /**
     * Sauvegarde avec PHP PDO (méthode de secours)
     */
    private function createBackupWithPHP(string $filepath, array $dbConfig): bool
    {
        try {
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset=utf8";
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Récupérer toutes les tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            $backup = "-- Sauvegarde générée par PHP\n";
            $backup .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach ($tables as $table) {
                // Structure de la table
                $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
                $backup .= "\n-- Structure de la table `$table`\n";
                $backup .= "DROP TABLE IF EXISTS `$table`;\n";
                $backup .= $createTable['Create Table'] . ";\n\n";
                
                // Données de la table
                $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    $backup .= "-- Données de la table `$table`\n";
                    foreach ($rows as $row) {
                        $values = array_map(function($value) use ($pdo) {
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
            $this->logger->error("Erreur createBackupWithPHP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Action : Lance une sauvegarde manuelle (CREATE)
     */
    public function createBackup(): bool
    {
        // Vérification des permissions
        if (!$this->checkPermission('create')) {
            return false;
        }

        // S'assurer qu'aucune sortie n'a été envoyée
        if (headers_sent()) {
            return false;
        }

        try {
            $backupName = isset($_POST['backup_name']) && $_POST['backup_name'] ? 
                preg_replace('/[^a-zA-Z0-9_-]/', '_', $this->security->sanitizeInput($_POST['backup_name'])) : 
                'backup_' . date('Ymd_His');
            $filename = $backupName . '_' . date('Ymd_His') . '.sql';
            $filepath = $this->backupDir . $filename;
            
            // Essayer d'abord avec la méthode PHP (plus fiable)
            if ($this->createBackupWithPHP($filepath, $this->getDbConfig())) {
                $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Sauvegarde', 'base_de_donnees', 'Succès');
                $this->logger->info("Sauvegarde créée: " . $filename);
                
                header('Location: ?page=sauvegarde_restauration&success=1');
                exit;
            }
            
            // Si PHP échoue, essayer avec Docker
            if ($this->isDockerAvailable()) {
                $containerName = $this->getDockerContainerName();
            
            // Vérifier si le conteneur est en cours d'exécution
            if ($this->isContainerRunning($containerName)) {
                // Utiliser Docker pour exécuter mysqldump
                $cmd = sprintf('docker exec -i %s mysqldump -h%s -u%s -p%s %s > %s 2>/dev/null',
                    escapeshellarg($containerName),
                    escapeshellarg($this->getDbConfig()['host']),
                    escapeshellarg($this->getDbConfig()['user']),
                    escapeshellarg($this->getDbConfig()['pass']),
                    escapeshellarg($this->getDbConfig()['db']),
                    escapeshellarg($filepath)
                );
                
                system($cmd, $retval);
                
                // Vérifier si le fichier a été créé et n'est pas vide
                if ($retval === 0 && file_exists($filepath) && filesize($filepath) > 0) {
                    // Enregistrer l'action d'audit
                    $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Sauvegarde', 'base_de_donnees', 'Succès');
                    
                    header('Location: ?page=sauvegarde_restauration&success=1');
                    exit;
                }
            }
        }
        
        // Si toutes les méthodes échouent
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Sauvegarde', 'base_de_donnees', 'Erreur');
        header('Location: ?page=sauvegarde_restauration&error=backup_failed');
        exit;
    }

    /**
     * Vérifie si un conteneur est en cours d'exécution
     */
    private function isContainerRunning(string $containerName): bool
    {
        $cmd = sprintf('docker ps --filter "name=%s" --format "{{.Names}}" 2>/dev/null', escapeshellarg($containerName));
        $output = shell_exec($cmd);
        return $output !== null && !empty(trim($output));
    }

    /**
     * Action : Restaure la base de données à partir d'un fichier SQL de sauvegarde (CREATE - Action critique)
     * Tente d'abord avec PHP PDO, puis avec Docker si disponible et que PHP échoue.
     */
    public function restoreBackup(): bool
    {
        // Vérification des permissions (Action critique)
        if (!$this->checkPermission('create')) {
            return false;
        }

        // S'assurer qu'aucune sortie n'a été envoyée avant les redirections
        if (headers_sent()) {
            $this->logger->error("Les en-têtes ont déjà été envoyés, redirection impossible.");
            return false;
        }

        try {
            if (!isset($_POST['filename'])) {
                header('Location: ?page=sauvegarde_restauration&error=1');
                exit;
            }

            $filename = basename($this->security->sanitizeInput($_POST['filename']));
            $filepath = $this->backupDir . $filename;

            if (!file_exists($filepath)) {
                $this->logger->error("Fichier de sauvegarde introuvable: " . $filepath);
                header('Location: ?page=sauvegarde_restauration&error=1');
                exit;
            }

            $dbConfig = $this->getDbConfig();

            // Essayer d'abord avec PHP PDO
            if ($this->restoreBackupWithPHP($filepath, $dbConfig)) {
                $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Restauration', 'base_de_donnees', 'Succès');
                $this->logger->info("Base de données restaurée depuis: " . $filename);
                
                header('Location: ?page=sauvegarde_restauration&restored=1');
                exit;
            }

            // Si PHP échoue, essayer avec Docker (si disponible et configuré)
            if ($this->isDockerAvailable()) {
                $containerName = $this->getDockerContainerName();

                if ($this->isContainerRunning($containerName)) {
                    $cmd = sprintf('docker exec -i %s mysql -h%s -u%s -p%s %s < %s 2>/dev/null',
                        escapeshellarg($containerName),
                        escapeshellarg($dbConfig['host']),
                        escapeshellarg($dbConfig['user']),
                        escapeshellarg($dbConfig['pass']),
                        escapeshellarg($dbConfig['db']),
                        escapeshellarg($filepath)
                    );

                    system($cmd, $retval);
                    if ($retval === 0) {
                        $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Restauration', 'base_de_donnees', 'Succès');
                        $this->logger->info("Base de données restaurée via Docker depuis: " . $filename);
                        
                        header('Location: ?page=sauvegarde_restauration&restored=1');
                        exit;
                    } else {
                        $this->logger->error("La commande de restauration Docker a échoué avec le code: " . $retval);
                }
            } else {
                $this->logger->error("Le conteneur Docker '" . $containerName . "' n'est pas en cours d'exécution.");
            }
        }

        // Si toutes les tentatives échouent
        $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Restauration', 'base_de_donnees', 'Erreur');
        header('Location: ?page=sauvegarde_restauration&error=1');
        exit;
        
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la restauration: " . $e->getMessage());
            $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Restauration', 'base_de_donnees', 'Erreur');
            header('Location: ?page=sauvegarde_restauration&error=1');
            exit;
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
            
            // Désactiver les vérifications de clés étrangères
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            
            // Vider complètement la base de données avant restauration
            $this->truncateAllTables($pdo);
            
            // Lire le fichier SQL
            $sql = file_get_contents($filepath);
            if ($sql === false) {
                $this->logger->error("Impossible de lire le fichier de sauvegarde: $filepath");
                return false;
            }
            
            // Diviser le SQL en requêtes individuelles
            $queries = $this->splitSQL($sql);
            $this->logger->info("Nombre de requêtes parsées: " . count($queries));
            
            $successCount = 0;
            $errorCount = 0;
            $insertCount = 0;
            $createCount = 0;
            
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
                    } catch (\PDOException $e) {
                        $errorMsg = $e->getMessage();
                        if (strpos($errorMsg, 'already exists') !== false || 
                            strpos($errorMsg, 'Duplicate entry') !== false ||
                            strpos($errorMsg, 'doesn\'t exist') !== false) {
                            continue;
                        }
                        
                        $this->logger->error("Erreur SQL à la requête #$index: " . $errorMsg);
                        $errorCount++;
                    }
                }
            }
            
            // Réactiver les vérifications de clés étrangères
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            
            $this->logger->info("Restauration terminée - Succès: $successCount, Erreurs: $errorCount, INSERT: $insertCount, CREATE: $createCount");
            
            return $successCount > 0;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur générale lors de la restauration: " . $e->getMessage());
            // En cas d'erreur, réactiver les contraintes
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

    /**
     * Vide toutes les tables de la base de données.
     * @param PDO $pdo L'objet PDO connecté à la base de données.
     * @return bool True si toutes les tables ont été vidées avec succès, false sinon.
     */
    private function truncateAllTables($pdo) {
        try {
            // Récupérer toutes les tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            // Vider chaque table
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `$table`");
            }

            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Divise une chaîne SQL en requêtes individuelles
     */
    public function splitSQL(string $sql): array
    {
        $sql = trim($sql);
        
        $queries = [];
        $currentQuery = '';
        $inString = false;
        $stringChar = '';
        $inComment = false;
        $commentType = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            $nextChar = ($i < strlen($sql) - 1) ? $sql[$i + 1] : '';
            
            // Gestion des commentaires
            if (!$inString && !$inComment) {
                if ($char === '-' && $nextChar === '-') {
                    $inComment = true;
                    $commentType = 'line';
                    $i++;
                    continue;
                }
                if ($char === '#') {
                    $inComment = true;
                    $commentType = 'line';
                    continue;
                }
                if ($char === '/' && $nextChar === '*') {
                    $inComment = true;
                    $commentType = 'block';
                    $i++;
                    continue;
                }
            }
            
            if ($inComment) {
                if ($commentType === 'line' && $char === "\n") {
                    $inComment = false;
                    $commentType = '';
                } elseif ($commentType === 'block' && $char === '*' && $nextChar === '/') {
                    $inComment = false;
                    $commentType = '';
                    $i++;
                }
                continue;
            }
            
            // Gestion des chaînes de caractères
            if (!$inComment) {
                if (!$inString && ($char === "'" || $char === '"')) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($inString && $char === $stringChar) {
                    if ($i > 0 && $sql[$i - 1] !== '\\') {
                        $inString = false;
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

    /**
     * Action : Supprime une sauvegarde (DELETE)
     */
    public function deleteBackup(): bool
    {
        if (!$this->checkPermission('delete')) {
            return false;
        }

        if (headers_sent()) {
            return false;
        }

        try {
            if (!isset($_POST['filename'])) {
                header('Location: ?page=sauvegarde_restauration&error=1');
                exit;
            }
            
            $filename = basename($this->security->sanitizeInput($_POST['filename']));
            $filepath = $this->backupDir . $filename;
            
            if (file_exists($filepath)) {
                unlink($filepath);
                $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Suppression', 'sauvegarde', 'Succès');
                $this->logger->info("Sauvegarde supprimée: " . $filename);
                header('Location: ?page=sauvegarde_restauration&deleted=1');
            } else {
                $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Suppression', 'sauvegarde', 'Erreur');
                header('Location: ?page=sauvegarde_restauration&error=1');
            }
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la suppression: " . $e->getMessage());
            header('Location: ?page=sauvegarde_restauration&error=1');
            exit;
        }
    }

    /**
     * Action : Télécharge une sauvegarde (READ)
     */
    public function downloadBackup(): bool
    {
        if (!$this->checkPermission('read')) {
            return false;
        }

        if (headers_sent()) {
            return false;
        }

        try {
            if (!isset($_GET['filename'])) {
                header('Location: ?page=sauvegarde_restauration&error=1');
                exit;
            }
            
            $filename = basename($this->security->sanitizeInput($_GET['filename']));
            $filepath = $this->backupDir . $filename;
            if (file_exists($filepath)) {
                $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Téléchargement', 'sauvegarde', 'Succès');
                $this->logger->info("Sauvegarde téléchargée: " . $filename);
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                exit;
            } else {
                $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Téléchargement', 'sauvegarde', 'Erreur');
                header('Location: ?page=sauvegarde_restauration&error=1');
                exit;
            }
            
        } catch (Exception $e) {
            $this->logger->error("Erreur lors du téléchargement: " . $e->getMessage());
            header('Location: ?page=sauvegarde_restauration&error=1');
            exit;
        }
    }

    /**
     * Liste les sauvegardes existantes (READ)
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
                    'filename' => basename($file),
                    'size' => $this->humanFileSize(filesize($file)),
                    'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                    'type' => 'Manuelle',
                ];
            }
        }
        
        usort($backups, function($a, $b) { 
            return strcmp($b['created_at'], $a['created_at']); 
        });
        
        return $backups;
    }

    /**
     * Convertit la taille de fichier en format lisible
     */
    private function humanFileSize(int $size, int $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $unit = 0;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        return round($size, $precision) . ' ' . $units[$unit];
    }

    /**
     * Action : Affiche la page principale avec la liste des sauvegardes (READ)
     */
    public function index(): array
    {
        if (!$this->checkPermission('read')) {
            return [];
        }

        return $this->getBackups();
    }
} 