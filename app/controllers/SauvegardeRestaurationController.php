<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AuditLog.php';

require_once __DIR__ . '/../utils/CSRFProtection.php';
class SauvegardeRestaurationController {
    private $backupDir;
    private $auditService;
    private $auditLog;

    public function __construct() {
        // Utiliser un chemin absolu pour le dossier de sauvegarde
        $this->backupDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
        
        $this->auditLog = new AuditLog(Database::getConnection());
    }

    // Obtient la configuration de la base de données
    public function getDbConfig() {
        return [
            'host' => 'db',
            'db'   => 'soutenance_manager',
            'user' => 'root',
            'pass' => 'password',
        ];
    }

    // Détecte automatiquement le nom du conteneur Docker
    private function getDockerContainerName() {
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

    // Vérifie si Docker est disponible
    private function isDockerAvailable() {
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

    // Sauvegarde avec PHP PDO (méthode de secours)
    private function createBackupWithPHP($filepath, $dbConfig) {
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
            return false;
        }
    }

    // Lance une sauvegarde manuelle
    public function createBackup() {
        // S'assurer qu'aucune sortie n'a été envoyée
        if (headers_sent()) {
            return false;
        }
        
        $backupName = isset($_POST['backup_name']) && $_POST['backup_name'] ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $_POST['backup_name']) : 'backup_' . date('Ymd_His');
        $filename = $backupName . '_' . date('Ymd_His') . '.sql';
        $filepath = $this->backupDir . $filename;
        
        // Essayer d'abord avec la méthode PHP (plus fiable)
        if ($this->createBackupWithPHP($filepath, $this->getDbConfig())) {
            // Enregistrer l'action d'audit
            $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Sauvegarde', 'base_de_donnees', 'Succès');
            
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

    // Vérifie si un conteneur est en cours d'exécution
    private function isContainerRunning($containerName) {
        $cmd = sprintf('docker ps --filter "name=%s" --format "{{.Names}}" 2>/dev/null', escapeshellarg($containerName));
        $output = shell_exec($cmd);
        return $output !== null && !empty(trim($output));
    }

   /**
     * Restaure la base de données à partir d'un fichier SQL de sauvegarde.
     * Tente d'abord avec PHP PDO, puis avec Docker si disponible et que PHP échoue.
     * Redirige l'utilisateur après l'opération.
     */
    public function restoreBackup() {
        // S'assurer qu'aucune sortie n'a été envoyée avant les redirections
        if (headers_sent()) {
            error_log("Erreur: Les en-têtes ont déjà été envoyés, redirection impossible.");
            return false;
        }

        if (!isset($_POST['filename'])) {
            header('Location: ?page=sauvegarde_restauration&error=1');
            exit;
        }

        $filename = basename($_POST['filename']);
        $filepath = $this->backupDir . $filename;

        if (!file_exists($filepath)) {
            error_log("Erreur: Fichier de sauvegarde introuvable: " . $filepath);
            header('Location: ?page=sauvegarde_restauration&error=1');
            exit;
        }

        $dbConfig = $this->getDbConfig();

        // Essayer d'abord avec PHP PDO
        if ($this->restoreBackupWithPHP($filepath, $dbConfig)) {
            // Enregistrer l'action d'audit
            $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Restauration', 'base_de_donnees', 'Succès');
            
            header('Location: ?page=sauvegarde_restauration&restored=1');
            exit;
        }

        // Si PHP échoue, essayer avec Docker (si disponible et configuré)
        if ($this->isDockerAvailable()) {
            $containerName = $this->getDockerContainerName();

            if ($this->isContainerRunning($containerName)) {
                // Utiliser Docker pour exécuter mysql
                // ATTENTION: Assurez-vous que le fichier de backup est accessible par le conteneur Docker
                // Cela peut nécessiter un montage de volume Docker.
                $cmd = sprintf('docker exec -i %s mysql -h%s -u%s -p%s %s < %s 2>/dev/null',
                    escapeshellarg($containerName),
                    escapeshellarg($dbConfig['host']),
                    escapeshellarg($dbConfig['user']),
                    escapeshellarg($dbConfig['pass']),
                    escapeshellarg($dbConfig['db']),
                    escapeshellarg($filepath) // Le chemin doit être accessible depuis le conteneur
                );

                system($cmd, $retval);
                if ($retval === 0) {
                    // Enregistrer l'action d'audit
                    $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Restauration', 'base_de_donnees', 'Succès');
                    
                    header('Location: ?page=sauvegarde_restauration&restored=1');
                    exit;
                } else {
                    error_log("Erreur Docker: La commande de restauration Docker a échoué avec le code de retour: " . $retval);
                }
            } else {
                error_log("Erreur Docker: Le conteneur Docker '" . $containerName . "' n'est pas en cours d'exécution.");
            }
        }

        // Si toutes les tentatives échouent
        $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Restauration', 'base_de_donnees', 'Erreur');
        header('Location: ?page=sauvegarde_restauration&error=1');
        exit;
    }

    /**
     * Restaure la base de données en utilisant PHP PDO.
     * @param string $filepath Chemin complet vers le fichier SQL de sauvegarde.
     * @param array $dbConfig Tableau de configuration de la base de données (host, user, pass, db).
     * @return bool True si la restauration est réussie, false sinon.
     */
    public function restoreBackupWithPHP($filepath, $dbConfig) {
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
                error_log("Erreur: Impossible de lire le fichier de sauvegarde: $filepath");
                return false;
            }
            
            // Diviser le SQL en requêtes individuelles
            $queries = $this->splitSQL($sql);
            error_log("Nombre de requêtes parsées: " . count($queries));
            
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
                            
                            // Compter les types de requêtes
                            if (preg_match('/^INSERT\s+INTO/i', $query)) {
                                $insertCount++;
                            } elseif (preg_match('/^CREATE\s+TABLE/i', $query)) {
                                $createCount++;
                            }
                        }
                    } catch (PDOException $e) {
                        // Ignorer certaines erreurs courantes
                        $errorMsg = $e->getMessage();
                        if (strpos($errorMsg, 'already exists') !== false || 
                            strpos($errorMsg, 'Duplicate entry') !== false ||
                            strpos($errorMsg, 'doesn\'t exist') !== false) {
                            // Ignorer ces erreurs mais les logger
                            error_log("Requête ignorée (erreur attendue): " . substr($query, 0, 100) . "... - " . $errorMsg);
                            continue;
                        }
                        
                        // Logger les erreurs importantes
                        error_log("Erreur SQL à la requête #$index: " . $errorMsg);
                        error_log("Requête problématique: " . substr($query, 0, 200) . "...");
                        $errorCount++;
                    }
                }
            }
            
            // Réactiver les vérifications de clés étrangères
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            
            // Logger les statistiques
            error_log("Restauration terminée - Succès: $successCount, Erreurs: $errorCount, INSERT: $insertCount, CREATE: $createCount");
            
            // Considérer comme réussi si au moins quelques requêtes ont fonctionné
            return $successCount > 0;
            
        } catch (Exception $e) {
            error_log("Erreur générale lors de la restauration: " . $e->getMessage());
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
     * Divise une chaîne SQL en requêtes individuelles, en gérant les commentaires et les délimiteurs.
     * Version améliorée qui gère correctement les requêtes INSERT multi-lignes.
     * @param string $sql La chaîne SQL complète.
     * @return array Un tableau de requêtes SQL individuelles.
     */
    public function splitSQL($sql) {
        // Nettoyer le SQL
        $sql = trim($sql);
        
        $queries = [];
        $currentQuery = '';
        $inString = false;
        $stringChar = '';
        $inComment = false;
        $commentType = '';
        $lineNumber = 0;
        
        // Parcourir le SQL caractère par caractère
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            $nextChar = ($i < strlen($sql) - 1) ? $sql[$i + 1] : '';
            
            // Gestion des sauts de ligne
            if ($char === "\n") {
                $lineNumber++;
            }
            
            // Gestion des commentaires
            if (!$inString && !$inComment) {
                // Commentaire sur une ligne (--)
                if ($char === '-' && $nextChar === '-') {
                    $inComment = true;
                    $commentType = 'line';
                    $i++; // Passer le deuxième tiret
                    continue;
                }
                // Commentaire sur une ligne (#)
                if ($char === '#') {
                    $inComment = true;
                    $commentType = 'line';
                    continue;
                }
                // Commentaire multi-lignes (/*)
                if ($char === '/' && $nextChar === '*') {
                    $inComment = true;
                    $commentType = 'block';
                    $i++; // Passer l'astérisque
                    continue;
                }
            }
            
            // Fin des commentaires
            if ($inComment) {
                if ($commentType === 'line' && $char === "\n") {
                    $inComment = false;
                    $commentType = '';
                } elseif ($commentType === 'block' && $char === '*' && $nextChar === '/') {
                    $inComment = false;
                    $commentType = '';
                    $i++; // Passer le slash
                }
                continue;
            }
            
            // Gestion des chaînes de caractères
            if (!$inComment) {
                if (!$inString && ($char === "'" || $char === '"')) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($inString && $char === $stringChar) {
                    // Vérifier si c'est un caractère d'échappement
                    if ($i > 0 && $sql[$i - 1] !== '\\') {
                        $inString = false;
                        $stringChar = '';
                    }
                }
            }
            
            // Ajouter le caractère à la requête courante
            if (!$inComment) {
                $currentQuery .= $char;
            }
            
            // Détecter la fin d'une requête (point-virgule hors chaîne)
            if ($char === ';' && !$inString && !$inComment) {
                $currentQuery = trim($currentQuery);
                
                // Ignorer les requêtes vides
                if (!empty($currentQuery) && !preg_match('/^\s*$/', $currentQuery)) {
                    $queries[] = $currentQuery;
                }
                
                $currentQuery = '';
            }
        }
        
        // Ajouter la dernière requête si elle existe
        $currentQuery = trim($currentQuery);
        if (!empty($currentQuery) && !preg_match('/^\s*$/', $currentQuery)) {
            $queries[] = $currentQuery;
        }
        
        return $queries;
    }

    // Supprime une sauvegarde
    public function deleteBackup() {
        // S'assurer qu'aucune sortie n'a été envoyée
        if (headers_sent()) {
            return false;
        }
        
        if (!isset($_POST['filename'])) {
            header('Location: ?page=sauvegarde_restauration&error=1');
            exit;
        }
        $filename = basename($_POST['filename']);
        $filepath = $this->backupDir . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
            $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Suppression', 'sauvegarde', 'Succès');
            header('Location: ?page=sauvegarde_restauration&deleted=1');
        } else {
            $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Suppression', 'sauvegarde', 'Erreur');
            header('Location: ?page=sauvegarde_restauration&error=1');
        }
        exit;
    }

    // Télécharge une sauvegarde
    public function downloadBackup() {
        // S'assurer qu'aucune sortie n'a été envoyée
        if (headers_sent()) {
            return false;
        }
        
        if (!isset($_GET['filename'])) {
            header('Location: ?page=sauvegarde_restauration&error=1');
            exit;
        }
        $filename = basename($_GET['filename']);
        $filepath = $this->backupDir . $filename;
        if (file_exists($filepath)) {
            $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Téléchargement', 'sauvegarde', 'Succès');
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
    }

    // Liste les sauvegardes existantes
    public function getBackups() {
        if (!is_dir($this->backupDir)) {
            return [];
        }
        
        // Rechercher les fichiers .sql
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
        
        // Trier par date (plus récent en premier)
        usort($backups, function($a, $b) { 
            return strcmp($b['created_at'], $a['created_at']); 
        });
        
        return $backups;
    }

    private function humanFileSize($size, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $unit = 0;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        return round($size, $precision) . ' ' . $units[$unit];
    }

    // Affiche la page principale avec la liste des sauvegardes
    public function index() {
        $backups = $this->getBackups();
        return $backups;
    }

    /**
     * Méthode de test pour diagnostiquer les problèmes de restauration.
     * @param string $filepath Chemin vers le fichier de sauvegarde.
     * @return array Informations de diagnostic.
     */
    public function testRestore($filepath) {
        $diagnostic = [
            'success' => false,
            'errors' => [],
            'warnings' => [],
            'info' => []
        ];
        
        try {
            $dbConfig = $this->getDbConfig();
            $diagnostic['info']['db_config'] = $dbConfig;
            
            // Test 1: Vérifier que le fichier existe
            if (!file_exists($filepath)) {
                $diagnostic['errors'][] = "Fichier introuvable: $filepath";
                return $diagnostic;
            }
            
            $diagnostic['info']['file_size'] = filesize($filepath);
            $diagnostic['info']['file_path'] = $filepath;
            
            // Test 2: Tester la connexion à la base de données
            try {
                $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset=utf8";
                $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $diagnostic['info']['connection'] = 'success';
                
                // Vérifier les tables existantes
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                $diagnostic['info']['existing_tables'] = count($tables);
                
            } catch (Exception $e) {
                $diagnostic['errors'][] = "Erreur de connexion: " . $e->getMessage();
                return $diagnostic;
            }
            
            // Test 3: Lire et analyser le fichier SQL
            $sql = file_get_contents($filepath);
            if ($sql === false) {
                $diagnostic['errors'][] = "Impossible de lire le fichier SQL";
                return $diagnostic;
            }
            
            $diagnostic['info']['sql_length'] = strlen($sql);
            
            // Test 4: Parser le SQL
            $queries = $this->splitSQL($sql);
            $diagnostic['info']['parsed_queries'] = count($queries);
            
            if (count($queries) == 0) {
                $diagnostic['errors'][] = "Aucune requête SQL trouvée dans le fichier";
                return $diagnostic;
            }
            
            // Test 5: Analyser les premières requêtes
            $diagnostic['info']['first_query'] = substr($queries[0], 0, 100) . "...";
            $diagnostic['info']['last_query'] = substr($queries[count($queries)-1], 0, 100) . "...";
            
            // Test 6: Tester quelques requêtes simples
            $testQueries = array_slice($queries, 0, 5);
            $testResults = [];
            
            foreach ($testQueries as $index => $query) {
                try {
                    $result = $pdo->exec($query);
                    $testResults[$index] = [
                        'success' => true,
                        'rows_affected' => $result
                    ];
                } catch (PDOException $e) {
                    $testResults[$index] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                    $diagnostic['warnings'][] = "Erreur dans la requête #$index: " . $e->getMessage();
                }
            }
            
            $diagnostic['info']['test_results'] = $testResults;
            
            // Si on arrive ici, le test est réussi
            $diagnostic['success'] = true;
            
        } catch (Exception $e) {
            $diagnostic['errors'][] = "Erreur générale: " . $e->getMessage();
        }
        
        return $diagnostic;
    }

    /**
     * Méthode de diagnostic spécifique pour analyser les requêtes INSERT.
     * @param string $filepath Chemin vers le fichier de sauvegarde.
     * @return array Informations de diagnostic détaillées.
     */
    public function diagnoseInsertQueries($filepath) {
        $diagnostic = [
            'success' => false,
            'insert_queries' => [],
            'problems' => [],
            'statistics' => []
        ];
        
        try {
            // Lire le fichier SQL
            $sql = file_get_contents($filepath);
            if ($sql === false) {
                $diagnostic['problems'][] = "Impossible de lire le fichier SQL";
                return $diagnostic;
            }
            
            // Diviser le SQL en requêtes
            $queries = $this->splitSQL($sql);
            
            $insertQueries = [];
            $tableStats = [];
            
            foreach ($queries as $index => $query) {
                if (preg_match('/^INSERT\s+INTO\s+`?(\w+)`?\s+VALUES/i', $query, $matches)) {
                    $tableName = $matches[1];
                    
                    // Compter les valeurs dans cette requête INSERT
                    $valueCount = preg_match_all('/\([^)]+\)/', $query, $valueMatches);
                    
                    $insertQueries[] = [
                        'index' => $index,
                        'table' => $tableName,
                        'query' => $query,
                        'value_count' => $valueCount,
                        'first_value' => $valueMatches[0][0] ?? 'N/A',
                        'last_value' => $valueMatches[0][$valueCount - 1] ?? 'N/A'
                    ];
                    
                    // Statistiques par table
                    if (!isset($tableStats[$tableName])) {
                        $tableStats[$tableName] = [
                            'query_count' => 0,
                            'total_values' => 0,
                            'queries' => []
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
                'tables_affected' => count($tableStats),
                'table_statistics' => $tableStats
            ];
            
            // Analyser les problèmes potentiels
            foreach ($tableStats as $tableName => $stats) {
                if ($stats['query_count'] > 1) {
                    $diagnostic['problems'][] = "Table '$tableName' a {$stats['query_count']} requêtes INSERT - risque de fragmentation";
                }
                
                if ($stats['total_values'] < 1) {
                    $diagnostic['problems'][] = "Table '$tableName' n'a aucune valeur INSERT détectée";
                }
            }
            
            // Vérifier les requêtes problématiques
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
} 
