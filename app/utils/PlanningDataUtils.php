<?php

declare(strict_types=1);

namespace App\Utils;

require_once __DIR__ . '/../Services/Document/DocumentStorageService.php';

use App\Services\Document\DocumentStorageService;
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
 * - qualite_jury (id_role_jury PK, lib_role)
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
    /** @var array<string, bool> */
    private array $tableExistsCache = [];

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
                       COALESCE(NULLIF(e.num_carte_etud, \'\'), NULLIF(e.num_ident_etud, \'\'), ps.num_etud) AS matricule_etudiant, 
                       e.email_etu AS email_etudiant,
                  (SELECT CONCAT(ens_p.prenom_enseignant, CHAR(32), ens_p.nom_enseignant)
                   FROM enseignant_jury ej_p
                   LEFT JOIN enseignants ens_p ON ens_p.id_enseignant = ej_p.id_enseignant
                   WHERE ej_p.num_soutenance = ps.num_soutenance AND ej_p.id_qualite_jury = "PJ"
                   LIMIT 1) AS president_jury_nom,
                  (SELECT COALESCE(
                              NULLIF(TRIM(CONCAT(COALESCE(ens_m.prenom_enseignant, \'\'), CHAR(32), COALESCE(ens_m.nom_enseignant, \'\'))), \'\'),
                              NULLIF(TRIM(CONCAT(COALESCE(ms_j.prenom, \'\'), CHAR(32), COALESCE(ms_j.Nom, \'\'))), \'\')
                          )
                   FROM enseignant_jury ej_m
                   LEFT JOIN enseignants ens_m ON ens_m.id_enseignant = ej_m.id_enseignant
                   LEFT JOIN maitre_de_stage ms_j ON CAST(ms_j.id_maitre_stage AS CHAR) = CAST(ej_m.id_enseignant AS CHAR)
                   WHERE ej_m.num_soutenance = ps.num_soutenance AND ej_m.id_qualite_jury = "MS"
                   LIMIT 1) AS maitre_stage_jury_nom,
                       sa.lib_salle,
                       s.lib_session,
                       NULLIF(TRIM(CONCAT(COALESCE(ms.prenom, \'\'), CHAR(32), COALESCE(ms.Nom, \'\'))), \'\') AS maitre_stage_nom,
                       COALESCE(ent.lib_long_entreprise, ent_ms.lib_long_entreprise, "N/A") AS entreprise_accueil
                FROM programmer_soutenance ps
                LEFT JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
                LEFT JOIN salles sa ON sa.id_salle = ps.id_salle
                LEFT JOIN session s ON s.id_session = ps.id_session
                LEFT JOIN informations_stage ist ON ist.num_etu IN (
                    ps.num_etud,
                    COALESCE(NULLIF(e.num_carte_etud, \'\'), ps.num_etud),
                    COALESCE(NULLIF(e.num_ident_etud, \'\'), ps.num_etud)
                )
                  LEFT JOIN maitre_de_stage ms ON ms.id_maitre_stage = ist.id_maitre_stage
                LEFT JOIN entreprises ent ON ent.id_entreprise = ist.id_entreprise
                LEFT JOIN entreprises ent_ms ON ent_ms.id_entreprise = ms.id_entreprise
                WHERE 1=1';

        $params = [];
        if ($sessionId !== null) {
            $sql .= ' AND ps.id_session = :sessionId';
            $params['sessionId'] = $sessionId;
        }
        if ($dateFrom !== null) {
            $sql .= ' AND ps.date_soutenance >= :from';
            $params['from'] = $dateFrom;
        }
        if ($dateTo !== null) {
            $sql .= ' AND ps.date_soutenance <= :to';
            $params['to'] = $dateTo;
        }

        $sql .= ' ORDER BY ps.date_soutenance ASC, ps.heure_soutenance ASC';

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Récupère un ensemble ciblé de soutenances par identifiants.
     *
     * @param array<int, string> $soutenanceIds
     * @return array<int, array<string, mixed>>
     */
    public function getSoutenancesByIds(array $soutenanceIds): array
    {
        $ids = array_values(array_filter(array_map(static fn($id): string => trim((string) $id), $soutenanceIds), static fn(string $id): bool => $id !== ''));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = "SELECT ps.*,
                       ps.heure_soutenance AS heure_debut,
                       ps.theme_soutenance AS theme_soutenance,
                       e.nom_etu AS nom_etudiant,
                       e.prenom_etu AS prenom_etudiant,
                       COALESCE(NULLIF(e.num_carte_etud, ''), NULLIF(e.num_ident_etud, ''), ps.num_etud) AS matricule_etudiant,
                       e.email_etu AS email_etudiant,
                  (SELECT CONCAT(ens_p.prenom_enseignant, CHAR(32), ens_p.nom_enseignant)
                   FROM enseignant_jury ej_p
                   LEFT JOIN enseignants ens_p ON ens_p.id_enseignant = ej_p.id_enseignant
                   WHERE ej_p.num_soutenance = ps.num_soutenance AND ej_p.id_qualite_jury = 'PJ'
                   LIMIT 1) AS president_jury_nom,
                  (SELECT COALESCE(
                              NULLIF(TRIM(CONCAT(COALESCE(ens_m.prenom_enseignant, ''), CHAR(32), COALESCE(ens_m.nom_enseignant, ''))), ''),
                              NULLIF(TRIM(CONCAT(COALESCE(ms_j.prenom, ''), CHAR(32), COALESCE(ms_j.Nom, ''))), '')
                          )
                   FROM enseignant_jury ej_m
                   LEFT JOIN enseignants ens_m ON ens_m.id_enseignant = ej_m.id_enseignant
                   LEFT JOIN maitre_de_stage ms_j ON CAST(ms_j.id_maitre_stage AS CHAR) = CAST(ej_m.id_enseignant AS CHAR)
                   WHERE ej_m.num_soutenance = ps.num_soutenance AND ej_m.id_qualite_jury = 'MS'
                   LIMIT 1) AS maitre_stage_jury_nom,
                       sa.lib_salle,
                       s.lib_session,
                       NULLIF(TRIM(CONCAT(COALESCE(ms.prenom, ''), CHAR(32), COALESCE(ms.Nom, ''))), '') AS maitre_stage_nom,
                       COALESCE(ent.lib_long_entreprise, ent_ms.lib_long_entreprise, 'N/A') AS entreprise_accueil
                FROM programmer_soutenance ps
                LEFT JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
                LEFT JOIN salles sa ON sa.id_salle = ps.id_salle
                LEFT JOIN session s ON s.id_session = ps.id_session
                LEFT JOIN informations_stage ist ON ist.num_etu IN (
                    ps.num_etud,
                    COALESCE(NULLIF(e.num_carte_etud, ''), ps.num_etud),
                    COALESCE(NULLIF(e.num_ident_etud, ''), ps.num_etud)
                )
                  LEFT JOIN maitre_de_stage ms ON ms.id_maitre_stage = ist.id_maitre_stage
                LEFT JOIN entreprises ent ON ent.id_entreprise = ist.id_entreprise
                LEFT JOIN entreprises ent_ms ON ent_ms.id_entreprise = ms.id_entreprise
                WHERE ps.num_soutenance IN ({$placeholders})
                ORDER BY ps.date_soutenance ASC, ps.heure_soutenance ASC";

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($ids);
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
        $juryTable = $this->getJuryTable();
        $rolesTable = $this->getJuryRoleTable();
        if ($juryTable === null || $rolesTable === null) {
            return [];
        }

        $roleKeyMap = $this->getRoleKeyMap();

        $juryRefCol = $this->getJuryRefColumn($juryTable);
        $roleIdCol = $this->getRoleIdColumn($rolesTable);
        $roleLabelCol = $this->getRoleLabelColumn($rolesTable);

        $roleCodeSelect = $this->columnExists($rolesTable, 'code_qltjury')
            ? 'qj.code_qltjury AS code_role,'
            : "qj.{$roleIdCol} AS code_role,";

        $sql = "SELECT ej.id_enseignant,
                       COALESCE(ens.nom_enseignant, ms.Nom) AS nom_personne,
                       COALESCE(ens.prenom_enseignant, ms.prenom) AS prenom_personne,
                       qj.{$roleLabelCol} AS lib_role,
                       {$roleCodeSelect}
                       ej.id_qualite_jury
                FROM {$juryTable} ej
                LEFT JOIN enseignants ens ON ens.id_enseignant = ej.id_enseignant
                LEFT JOIN maitre_de_stage ms ON CAST(ms.id_maitre_stage AS CHAR) = CAST(ej.id_enseignant AS CHAR)
                INNER JOIN {$rolesTable} qj ON qj.{$roleIdCol} = ej.id_qualite_jury
                WHERE ej.{$juryRefCol} = :jury_ref";

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute(['jury_ref' => $numSoutenance]);
        $membres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $juryDetails = [];
        if (is_array($membres)) {
            foreach ($membres as $m) {
                $roleId = trim((string) ($m['id_qualite_jury'] ?? ''));
                $roleKey = $roleKeyMap[$roleId] ?? '';
                $role = $this->normalizeRoleLabel(
                    (string) ($m['lib_role'] ?? '') . ' ' . (string) ($m['code_role'] ?? '')
                );
                // On met "M." par défaut, mais cela pourrait être "Mme." selon le genre si la base le permettait
                $identite = trim((string) ($m['prenom_personne'] ?? '') . ' ' . (string) ($m['nom_personne'] ?? ''));
                if ($identite === '') {
                    continue;
                }

                if ($roleKey === 'president' || str_contains($role, 'PRESIDENT') || str_contains($role, 'PDT')) {
                    $juryDetails['president'] = 'M. ' . $identite;
                } elseif ($roleKey === 'examinateur' || str_contains($role, 'EXAMINATEUR')) {
                    if (!isset($juryDetails['examinateur'])) {
                        $juryDetails['examinateur'] = [];
                    }
                    $juryDetails['examinateur'][] = 'M. ' . $identite;
                } elseif ($roleKey === 'maitre_stage' || str_contains($role, 'MAITRE') || str_contains($role, 'STAGE') || str_contains($role, 'MDS')) {
                    $juryDetails['maitre_stage'] = 'M. ' . $identite;
                } elseif ($roleKey === 'directeur' || str_contains($role, 'DIRECTEUR')) {
                    $juryDetails['directeur'] = 'M. ' . $identite;
                } elseif ($roleKey === 'encadreur' || str_contains($role, 'ENCADR')) {
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
             INNER JOIN etudiants e ON (e.num_carte_etud = r.num_etu OR e.num_ident_etud = r.num_etu)
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
        $latestInscriptionSql = $this->getLatestInscriptionSubquery();
        $stmt = $this->db->pdo()->prepare(
            'SELECT ps.num_soutenance, ps.num_etud, ps.theme_soutenance,
                    ps.date_soutenance, ps.heure_soutenance,
                    ps.id_session, ps.id_salle, ps.id_domaine,
                    e.num_carte_etud AS matricule_etudiant,
                    e.nom_etu AS nom_etudiant,
                    e.prenom_etu AS prenom_etudiant,
                    e.email_etu AS email_etudiant,
                    i.id_niv_etude,
                    i.id_annee_acad AS inscription_annee_acad,
                    s.lib_session,
                    sa.lib_salle,
                    niv.lib_niv_etude AS libelle_niveau
             FROM programmer_soutenance ps
             LEFT JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
             LEFT JOIN ' . $latestInscriptionSql . ' i
                    ON i.num_carte_etud = COALESCE(NULLIF(e.num_ident_etud, \'\'), NULLIF(e.num_carte_etud, \'\'), ps.num_etud)
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
        $juryTable = $this->getJuryTable();
        $rolesTable = $this->getJuryRoleTable();
        if ($juryTable === null || $rolesTable === null) {
            return [];
        }

        $juryRefCol = $this->getJuryRefColumn($juryTable);
        $roleIdCol = $this->getRoleIdColumn($rolesTable);
        $roleLabelCol = $this->getRoleLabelColumn($rolesTable);

        $roleCodeSelect = $this->columnExists($rolesTable, 'code_qltjury')
            ? 'qj.code_qltjury AS code_role,'
            : "qj.{$roleIdCol} AS code_role,";

        $sql = "SELECT COALESCE(ens.nom_enseignant, ms.Nom) AS nom_utilisateur,
                       COALESCE(ens.prenom_enseignant, ms.prenom) AS prenom,
                       qj.{$roleLabelCol} AS role_jury,
                       qj.{$roleLabelCol} AS fonction,
                       {$roleCodeSelect}
                       ej.id_qualite_jury
                FROM {$juryTable} ej
                LEFT JOIN enseignants ens ON ens.id_enseignant = ej.id_enseignant
                LEFT JOIN maitre_de_stage ms ON CAST(ms.id_maitre_stage AS CHAR) = CAST(ej.id_enseignant AS CHAR)
                INNER JOIN {$rolesTable} qj ON qj.{$roleIdCol} = ej.id_qualite_jury
                WHERE ej.{$juryRefCol} = :jury_ref
                ORDER BY qj.{$roleIdCol} ASC";

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute(['jury_ref' => $numSoutenance]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    private function getJuryTable(): ?string
    {
        if ($this->tableExists('enseignant_jury')) {
            return 'enseignant_jury';
        }
        if ($this->tableExists('composer_jury')) {
            return 'composer_jury';
        }

        return null;
    }

    private function getJuryRoleTable(): ?string
    {
        if ($this->tableExists('qualite_jury')) {
            return 'qualite_jury';
        }
        if ($this->tableExists('roles_jury')) {
            return 'roles_jury';
        }

        return null;
    }

    private function getJuryRefColumn(string $juryTable): string
    {
        return $juryTable === 'composer_jury' ? 'num_jury' : 'num_soutenance';
    }

    private function getRoleIdColumn(string $rolesTable): string
    {
        if ($this->columnExists($rolesTable, 'id_role_jury')) {
            return 'id_role_jury';
        }

        return 'id_qualite_jury';
    }

    private function getRoleLabelColumn(string $rolesTable): string
    {
        if ($this->columnExists($rolesTable, 'lib_role')) {
            return 'lib_role';
        }

        return 'lib_qualite_jury';
    }

    private function normalizeRoleLabel(string $label): string
    {
        $normalized = function_exists('mb_strtoupper')
            ? mb_strtoupper($label, 'UTF-8')
            : strtoupper($label);

        if (function_exists('iconv')) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
            if (is_string($ascii) && $ascii !== '') {
                $normalized = $ascii;
            }
        }

        $normalized = str_replace(['_', '-', "'"], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * @return array<string, string> map id_role -> role key
     */
    private function getRoleKeyMap(): array
    {
        $rolesTable = $this->getJuryRoleTable();
        if ($rolesTable === null) {
            return [];
        }

        $roleIdCol = $this->getRoleIdColumn($rolesTable);
        $roleLabelCol = $this->getRoleLabelColumn($rolesTable);
        $roleCodeSelect = $this->columnExists($rolesTable, 'code_qltjury')
            ? 'code_qltjury AS role_code'
            : "{$roleIdCol} AS role_code";

        try {
            $sql = "SELECT {$roleIdCol} AS role_id, {$roleLabelCol} AS role_label, {$roleCodeSelect} FROM {$rolesTable}";
            $rows = $this->db->pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $map = [];
            foreach ((array) $rows as $row) {
                $roleId = trim((string) ($row['role_id'] ?? ''));
                if ($roleId === '') {
                    continue;
                }
                $fullLabel = $this->normalizeRoleLabel(
                    (string) ($row['role_label'] ?? '') . ' ' . (string) ($row['role_code'] ?? '')
                );
                if ($this->roleLabelMatches($fullLabel, ['PRESIDENT', 'PDT'])) {
                    $map[$roleId] = 'president';
                } elseif ($this->roleLabelMatches($fullLabel, ['EXAMINATEUR'])) {
                    $map[$roleId] = 'examinateur';
                } elseif ($this->roleLabelMatches($fullLabel, ['DIRECTEUR'])) {
                    $map[$roleId] = 'directeur';
                } elseif ($this->roleLabelMatches($fullLabel, ['ENCADR', 'ENCADRANT', 'ENCADREUR'])) {
                    $map[$roleId] = 'encadreur';
                } elseif ($this->roleLabelMatches($fullLabel, ['MAITRE', 'STAGE', 'MDS'])) {
                    $map[$roleId] = 'maitre_stage';
                }
            }

            return $map;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array<int, string> $needles
     */
    private function roleLabelMatches(string $normalizedLabel, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($normalizedLabel, $this->normalizeRoleLabel($needle))) {
                return true;
            }
        }

        return false;
    }

    private function tableExists(string $table): bool
    {
        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        try {
            $stmt = $this->db->pdo()->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name = :table_name'
            );
            $stmt->execute(['table_name' => $table]);
            $exists = (int) $stmt->fetchColumn() > 0;
            $this->tableExistsCache[$table] = $exists;
            return $exists;
        } catch (\Throwable) {
            $this->tableExistsCache[$table] = false;
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->db->pdo()->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = :table_name
                   AND column_name = :column_name'
            );
            $stmt->execute([
                'table_name' => $table,
                'column_name' => $column,
            ]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (\Throwable) {
            return false;
        }
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
        return $this->getNotesSoutenanceForJury($numEtudiant, null, $idAnneeAcad);
    }

    /**
     * Récupère les notes d'évaluation de soutenance pour un étudiant et, si fourni, pour un jury précis.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getNotesSoutenanceForJury(string $numEtudiant, ?string $juryRef = null, ?int $idAnneeAcad = null): array
    {
        $codeCritereSelect = $this->columnExists('critere_evaluation', 'code_critere')
            ? 'ce.code_critere'
            : 'ce.id_critere AS code_critere';

        $sql = 'SELECT ev.id_critere, ev.num_etudiant,
                       ' . $codeCritereSelect . ', ce.lib_critere AS libelle_critere,
                       ev.note,
                       COALESCE(
                           bc.bareme,
                           (
                               SELECT b2.bareme
                               FROM bareme_critere b2
                               WHERE b2.id_critere = ev.id_critere
                               ORDER BY b2.id_annee_acad DESC
                               LIMIT 1
                           ),
                           20
                       ) AS bareme
                FROM evaluer ev
                INNER JOIN critere_evaluation ce ON ce.id_critere = ev.id_critere
                LEFT JOIN bareme_critere bc ON bc.id_critere = ev.id_critere';

        $params = ['num_etu' => $numEtudiant];

        if ($idAnneeAcad !== null) {
            $sql .= ' AND bc.id_annee_acad = :id_annee_acad';
            $params['id_annee_acad'] = $idAnneeAcad;
        }

        $sql .= ' WHERE ev.num_etudiant = :num_etu';
        if ($juryRef !== null && $juryRef !== '') {
            $sql .= ' AND ev.num_jury = :jury_ref';
            $params['jury_ref'] = $juryRef;
        }
        $sql .= ' ORDER BY ce.id_critere ASC';

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
        $latestInscriptionSql = $this->getLatestInscriptionSubquery();
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
             INNER JOIN etudiants e ON (e.num_carte_etud = r.num_etu OR e.num_ident_etud = r.num_etu)
             LEFT JOIN ' . $latestInscriptionSql . ' i
                    ON (i.num_carte_etud = e.num_carte_etud OR i.num_carte_etud = e.num_ident_etud)
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
        $latestInscriptionSql = $this->getLatestInscriptionSubquery();
        $genreSelect = $this->columnExists('etudiants', 'genre_etu')
            ? 'e.genre_etu'
            : ($this->columnExists('etudiants', 'id_genre') ? 'e.id_genre AS genre_etu' : 'NULL AS genre_etu');
        $stmt = $this->db->pdo()->prepare(
            'SELECT e.num_carte_etud, e.num_ident_etud, e.nom_etu, e.prenom_etu,
                    e.email_etu, e.date_naiss_etu, ' . $genreSelect . ', e.promotion_etu,
                    i.id_niv_etude, i.id_annee_acad,
                    niv.lib_niv_etude AS libelle_niveau
             FROM etudiants e
             LEFT JOIN ' . $latestInscriptionSql . ' i
                    ON (i.num_carte_etud = e.num_carte_etud OR i.num_carte_etud = e.num_ident_etud)
             LEFT JOIN niveau_etude niv ON niv.id_niv_etude = i.id_niv_etude
             WHERE (e.num_carte_etud = :num_carte OR e.num_ident_etud = :num_carte)'
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
            'chemin_fichier',
            'statut_rapport',
            'date_modification',
            'taille_fichier',
            'version',
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
     * La génération reste indépendante d'une éventuelle persistance.
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
     * Persiste un document généré si la table optionnelle `document_genere` existe.
     * Sinon, conserve un no-op traçable pour rester compatible avec le schéma courant.
     *
     * @param array<string, mixed> $data Données du document
     * @return int ID du document ou 0 si la table n'est pas disponible
     */
    public function saveDocumentRecord(array $data): int
    {
        $storageDocumentId = $this->persistBinaryDocument($data);

        if (!$this->tableExists('document_genere')) {
            error_log(sprintf(
                '[PlanningDataUtils] Document généré (non persisté): ref=%s, type=%s, fichier=%s',
                (string) ($data['reference_document'] ?? '?'),
                (string) ($data['type_document'] ?? '?'),
                (string) ($data['chemin_fichier'] ?? '?')
            ));

            return $storageDocumentId;
        }

        $stmt = $this->db->pdo()->prepare(
            'INSERT INTO document_genere (
                reference,
                type_document,
                id_utilisateur,
                id_source,
                chemin_fichier,
                nom_fichier,
                taille_fichier
             ) VALUES (
                :reference,
                :type_document,
                :id_utilisateur,
                :id_source,
                :chemin_fichier,
                :nom_fichier,
                :taille_fichier
             )'
        );

        $stmt->execute([
            'reference' => (string) ($data['reference_document'] ?? ''),
            'type_document' => (string) ($data['type_document'] ?? ''),
            'id_utilisateur' => max(0, (int) ($data['id_utilisateur_generation'] ?? 0)),
            'id_source' => isset($data['id_source']) ? (string) $data['id_source'] : null,
            'chemin_fichier' => (string) ($data['chemin_fichier'] ?? ''),
            'nom_fichier' => (string) ($data['nom_fichier'] ?? basename((string) ($data['chemin_fichier'] ?? 'document.pdf'))),
            'taille_fichier' => isset($data['taille_fichier']) ? (int) $data['taille_fichier'] : 0,
        ]);

        return (int) $this->db->pdo()->lastInsertId();
    }

    private function getLatestInscriptionSubquery(): string
    {
        return '(
            SELECT i1.num_carte_etud, i1.id_annee_acad, i1.id_niv_etude, i1.num_versement
            FROM inscriptions i1
            INNER JOIN (
                SELECT
                    num_carte_etud,
                    MAX(CONCAT(LPAD(id_annee_acad, 10, "0"), LPAD(num_versement, 10, "0"))) AS latest_key
                FROM inscriptions
                GROUP BY num_carte_etud
            ) latest
                ON latest.num_carte_etud = i1.num_carte_etud
               AND CONCAT(LPAD(i1.id_annee_acad, 10, "0"), LPAD(i1.num_versement, 10, "0")) = latest.latest_key
        )';
    }

    private function persistBinaryDocument(array $data): int
    {
        $path = trim((string) ($data['chemin_fichier'] ?? ''));
        if ($path === '') {
            return 0;
        }

        $mapping = $this->mapLegacyDocumentType((string) ($data['type_document'] ?? ''));
        if ($mapping === null) {
            return 0;
        }

        $storage = new DocumentStorageService($this->db->pdo(), dirname(__DIR__, 2));
        $document = $storage->storeFileFromPath(
            $mapping['type_document'],
            $path,
            $mapping['entite_type'],
            isset($data['id_source']) ? (string) $data['id_source'] : null,
            max(0, (int) ($data['id_utilisateur_generation'] ?? 0)),
            (string) ($data['reference_document'] ?? ''),
            $mapping['sous_type'],
            true
        );

        return is_array($document) ? (int) ($document['id_document'] ?? 0) : 0;
    }

    /**
     * @return array{type_document: string, entite_type: string|null, sous_type: string|null}|null
     */
    private function mapLegacyDocumentType(string $legacyType): ?array
    {
        return match (strtoupper(trim($legacyType))) {
            'RAP' => [
                'type_document' => 'rapport',
                'entite_type' => 'rapport_etudiants',
                'sous_type' => 'generated',
            ],
            'PVC' => [
                'type_document' => 'pv_commission',
                'entite_type' => 'compte_rendu',
                'sous_type' => null,
            ],
            'PVF', 'PV_FINAL' => [
                'type_document' => 'pv_final',
                'entite_type' => 'programmer_soutenance',
                'sous_type' => null,
            ],
            'PLN' => [
                'type_document' => 'planning',
                'entite_type' => null,
                'sous_type' => null,
            ],
            default => null,
        };
    }

}
