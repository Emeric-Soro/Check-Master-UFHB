<?php

namespace App\Controllers;

use PDO;
use App\Models\Programmer;
use App\Models\Enseignant;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * ProgrammationSoutenanceController - Constitution des jurys (Qui évalue qui ?)
 * 
 * Ce contrôleur gère la programmation des soutenances :
 * - Constitution des jurys
 * - Attribution des rapporteurs
 * - Gestion des étudiants validés
 * 
 * @package App\Controllers
 */
class ProgrammationSoutenanceController
{
    private PDO $pdo;
    private Enseignant $enseignant;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        Enseignant $enseignant,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->enseignant = $enseignant;
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
        
        if (!$this->security->can($idGroupe, 'programation_soutenance', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur programation_soutenance"
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
     * Récupère les étudiants validés disponibles pour attribution de jury (READ)
     */
    public function getEtudiantsValidesDisponibles(): array
    {
        if (!$this->checkPermission('read')) {
            return [];
        }

        try {
            $sql = "
                SELECT DISTINCT
                    e.num_etu as id,
                    e.num_etu as matricule,
                    e.nom_etu,
                    e.prenom_etu,
                    r.nom_rapport as theme,
                    r.id_rapport
                FROM valider v
                INNER JOIN rapport_etudiants r ON v.id_rapport = r.id_rapport
                INNER JOIN etudiants e ON r.num_etu = e.num_etu
                WHERE v.decision_validation = 'valider'
                AND NOT EXISTS (
                    SELECT 1 FROM programmer p WHERE p.num_etud = e.num_etu AND p.num_jury IS NOT NULL
                )
                ORDER BY e.nom_etu, e.prenom_etu
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getEtudiantsValidesDisponibles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère tous les enseignants pour constitution du jury (READ)
     */
    public function getEnseignantsForJury(): array
    {
        if (!$this->checkPermission('read')) {
            return [];
        }

        try {
            return $this->enseignant->getAllEnseignants();
        } catch (Exception $e) {
            $this->logger->error("Erreur getEnseignantsForJury: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les programmations existantes avec leurs jurys (READ)
     */
    public function getProgrammationsAvecJury(): array
    {
        if (!$this->checkPermission('read')) {
            return [];
        }

        try {
            $sql = "
                SELECT 
                    p.id_programmation,
                    p.num_etud,
                    p.num_jury,
                    p.theme_soutenance,
                    e.nom_etu,
                    e.prenom_etu,
                    j.nom_jury,
                    GROUP_CONCAT(DISTINCT CONCAT(ens.prenom_enseignant, ' ', ens.nom_enseignant) ORDER BY ens.nom_enseignant SEPARATOR ', ') as membres_jury
                FROM programmer p
                INNER JOIN etudiants e ON p.num_etud = e.num_etu
                LEFT JOIN jury j ON p.num_jury = j.num_jury
                LEFT JOIN composer c ON j.num_jury = c.num_jury
                LEFT JOIN enseignants ens ON c.id_enseignant = ens.id_enseignant
                WHERE p.num_jury IS NOT NULL
                GROUP BY p.id_programmation, p.num_etud, p.num_jury, p.theme_soutenance, e.nom_etu, e.prenom_etu, j.nom_jury
                ORDER BY e.nom_etu, e.prenom_etu
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur getProgrammationsAvecJury: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Action : Attribue un jury à un étudiant (CREATE)
     */
    public function attribuerJury(): array
    {
        if (!$this->checkPermission('create')) {
            return ['success' => false, 'message' => 'Accès refusé'];
        }

        try {
            $numEtu = $this->security->sanitizeInput($_POST['num_etu'] ?? null);
            $theme = $this->security->sanitizeInput($_POST['theme'] ?? '');
            $president = (int)($_POST['president'] ?? 0);
            $rapporteur = (int)($_POST['rapporteur'] ?? 0);
            $examinateur = (int)($_POST['examinateur'] ?? 0);

            if (empty($numEtu)) {
                throw new Exception('Étudiant non sélectionné');
            }

            if ($president === 0 || $rapporteur === 0 || $examinateur === 0) {
                throw new Exception('Tous les membres du jury doivent être sélectionnés');
            }

            // Vérifier que les membres du jury sont différents
            $membres = [$president, $rapporteur, $examinateur];
            if (count($membres) !== count(array_unique($membres))) {
                throw new Exception('Les membres du jury doivent être différents');
            }

            $this->pdo->beginTransaction();

            // Créer le jury
            $nomJury = 'Jury-' . $numEtu . '-' . date('Ymd');
            $sqlJury = "INSERT INTO jury (nom_jury, date_creation) VALUES (?, NOW())";
            $stmtJury = $this->pdo->prepare($sqlJury);
            $stmtJury->execute([$nomJury]);
            $numJury = $this->pdo->lastInsertId();

            // Ajouter les membres du jury
            $sqlComposer = "INSERT INTO composer (num_jury, id_enseignant, role_jury) VALUES (?, ?, ?)";
            $stmtComposer = $this->pdo->prepare($sqlComposer);
            $stmtComposer->execute([$numJury, $president, 'Président']);
            $stmtComposer->execute([$numJury, $rapporteur, 'Rapporteur']);
            $stmtComposer->execute([$numJury, $examinateur, 'Examinateur']);

            // Créer la programmation
            $sqlProgrammer = "INSERT INTO programmer (num_etud, num_jury, theme_soutenance) VALUES (?, ?, ?)";
            $stmtProgrammer = $this->pdo->prepare($sqlProgrammer);
            $stmtProgrammer->execute([$numEtu, $numJury, $theme]);

            $this->pdo->commit();

            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'programmer', 'Succès');
            $this->logger->info("Jury {$numJury} attribué à l'étudiant {$numEtu}");

            return [
                'success' => true,
                'message' => 'Jury attribué avec succès'
            ];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->logger->error("Erreur attribuerJury: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Action : Modifie un jury existant (UPDATE)
     */
    public function modifierJury(): array
    {
        if (!$this->checkPermission('update')) {
            return ['success' => false, 'message' => 'Accès refusé'];
        }

        try {
            $idProgrammation = (int)($_POST['id_programmation'] ?? 0);
            $president = (int)($_POST['president'] ?? 0);
            $rapporteur = (int)($_POST['rapporteur'] ?? 0);
            $examinateur = (int)($_POST['examinateur'] ?? 0);

            if ($idProgrammation === 0) {
                throw new Exception('Programmation non trouvée');
            }

            if ($president === 0 || $rapporteur === 0 || $examinateur === 0) {
                throw new Exception('Tous les membres du jury doivent être sélectionnés');
            }

            $membres = [$president, $rapporteur, $examinateur];
            if (count($membres) !== count(array_unique($membres))) {
                throw new Exception('Les membres du jury doivent être différents');
            }

            $this->pdo->beginTransaction();

            // Récupérer le numéro de jury
            $sqlGet = "SELECT num_jury FROM programmer WHERE id_programmation = ?";
            $stmtGet = $this->pdo->prepare($sqlGet);
            $stmtGet->execute([$idProgrammation]);
            $result = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if (!$result || !$result['num_jury']) {
                throw new Exception('Jury non trouvé');
            }

            $numJury = $result['num_jury'];

            // Supprimer les anciens membres
            $sqlDelete = "DELETE FROM composer WHERE num_jury = ?";
            $stmtDelete = $this->pdo->prepare($sqlDelete);
            $stmtDelete->execute([$numJury]);

            // Ajouter les nouveaux membres
            $sqlComposer = "INSERT INTO composer (num_jury, id_enseignant, role_jury) VALUES (?, ?, ?)";
            $stmtComposer = $this->pdo->prepare($sqlComposer);
            $stmtComposer->execute([$numJury, $president, 'Président']);
            $stmtComposer->execute([$numJury, $rapporteur, 'Rapporteur']);
            $stmtComposer->execute([$numJury, $examinateur, 'Examinateur']);

            $this->pdo->commit();

            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'programmer', 'Succès');
            $this->logger->info("Jury {$numJury} modifié pour la programmation {$idProgrammation}");

            return [
                'success' => true,
                'message' => 'Jury modifié avec succès'
            ];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->logger->error("Erreur modifierJury: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Action : Supprime une attribution de jury (DELETE)
     */
    public function supprimerAttribution(): array
    {
        if (!$this->checkPermission('delete')) {
            return ['success' => false, 'message' => 'Accès refusé'];
        }

        try {
            $idProgrammation = (int)($_POST['id_programmation'] ?? 0);

            if ($idProgrammation === 0) {
                throw new Exception('Programmation non trouvée');
            }

            $this->pdo->beginTransaction();

            // Récupérer le numéro de jury
            $sqlGet = "SELECT num_jury FROM programmer WHERE id_programmation = ?";
            $stmtGet = $this->pdo->prepare($sqlGet);
            $stmtGet->execute([$idProgrammation]);
            $result = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['num_jury']) {
                $numJury = $result['num_jury'];

                // Supprimer les membres du jury
                $sqlDeleteComposer = "DELETE FROM composer WHERE num_jury = ?";
                $stmtDeleteComposer = $this->pdo->prepare($sqlDeleteComposer);
                $stmtDeleteComposer->execute([$numJury]);

                // Supprimer le jury
                $sqlDeleteJury = "DELETE FROM jury WHERE num_jury = ?";
                $stmtDeleteJury = $this->pdo->prepare($sqlDeleteJury);
                $stmtDeleteJury->execute([$numJury]);
            }

            // Supprimer la programmation
            $sqlDeleteProgrammer = "DELETE FROM programmer WHERE id_programmation = ?";
            $stmtDeleteProgrammer = $this->pdo->prepare($sqlDeleteProgrammer);
            $stmtDeleteProgrammer->execute([$idProgrammation]);

            $this->pdo->commit();

            $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'programmer', 'Succès');
            $this->logger->info("Attribution de jury supprimée pour la programmation {$idProgrammation}");

            return [
                'success' => true,
                'message' => 'Attribution supprimée avec succès'
            ];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->logger->error("Erreur supprimerAttribution: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }
}
