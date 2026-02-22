<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Enseignant.php";
require_once __DIR__ . "/../models/Etudiant.php";
require_once __DIR__ . "/../models/Ue.php";
require_once __DIR__ . "/../models/Ecue.php";
require_once __DIR__ . "/../models/NiveauEtude.php";
require_once __DIR__ . '/../utils/AcademicYear.php';

use Enseignant;
use Etudiant;
use Ue;
use Ecue;
use NiveauEtude;
use PDO;

/**
 * Service métier du tableau de bord enseignant
 *
 * Contient toute la logique métier pour le tableau de bord enseignant :
 * - Statistiques des cours enseignés (UE/ECUE)
 * - Statistiques des étudiants encadrés
 * - Données des cours pour l'affichage
 */
class DashboardEnseignantService
{
    /** @var PDO */
    private $db;

    /** @var Enseignant */
    private $enseignant;

    /** @var Etudiant */
    private $etudiant;

    /** @var Ue */
    private $ue;

    /** @var Ecue */
    private $ecue;

    /** @var NiveauEtude */
    private $niveauEtude;

    /**
     * Constructeur du service
     *
     * @param PDO $db Connexion à la base de données
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->enseignant = new Enseignant($db);
        $this->etudiant = new Etudiant($db);
        $this->ue = new Ue($db);
        $this->ecue = new Ecue($db);
        $this->niveauEtude = new NiveauEtude($db);
    }

    /**
     * Récupère les statistiques globales pour un enseignant
     *
     * @param string $loginUtilisateur Login de l'enseignant connecté
     * @return array Tableau associatif des statistiques globales
     */
    public function getGlobalStats($loginUtilisateur)
    {
        $enseignantId = $this->enseignant->getEnseignantByLogin($loginUtilisateur)->id_enseignant;

        // UE et ECUE pris en charge par l'enseignant
        $ues = $this->ue->getUesByEnseignant($enseignantId);
        $ecues = $this->ecue->getEcuesByEnseignant($enseignantId);

        // Récupérer tous les niveaux concernés par les UE/ECUE pris en charge
        $niveauIds = $this->extractNiveauIds($ues, $ecues);

        // Étudiants inscrits dans ces niveaux (sans doublons)
        $etudiantsSuivantCours = $this->getEtudiantsByNiveaux($niveauIds);

        // Construire la liste des cours
        $mesCours = $this->buildMesCours($ues, $ecues, $etudiantsSuivantCours);

        return [
            'total_etudiants' => count($etudiantsSuivantCours),
            'total_ues' => count($ues),
            'total_ecues' => count($ecues),
            'mes_cours' => $mesCours,
        ];
    }

    /**
     * Extrait les identifiants de niveaux d'étude à partir des UE et ECUE
     *
     * @param array $ues Liste des UE
     * @param array $ecues Liste des ECUE
     * @return array Liste d'identifiants de niveaux (sans doublons)
     */
    private function extractNiveauIds(array $ues, array $ecues)
    {
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
        return $niveauIds;
    }

    /**
     * Récupère les étudiants inscrits dans les niveaux donnés (sans doublons)
     *
     * @param array $niveauIds Liste d'identifiants de niveaux
     * @return array Étudiants indexés par num_etu
     */
    private function getEtudiantsByNiveaux(array $niveauIds)
    {
        $selectedYearId = \AcademicYear::getSelectedIdFromSession();
        $etudiants = $this->etudiant->getAllListeEtudiants($selectedYearId);
        $etudiantsSuivantCours = [];
        foreach ($etudiants as $etudiant) {
            if (in_array($etudiant->id_niv_etude, $niveauIds)) {
                $studentKey = (string) ($etudiant->num_carte_etud ?? $etudiant->num_etu ?? '');
                if ($studentKey === '') {
                    continue;
                }
                $etudiantsSuivantCours[$studentKey] = $etudiant;
            }
        }
        return $etudiantsSuivantCours;
    }

    /**
     * Construit la liste des cours (UE + ECUE) avec le nombre d'étudiants par cours
     *
     * @param array $ues Liste des UE
     * @param array $ecues Liste des ECUE
     * @param array $etudiantsSuivantCours Étudiants indexés par num_etu
     * @return array Liste des cours avec nom, niveau et nombre d'étudiants
     */
    private function buildMesCours(array $ues, array $ecues, array $etudiantsSuivantCours)
    {
        $mesCours = [];
        foreach ($ues as $ue) {
            $mesCours[] = [
                'nom' => $ue->lib_ue,
                'niveau' => $ue->lib_niv_etude,
                'nombre_etudiants' => array_reduce($etudiantsSuivantCours, function ($carry, $etu) use ($ue) {
                    return $carry + ((isset($ue->id_niveau_etude) && $etu->id_niv_etude == $ue->id_niveau_etude) ? 1 : 0);
                }, 0)
            ];
        }
        foreach ($ecues as $ecue) {
            $mesCours[] = [
                'nom' => $ecue->lib_ecue,
                'niveau' => $ecue->lib_niv_etude,
                'nombre_etudiants' => array_reduce($etudiantsSuivantCours, function ($carry, $etu) use ($ecue) {
                    return $carry + ((isset($ecue->id_niveau_etude) && $etu->id_niv_etude == $ecue->id_niveau_etude) ? 1 : 0);
                }, 0)
            ];
        }
        return $mesCours;
    }

    /**
     * Récupère toutes les données du tableau de bord et les assigne aux \$GLOBALS
     *
     * Méthode de convenance pour la compatibilité avec les vues existantes.
     *
     * @param string $loginUtilisateur Login de l'enseignant connecté
     */
    public function populateDashboardGlobals($loginUtilisateur)
    {
        $stats = $this->getGlobalStats($loginUtilisateur);

        $GLOBALS['total_etudiants'] = $stats['total_etudiants'];
        $GLOBALS['total_ues'] = $stats['total_ues'];
        $GLOBALS['total_ecues'] = $stats['total_ecues'];
        $GLOBALS['mes_cours'] = $stats['mes_cours'];
    }
}
