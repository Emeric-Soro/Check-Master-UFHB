<?php

namespace App\Controllers;

use PDO;
use App\Models\DossierAcademique;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * DossierAcademiqueController - Centralisation des notes et documents académiques
 * 
 * @package App\Controllers
 */
class DossierAcademiqueController
{
    private PDO $pdo;
    private DossierAcademique $model;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        DossierAcademique $model,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->model = $model;
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Vérification des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        // Selon les specs : gestion_etudiants (lecture), admin_systeme (modification)
        $slug = ($action === 'read') ? 'gestion_etudiants' : 'admin_systeme';
        
        if (!$this->security->can($idGroupe, $slug, $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur {$slug}"
            );
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Accès refusé."]);
            } else {
                $GLOBALS['messageErreur'] = "Vous n'avez pas les droits nécessaires.";
                if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                    http_response_code(403);
                    require __DIR__ . '/../../ressources/views/errors/403.php';
                }
            }
            return false;
        }
        return true;
    }

    /**
     * Point d'entrée principal
     */
    public function index(): void
    {
        $action = $_GET['action'] ?? '';
        
        if ($action === 'enregistrer_dossier') {
            $this->enregsitrer_dossier();
        } elseif ($action === 'get_dossier') {
            $this->getDossier();
        }
    }

    /**
     * Action : Enregistrer ou mettre à jour un dossier (CREATE/UPDATE)
     */
    public function enregsitrer_dossier(): void
    {
        if (!$this->checkPermission('update')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Sanitize input data
                $data = [];
                foreach ($_POST as $key => $value) {
                    $data[$key] = $this->security->sanitizeInput($value);
                }

                $success = $this->model->saveOrUpdate($data);
                
                if ($success) {
                    $this->auditLog->logCreation($_SESSION['id_utilisateur'], "dossiers_academiques", "Succès");
                    $GLOBALS['messageSuccess'] = "Dossier enregistré avec succès.";
                } else {
                    $this->auditLog->logCreation($_SESSION['id_utilisateur'], "dossiers_academiques", "Erreur");
                    $GLOBALS['messageErreur'] = "Erreur lors de l'enregistrement du dossier.";
                }
                
                // Redirection (comportement d'origine)
                $redirect = $_SERVER['HTTP_REFERER'] ?? '/';
                $sep = (strpos($redirect, '?') === false) ? '?' : '&';
                header('Location: ' . $redirect . $sep . 'success=' . ($success ? '1' : '0'));
                exit;
            } catch (Exception $e) {
                $this->logger->error("Erreur enregistrer_dossier: " . $e->getMessage());
                $GLOBALS['messageErreur'] = "Une erreur est survenue.";
            }
        }
    }

    /**
     * Action : Récupérer un dossier par numéro étudiant (READ)
     */
    public function getDossier(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        if (isset($_GET['num_etu'])) {
            try {
                $num_etu = $this->security->sanitizeInput($_GET['num_etu']);
                $dossier = $this->model->getByNumEtu($num_etu);
                
                header('Content-Type: application/json');
                echo json_encode($dossier ?: []);
                exit;
            } catch (Exception $e) {
                $this->logger->error("Erreur getDossier: " . $e->getMessage());
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Erreur lors de la récupération du dossier.']);
                exit;
            }
        }
    }
}
 