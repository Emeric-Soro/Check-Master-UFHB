<?php

declare(strict_types=1);

namespace App\Utils;

use App\Support\Database;
use PDO;

/**
 * Utilitaire pour l'extraction des données du planning de soutenance.
 * Remplace les anciens repositories non adaptés à la base de données actuelle.
 *
 * Tables réelles du schéma (base.txt) :
 * - programmer_soutenance (num_soutenance PK, num_etud, theme_soutenance, id_domaine, id_session, id_salle, date_soutenance, heure_soutenance)
 * - etudiants (num_carte_etud PK, nom_etu, prenom_etu, email_etu, ...)
 * - enseignants (id_enseignant varchar PK, nom_enseignant, prenom_enseignant, ...)
 * - enseignant_jury (num_soutenance, id_enseignant, id_qualite_jury, date_composer_jury)
 * - qualite_jury (id_role_jury PK, code_qltjury, lib_role)
 * - rapport_etudiants (id_rapport PK, num_etu, theme_rapport, chemin_fichier, statut_rapport, ...)
 * - compte_rendu (id_CR PK, num_etu, nom_CR, contenu_CR, chemin_fichier_pdf, date_CR)
 * - compte_rendu_rapport (id_CR FK, id_rapport FK — composite PK, SEULEMENT 2 colonnes)
 * - affecter (id_enseignant + id_rapport composite PK, role enum('encadrant','directeur'), id_jury)
 * - valider (id_enseignant + id_rapport composite PK, date_validation, commentaire_validation, decision_validation)
 * - session (id_session PK, lib_session)
 * - salles (id_salle PK, lib_salle)
 * - notes (id PK, num_etu, id_annee_acad, moyenne_M1, moyenne_M2, ...)
 * - candidature_soutenance (id_candidature PK, num_etu, date_candidature, statut_candidature, ...)
 * - informations_stage (id_info_stage PK, num_etu, id_entreprise, date_debut_stage, date_fin_stage, sujet_stage, id_maitre_stage)
 * - entreprises (id_entreprise PK, lib_long_entreprise, lib_court_en, ...)
 * - maitre_de_stage (id_maitre_stage PK, Nom, prenom, email, telephone, id_entreprise, id_fonction)
 * - rendre (id_CR, id_enseignant, date_env) — qui a reçu un compte-rendu
 * - evaluer (id_critere FK, num_etudiant FK — structure partielle connue)
 * - critere_evaluation (id_critere PK, code_critere, lib_critere)
 * - bareme_critere (id_annee_acad + id_critere composite PK, bareme)
 * - niveau_etude (id_niv_etude PK, lib_niv_etude, id_enseignant, ...)
 * - utilisateur (id_utilisateur PK, nom_utilisateur, ...)
 */
class PlanningDataUtils
{
    public function __construct(private readonly Database $db)
    {
    }

    // =========================================================================
    //  Méthodes existantes (planning)
    // =========================================================================

    /**
     * Récupère les soutenances en fonction des critères de filtrage.
     *
     * @param int|null $sessionId ID de la session
     * @param string|null $dateFrom Date de début (YYYY-MM-DD)
     * @param string|null $dateTo Date de fin (YYYY-MM-DD)
     * @return array
     */
    public function getSoutenancesForPlanning(?int $sessionId, ?string $dateFrom, ?string $dateTo): array
    {
        // On récupère les soutenances depuis `programmer_soutenance`
        // On joint `etudiants`, `salles`, `session`, et les tables de stage/entreprise
        $sql = 'SELECT ps.*, 
                       ps.heure_soutenance AS heure_debut,
                       ps.theme_soutenance AS theme_soutenance,
                       e.nom_etu AS nom_etudiant, 
                       e.prenom_etu AS prenom_etudiant, 
                       e.num_carte_etud AS matricule_etudiant, 
                       e.email_etu AS email_etudiant,
                       sa.lib_salle,
                       s.lib_session,
                       COALESCE(ent.lib_long_entreprise, "N/A") AS entreprise_accueil
                FROM programmer_soutenance ps
                INNER JOIN etudiants e ON e.num_carte_etud = ps.num_etud
                LEFT JOIN salles sa ON sa.id_salle = ps.id_salle
                LEFT JOIN session s ON s.id_session = ps.id_session
                LEFT JOIN informations_stage ist ON ist.num_etu = ps.num_etud
                LEFT JOIN entreprises ent ON ent.id_entreprise = ist.id_entreprise
                WHERE 1=1';

        $params = [];
        if ($sessionId !== null) {
            $sql .= ' AND ps.id_session = :sessionId';
            $params['sessionId'] = $sessionId;
        } elseif ($dateFrom !== null && $dateTo !== null) {
            $sql .= ' AND ps.date_soutenance BETWEEN :from AND :to';
            $params['from'] = $dateFrom;
            $params['to'] = $dateTo;
        }

        $sql .= ' ORDER BY ps.date_soutenance ASC, ps.heure_soutenance ASC';

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Récupère les détails du jury pour une soutenance spécifique.
     *
     * @param string $numSoutenance Identifiant de la soutenance
     * @return array
     */
    public function getJuryDetails(string $numSoutenance): array
    {
        $sql = 'SELECT ej.id_enseignant, ens.nom_enseignant, ens.prenom_enseignant,
                       qj.lib_role, qj.code_qltjury
                FROM enseignant_jury ej
                INNER JOIN enseignants ens ON ens.id_enseignant = ej.id_enseignant
                INNER JOIN qualite_jury qj ON qj.id_role_jury = ej.id_qualite_jury
                WHERE ej.num_soutenance = :num_soutenance';
                
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute(['num_soutenance' => $numSoutenance]);
        $membres = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $juryDetails = [];
        if (is_array($membres)) {
            foreach ($membres as $m) {
                $role = strtoupper((string)($m['lib_role'] ?? ''));
                // On met "M." par défaut, mais cela pourrait être "Mme." selon le genre si la base le permettait
                $identite = trim((string)($m['prenom_enseignant'] ?? '') . ' ' . (string)($m['nom_enseignant'] ?? ''));
                
                if (str_contains($role, 'PRÉSIDENT') || str_contains($role, 'PRESIDENT')) {
                    $juryDetails['president'] = 'M. ' . $identite;
                } elseif (str_contains($role, 'EXAMINATEUR')) {
                    if (!isset($juryDetails['examinateur'])) {
                        $juryDetails['examinateur'] = [];
                    }
                    $juryDetails['examinateur'][] = 'M. ' . $identite;
                } elseif (str_contains($role, 'MAÎTRE DE STAGE') || str_contains($role, 'MAITRE DE STAGE')) {
                    $juryDetails['maitre_stage'] = 'M. ' . $identite;
                } elseif (str_contains($role, 'DIRECTEUR')) {
                    $juryDetails['directeur'] = 'M. ' . $identite;
                } elseif (str_contains($role, 'ENCADRANT') || str_contains($role, 'ENCADREUR')) {
                    $juryDetails['encadreur'] = 'M. ' . $identite;
                }
            }
        }
        
        return $juryDetails;
    }

    // =========================================================================
    //  Méthodes pour PvCommissionGeneratorService
    // =========================================================================

    /**
     * Récupère un compte-rendu par son identifiant.
     * Table: compte_rendu (id_CR, num_etu, nom_CR, contenu_CR, chemin_fichier_pdf, date_CR)
     *
     * @return array|null
     */
    public function getCompteRenduById(int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT cr.id_CR, cr.num_etu, cr.nom_CR, cr.contenu_CR, cr.chemin_fichier_pdf, cr.date_CR
             FROM compte_rendu cr
             WHERE cr.id_CR = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Récupère une session par son identifiant.
     * Table: session (id_session, lib_session) — SEULEMENT 2 colonnes.
     *
     * @return array|null
     */
    public function getSessionById(int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT s.id_session, s.lib_session
             FROM session s
             WHERE s.id_session = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Récupère les rapports associés à un compte-rendu via la table pivot.
     * Table pivot: compte_rendu_rapport (id_CR, id_rapport) — SEULEMENT 2 colonnes.
     * Jointures: rapport_etudiants, etudiants, affecter (role='directeur' / role='encadrant'), enseignants, valider
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRapportsForCompteRendu(int $idCR): array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT r.id_rapport, r.num_etu, r.theme_rapport, r.statut_rapport,
                    r.chemin_fichier, r.date_redaction_rapport,
                    e.num_carte_etud, e.nom_etu AS nom_etudiant, e.prenom_etu AS prenom_etudiant,
                    e.email_etu AS email_etudiant,
                    CONCAT(dir_ens.prenom_enseignant, " ", dir_ens.nom_enseignant) AS directeur_nom,
                    dir_ens.mail_enseignant AS directeur_email,
                    CONCAT(enc_ens.prenom_enseignant, " ", enc_ens.nom_enseignant) AS encadreur_nom,
                    enc_ens.mail_enseignant AS encadreur_email,
                    v.decision_validation AS decision_evaluation,
                    v.commentaire_validation AS remarque_specifique
             FROM compte_rendu_rapport crr
             INNER JOIN rapport_etudiants r ON r.id_rapport = crr.id_rapport
             INNER JOIN etudiants e ON e.num_carte_etud = r.num_etu
             LEFT JOIN affecter dir_aff ON dir_aff.id_rapport = r.id_rapport AND dir_aff.role = "directeur"
             LEFT JOIN enseignants dir_ens ON dir_ens.id_enseignant = dir_aff.id_enseignant
             LEFT JOIN affecter enc_aff ON enc_aff.id_rapport = r.id_rapport AND enc_aff.role = "encadrant"
             LEFT JOIN enseignants enc_ens ON enc_ens.id_enseignant = enc_aff.id_enseignant
             LEFT JOIN valider v ON v.id_rapport = r.id_rapport
             WHERE crr.id_CR = :id_cr
             ORDER BY e.nom_etu ASC'
        );
        $stmt->execute(['id_cr' => $idCR]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Récupère les enseignants ayant reçu un compte-rendu (table rendre).
     * Sert de « membres de la commission » puisque la table membre_commission n'existe pas.
     * Table: rendre (id_CR, id_enseignant, date_env) → enseignants
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCommissionMembers(int $idCR): array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT ens.id_enseignant, ens.nom_enseignant, ens.prenom_enseignant,
                    ens.mail_enseignant, r.date_env,
                    CONCAT(ens.prenom_enseignant, " ", ens.nom_enseignant) AS nom_complet
             FROM rendre r
             INNER JOIN enseignants ens ON ens.id_enseignant = r.id_enseignant
             WHERE r.id_CR = :id_cr
             ORDER BY ens.nom_enseignant ASC'
        );
        $stmt->execute(['id_cr' => $idCR]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Met à jour le chemin du fichier PDF d'un compte-rendu.
     * Table: compte_rendu (chemin_fichier_pdf)
     */
    public function finaliserCompteRendu(int $idCR, string $cheminPdf): void
    {
        $stmt = $this->db->pdo()->prepare(
            'UPDATE compte_rendu SET chemin_fichier_pdf = :chemin WHERE id_CR = :id'
        );
        $stmt->execute(['chemin' => $cheminPdf, 'id' => $idCR]);
    }

    // =========================================================================
    //  Méthodes pour PvFinalGeneratorService
    // =========================================================================

    /**
     * Récupère une soutenance avec tous ses détails.
     * Jointures: programmer_soutenance → etudiants, session, salles, niveau_etude
     *
     * @return array|null
     */
    public function getSoutenanceWithFullDetails(string $numSoutenance): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT ps.num_soutenance, ps.num_etud, ps.theme_soutenance,
                    ps.date_soutenance, ps.heure_soutenance,
                    ps.id_session, ps.id_salle, ps.id_domaine,
                    e.num_carte_etud AS matricule_etudiant,
                    e.nom_etu AS nom_etudiant,
                    e.prenom_etu AS prenom_etudiant,
                    e.email_etu AS email_etudiant,
                    i.id_niv_etude,
                    s.lib_session,
                    sa.lib_salle,
                    niv.lib_niv_etude AS libelle_niveau
             FROM programmer_soutenance ps
             INNER JOIN etudiants e ON e.num_carte_etud = ps.num_etud
             LEFT JOIN inscriptions i ON (i.num_carte_etud, i.id_annee_acad, i.num_versement) = (
                 SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement FROM inscriptions i2
                 WHERE i2.num_carte_etud = e.num_carte_etud
                 ORDER BY i2.date_inscription DESC, i2.id_annee_acad DESC, i2.num_versement DESC LIMIT 1
             )
             LEFT JOIN session s ON s.id_session = ps.id_session
             LEFT JOIN salles sa ON sa.id_salle = ps.id_salle
             LEFT JOIN niveau_etude niv ON niv.id_niv_etude = i.id_niv_etude
             WHERE ps.num_soutenance = :num_soutenance'
        );
        $stmt->execute(['num_soutenance' => $numSoutenance]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Récupère les membres du jury pour une soutenance, au format attendu par PvFinalGeneratorService.
     * Retourne un tableau indexé d'objets avec nom_utilisateur, prenom, role_jury, fonction.
     *
     * @return array<int, array<string, string>>
     */
    public function getJuryMembersForSoutenance(string $numSoutenance): array
    {
        $sql = 'SELECT ens.nom_enseignant AS nom_utilisateur, 
                       ens.prenom_enseignant AS prenom,
                       qj.lib_role AS role_jury,
                       qj.lib_role AS fonction
                FROM enseignant_jury ej
                INNER JOIN enseignants ens ON ens.id_enseignant = ej.id_enseignant
                INNER JOIN qualite_jury qj ON qj.id_role_jury = ej.id_qualite_jury
                WHERE ej.num_soutenance = :num_soutenance
                ORDER BY qj.id_role_jury ASC';

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute(['num_soutenance' => $numSoutenance]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    public function getPvDataForSoutenance(string $numSoutenance): ?array
    {
        $details = $this->getSoutenanceWithFullDetails($numSoutenance);
        if (!$details) {
            return null;
        }

        $m1m2 = $this->getMoyennesAcademiques($details['num_etud'] ?? '');

        return [
            'id_pv' => crc32($numSoutenance),
            'decision_jury' => 'ADMIS',
            'moyenne_m1' => $m1m2['moyenne_M1'] ?? 0.0,
            'moyenne_s1_m2' => $m1m2['moyenne_M2'] ?? 0.0,
            'note_finale' => null,
            'note_memoire' => null,
            'numero_pv' => $this->generateReference('PVF'),
            'date_finalisation' => $details['date_soutenance'] ?? date('Y-m-d'),
            'date_creation' => $details['date_soutenance'] ?? date('Y-m-d'),
            'libelle_mention' => 'PASSABLE',
            'chemin_fichier_pdf' => null
        ];
    }

    /**
     * Récupère les notes d'évaluation de soutenance pour un étudiant.
     * Tables: evaluer (id_critere, num_etudiant + colonnes inconnues) → critere_evaluation, bareme_critere
     * Note: La structure exacte de `evaluer` n'est pas dans base.txt, on connaît seulement les FK.
     * On suppose: evaluer(id_critere, num_etudiant, note, id_annee_acad)
     *
     * @return array<int, array<string, mixed>>
     */
    public function getNotesSoutenance(string $numEtudiant, ?int $idAnneeAcad = null): array
    {
        $sql = 'SELECT ev.id_critere, ev.num_etudiant,
                       ce.code_critere, ce.lib_critere AS libelle_critere,
                       ev.note,
                       COALESCE(bc.bareme, 20) AS bareme
                FROM evaluer ev
                INNER JOIN critere_evaluation ce ON ce.id_critere = ev.id_critere
                LEFT JOIN bareme_critere bc ON bc.id_critere = ev.id_critere';

        $params = ['num_etu' => $numEtudiant];

        if ($idAnneeAcad !== null) {
            $sql .= ' AND bc.id_annee_acad = :id_annee_acad';
            $params['id_annee_acad'] = $idAnneeAcad;
        }

        $sql .= ' WHERE ev.num_etudiant = :num_etu ORDER BY ce.id_critere ASC';

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Récupère les moyennes académiques d'un étudiant.
     * Table: notes (id, num_etu, id_annee_acad, moyenne_M1, moyenne_M2, ...)
     * NB: Il n'y a PAS de colonne `moyenne_S1_M2`, seulement `moyenne_M2`.
     *
     * @return array|null ['moyenne_M1' => float, 'moyenne_M2' => float]
     */
    public function getMoyennesAcademiques(string $numEtu): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT n.moyenne_M1, n.moyenne_M2
             FROM notes n
             WHERE n.num_etu = :num_etu
             ORDER BY n.id_annee_acad DESC
             LIMIT 1'
        );
        $stmt->execute(['num_etu' => $numEtu]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    // =========================================================================
    //  Méthodes pour RapportPdfGeneratorService
    // =========================================================================

    /**
     * Récupère un rapport avec ses détails complets.
     * Table: rapport_etudiants → etudiants
     * NB: rapport_etudiants n'a PAS de `titre_rapport` (utiliser `theme_rapport`),
     *     PAS de `contenu_html` (utiliser `chemin_fichier`),
     *     PAS de `reference_document`.
     *
     * @return array|null
     */
    public function getRapportWithDetails(int $rapportId): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT r.id_rapport, r.num_etu, r.date_redaction_rapport,
                    r.theme_rapport, r.chemin_fichier, r.statut_rapport,
                    r.date_modification, r.taille_fichier, r.version,
                    e.num_carte_etud AS matricule_etudiant,
                    e.nom_etu AS nom_etudiant,
                    e.prenom_etu AS prenom_etudiant,
                    e.email_etu AS email_etudiant,
                    i.id_annee_acad,
                    CONCAT(YEAR(aa.date_deb), "-", YEAR(aa.date_fin)) AS libelle_annee,
                    niv.lib_niv_etude AS libelle_niveau
             FROM rapport_etudiants r
             INNER JOIN etudiants e ON e.num_carte_etud = r.num_etu
             LEFT JOIN inscriptions i ON (i.num_carte_etud, i.id_annee_acad, i.num_versement) = (
                 SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement FROM inscriptions i2 
                  WHERE i2.num_carte_etud = e.num_carte_etud 
                  ORDER BY i2.date_inscription DESC, i2.id_annee_acad DESC, i2.num_versement DESC LIMIT 1
              )
             LEFT JOIN annee_academique aa ON aa.id_annee_acad = i.id_annee_acad
             LEFT JOIN niveau_etude niv ON niv.id_niv_etude = i.id_niv_etude
             WHERE r.id_rapport = :id_rapport'
        );
        $stmt->execute(['id_rapport' => $rapportId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Récupère un étudiant par son numéro de carte.
     * Table: etudiants (num_carte_etud PK)
     *
     * @return array|null
     */
    public function getEtudiantByNumCarte(string $numCarte): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT e.num_carte_etud, e.num_ident_etud, e.nom_etu, e.prenom_etu,
                    e.email_etu, e.date_naiss_etu, e.id_genre, e.promotion_etu,
                    i.id_niv_etude, i.id_annee_acad,
                    niv.lib_niv_etude AS libelle_niveau
             FROM etudiants e
             LEFT JOIN inscriptions i ON (i.num_carte_etud, i.id_annee_acad, i.num_versement) = (
                 SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement FROM inscriptions i2 
                 WHERE i2.num_carte_etud = e.num_carte_etud 
                 ORDER BY i2.date_inscription DESC, i2.id_annee_acad DESC, i2.num_versement DESC LIMIT 1
             )
             LEFT JOIN niveau_etude niv ON niv.id_niv_etude = i.id_niv_etude
             WHERE e.num_carte_etud = :num_carte'
        );
        $stmt->execute(['num_carte' => $numCarte]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Récupère la candidature d'un étudiant.
     * Table: candidature_soutenance (id_candidature, num_etu, date_candidature, statut_candidature, ...)
     *
     * @return array|null
     */
    public function getCandidatureByEtudiant(string $numEtu): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT cs.id_candidature, cs.num_etu, cs.date_candidature,
                    cs.statut_candidature, cs.date_traitement,
                    cs.commentaire_admin
             FROM candidature_soutenance cs
             WHERE cs.num_etu = :num_etu
             ORDER BY cs.date_candidature DESC
             LIMIT 1'
        );
        $stmt->execute(['num_etu' => $numEtu]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Récupère les informations de stage d'un étudiant.
     * Table: informations_stage → entreprises, maitre_de_stage
     * NB: La liaison est via num_etu, PAS via id_candidature.
     *
     * @return array|null
     */
    public function getInformationsStage(string $numEtu): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT ist.id_info_stage, ist.num_etu, ist.date_debut_stage, ist.date_fin_stage,
                    ist.sujet_stage, ist.id_maitre_stage,
                    ent.id_entreprise, ent.lib_long_entreprise AS nom_entreprise,
                    ent.lib_court_en AS sigle_entreprise,
                    ent.email AS email_entreprise, ent.telephone AS tel_entreprise,
                    ms.Nom AS nom_maitre_stage, ms.prenom AS prenom_maitre_stage,
                    ms.email AS email_maitre_stage, ms.telephone AS tel_maitre_stage
             FROM informations_stage ist
             LEFT JOIN entreprises ent ON ent.id_entreprise = ist.id_entreprise
             LEFT JOIN maitre_de_stage ms ON ms.id_maitre_stage = ist.id_maitre_stage
             WHERE ist.num_etu = :num_etu
             ORDER BY ist.date_debut_stage DESC
             LIMIT 1'
        );
        $stmt->execute(['num_etu' => $numEtu]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Met à jour un rapport (chemin fichier PDF, taille, etc.).
     * Table: rapport_etudiants
     *
     * @param array<string, mixed> $data Colonnes à mettre à jour
     */
    public function updateRapport(int $rapportId, array $data): void
    {
        if (empty($data)) {
            return;
        }

        // Seules les colonnes réelles de rapport_etudiants
        $allowedColumns = [
            'chemin_fichier', 'statut_rapport', 'date_modification',
            'taille_fichier', 'version',
        ];

        $sets = [];
        $params = ['id' => $rapportId];

        foreach ($data as $col => $val) {
            // Mapper les anciennes clés vers les bonnes colonnes
            $realCol = match ($col) {
                'chemin_fichier_pdf' => 'chemin_fichier',
                default => $col,
            };

            if (!in_array($realCol, $allowedColumns, true)) {
                continue;
            }

            $sets[] = "{$realCol} = :{$realCol}";
            $params[$realCol] = $val;
        }

        if (empty($sets)) {
            return;
        }

        $sql = 'UPDATE rapport_etudiants SET ' . implode(', ', $sets) . ' WHERE id_rapport = :id';
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
    }

    // =========================================================================
    //  Méthodes utilitaires communes
    // =========================================================================

    /**
     * Génère une référence unique de document au format TYPE-YYYY-NNNNN.
     * Puisqu'il n'y a PAS de table `document_genere` dans la base,
     * on génère un identifiant basé sur le timestamp et un numéro aléatoire.
     */
    public function generateReference(string $type): string
    {
        $year = date('Y');
        // Utiliser un timestamp-based unique pour éviter les collisions
        $seq = (int) (microtime(true) * 100) % 99999;
        $num = str_pad((string) max($seq, 1), 5, '0', STR_PAD_LEFT);

        return strtoupper($type) . '-' . $year . '-' . $num;
    }

    /**
     * Enregistrement factice d'un document généré.
     * La table `document_genere` N'EXISTE PAS dans le schéma actuel (base.txt).
     * Cette méthode fait un no-op mais retourne un ID fictif pour maintenir la compatibilité.
     *
     * @param array<string, mixed> $data Données du document
     * @return int ID fictif du document (0)
     */
    public function saveDocumentRecord(array $data): int
    {
        // Pas de table document_genere dans le schéma.
        // On log les informations pour traçabilité mais on ne persiste rien.
        error_log(sprintf(
            '[PlanningDataUtils] Document généré (non persisté): ref=%s, type=%s, fichier=%s',
            (string) ($data['reference_document'] ?? '?'),
            (string) ($data['type_document'] ?? '?'),
            (string) ($data['chemin_fichier'] ?? '?')
        ));

        return 0;
    }
}
