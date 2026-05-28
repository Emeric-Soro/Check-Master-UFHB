<?php

namespace CheckMaster\Services;

use Database;
use Etudiant;
use Inscription;
use InfoStage;
use RapportEtudiant;
use Note;
use Reclamation;
use Soutenance;
use AnneeAcademique;
use PDO;
use PDOException;
use DocumentRegistry;

require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Inscription.php';
require_once __DIR__ . '/../models/InfoStage.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/CandidatureSoutenance.php';
require_once __DIR__ . '/../models/CompteRendu.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Reclamation.php';
require_once __DIR__ . '/../models/Soutenance.php';
require_once __DIR__ . '/../Services/Document/DocumentRegistry.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

/**
 * EtudiantFicheService – Agrégation des 10 onglets de la Fiche Étudiante Complète.
 * 
 * Chaque méthode publique correspond à un onglet et retourne un tableau
 * de données prêtes à l'affichage.
 */
class EtudiantFicheService
{
    private $db;
    private $etudiantModel;
    private $inscriptionModel;
    private $infoStageModel;
    private $rapportModel;
    private $noteModel;
    private $reclamationModel;
    private $soutenanceModel;
    private $anneeModel;
    private $registry;

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getConnection();
        $this->etudiantModel = new Etudiant($this->db);
        $this->inscriptionModel = new Inscription($this->db);
        $this->infoStageModel = new InfoStage($this->db);
        $this->rapportModel = new RapportEtudiant($this->db);
        $this->noteModel = new Note($this->db);
        $this->reclamationModel = new Reclamation();
        $this->soutenanceModel = new Soutenance($this->db);
        $this->anneeModel = new AnneeAcademique($this->db);
        $this->registry = new DocumentRegistry($this->db);
    }

    /**
     * Point d'entrée unique : agrège les données des 10 onglets.
     *
     * @param string $matricule num_carte_etud
     * @param int|null $anneeId  id_annee_acad
     * @return array|null
     */
    public function getCompleteProfile($matricule, $anneeId = null)
    {
        // Utilise le profil archive existant comme base
        $archiveProfile = $this->etudiantModel->getArchiveProfile($matricule, $anneeId);
        if (!$archiveProfile) {
            return null;
        }

        // Construit chaque onglet
        $profile = [
            'identite'         => $this->getIdentite($matricule, $anneeId, $archiveProfile),
            'inscriptions'     => $this->getInscriptions($matricule),
            'stage'            => $this->getStage($matricule, $archiveProfile),
            'rapport'          => $this->getRapport($matricule, $archiveProfile),
            'candidature'      => $this->getCandidature($matricule, $archiveProfile),
            'soutenance'       => $this->getSoutenance($matricule, $archiveProfile),
            'cr'               => $this->getCompteRendu($matricule),
            'notes'            => $this->getNotes($matricule),
            'reclamations'     => $this->getReclamations($matricule),
            'documents'        => $this->getDocuments($matricule, $anneeId),
        ];

        return $profile;
    }

    // ──────────────────────────────────────────────
    //  Onglet 1 : Identité + Dossier Académique
    // ──────────────────────────────────────────────

    /**
     * @return array{etudiant: object|null, dossier: array, niveau: object|null}
     */
    public function getIdentite($matricule, $anneeId = null, $archiveProfile = null)
    {
        if ($archiveProfile) {
            $base = $archiveProfile['base'];
            $etudiant = (object) $base;
        } else {
            $etudiant = $this->etudiantModel->getEtudiantById($matricule);
            if (!$etudiant) {
                return ['etudiant' => null, 'dossier' => [], 'niveau' => null];
            }
        }

        $niveau = $this->etudiantModel->getNiveauByEtudiant($matricule);

        // Calcul du dossier académique
        $notes = $this->noteModel->getByStudent($matricule, $anneeId);
        $dossier = [
            'moyenne_M1' => null,
            'moyenne_M2' => null,
            'credits_total' => 0,
            'credits_valides' => 0,
        ];
        if (!empty($notes)) {
            $lastNote = $notes[0];
            $dossier['moyenne_M1'] = $lastNote->moyenne_M1 ?? null;
            $dossier['moyenne_M2'] = $lastNote->moyenne_M2 ?? null;
        }

        return [
            'etudiant' => $etudiant,
            'dossier'  => $dossier,
            'niveau'   => $niveau,
        ];
    }

    // ──────────────────────────────────────────────
    //  Onglet 2 : Inscriptions
    // ──────────────────────────────────────────────

    /**
     * @return array<int, object>
     */
    public function getInscriptions($matricule)
    {
        return $this->inscriptionModel->getInscriptionsByEtudiant($matricule);
    }

    // ──────────────────────────────────────────────
    //  Onglet 3 : Stage
    // ──────────────────────────────────────────────

    /**
     * @return array|null
     */
    public function getStage($matricule, $archiveProfile = null)
    {
        if ($archiveProfile && !empty($archiveProfile['stage'])) {
            return $archiveProfile['stage'];
        }
        return $this->infoStageModel->getStageInfo($matricule);
    }

    // ──────────────────────────────────────────────
    //  Onglet 4 : Rapport
    // ──────────────────────────────────────────────

    /**
     * @return array{rapport: object|null, encadrement: array, validation: array|null}
     */
    public function getRapport($matricule, $archiveProfile = null)
    {
        if ($archiveProfile) {
            $rapportData = $archiveProfile['rapport'] ?? null;
            $encadrement = $archiveProfile['encadrement'] ?? [];
        } else {
            $rapports = $this->rapportModel->getRapportsByEtudiant($matricule);
            $rapportData = !empty($rapports) ? (array) $rapports[0] : null;
            $encadrement = [];
            if ($rapportData && isset($rapportData['id_rapport'])) {
                $stmt = $this->db->prepare("
                    SELECT af.role, ens.id_enseignant, ens.nom_enseignant, ens.prenom_enseignant
                    FROM affecter af
                    INNER JOIN enseignants ens ON ens.id_enseignant = af.id_enseignant
                    WHERE af.id_rapport = ?
                ");
                $stmt->execute([$rapportData['id_rapport']]);
                $encadrement = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        // Validation
        $validation = null;
        $idRapport = $rapportData['id_rapport'] ?? null;
        if ($idRapport) {
            $stmt = $this->db->prepare("
                SELECT
                    v.*,
                    TRIM(CONCAT(COALESCE(e.nom_enseignant, ''), ' ', COALESCE(e.prenom_enseignant, ''))) AS valide_par
                FROM valider v
                LEFT JOIN enseignants e ON e.id_enseignant = v.id_enseignant
                WHERE v.id_rapport = ?
                ORDER BY v.date_validation DESC
                LIMIT 1
            ");
            $stmt->execute([$idRapport]);
            $validation = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        return [
            'rapport'     => $rapportData ?? null,
            'encadrement' => $encadrement,
            'validation'  => $validation,
        ];
    }

    // ──────────────────────────────────────────────
    //  Onglet 5 : Candidature Soutenance
    // ──────────────────────────────────────────────

    /**
     * @return array<int, array>
     */
    public function getCandidature($matricule, $archiveProfile = null)
    {
        if ($archiveProfile && !empty($archiveProfile['candidatures'])) {
            return $archiveProfile['candidatures'];
        }
        return $this->etudiantModel->getCandidatures($matricule);
    }

    // ──────────────────────────────────────────────
    //  Onglet 6 : Soutenance + Jury + Évaluation
    // ──────────────────────────────────────────────

    /**
     * @return array{soutenances: array, jury: array, evaluation: array}
     */
    public function getSoutenance($matricule, $archiveProfile = null)
    {
        if ($archiveProfile) {
            $soutenance = $archiveProfile['soutenance'] ?? null;
            $jury = $archiveProfile['jury'] ?? [];
            $evaluation = $archiveProfile['evaluation'] ?? [];
        } else {
            $soutenance = null;
            $jury = [];
            $evaluation = [];

            $stmt = $this->db->prepare("
                SELECT ps.*, s.lib_salle, d.lib_domaine, se.lib_session
                FROM programmer_soutenance ps
                LEFT JOIN salles s ON s.id_salle = ps.id_salle
                LEFT JOIN domaine d ON d.id_domaine = ps.id_domaine
                LEFT JOIN session se ON se.id_session = ps.id_session
                WHERE ps.num_etud = ?
                ORDER BY ps.date_soutenance DESC
                LIMIT 1
            ");
            $stmt->execute([$matricule]);
            $soutenance = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($soutenance && !empty($soutenance['num_soutenance'])) {
                $stmtJury = $this->db->prepare("
                    SELECT en.id_enseignant, en.nom_enseignant, en.prenom_enseignant,
                           qj.lib_role, g.lib_grade
                    FROM enseignant_jury ej
                    INNER JOIN enseignants en ON en.id_enseignant = ej.id_enseignant
                    INNER JOIN qualite_jury qj ON qj.id_role_jury = ej.id_qualite_jury
                    LEFT JOIN avoir a ON a.id_enseignant = en.id_enseignant
                    LEFT JOIN grade g ON g.id_grade = a.id_grade
                    WHERE ej.num_soutenance = ?
                    ORDER BY qj.id_role_jury ASC
                ");
                $stmtJury->execute([$soutenance['num_soutenance']]);
                $jury = $stmtJury->fetchAll(PDO::FETCH_ASSOC);

                $stmtEval = $this->db->prepare("
                    SELECT ce.lib_critere, ev.note, bc.bareme AS coefficient
                    FROM evaluer ev
                    INNER JOIN critere_evaluation ce ON ce.id_critere = ev.id_critere
                    LEFT JOIN bareme_critere bc ON bc.id_critere = ce.id_critere
                    WHERE ev.num_etudiant = ? AND ev.num_jury = ?
                ");
                $stmtEval->execute([$matricule, $soutenance['num_soutenance']]);
                $evaluation = $stmtEval->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        return [
            'soutenances' => $soutenance ? [$soutenance] : [],
            'jury'        => $jury,
            'evaluation'  => $evaluation,
        ];
    }

    // ──────────────────────────────────────────────
    //  Onglet 7 : Compte Rendu
    // ──────────────────────────────────────────────

    /**
     * @return array|null
     */
    public function getCompteRendu($matricule)
    {
        return $this->etudiantModel->getCompteRendu($matricule);
    }

    // ──────────────────────────────────────────────
    //  Onglet 8 : Notes / Résultats
    // ──────────────────────────────────────────────

    /**
     * @return array{notes_unifies: array, note_stats: array}
     */
    public function getNotes($matricule)
    {
        $notesUnified = [];

        // Notes par UE (notes table jointe avec ue)
        try {
            $stmt = $this->db->prepare("
                SELECT n.moyenne, n.moyenne_M1, n.moyenne_M2, n.date_creation,
                       u.lib_ue, u.credit, u.code_ue,
                       s.lib_semestre,
                       ne.lib_niv_etude
                FROM notes n
                LEFT JOIN ue u ON n.id_ue = u.id_ue
                LEFT JOIN semestre s ON u.id_semestre = s.id_semestre
                LEFT JOIN niveau_etude ne ON n.id_niv_etude = ne.id_niv_etude
                WHERE n.num_etu = ?
                ORDER BY n.date_creation DESC
            ");
            $stmt->execute([$matricule]);
            $notesUnified = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("EtudiantFicheService::getNotes erreur: " . $e->getMessage());
        }

        // Statistiques globales
        $stats = $this->etudiantModel->getNotesEtudiant($matricule);

        return [
            'notes_unifies' => $notesUnified,
            'note_stats'    => $stats,
        ];
    }

    // ──────────────────────────────────────────────
    //  Onglet 9 : Réclamations
    // ──────────────────────────────────────────────

    /**
     * @return array<int, object>
     */
    public function getReclamations($matricule)
    {
        $all = $this->reclamationModel->getAllReclamationsWithEtudiant();
        return array_values(array_filter($all, function ($r) use ($matricule) {
            $num = $r->num_etu ?? $r->num_carte_etud ?? '';
            return (string) $num === (string) $matricule;
        }));
    }

    // ──────────────────────────────────────────────
    //  Onglet 10 : Documents
    // ──────────────────────────────────────────────

    /**
     * @return array<int, array>
     */
    public function getDocuments($matricule, $anneeId = null)
    {
        return $this->etudiantModel->getDocuments($matricule, $anneeId);
    }
}
