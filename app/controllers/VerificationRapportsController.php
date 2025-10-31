<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/Approuver.php';
require_once __DIR__ . '/../models/PersAdmin.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/EmailService.php';
require_once __DIR__ . '/../utils/permissions.php';

class VerificationRapportsController
{

    private $rapportModel;
    private $approbationModel;
    private $persAdminModel;
    private $auditLog;
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->rapportModel = new RapportEtudiant($this->pdo);
        $this->approbationModel = new Approuver($this->pdo);
        $this->persAdminModel = new PersAdmin($this->pdo);
        $this->auditLog = new AuditLog($this->pdo);
    }

    public function index()
    {
        try {
            // Récupérer les rapports déposés depuis la table deposer
            $rapports = $this->rapportModel->getRapportsDeposes();

            // Statistiques des rapports par statut
            $stats = $this->getStatsRapports();
            
            // Retourner les données pour la vue
            return [
                'rapports' => $rapports,
                'nbRapports' => count($rapports),
                'statsRapports' => $stats
            ];

        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des rapports: " . $e->getMessage());
            return [
                'rapports' => [],
                'nbRapports' => 0,
                'statsRapports' => []
            ];
        }
    }

    /**
     * Récupère les statistiques des rapports par statut
     */
    private function getStatsRapports()
    {
        try {
            $rapports = $this->rapportModel->getRapportsDeposes();
            $stats = [
                'total' => 0, // en_attente_communication
                'approuves' => 0, // approuve_communication
                'desapprouves' => 0 // desapprouve_communication
            ];
            foreach ($rapports as $rapport) {
                $etape = $rapport->etape_validation ?? '';
                if ($etape === 'en_attente_communication') {
                    $stats['total']++;
                } elseif ($etape === 'approuve_communication') {
                    $stats['approuves']++;
                } elseif ($etape === 'desapprouve_communication') {
                    $stats['desapprouves']++;
                }
            }
            return $stats;
        } catch (Exception $e) {
            error_log("Erreur lors du calcul des statistiques: " . $e->getMessage());
            return [
                'total' => 0,
                'approuves' => 0,
                'desapprouves' => 0
            ];
        }
    }

    /**
     * Valider un rapport (approuver)
     */
    public function validerRapport()
    {
        // Vérifier la permission UPDATE pour valider un rapport
        if (!hasPermission('verification_rapports', 'UPDATE')) {
            return ['success' => false, 'message' => 'Vous n\'avez pas la permission de valider des rapports.'];
        }
        
        try {
            $id_rapport = $_POST['id_rapport'] ?? 0;
            $commentaire = $_POST['commentaire'] ?? '';
            $id_approb = 4; // Niveau 2 (id_approb=4 dans la table niveau_approbation)

            // Déterminer l'administrateur courant (personnel_admin) à partir de l'utilisateur connecté
            $id_admin = null;
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            if (!empty($_SESSION['id_utilisateur'])) {
                $pers = $this->persAdminModel->getByUserId($_SESSION['id_utilisateur']);
                if ($pers) {
                    // getByUserId peut retourner un assoc ou un objet selon l'implémentation
                    if (is_object($pers) && isset($pers->id_pers_admin)) {
                        $id_admin = $pers->id_pers_admin;
                    } elseif (is_array($pers) && isset($pers['id_pers_admin'])) {
                        $id_admin = $pers['id_pers_admin'];
                    }
                }
            }

            if (!$id_rapport || !$commentaire || !$id_admin) {
                return ['success' => false, 'message' => 'Paramètres manquants ou administrateur non reconnu'];
            }

            // Insérer l'approbation dans la table approuver
            $stmt = $this->pdo->prepare("
                INSERT INTO approuver (id_rapport, id_pers_admin, commentaire_approv, decision, date_approv, id_approb)
                VALUES (?, ?, ?, 'approuve', NOW(), ?)
            ");

            if ($stmt->execute([$id_rapport, $id_admin, $commentaire, $id_approb])) {
                // Mettre à jour l'étape de validation du rapport
                $updateStmt = $this->pdo->prepare("UPDATE rapport_etudiants SET etape_validation = 'approuve_communication' WHERE id_rapport = ?");
                $updateStmt->execute([$id_rapport]);

                // Suppression du log d'audit (pister)
                // $this->auditLog->logValidation($id_admin, 'rapport_etudiants', 'Succès');

                return ['success' => true, 'message' => 'Rapport approuvé avec succès'];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log('APPROBATION SQL ERROR: ' . $errorInfo[2]);
                return ['success' => false, 'message' => "Erreur lors de l'approbation : " . $errorInfo[2]];
            }
        } catch (Exception $e) {
            error_log("Erreur approbation rapport: " . $e->getMessage());
            return ['success' => false, 'message' => "Exception : " . $e->getMessage()];
        }
    }

    /**
     * Rejeter un rapport (désapprouver)
     */
    public function rejeterRapport()
    {
        // Vérifier la permission UPDATE pour rejeter un rapport
        if (!hasPermission('verification_rapports', 'UPDATE')) {
            return ['success' => false, 'message' => 'Vous n\'avez pas la permission de rejeter des rapports.'];
        }
        
        try {
            $id_rapport = $_POST['id_rapport'] ?? 0;
            $commentaire = $_POST['commentaire'] ?? '';
            $id_approb = 4; // Niveau 2 (id_approb=4 dans la table niveau_approbation)

            // Déterminer l'administrateur courant (personnel_admin) à partir de l'utilisateur connecté
            $id_admin = null;
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            if (!empty($_SESSION['id_utilisateur'])) {
                $pers = $this->persAdminModel->getByUserId($_SESSION['id_utilisateur']);
                if ($pers) {
                    if (is_object($pers) && isset($pers->id_pers_admin)) {
                        $id_admin = $pers->id_pers_admin;
                    } elseif (is_array($pers) && isset($pers['id_pers_admin'])) {
                        $id_admin = $pers['id_pers_admin'];
                    }
                }
            }

            if (!$id_rapport || !$commentaire || !$id_admin) {
                return ['success' => false, 'message' => 'Paramètres manquants ou administrateur non reconnu'];
            }

            // Insérer le rejet dans la table approuver
            $stmt = $this->pdo->prepare("
                INSERT INTO approuver (id_rapport, id_pers_admin, commentaire_approv, decision, date_approv, id_approb)
                VALUES (?, ?, ?, 'desapprouve', NOW(), ?)
            ");

            if ($stmt->execute([$id_rapport, $id_admin, $commentaire, $id_approb])) {
                // Mettre à jour l'étape de validation du rapport
                $updateStmt = $this->pdo->prepare("UPDATE rapport_etudiants SET etape_validation = 'approuve_communication' WHERE id_rapport = ?");
                $updateStmt->execute([$id_rapport]);

                // Suppression du log d'audit (pister)
                // $this->auditLog->logRejet($id_admin, 'rapport_etudiants', 'Succès');

                return ['success' => true, 'message' => 'Rapport rejeté avec succès'];
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log('APPROBATION SQL ERROR: ' . $errorInfo[2]);
                return ['success' => false, 'message' => "Erreur lors de la désapprobation : " . $errorInfo[2]];
            }
        } catch (Exception $e) {
            error_log("Erreur désapprobation rapport: " . $e->getMessage());
            return ['success' => false, 'message' => "Exception : " . $e->getMessage()];
        }
    }

    /**
     * Récupérer les détails d'un rapport
     */
    public function getRapportDetail($id_rapport)
    {
        try {
            return $this->rapportModel->getRapportDetail($id_rapport);
        } catch (Exception $e) {
            error_log("Erreur récupération détail rapport: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer les décisions d'évaluation d'un rapport
     */
    public function getDecisionsEvaluation($id_rapport)
    {
        try {
            return $this->rapportModel->getDecisionsEvaluation($id_rapport);
        } catch (Exception $e) {
            error_log("Erreur récupération décisions évaluation: " . $e->getMessage());
            return [];
        }
    }
}