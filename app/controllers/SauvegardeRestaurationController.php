<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../Core/Autoload.php';
require_once __DIR__ . '/../Services/SauvegardeRestaurationService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Core\Csrf;
use CheckMaster\Services\SauvegardeRestaurationService;

class SauvegardeRestaurationController {
    private $service;

    public function __construct() {
        $this->service = new SauvegardeRestaurationService(Database::getConnection());
    }

    private function requireAdmin(): void
    {
        $lib = $_SESSION['lib_GU'] ?? null;
        $isAdmin = is_string($lib) && in_array(strtolower(trim($lib)), ['administrateur', 'admin'], true);
        if (!$isAdmin) {
            header('Location: ?page=access_denied');
            exit;
        }
    }

    private function requireCsrf(): void
    {
        $ok = Csrf::validate($_POST['csrf_token'] ?? null);
        if (!$ok) {
            header('Location: ?page=sauvegarde_restauration&error=csrf');
            exit;
        }
    }

    // Obtient la configuration de la base de données
    public function getDbConfig() {
        return $this->service->getDbConfig();
    }

    // Lance une sauvegarde manuelle
    public function createBackup() {
        $this->requireAdmin();
        $this->requireCsrf();
if (!canCreate('sauvegarde_restauration')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        if (headers_sent()) {
            return false;
        }

        $backupName = isset($_POST['backup_name']) && $_POST['backup_name'] ? $_POST['backup_name'] : null;
        $result = $this->service->createBackup($backupName, $_SESSION['id_utilisateur']);

        header('Location: ' . $result['redirect']);
        exit;
    }

    /**
     * Restaure la base de données à partir d'un fichier SQL de sauvegarde.
     */
    public function restoreBackup() {
        $this->requireAdmin();
        $this->requireCsrf();
if (!canEdit('sauvegarde_restauration')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        if (headers_sent()) {
            error_log("Erreur: Les en-têtes ont déjà été envoyés, redirection impossible.");
            return false;
        }

        if (!isset($_POST['filename'])) {
            header('Location: ?page=sauvegarde_restauration&error=1');
            exit;
        }

        $result = $this->service->restoreBackup($_POST['filename'], $_SESSION['id_utilisateur']);

        header('Location: ' . $result['redirect']);
        exit;
    }

    /**
     * Restaure la base de données en utilisant PHP PDO.
     * @param string $filepath Chemin complet vers le fichier SQL de sauvegarde.
     * @param array $dbConfig Tableau de configuration de la base de données (host, user, pass, db).
     * @return bool True si la restauration est réussie, false sinon.
     */
    public function restoreBackupWithPHP($filepath, $dbConfig) {
        return $this->service->restoreBackupWithPHP($filepath, $dbConfig);
    }

    /**
     * Divise une chaîne SQL en requêtes individuelles.
     * @param string $sql La chaîne SQL complète.
     * @return array Un tableau de requêtes SQL individuelles.
     */
    public function splitSQL($sql) {
        return $this->service->splitSQL($sql);
    }

    // Supprime une sauvegarde
    public function deleteBackup() {
        $this->requireAdmin();
        $this->requireCsrf();
if (!canDelete('sauvegarde_restauration')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }
        if (headers_sent()) {
            return false;
        }

        if (!isset($_POST['filename'])) {
            header('Location: ?page=sauvegarde_restauration&error=1');
            exit;
        }

        $result = $this->service->deleteBackup($_POST['filename'], $_SESSION['id_utilisateur']);

        header('Location: ' . $result['redirect']);
        exit;
    }

    // Télécharge une sauvegarde
    public function downloadBackup() {
        if (!isset($_SESSION['id_utilisateur'])) {
            header('Location: page_connexion.php');
            exit;
        }
        if (headers_sent()) {
            return false;
        }

        if (!isset($_GET['filename'])) {
            header('Location: ?page=sauvegarde_restauration&error=1');
            exit;
        }

        $result = $this->service->downloadBackup($_GET['filename'], $_SESSION['id_utilisateur']);

        if ($result['success']) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . $result['filesize']);
            readfile($result['filepath']);
            exit;
        }

        header('Location: ' . $result['redirect']);
        exit;
    }

    // Liste les sauvegardes existantes
    public function getBackups() {
        return $this->service->getBackups();
    }

    // Affiche la page principale avec la liste des sauvegardes
    public function index() {
        return $this->service->getBackups();
    }

    /**
     * Méthode de test pour diagnostiquer les problèmes de restauration.
     * @param string $filepath Chemin vers le fichier de sauvegarde.
     * @return array Informations de diagnostic.
     */
    public function testRestore($filepath) {
        return $this->service->testRestore($filepath);
    }

    /**
     * Méthode de diagnostic spécifique pour analyser les requêtes INSERT.
     * @param string $filepath Chemin vers le fichier de sauvegarde.
     * @return array Informations de diagnostic détaillées.
     */
    public function diagnoseInsertQueries($filepath) {
        return $this->service->diagnoseInsertQueries($filepath);
    }
}
