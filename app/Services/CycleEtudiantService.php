<?php

namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Inscription.php';
require_once __DIR__ . '/../models/InfoStage.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/CandidatureSoutenance.php';
require_once __DIR__ . '/../models/CompteRendu.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../Services/EtudiantFicheService.php';
require_once __DIR__ . '/../Services/CandidatureSoutenanceService.php';
require_once __DIR__ . '/../Services/GestionRapportService.php';
require_once __DIR__ . '/../Services/ProcessusValidationService.php';
require_once __DIR__ . '/../Services/ProgrammationSoutenanceService.php';
require_once __DIR__ . '/../Services/EvaluationSoutenanceService.php';
require_once __DIR__ . '/../Services/EditionBulletinService.php';
require_once __DIR__ . '/../utils/AcademicYear.php';

use Etudiant;
use Inscription;
use InfoStage;
use RapportEtudiant;
use AnneeAcademique;
use PDO;
use Exception;

/**
 * CycleEtudiantService – Orchestration du cycle de vie complet d'un etudiant M2.
 *
 * Agrege les services existants pour fournir une vue unifiee
 * de l'inscription jusqu'au PV final.
 */
class CycleEtudiantService
{
    private $pdo;
    private $etudiantModel;
    private $inscriptionModel;
    private $infoStageModel;
    private $rapportModel;
    private $anneeModel;

    // Services existants (lazy-loaded)
    private $ficheService;
    private $candidatureService;
    private $rapportService;
    private $validationService;
    private $programmationService;
    private $evaluationService;
    private $bulletinService;

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?: \Database::getConnection();
        $this->etudiantModel = new Etudiant($this->pdo);
        $this->inscriptionModel = new Inscription($this->pdo);
        $this->infoStageModel = new InfoStage($this->pdo);
        $this->rapportModel = new RapportEtudiant($this->pdo);
        $this->anneeModel = new AnneeAcademique($this->pdo);
    }

    // ──────────────────────────────────────────────
    //  Services existants (lazy loading)
    // ──────────────────────────────────────────────

    private function ficheService()
    {
        if ($this->ficheService === null) {
            $this->ficheService = new EtudiantFicheService($this->pdo);
        }
        return $this->ficheService;
    }

    private function candidatureService()
    {
        if ($this->candidatureService === null) {
            $this->candidatureService = new CandidatureSoutenanceService($this->pdo);
        }
        return $this->candidatureService;
    }

    private function rapportService()
    {
        if ($this->rapportService === null) {
            $this->rapportService = new GestionRapportService($this->pdo);
        }
        return $this->rapportService;
    }

    private function validationService()
    {
        if ($this->validationService === null) {
            $this->validationService = new ProcessusValidationService($this->pdo);
        }
        return $this->validationService;
    }

    private function programmationService()
    {
        if ($this->programmationService === null) {
            $this->programmationService = new ProgrammationSoutenanceService($this->pdo);
        }
        return $this->programmationService;
    }

    private function evaluationService()
    {
        if ($this->evaluationService === null) {
            $this->evaluationService = new EvaluationSoutenanceService($this->pdo);
        }
        return $this->evaluationService;
    }

    private function bulletinService()
    {
        if ($this->bulletinService === null) {
            $this->bulletinService = new EditionBulletinService($this->pdo);
        }
        return $this->bulletinService;
    }

    // ═══════════════════════════════════════════════
    //  RECHERCHE ETUDIANTS
    // ═══════════════════════════════════════════════

    /**
     * Recherche d'etudiants par nom, prenom, matricule (autocompletion).
     *
     * @param string $q Terme de recherche
     * @return array Liste d'etudiants [{id, text}]
     */
    public function rechercherEtudiants(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT num_carte_etud, num_ident_etud, nom_etu, prenom_etu, promotion_etu
            FROM etudiants
            WHERE num_carte_etud LIKE :q1 OR num_ident_etud LIKE :q2
               OR nom_etu LIKE :q3 OR prenom_etu LIKE :q4
               OR CONCAT(nom_etu, ' ', prenom_etu) LIKE :q5
            ORDER BY nom_etu, prenom_etu
            LIMIT 15
        ");
        $like = '%' . $q . '%';
        $stmt->execute(['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like, 'q5' => $like]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'id' => $row['num_carte_etud'],
                'text' => $row['nom_etu'] . ' ' . $row['prenom_etu'] . ' (' . $row['num_carte_etud'] . ')',
                'promotion' => $row['promotion_etu'] ?? '',
            ];
        }
        return $results;
    }

    // ═══════════════════════════════════════════════
    //  PROGRESSION (13 statuts)
    // ═══════════════════════════════════════════════

    /**
     * Calcule la progression complete d'un etudiant (13 statuts).
     * Met a jour cycle_etudiant_statut en cache.
     *
     * @return array 13 statuts + progression_pct
     */
    public function getProgressionComplete(string $numEtu, int $idAnneeAcad): array
    {
        if ($idAnneeAcad <= 0) {
            return $this->formatProgression([
                'num_etu' => $numEtu,
                'id_annee_acad' => 0,
            ]);
        }

        // Essayer le cache d'abord
        $stmt = $this->pdo->prepare("
            SELECT * FROM cycle_etudiant_statut
            WHERE num_etu = :etu AND id_annee_acad = :annee
        ");
        $stmt->execute(['etu' => $numEtu, 'annee' => $idAnneeAcad]);
        $cache = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cache && $cache['date_maj'] > date('Y-m-d H:i:s', strtotime('-5 minutes'))) {
            return $this->formatProgression($cache);
        }

        // Recalculer
        $statuts = $this->calculerStatuts($numEtu, $idAnneeAcad);
        $this->sauvegarderCache($numEtu, $idAnneeAcad, $statuts);

        return $this->formatProgression($statuts);
    }

    /**
     * Force la mise a jour du cache pour un etudiant.
     */
    public function mettreAJourStatutCycle(string $numEtu, int $idAnneeAcad): void
    {
        $statuts = $this->calculerStatuts($numEtu, $idAnneeAcad);
        $this->sauvegarderCache($numEtu, $idAnneeAcad, $statuts);
    }

    /**
     * Retourne la progression de tous les etudiants d'une annee (pour listes).
     * Utilise le cache cycle_etudiant_statut -> 1 requete au lieu de N x 11.
     */
    public function getProgressionGlobale(int $idAnneeAcad): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, e.nom_etu, e.prenom_etu, e.promotion_etu
            FROM cycle_etudiant_statut c
            JOIN etudiants e ON c.num_etu = e.num_carte_etud
            WHERE c.id_annee_acad = :annee
            ORDER BY c.progression_pct DESC, e.nom_etu
        ");
        $stmt->execute(['annee' => $idAnneeAcad]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $results[] = $this->formatProgression($row);
        }
        return $results;
    }

    /**
     * Retourne les donnees detaillees d'une phase.
     */
    public function getPhaseDetails(string $numEtu, string $phase): array
    {
        $profile = $this->ficheService()->getCompleteProfile($numEtu);
        if (!$profile) {
            return ['error' => 'Etudiant non trouve'];
        }

        $phaseMap = [
            'identite' => 'identite',
            'inscription' => 'inscriptions',
            'stage' => 'stage',
            'candidature' => 'candidature',
            'rapport' => 'rapport',
            'pv_commission' => 'cr',
            'planning' => 'soutenance',
            'jury' => 'soutenance',
            'notes' => 'notes',
            'pv_final' => 'cr',
        ];

        $key = $phaseMap[$phase] ?? null;
        if ($key === null) {
            return ['error' => 'Phase inconnue : ' . $phase];
        }

        return $profile[$key] ?? [];
    }

    // ═══════════════════════════════════════════════
    //  CALCUL DES 13 STATUTS
    // ═══════════════════════════════════════════════

    private function calculerStatuts(string $numEtu, int $idAnneeAcad): array
    {
        return [
            'statut_inscription' => $this->calcStatutInscription($numEtu, $idAnneeAcad),
            'statut_stage' => $this->calcStatutStage($numEtu),
            'statut_candidature' => $this->calcStatutCandidature($numEtu),
            'statut_rapport' => $this->calcStatutRapport($numEtu),
            'statut_evaluations' => $this->calcStatutEvaluations($numEtu),
            'statut_validation_finale' => $this->calcStatutValidationFinale($numEtu),
            'statut_pv_commission' => $this->calcStatutPvCommission($numEtu),
            'statut_soutenance' => $this->calcStatutSoutenance($numEtu),
            'statut_jury' => $this->calcStatutJury($numEtu),
            'statut_notes_soutenance' => $this->calcStatutNotesSoutenance($numEtu),
            'statut_notes_m1m2' => $this->calcStatutNotesM1M2($numEtu),
            'statut_pv_final' => $this->calcStatutPvFinal($numEtu),
        ];
    }

    private function calcStatutInscription(string $numEtu, int $idAnneeAcad): string
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as cnt, SUM(montant_verser) as total_verse, MAX(solde) as solde
            FROM inscriptions
            WHERE num_carte_etud = :etu AND id_annee_acad = :annee
        ");
        $stmt->execute(['etu' => $numEtu, 'annee' => $idAnneeAcad]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['cnt'] == 0) {
            return 'absent';
        }

        // Verifier si le solde est à 0 (tout payé)
        $solde = (float) ($row['solde'] ?? 0);
        if ($solde <= 0 && (float) ($row['total_verse'] ?? 0) > 0) {
            return 'complet';
        }

        return 'incomplet';
    }

    private function calcStatutStage(string $numEtu): string
    {
        $stage = $this->infoStageModel->getStageInfo($numEtu);
        return ($stage && !empty($stage->id_info_stage)) ? 'renseigne' : 'absent';
    }

    private function calcStatutCandidature(string $numEtu): string
    {
        $stmt = $this->pdo->prepare("
            SELECT statut_candidature FROM candidature_soutenance
            WHERE num_etu = :etu
            ORDER BY id_candidature DESC LIMIT 1
        ");
        $stmt->execute(['etu' => $numEtu]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return 'absent';
        }

        $statut = trim((string) ($row['statut_candidature'] ?? ''));
        if (stripos($statut, 'Valid') !== false) {
            return 'validee';
        }
        if (stripos($statut, 'Rejet') !== false) {
            return 'rejetee';
        }
        if (stripos($statut, 'attente') !== false) {
            return 'en_attente';
        }
        return 'absent';
    }

    private function calcStatutRapport(string $numEtu): string
    {
        $stmt = $this->pdo->prepare("
            SELECT r.statut_rapport FROM rapport_etudiants r
            JOIN deposer d ON r.id_rapport = d.id_rapport
            WHERE d.num_etu = :etu
            ORDER BY d.date_depot DESC LIMIT 1
        ");
        $stmt->execute(['etu' => $numEtu]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return 'absent';
        }

        return match ($row['statut_rapport'] ?? '') {
            'valider' => 'valide',
            'rejeter' => 'rejete',
            'en_cours' => 'en_evaluation',
            'en_attente' => 'depose',
            default => 'depose',
        };
    }

    private function calcStatutEvaluations(string $numEtu): string
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as cnt FROM evaluations_rapports er
            JOIN deposer d ON d.id_rapport = er.id_rapport
            WHERE d.num_etu = :etu
        ");
        $stmt->execute(['etu' => $numEtu]);
        $cnt = (int) $stmt->fetchColumn();

        if ($cnt === 0) {
            return 'absent';
        }
        return $cnt >= 4 ? 'complet' : 'partiel';
    }

    private function calcStatutValidationFinale(string $numEtu): string
    {
        $stmt = $this->pdo->prepare("
            SELECT v.decision_validation FROM valider v
            JOIN deposer d ON d.id_rapport = v.id_rapport
            WHERE d.num_etu = :etu
            ORDER BY v.date_validation DESC LIMIT 1
        ");
        $stmt->execute(['etu' => $numEtu]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($row && $row['decision_validation'] === 'valider') ? 'valide' : 'absent';
    }

    private function calcStatutPvCommission(string $numEtu): string
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM compte_rendu cr
            JOIN compte_rendu_rapport crr ON cr.id_CR = crr.id_CR
            JOIN rapport_etudiants r ON crr.id_rapport = r.id_rapport
            JOIN deposer d ON d.id_rapport = r.id_rapport
            WHERE d.num_etu = :etu
        ");
        $stmt->execute(['etu' => $numEtu]);
        return ((int) $stmt->fetchColumn() > 0) ? 'redige' : 'absent';
    }

    private function calcStatutSoutenance(string $numEtu): string
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM programmer_soutenance
            WHERE num_etud = :etu
        ");
        $stmt->execute(['etu' => $numEtu]);
        return ((int) $stmt->fetchColumn() > 0) ? 'programme' : 'absent';
    }

    private function calcStatutJury(string $numEtu): string
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM enseignant_jury ej
            JOIN programmer_soutenance ps ON ej.num_soutenance = ps.num_soutenance
            WHERE ps.num_etud = :etu
        ");
        $stmt->execute(['etu' => $numEtu]);
        $cnt = (int) $stmt->fetchColumn();

        if ($cnt === 0) {
            return 'absent';
        }
        return $cnt >= 5 ? 'complet' : 'incomplet';
    }

    private function calcStatutNotesSoutenance(string $numEtu): string
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT e.id_critere) FROM evaluer e
            WHERE e.num_etudiant = :etu
        ");
        $stmt->execute(['etu' => $numEtu]);
        $cnt = (int) $stmt->fetchColumn();

        if ($cnt === 0) {
            return 'absent';
        }
        return $cnt >= 5 ? 'complet' : 'partiel';
    }

    private function calcStatutNotesM1M2(string $numEtu): string
    {
        $stmt = $this->pdo->prepare("
            SELECT moyenne_M1, moyenne_M2 FROM notes
            WHERE num_etu = :etu LIMIT 1
        ");
        $stmt->execute(['etu' => $numEtu]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return 'absent';
        }
        // Verifier que ce ne sont pas les valeurs par defaut (12.00)
        $m1 = (float) ($row['moyenne_M1'] ?? 0);
        $m2 = (float) ($row['moyenne_M2'] ?? 0);
        return ($m1 > 0 && $m2 > 0) ? 'present' : 'absent';
    }

    private function calcStatutPvFinal(string $numEtu): string
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM compte_rendu cr
            WHERE cr.num_etu = :etu AND cr.chemin_fichier_pdf IS NOT NULL
        ");
        $stmt->execute(['etu' => $numEtu]);
        return ((int) $stmt->fetchColumn() > 0) ? 'genere' : 'absent';
    }

    // ═══════════════════════════════════════════════
    //  CACHE
    // ═══════════════════════════════════════════════

    private function sauvegarderCache(string $numEtu, int $idAnneeAcad, array $statuts): void
    {
        $phasesCompletes = count(array_filter($statuts, function ($s) {
            return in_array($s, ['complet', 'valide', 'validee', 'redige', 'programme', 'genere', 'renseigne', 'present'], true);
        }));
        $pct = (int) round(($phasesCompletes / 13) * 100);

        $stmt = $this->pdo->prepare("
            INSERT INTO cycle_etudiant_statut
                (num_etu, id_annee_acad, statut_inscription, statut_stage, statut_candidature,
                 statut_rapport, statut_evaluations, statut_validation_finale, statut_pv_commission,
                 statut_soutenance, statut_jury, statut_notes_soutenance, statut_notes_m1m2,
                 statut_pv_final, progression_pct)
            VALUES (:etu, :annee, :statut_inscription, :statut_stage, :statut_candidature,
                 :statut_rapport, :statut_evaluations, :statut_validation_finale, :statut_pv_commission,
                 :statut_soutenance, :statut_jury, :statut_notes_soutenance, :statut_notes_m1m2,
                 :statut_pv_final, :pct)
            ON DUPLICATE KEY UPDATE
                statut_inscription = VALUES(statut_inscription),
                statut_stage = VALUES(statut_stage),
                statut_candidature = VALUES(statut_candidature),
                statut_rapport = VALUES(statut_rapport),
                statut_evaluations = VALUES(statut_evaluations),
                statut_validation_finale = VALUES(statut_validation_finale),
                statut_pv_commission = VALUES(statut_pv_commission),
                statut_soutenance = VALUES(statut_soutenance),
                statut_jury = VALUES(statut_jury),
                statut_notes_soutenance = VALUES(statut_notes_soutenance),
                statut_notes_m1m2 = VALUES(statut_notes_m1m2),
                statut_pv_final = VALUES(statut_pv_final),
                progression_pct = VALUES(progression_pct)
        ");

        $params = array_merge(
            ['etu' => $numEtu, 'annee' => $idAnneeAcad],
            $statuts,
            ['pct' => $pct]
        );
        $stmt->execute($params);
    }

    private function formatProgression(array $data): array
    {
        $statuts = [
            'statut_inscription' => $data['statut_inscription'] ?? 'absent',
            'statut_stage' => $data['statut_stage'] ?? 'absent',
            'statut_candidature' => $data['statut_candidature'] ?? 'absent',
            'statut_rapport' => $data['statut_rapport'] ?? 'absent',
            'statut_evaluations' => $data['statut_evaluations'] ?? 'absent',
            'statut_validation_finale' => $data['statut_validation_finale'] ?? 'absent',
            'statut_pv_commission' => $data['statut_pv_commission'] ?? 'absent',
            'statut_soutenance' => $data['statut_soutenance'] ?? 'absent',
            'statut_jury' => $data['statut_jury'] ?? 'absent',
            'statut_notes_soutenance' => $data['statut_notes_soutenance'] ?? 'absent',
            'statut_notes_m1m2' => $data['statut_notes_m1m2'] ?? 'absent',
            'statut_pv_final' => $data['statut_pv_final'] ?? 'absent',
        ];

        // Calculer le pourcentage
        $phasesCompletes = count(array_filter($statuts, function ($s) {
            return in_array($s, ['complet', 'valide', 'validee', 'redige', 'programme', 'genere', 'renseigne', 'present'], true);
        }));
        $pct = (int) round(($phasesCompletes / 13) * 100);

        return [
            'num_etu' => $data['num_etu'] ?? '',
            'id_annee_acad' => $data['id_annee_acad'] ?? 0,
            'nom_etu' => $data['nom_etu'] ?? '',
            'prenom_etu' => $data['prenom_etu'] ?? '',
            'promotion_etu' => $data['promotion_etu'] ?? '',
            'statuts' => $statuts,
            'progression_pct' => $pct,
            'phases_completes' => $phasesCompletes,
            'phases_total' => 13,
        ];
    }

    // ═══════════════════════════════════════════════
    //  ACTIONS D'ÉCRITURE
    // ═══════════════════════════════════════════════

    /**
     * Met à jour les informations d'identité d'un étudiant.
     */
    public function updateIdentite(string $numEtu, array $data): void
    {
        $fields = [];
        $params = ['num_etu' => $numEtu];

        if (isset($data['nom_etu'])) {
            $fields[] = 'nom_etu = :nom_etu';
            $params['nom_etu'] = $data['nom_etu'];
        }
        if (isset($data['prenom_etu'])) {
            $fields[] = 'prenom_etu = :prenom_etu';
            $params['prenom_etu'] = $data['prenom_etu'];
        }
        if (isset($data['id_genre'])) {
            $fields[] = 'id_genre = :id_genre';
            $params['id_genre'] = $data['id_genre'];
        }

        if (!empty($fields)) {
            $stmt = $this->pdo->prepare('
                UPDATE etudiants
                SET ' . implode(', ', $fields) . '
                WHERE num_carte_etud = :num_etu
            ');
            $stmt->execute($params);
        }
    }

    /**
     * Ajoute un versement (inscription) pour un étudiant.
     */
    public function addVersement(string $numEtu, int $idAnneeAcad, float $montant, string $mode): void
    {
        // Récupérer le dernier numéro de versement
        $stmt = $this->pdo->prepare('
            SELECT COALESCE(MAX(num_versement), 0) + 1
            FROM inscriptions
            WHERE num_carte_etud = :etu AND id_annee_acad = :annee
        ');
        $stmt->execute(['etu' => $numEtu, 'annee' => $idAnneeAcad]);
        $numVersement = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare('
            INSERT INTO inscriptions
                (num_carte_etud, id_annee_acad, num_versement, montant_versement,
                 mode_paiement, date_inscription, solde)
            VALUES
                (:etu, :annee, :num_v, :montant, :mode, NOW(), :solde)
        ');
        $stmt->execute([
            'etu' => $numEtu,
            'annee' => $idAnneeAcad,
            'num_v' => $numVersement,
            'montant' => $montant,
            'mode' => $mode,
            'solde' => -$montant, // Négatif car c'est un versement
        ]);
    }

    /**
     * Met à jour le stage d'un étudiant.
     */
    public function updateStage(string $numEtu, array $data): void
    {
        $stageExistant = $this->infoStageModel->getStageInfo($numEtu);

        if ($stageExistant && !empty($stageExistant->id_info_stage)) {
            // Mettre à jour le stage existant
            $sql = 'UPDATE informations_stage SET ';
            $fields = [];
            $params = ['id' => $stageExistant->id_info_stage];

            if (isset($data['entreprise'])) {
                $fields[] = 'entreprise_stage = :entreprise';
                $params['entreprise'] = $data['entreprise'];
            }
            if (isset($data['date_debut'])) {
                $fields[] = 'date_debut_stage = :date_debut';
                $params['date_debut'] = $data['date_debut'];
            }
            if (isset($data['date_fin'])) {
                $fields[] = 'date_fin_stage = :date_fin';
                $params['date_fin'] = $data['date_fin'];
            }
            if (isset($data['theme_stage'])) {
                $fields[] = 'sujet_stage = :theme';
                $params['theme'] = $data['theme_stage'];
            }

            if (!empty($fields)) {
                $sql .= implode(', ', $fields) . ' WHERE id_info_stage = :id';
                $this->pdo->prepare($sql)->execute($params);
            }
        } else {
            // Créer un nouveau stage
            $stmt = $this->pdo->prepare('
                INSERT INTO informations_stage
                    (num_etu, entreprise_stage, date_debut_stage, date_fin_stage, sujet_stage)
                VALUES
                    (:etu, :entreprise, :date_debut, :date_fin, :theme)
            ');
            $stmt->execute([
                'etu' => $numEtu,
                'entreprise' => $data['entreprise'] ?? '',
                'date_debut' => $data['date_debut'] ?? null,
                'date_fin' => $data['date_fin'] ?? null,
                'theme' => $data['theme_stage'] ?? '',
            ]);
        }
    }

    /**
     * Met à jour le statut de candidature.
     */
    public function updateCandidature(string $numEtu, string $statut): void
    {
        // Vérifier si une candidature existe
        $stmt = $this->pdo->prepare('
            SELECT id_candidature FROM candidature_soutenance
            WHERE num_etu = :etu ORDER BY id_candidature DESC LIMIT 1
        ');
        $stmt->execute(['etu' => $numEtu]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmt = $this->pdo->prepare('
                UPDATE candidature_soutenance
                SET statut_candidature = :statut, date_traitement = NOW()
                WHERE id_candidature = :id
            ');
            $stmt->execute(['statut' => $statut, 'id' => $existing['id_candidature']]);
        } else {
            $stmt = $this->pdo->prepare('
                INSERT INTO candidature_soutenance
                    (num_etu, date_candidature, statut_candidature, date_traitement)
                VALUES (:etu, NOW(), :statut, NOW())
            ');
            $stmt->execute(['etu' => $numEtu, 'statut' => $statut]);
        }
    }

    /**
     * Sauvegarde les notes M1/M2.
     */
    public function saveNotesM1M2(string $numEtu, float $moyenneM1, float $moyenneM2): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO notes (num_etu, moyenne_M1, moyenne_M2)
            VALUES (:etu, :m1, :m2)
            ON DUPLICATE KEY UPDATE
                moyenne_M1 = VALUES(moyenne_M1),
                moyenne_M2 = VALUES(moyenne_M2)
        ');
        $stmt->execute(['etu' => $numEtu, 'm1' => $moyenneM1, 'm2' => $moyenneM2]);
    }
}
