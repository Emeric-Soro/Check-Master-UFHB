<?php
namespace CheckMaster\Services;

class FicheFinanciereService
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Retourne la liste de toutes les années académiques avec leurs stats financières.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllAnnees(): array
    {
        $stmt = $this->db->query("
            SELECT 
                aa.id_annee_acad,
                CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) AS label,
                aa.date_deb,
                aa.date_fin,
                CASE WHEN CURDATE() BETWEEN aa.date_deb AND aa.date_fin THEN 1 ELSE 0 END AS is_active
            FROM annee_academique aa
            ORDER BY aa.date_deb DESC
        ");
        $annees = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($annees as &$annee) {
            $id = (int) $annee['id_annee_acad'];
            $recap = $this->getRecapAnnee($id);
            $annee = array_merge($annee, $recap);
        }
        unset($annee);

        return $annees;
    }

    /**
     * Récupère le récapitulatif financier complet pour une année donnée.
     *
     * @param int $idAnnee
     * @return array<string, mixed>
     */
    public function getRecapAnnee(int $idAnnee): array
    {
        // Total attendu par niveau
        $stmt = $this->db->prepare("
            SELECT 
                n.id_niv_etude,
                n.lib_niv_etude AS niveau_label,
                f.montant AS frais_inscription,
                (
                    SELECT COUNT(DISTINCT i_sub.num_carte_etud)
                    FROM inscriptions i_sub
                    WHERE i_sub.id_niv_etude = n.id_niv_etude
                      AND i_sub.id_annee_acad = :annee_id
                ) AS nb_inscrits,
                (f.montant * (
                    SELECT COUNT(DISTINCT i_sub.num_carte_etud)
                    FROM inscriptions i_sub
                    WHERE i_sub.id_niv_etude = n.id_niv_etude
                      AND i_sub.id_annee_acad = :annee_id2
                )) AS total_attendu
            FROM niveau_etude n
            INNER JOIN frais_inscription f ON f.id_niv_etude = n.id_niv_etude AND f.id_annee_acad = :annee_id3
            ORDER BY n.id_niv_etude
        ");
        $stmt->execute([
            ':annee_id' => $idAnnee,
            ':annee_id2' => $idAnnee,
            ':annee_id3' => $idAnnee,
        ]);
        $niveaux = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Total versé global
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(montant_verser), 0) AS total_verse
            FROM inscriptions
            WHERE id_annee_acad = :annee_id
        ");
        $stmt->execute([':annee_id' => $idAnnee]);
        $totalVerse = (float) $stmt->fetchColumn();

        // Total attendu global
        $totalAttenduGlobal = 0;
        $nbTotalInscrits = 0;
        foreach ($niveaux as $niveau) {
            $totalAttenduGlobal += (float) ($niveau['total_attendu'] ?? 0);
            $nbTotalInscrits += (int) ($niveau['nb_inscrits'] ?? 0);
        }

        // Taux de recouvrement
        $tauxRecouvrement = $totalAttenduGlobal > 0
            ? round(($totalVerse / $totalAttenduGlobal) * 100, 1)
            : 0;

        // Échéances en retard
        $echeancesRetard = $this->getEcheancesEnRetard($idAnnee);

        // Échéances à venir (30 jours)
        $echeancesAVenir = $this->getEcheancesAVenir($idAnnee, 30);

        return [
            'niveaux' => $niveaux,
            'total_attendu' => $totalAttenduGlobal,
            'total_verse' => $totalVerse,
            'taux_recouvrement' => $tauxRecouvrement,
            'nb_total_inscrits' => $nbTotalInscrits,
            'echeances_retard' => $echeancesRetard,
            'echeances_a_venir' => $echeancesAVenir,
        ];
    }

    /**
     * Récupère les échéances en retard pour une année donnée.
     *
     * @param int $idAnnee
     * @return array<int, array<string, mixed>>
     */
    public function getEcheancesEnRetard(int $idAnnee): array
    {
        if (!$this->tableExists('echeances')
            || !$this->columnExists('echeances', 'id_inscription')
            || !$this->columnExists('inscriptions', 'id_inscription')) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT 
                e.*,
                i_sub.num_carte_etud,
                et.nom_etu,
                et.prenom_etu,
                n.lib_niv_etude,
                CONCAT(i_sub.num_carte_etud, '-', i_sub.id_annee_acad, '-', i_sub.num_versement) AS id_inscription
            FROM echeances e
            INNER JOIN inscriptions i_sub ON e.id_inscription = i_sub.id_inscription
            INNER JOIN etudiants et ON (i_sub.num_carte_etud = et.num_carte_etud OR i_sub.num_carte_etud = et.num_ident_etud)
            INNER JOIN niveau_etude n ON i_sub.id_niv_etude = n.id_niv_etude
            WHERE i_sub.id_annee_acad = :annee_id
              AND e.date_echeance < CURDATE()
              AND e.statut_echeance != 'Payée'
            ORDER BY e.date_echeance ASC
        ");
        $stmt->execute([':annee_id' => $idAnnee]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les échéances à venir dans un intervalle de jours.
     *
     * @param int $idAnnee
     * @param int $jours
     * @return array<int, array<string, mixed>>
     */
    public function getEcheancesAVenir(int $idAnnee, int $jours = 30): array
    {
        if (!$this->tableExists('echeances')
            || !$this->columnExists('echeances', 'id_inscription')
            || !$this->columnExists('inscriptions', 'id_inscription')) {
            return [];
        }

        $jours = max(1, $jours);

        $stmt = $this->db->prepare("
            SELECT 
                e.*,
                i_sub.num_carte_etud,
                et.nom_etu,
                et.prenom_etu,
                n.lib_niv_etude,
                CONCAT(i_sub.num_carte_etud, '-', i_sub.id_annee_acad, '-', i_sub.num_versement) AS id_inscription
            FROM echeances e
            INNER JOIN inscriptions i_sub ON e.id_inscription = i_sub.id_inscription
            INNER JOIN etudiants et ON (i_sub.num_carte_etud = et.num_carte_etud OR i_sub.num_carte_etud = et.num_ident_etud)
            INNER JOIN niveau_etude n ON i_sub.id_niv_etude = n.id_niv_etude
            WHERE i_sub.id_annee_acad = :annee_id
              AND e.date_echeance BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$jours} DAY)
              AND e.statut_echeance = 'En attente'
            ORDER BY e.date_echeance ASC
        ");
        $stmt->execute([':annee_id' => $idAnnee]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Détail des versements d'un étudiant pour une année.
     *
     * @param string $numEtu
     * @param int $idAnnee
     * @return array<string, mixed>|null
     */
    public function getDetailEtudiant(string $numEtu, int $idAnnee): ?array
    {
        // Infos étudiant
        $stmt = $this->db->prepare("
            SELECT num_carte_etud, num_ident_etud, nom_etu, prenom_etu, email_etu
            FROM etudiants
            WHERE num_carte_etud = :num_etu OR num_ident_etud = :num_etu2
            LIMIT 1
        ");
        $stmt->execute([
            ':num_etu' => $numEtu,
            ':num_etu2' => $numEtu,
        ]);
        $etudiant = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$etudiant) {
            return null;
        }

        $numCarte = $etudiant['num_ident_etud'] ?? $etudiant['num_carte_etud'];

        // Récupération du niveau d'étude depuis la première inscription de l'année
        $stmt = $this->db->prepare("
            SELECT i.id_niv_etude, n.lib_niv_etude, f.montant AS montant_scolarite
            FROM inscriptions i
            LEFT JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
            LEFT JOIN frais_inscription f ON f.id_niv_etude = i.id_niv_etude AND f.id_annee_acad = i.id_annee_acad
            WHERE (i.num_carte_etud = :num_carte1 OR i.num_carte_etud = :num_carte2)
              AND i.id_annee_acad = :annee_id
            LIMIT 1
        ");
        $stmt->execute([
            ':num_carte1' => $numCarte,
            ':num_carte2' => $etudiant['num_ident_etud'] ?? $numCarte,
            ':annee_id' => $idAnnee,
        ]);
        $niveauInfo = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Versements de l'étudiant pour l'année
        $stmt = $this->db->prepare("
            SELECT 
                i.num_versement,
                i.date_versement,
                i.montant_verser,
                i.methode_paiement,
                i.num_piece_mp,
                i.solde,
                i.date_inscription,
                CONCAT(i.num_carte_etud, '-', i.id_annee_acad, '-', i.num_versement) AS id_inscription
            FROM inscriptions i
            WHERE (i.num_carte_etud = :num_carte1 OR i.num_carte_etud = :num_carte2)
              AND i.id_annee_acad = :annee_id
            ORDER BY i.num_versement ASC
        ");
        $stmt->execute([
            ':num_carte1' => $numCarte,
            ':num_carte2' => $etudiant['num_ident_etud'] ?? $numCarte,
            ':annee_id' => $idAnnee,
        ]);
        $versements = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Échéances
        $echeances = [];
        if ($this->tableExists('echeances')
            && $this->columnExists('echeances', 'id_inscription')
            && $this->columnExists('inscriptions', 'id_inscription')) {
            $stmt = $this->db->prepare("
                SELECT e.*
                FROM echeances e
                INNER JOIN inscriptions i_sub ON e.id_inscription = i_sub.id_inscription
                WHERE (i_sub.num_carte_etud = :num_carte1 OR i_sub.num_carte_etud = :num_carte2)
                  AND i_sub.id_annee_acad = :annee_id
                ORDER BY e.date_echeance ASC
            ");
            $stmt->execute([
                ':num_carte1' => $numCarte,
                ':num_carte2' => $etudiant['num_ident_etud'] ?? $numCarte,
                ':annee_id' => $idAnnee,
            ]);
            $echeances = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $montantScolarite = (float) ($niveauInfo['montant_scolarite'] ?? 0);
        $montantPaye = 0;
        foreach ($versements as $v) {
            $montantPaye += (float) ($v['montant_verser'] ?? 0);
        }
        $solde = max(0, $montantScolarite - $montantPaye);

        return [
            'etudiant' => $etudiant,
            'niveau' => $niveauInfo['lib_niv_etude'] ?? 'N/A',
            'id_niv_etude' => $niveauInfo['id_niv_etude'] ?? null,
            'montant_scolarite' => $montantScolarite,
            'montant_paye' => $montantPaye,
            'solde' => $solde,
            'versements' => $versements,
            'echeances' => $echeances,
        ];
    }

    /**
     * Récupère la liste des étudiants inscrits pour une année avec leur récap financier.
     *
     * @param int $idAnnee
     * @return array<int, array<string, mixed>>
     */
    public function getEtudiantsInscritsRecap(int $idAnnee): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                i_sub.num_carte_etud,
                COALESCE(e.nom_etu, 'Inconnu') AS nom,
                COALESCE(e.prenom_etu, '') AS prenom,
                n.lib_niv_etude AS niveau,
                COALESCE(f.montant, 0) AS montant_scolarite,
                COALESCE(SUM(i_sub.montant_verser), 0) AS montant_paye,
                COALESCE(MAX(i_sub.solde), 0) AS solde,
                COUNT(i_sub.num_versement) AS nb_versements,
                MAX(i_sub.date_versement) AS dernier_versement
            FROM inscriptions i_sub
            LEFT JOIN etudiants e ON (i_sub.num_carte_etud = e.num_carte_etud OR i_sub.num_carte_etud = e.num_ident_etud)
            LEFT JOIN niveau_etude n ON i_sub.id_niv_etude = n.id_niv_etude
            LEFT JOIN frais_inscription f ON f.id_niv_etude = i_sub.id_niv_etude AND f.id_annee_acad = i_sub.id_annee_acad
            WHERE i_sub.id_annee_acad = :annee_id
            GROUP BY i_sub.num_carte_etud, e.nom_etu, e.prenom_etu, n.lib_niv_etude, f.montant
            ORDER BY COALESCE(e.nom_etu, i_sub.num_carte_etud), e.prenom_etu
        ");
        $stmt->execute([':annee_id' => $idAnnee]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function tableExists(string $tableName): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE :table");
            $stmt->execute([':table' => $tableName]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE :column");
            $stmt->execute([':column' => $columnName]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
