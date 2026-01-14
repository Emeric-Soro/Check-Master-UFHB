<?php

namespace App\Controllers;

use PDO;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Ue;
use App\Models\Ecue;
use App\Models\NiveauEtude;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * DashboardEnseignantController - Vue spécifique Enseignant
 * 
 * Ce contrôleur gère l'affichage du tableau de bord enseignant :
 * - Statistiques des cours enseignés
 * - Statistiques des évaluations
 * - Statistiques des étudiants encadrés
 * - Calendrier et échéances
 * 
 * @package App\Controllers
 */
class DashboardEnseignantController
{
    private PDO $pdo;
    private Enseignant $enseignant;
    private Etudiant $etudiant;
    private Ue $ue;
    private Ecue $ecue;
    private NiveauEtude $niveauEtude;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        Enseignant $enseignant,
        Etudiant $etudiant,
        Ue $ue,
        Ecue $ecue,
        NiveauEtude $niveauEtude,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->enseignant = $enseignant;
        $this->etudiant = $etudiant;
        $this->ue = $ue;
        $this->ecue = $ecue;
        $this->niveauEtude = $niveauEtude;
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
        
        if (!$this->security->can($idGroupe, 'dashboard_enseignant', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur dashboard_enseignant"
            );
            
            $GLOBALS['error'] = "Vous n'avez pas les droits nécessaires.";
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                http_response_code(403);
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            return false;
        }
        return true;
    }

    /**
     * Action : Affiche le tableau de bord enseignant (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $this->getGlobalStats();
        } catch (Exception $e) {
            $this->logger->error("Erreur dans DashboardEnseignantController::index: " . $e->getMessage());
            $GLOBALS['error'] = "Erreur lors du chargement du tableau de bord.";
        }
    }

    /**
     * Récupère les statistiques globales
     */
    private function getGlobalStats(): void
    {
        try {
            $enseignantData = $this->enseignant->getEnseignantByLogin($_SESSION['login_utilisateur']);
            
            if (!$enseignantData) {
                $GLOBALS['total_etudiants'] = 0;
                $GLOBALS['total_ues'] = 0;
                $GLOBALS['total_ecues'] = 0;
                $GLOBALS['mes_cours'] = [];
                return;
            }

            $enseignantId = $enseignantData->id_enseignant;

            // UE et ECUE pris en charge par l'enseignant
            $ues = $this->ue->getUesByEnseignant($enseignantId);
            $ecues = $this->ecue->getEcuesByEnseignant($enseignantId);

            // Récupérer tous les niveaux concernés par les UE/ECUE pris en charge
            $niveauIds = [];
            foreach ($ues as $ue) {
                if (isset($ue->id_niveau_etude) && !in_array($ue->id_niveau_etude, $niveauIds)) {
                    $niveauIds[] = $ue->id_niveau_etude;
                }
            }
            foreach ($ecues as $ecue) {
                if (isset($ecue->id_niveau_etude) && !in_array($ecue->id_niveau_etude, $niveauIds)) {
                    $niveauIds[] = $ecue->id_niveau_etude;
                }
            }

            // Étudiants inscrits dans ces niveaux (sans doublons)
            $etudiants = $this->etudiant->getAllListeEtudiants();
            $etudiantsSuivantCours = [];
            foreach ($etudiants as $etudiant) {
                if (in_array($etudiant->id_niv_etude, $niveauIds)) {
                    $etudiantsSuivantCours[$etudiant->num_etu] = $etudiant;
                }
            }

            // Statistiques
            $GLOBALS['total_etudiants'] = count($etudiantsSuivantCours);
            $GLOBALS['total_ues'] = count($ues);
            $GLOBALS['total_ecues'] = count($ecues);

            // Pour affichage des cours
            $mes_cours = [];
            foreach ($ues as $ue) {
                $mes_cours[] = [
                    'nom' => $ue->lib_ue,
                    'niveau' => $ue->lib_niv_etude ?? '',
                    'nombre_etudiants' => array_reduce($etudiantsSuivantCours, function($carry, $etu) use ($ue) {
                        return $carry + ((isset($ue->id_niveau_etude) && $etu->id_niv_etude == $ue->id_niveau_etude) ? 1 : 0);
                    }, 0)
                ];
            }
            foreach ($ecues as $ecue) {
                $mes_cours[] = [
                    'nom' => $ecue->lib_ecue,
                    'niveau' => $ecue->lib_niv_etude ?? '',
                    'nombre_etudiants' => array_reduce($etudiantsSuivantCours, function($carry, $etu) use ($ecue) {
                        return $carry + ((isset($ecue->id_niveau_etude) && $etu->id_niv_etude == $ecue->id_niveau_etude) ? 1 : 0);
                    }, 0)
                ];
            }
           
            $GLOBALS['mes_cours'] = $mes_cours;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur getGlobalStats: " . $e->getMessage());
            $GLOBALS['total_etudiants'] = 0;
            $GLOBALS['total_ues'] = 0;
            $GLOBALS['total_ecues'] = 0;
            $GLOBALS['mes_cours'] = [];
        }
    }
}
