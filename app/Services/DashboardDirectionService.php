<?php
namespace CheckMaster\Services;

class DashboardDirectionService
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Agrège tous les indicateurs clés pour le dashboard direction.
     *
     * @return array<string, mixed>
     */
    public function getKPIs(): array
    {
        $anneeActive = $this->getAnneeActive();
        $idAnneeActive = $anneeActive ? (int) ($anneeActive['id_annee_acad'] ?? 0) : null;

        return [
            'annee_active' => $anneeActive,
            'nb_etudiants_inscrits' => $this->countEtudiantsInscrits($idAnneeActive),
            'candidatures' => $this->countCandidatures(),
            'soutenances_programmees' => $this->countSoutenancesProgrammees($idAnneeActive),
            'soutenances_realisees' => $this->countSoutenancesRealisees($idAnneeActive),
            'taux_reussite_global' => $this->getTauxReussiteGlobal($idAnneeActive),
            'recettes_encaissees' => $this->getRecettesEncaissees($idAnneeActive),
            'recettes_attendues' => $this->getRecettesAttendues($idAnneeActive),
            'nb_enseignants_actifs' => $this->countEnseignantsActifs(),
            'nb_utilisateurs_total' => $this->countUtilisateursTotal(),
            'nb_reclamations' => $this->countReclamations(),
            'evolution_par_annee' => $this->getEvolutionParAnnee(),
            'repartition_filiere' => $this->getRepartitionParFiliere($idAnneeActive),
            'dernieres_inscriptions' => $this->getDernieresInscriptions(10),
            'activites_recentes' => $this->getActivitesRecentes(8),
        ];
    }

    /**
     * Récupère l'année académique active ou la plus récente.
     *
     * @return array<string, mixed>|null
     */
    private function getAnneeActive(): ?array
    {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("
            SELECT id_annee_acad, date_deb, date_fin,
                   CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) AS label,
                   CASE WHEN :today >= date_deb AND :today2 <= date_fin THEN 1 ELSE 0 END AS is_active
            FROM annee_academique
            ORDER BY is_active DESC, date_deb DESC
            LIMIT 1
        ");
        $stmt->execute([
            ':today' => $today,
            ':today2' => $today,
        ]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Compte les étudiants inscrits (distincts) pour une année donnée.
     *
     * @param int|null $idAnnee
     * @return int
     */
    private function countEtudiantsInscrits(?int $idAnnee): int
    {
        if ($idAnnee === null) {
            $stmt = $this->db->query("SELECT COUNT(DISTINCT num_carte_etud) FROM inscriptions");
            return (int) $stmt->fetchColumn();
        }

        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT num_carte_etud) FROM inscriptions WHERE id_annee_acad = :annee_id");
        $stmt->execute([':annee_id' => $idAnnee]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Compte les candidatures à la soutenance par statut.
     *
     * @return array<string, int>
     */
    private function countCandidatures(): array
    {
        if (!$this->tableExists('candidature_soutenance')) {
            return [
                'total' => 0,
                'en_attente' => 0,
                'validee' => 0,
                'rejetee' => 0,
            ];
        }

        $stats = [
            'total' => 0,
            'en_attente' => 0,
            'validee' => 0,
            'rejetee' => 0,
        ];

        try {
            $stmt = $this->db->query("
                SELECT statut_candidature, COUNT(*) AS nb
                FROM candidature_soutenance
                GROUP BY statut_candidature
            ");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $statut = strtolower(trim((string) ($row['statut_candidature'] ?? '')));
                $nb = (int) ($row['nb'] ?? 0);
                $stats['total'] += $nb;
                if ($statut === 'en attente' || $statut === 'en_attente') {
                    $stats['en_attente'] += $nb;
                } elseif ($statut === 'validee' || $statut === 'validée') {
                    $stats['validee'] += $nb;
                } elseif ($statut === 'rejetee' || $statut === 'rejetée') {
                    $stats['rejetee'] += $nb;
                }
            }
        } catch (\Throwable $e) {
            error_log('DashboardDirectionService::countCandidatures: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Compte les soutenances programmées.
     *
     * @param int|null $idAnnee
     * @return int
     */
    private function countSoutenancesProgrammees(?int $idAnnee): int
    {
        $table = $this->resolveProgrammationTable();
        if (!$table) {
            return 0;
        }

        try {
            if ($idAnnee !== null && $this->columnExists($table, 'id_annee_acad')) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE id_annee_acad = :annee_id");
                $stmt->execute([':annee_id' => $idAnnee]);
            } else {
                $stmt = $this->db->query("SELECT COUNT(*) FROM {$table}");
            }
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Compte les soutenances réalisées (avec date de soutenance non nulle).
     *
     * @param int|null $idAnnee
     * @return int
     */
    private function countSoutenancesRealisees(?int $idAnnee): int
    {
        $table = $this->resolveProgrammationTable();
        if (!$table) {
            return 0;
        }

        try {
            $dateCol = $this->columnExists($table, 'date_soutenance') ? 'date_soutenance' : null;
            if (!$dateCol) {
                return $this->countSoutenancesProgrammees($idAnnee);
            }

            if ($idAnnee !== null && $this->columnExists($table, 'id_annee_acad')) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$dateCol} IS NOT NULL AND {$dateCol} <= CURDATE() AND id_annee_acad = :annee_id");
                $stmt->execute([':annee_id' => $idAnnee]);
            } else {
                $stmt = $this->db->query("SELECT COUNT(*) FROM {$table} WHERE {$dateCol} IS NOT NULL AND {$dateCol} <= CURDATE()");
            }
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Calcule le taux de réussite global.
     *
     * @param int|null $idAnnee
     * @return float
     */
    private function getTauxReussiteGlobal(?int $idAnnee): float
    {
        try {
            $sqlInscrits = "SELECT COUNT(DISTINCT num_carte_etud) FROM inscriptions";
            $params = [];
            if ($idAnnee !== null) {
                $sqlInscrits .= " WHERE id_annee_acad = :annee_id";
                $params[':annee_id'] = $idAnnee;
            }
            $stmt = $this->db->prepare($sqlInscrits);
            $stmt->execute($params);
            $totalInscrits = (int) $stmt->fetchColumn();

            if ($totalInscrits === 0) {
                return 0;
            }

            // Compter les soutenances réalisées
            $table = $this->resolveProgrammationTable();
            $realisees = 0;
            if ($table) {
                $dateCol = $this->columnExists($table, 'date_soutenance') ? 'date_soutenance' : null;
                if ($dateCol) {
                    if ($idAnnee !== null && $this->columnExists($table, 'id_annee_acad')) {
                        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT num_etud) FROM {$table} WHERE {$dateCol} IS NOT NULL AND {$dateCol} <= CURDATE() AND id_annee_acad = :annee_id2");
                        $stmt->execute([':annee_id2' => $idAnnee]);
                    } else {
                        $stmt = $this->db->query("SELECT COUNT(DISTINCT num_etud) FROM {$table} WHERE {$dateCol} IS NOT NULL AND {$dateCol} <= CURDATE()");
                    }
                    $realisees = (int) ($stmt->fetchColumn() ?: 0);
                }
            }

            return round(($realisees / $totalInscrits) * 100, 1);
        } catch (\Throwable $e) {
            error_log('DashboardDirectionService::getTauxReussiteGlobal: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Total des recettes encaissées (SUM montant_verser).
     *
     * @param int|null $idAnnee
     * @return float
     */
    private function getRecettesEncaissees(?int $idAnnee): float
    {
        try {
            if ($idAnnee !== null) {
                $stmt = $this->db->prepare("SELECT COALESCE(SUM(montant_verser), 0) FROM inscriptions WHERE id_annee_acad = :annee_id");
                $stmt->execute([':annee_id' => $idAnnee]);
            } else {
                $stmt = $this->db->query("SELECT COALESCE(SUM(montant_verser), 0) FROM inscriptions");
            }
            return (float) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Total des recettes attendues (SUM frais_inscription.montant * nb inscrits).
     *
     * @param int|null $idAnnee
     * @return float
     */
    private function getRecettesAttendues(?int $idAnnee): float
    {
        if ($idAnnee === null) {
            return 0;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(f.montant * sub.nb), 0)
                FROM (
                    SELECT id_niv_etude, COUNT(DISTINCT num_carte_etud) AS nb
                    FROM inscriptions
                    WHERE id_annee_acad = :annee_id
                    GROUP BY id_niv_etude
                ) sub
                INNER JOIN frais_inscription f ON f.id_niv_etude = sub.id_niv_etude AND f.id_annee_acad = :annee_id2
            ");
            $stmt->execute([
                ':annee_id' => $idAnnee,
                ':annee_id2' => $idAnnee,
            ]);
            return (float) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Compte les enseignants actifs.
     *
     * @return int
     */
    private function countEnseignantsActifs(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM enseignants WHERE 1=1");
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Compte le total des utilisateurs.
     *
     * @return int
     */
    private function countUtilisateursTotal(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM utilisateur WHERE statut_utilisateur = 'Actif' OR statut_utilisateur IS NULL");
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Compte les réclamations par statut.
     *
     * @return array<string, int>
     */
    private function countReclamations(): array
    {
        $stats = ['total' => 0, 'en_attente' => 0, 'en_cours' => 0, 'resolue' => 0];

        if (!$this->tableExists('reclamations')) {
            return $stats;
        }

        try {
            $stmt = $this->db->query("
                SELECT sr.libelle_statut_reclamation AS statut_reclamation, COUNT(*) AS nb
                FROM reclamations r
                LEFT JOIN statut_reclamation sr ON sr.id_statut_reclamation = r.statut_reclamation
                GROUP BY sr.libelle_statut_reclamation
            ");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $statut = strtolower(trim((string) ($row['statut_reclamation'] ?? '')));
                $nb = (int) ($row['nb'] ?? 0);
                $stats['total'] += $nb;
                if ($statut === 'en attente' || $statut === 'en_attente') {
                    $stats['en_attente'] += $nb;
                } elseif ($statut === 'en cours' || $statut === 'en_cours') {
                    $stats['en_cours'] += $nb;
                } elseif ($statut === 'résolue' || $statut === 'resolue' || $statut === 'résolu' || $statut === 'resolu') {
                    $stats['resolue'] += $nb;
                }
            }
        } catch (\Throwable $e) {
            error_log('DashboardDirectionService::countReclamations: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Évolution des inscriptions par année.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getEvolutionParAnnee(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT
                    CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) AS annee,
                    COUNT(DISTINCT i.num_carte_etud) AS inscrits,
                    COALESCE(SUM(i.montant_verser), 0) AS total_verse
                FROM inscriptions i
                INNER JOIN annee_academique aa ON i.id_annee_acad = aa.id_annee_acad
                GROUP BY aa.id_annee_acad, aa.date_deb
                ORDER BY aa.date_deb ASC
            ");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('DashboardDirectionService::getEvolutionParAnnee: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Répartition des étudiants par filière/niveau.
     *
     * @param int|null $idAnnee
     * @return array<int, array<string, mixed>>
     */
    private function getRepartitionParFiliere(?int $idAnnee): array
    {
        try {
            $sql = "
                SELECT
                    n.lib_niv_etude AS filiere,
                    COUNT(DISTINCT i.num_carte_etud) AS nb
                FROM inscriptions i
                INNER JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
            ";
            $params = [];
            if ($idAnnee !== null) {
                $sql .= " WHERE i.id_annee_acad = :annee_id";
                $params[':annee_id'] = $idAnnee;
            }
            $sql .= " GROUP BY n.lib_niv_etude ORDER BY nb DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Dernières inscriptions (versements) récentes.
     *
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    private function getDernieresInscriptions(int $limit = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    i.num_carte_etud,
                    COALESCE(e.nom_etu, 'Inconnu') AS nom,
                    COALESCE(e.prenom_etu, '') AS prenom,
                    i.montant_verser,
                    i.date_versement,
                    n.lib_niv_etude AS niveau
                FROM inscriptions i
                LEFT JOIN etudiants e ON (i.num_carte_etud = e.num_carte_etud OR i.num_carte_etud = e.num_ident_etud)
                LEFT JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                ORDER BY i.date_versement DESC
                LIMIT :lim
            ");
            $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Activités récentes (audit trail simplifié).
     *
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    private function getActivitesRecentes(int $limit = 8): array
    {
        if (!$this->tableExists('pister')) {
            return [];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT
                    p.date_creation,
                    p.action,
                    p.contexte,
                    COALESCE(NULLIF(u.nom_utilisateur, ''), NULLIF(u.login_utilisateur, ''), 'Système') AS utilisateur,
                    p.statut_action
                FROM pister p
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                ORDER BY p.date_creation DESC
                LIMIT :lim
            ");
            $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function tableExists(string $tableName): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE :tbl");
            $stmt->execute([':tbl' => $tableName]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE :col");
            $stmt->execute([':col' => $columnName]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function resolveProgrammationTable(): ?string
    {
        if ($this->tableExists('programmer_soutenance')) {
            return 'programmer_soutenance';
        }
        if ($this->tableExists('programmer')) {
            return 'programmer';
        }
        return null;
    }
}
