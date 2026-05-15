<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Enseignant.php";
require_once __DIR__ . "/../models/Etudiant.php";
require_once __DIR__ . "/../models/NiveauEtude.php";
require_once __DIR__ . '/../utils/AcademicYear.php';

use Enseignant;
use Etudiant;
use NiveauEtude;
use PDO;

/**
 * Service métier du tableau de bord enseignant
 *
 * Contient toute la logique métier pour le tableau de bord enseignant :
 * - Statistiques des niveaux d'étude gérés
 * - Statistiques des étudiants encadrés
 */
class DashboardEnseignantService
{
    /** @var PDO */
    private $db;

    /** @var Enseignant */
    private $enseignant;

    /** @var Etudiant */
    private $etudiant;

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
        $enseignantData = $this->enseignant->getEnseignantByLogin($loginUtilisateur);
        if (!$enseignantData) {
            return [
                'total_etudiants' => 0,
                'total_niveaux' => 0,
                'mes_niveaux' => [],
            ];
        }

        $enseignantId = $enseignantData->id_enseignant;

        // Niveaux d'étude pris en charge par l'enseignant (Responsable de niveau)
        $niveaux = $this->getNiveauxByEnseignant($enseignantId);
        $niveauIds = array_map(function($n) { return $n->id_niv_etude; }, $niveaux);

        // Étudiants inscrits dans ces niveaux
        $etudiantsSuivantCours = $this->getEtudiantsByNiveaux($niveauIds);

        // Construire la liste des niveaux avec statistiques
        $mesNiveaux = $this->buildMesNiveauxStats($niveaux, $etudiantsSuivantCours);

        // Ajouter les stats des étudiants supervisés (directeur/encadrant)
        $etudiantsSuivis = $this->getEtudiantsSuivisStats($enseignantId);

        return [
            'total_etudiants' => count($etudiantsSuivantCours),
            'total_niveaux' => count($niveaux),
            'mes_cours' => $mesNiveaux, // Garder le nom pour compatibilité vue
            'etudiants_suivis' => $etudiantsSuivis,
        ];
    }

    /**
     * Récupère les statistiques des étudiants supervisés par l'enseignant
     * (dont il est le directeur de mémoire ou l'encadrant pédagogique)
     *
     * Utilise la table `affecter` qui lie un enseignant à un rapport
     * avec un rôle (directeur ou encadrant).
     *
     * @param int|string $enseignantId ID de l'enseignant
     * @return array{total_etudiants_suivis: int, etudiants_encadres: int, etudiants_diriges: int}
     */
    private function getEtudiantsSuivisStats($enseignantId): array
    {
        try {
            $sql = "SELECT
                        COUNT(DISTINCT r.num_etu) AS total,
                        SUM(CASE WHEN LOWER(a.role) LIKE 'encadr%' THEN 1 ELSE 0 END) AS encadres,
                        SUM(CASE WHEN a.role = 'directeur' THEN 1 ELSE 0 END) AS diriges
                    FROM affecter a
                    JOIN rapport_etudiants r ON a.id_rapport = r.id_rapport
                    WHERE a.id_enseignant = ?
                      AND a.role IN ('encadrant', 'directeur')";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$enseignantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_etudiants_suivis' => (int)($row['total'] ?? 0),
                'etudiants_encadres' => (int)($row['encadres'] ?? 0),
                'etudiants_diriges' => (int)($row['diriges'] ?? 0),
            ];
        } catch (\Exception $e) {
            error_log("Erreur getEtudiantsSuivisStats: " . $e->getMessage());
            return [
                'total_etudiants_suivis' => 0,
                'etudiants_encadres' => 0,
                'etudiants_diriges' => 0,
            ];
        }
    }

    /**
     * Récupère les niveaux d'étude gérés par un enseignant
     */
    private function getNiveauxByEnseignant($enseignantId) {
        $sql = "SELECT * FROM niveau_etude WHERE id_enseignant = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$enseignantId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Construit les stats par niveau
     */
    private function buildMesNiveauxStats(array $niveaux, array $etudiantsSuivantCours)
    {
        $mesCours = [];
        foreach ($niveaux as $niveau) {
            $mesCours[] = [
                'nom' => $niveau->lib_niv_etude,
                'niveau' => $niveau->lib_niv_etude,
                'nombre_etudiants' => array_reduce($etudiantsSuivantCours, function ($carry, $etu) use ($niveau) {
                    return $carry + ($etu->id_niv_etude == $niveau->id_niv_etude ? 1 : 0);
                }, 0)
            ];
        }
        return $mesCours;
    }

    /**
     * Récupère les étudiants inscrits dans les niveaux donnés (sans doublons)
     *
     * @param array $niveauIds Liste d'identifiants de niveaux
     * @return array Étudiants indexés par num_etu
     */
    private function getEtudiantsByNiveaux(array $niveauIds)
    {
        if (empty($niveauIds)) return [];
        
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
     * Récupère toutes les données du tableau de bord et les assigne aux \$GLOBALS
     *
     * Méthode de convenance pour la compatibilité avec les vues existantes.
     *
     * @param string $loginUtilisateur Login de l'enseignant connecté
     */
    public function populateDashboardGlobals($loginUtilisateur)
    {
        $stats = $this->getGlobalStats($loginUtilisateur);

        $GLOBALS['total_etudiants'] = (int) ($stats['total_etudiants'] ?? 0);
        $GLOBALS['total_ues'] = (int) ($stats['total_ues'] ?? ($stats['total_niveaux'] ?? 0));
        $GLOBALS['total_ecues'] = (int) ($stats['total_ecues'] ?? 0);
        $GLOBALS['mes_cours'] = $stats['mes_cours'] ?? ($stats['mes_niveaux'] ?? []);
        $GLOBALS['etudiants_suivis'] = $stats['etudiants_suivis'] ?? [
            'total_etudiants_suivis' => 0,
            'etudiants_encadres' => 0,
            'etudiants_diriges' => 0,
        ];
    }
}
