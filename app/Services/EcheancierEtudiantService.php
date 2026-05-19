<?php
/**
 * EcheancierEtudiantService
 *
 * Fournit les données pour l'échéancier étudiant :
 * - Liste des échéances détaillées avec statut de paiement
 * - Timeline des échéances
 *
 * Tables : echeances, inscriptions, etudiants, frais_inscription
 */

namespace CheckMaster\Services;

use PDO;

class EcheancierEtudiantService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupère toutes les échéances avec détails étudiant et inscription.
     */
    public function getAllEcheances(?int $idAnnee = null, string $search = '', string $statut = ''): array
    {
        if (!$this->tableExists('echeances')
            || !$this->columnExists('echeances', 'id_inscription')
            || !$this->columnExists('inscriptions', 'id_inscription')) {
            return [];
        }

        $sql = "SELECT
                    e.id_echeance,
                    e.date_echeance,
                    e.montant_echeance,
                    e.statut_echeance,
                    e.libelle_echeance,
                    e.id_inscription,
                    i.num_carte_etud,
                    i.id_niv_etude,
                    i.id_annee_acad,
                    i.montant_verser,
                    i.solde,
                    et.nom_etu,
                    et.prenom_etu,
                    et.email_etu,
                    n.lib_niv_etude,
                    fi.montant AS montant_scolarite,
                    CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) AS annee_label
                FROM echeances e
                JOIN inscriptions i ON e.id_inscription = i.id_inscription
                JOIN etudiants et ON (i.num_carte_etud = et.num_carte_etud OR i.num_carte_etud = et.num_ident_etud)
                JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                LEFT JOIN frais_inscription fi ON i.id_niv_etude = fi.id_niv_etude AND i.id_annee_acad = fi.id_annee_acad
                LEFT JOIN annee_academique aa ON i.id_annee_acad = aa.id_annee_acad
                WHERE 1=1";

        $params = [];

        if ($idAnnee !== null && $idAnnee > 0) {
            $sql .= " AND i.id_annee_acad = :id_annee";
            $params[':id_annee'] = $idAnnee;
        }

        if ($search !== '') {
            $sql .= " AND (et.nom_etu LIKE :search OR et.prenom_etu LIKE :search2 OR et.num_carte_etud LIKE :search3)";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
        }

        if ($statut !== '') {
            $sql .= " AND e.statut_echeance = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY e.date_echeance ASC, et.nom_etu ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les échéances pour un étudiant spécifique.
     */
    public function getEcheancesByEtudiant(string $numEtu, ?int $idAnnee = null): array
    {
        if (!$this->tableExists('echeances')
            || !$this->columnExists('echeances', 'id_inscription')
            || !$this->columnExists('inscriptions', 'id_inscription')) {
            return [];
        }

        $sql = "SELECT
                    e.*,
                    i.num_carte_etud,
                    i.id_niv_etude,
                    i.montant_verser,
                    i.solde,
                    n.lib_niv_etude,
                    fi.montant AS montant_scolarite
                FROM echeances e
                JOIN inscriptions i ON e.id_inscription = i.id_inscription
                JOIN etudiants et ON (i.num_carte_etud = et.num_carte_etud OR i.num_carte_etud = et.num_ident_etud)
                JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                LEFT JOIN frais_inscription fi ON i.id_niv_etude = fi.id_niv_etude AND i.id_annee_acad = fi.id_annee_acad
                WHERE (et.num_carte_etud = :num_etu OR et.num_ident_etud = :num_etu)";

        $params = [':num_etu' => $numEtu];

        if ($idAnnee !== null && $idAnnee > 0) {
            $sql .= " AND i.id_annee_acad = :id_annee";
            $params[':id_annee'] = $idAnnee;
        }

        $sql .= " ORDER BY e.date_echeance ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Prépare les données pour la timeline.
     */
    public function getTimelineData(?int $idAnnee = null, string $search = '', string $statut = ''): array
    {
        $echeances = $this->getAllEcheances($idAnnee, $search, $statut);

        $timeline = [];
        foreach ($echeances as $e) {
            $date = $e['date_echeance'] ?? '';
            if ($date === '') continue;

            $monthKey = date('Y-m', strtotime($date));
            $monthLabel = date('F Y', strtotime($date));

            if (!isset($timeline[$monthKey])) {
                $timeline[$monthKey] = [
                    'month_key'   => $monthKey,
                    'month_label' => $monthLabel,
                    'items'       => [],
                ];
            }

            $statutEcheance = $e['statut_echeance'] ?? 'en_attente';
            $badgeType = match (strtolower($statutEcheance)) {
                'payée', 'paye', 'payee', 'validé', 'valide' => 'success',
                'en_retard', 'impayée', 'impaye' => 'danger',
                'partielle', 'partiel' => 'warning',
                default => 'info',
            };

            $timeline[$monthKey]['items'][] = [
                'id_echeance'     => $e['id_echeance'] ?? '',
                'date_echeance'   => $date,
                'jour'            => (int) date('d', strtotime($date)),
                'jour_semaine'    => $this->frenchDayName(strtotime($date)),
                'libelle'         => $e['libelle_echeance'] ?? 'Échéance',
                'montant'         => (float) ($e['montant_echeance'] ?? 0),
                'montant_verse'   => (float) ($e['montant_verser'] ?? 0),
                'solde'           => (float) ($e['solde'] ?? 0),
                'statut'          => $statutEcheance,
                'badge_type'      => $badgeType,
                'etudiant'        => ($e['nom_etu'] ?? '') . ' ' . ($e['prenom_etu'] ?? ''),
                'num_etu'         => $e['num_ident_etud'] ?? $e['num_carte_etud'] ?? '',
                'niveau'          => $e['lib_niv_etude'] ?? '',
                'annee'           => $e['annee_label'] ?? '',
            ];
        }

        // Trier par mois
        ksort($timeline);
        return array_values($timeline);
    }

    /**
     * Statistiques globales des échéances.
     */
    public function getStats(?int $idAnnee = null): array
    {
        $echeances = $this->getAllEcheances($idAnnee);
        $total = count($echeances);
        $payees = 0;
        $impayees = 0;
        $enAttente = 0;
        $montantTotal = 0;
        $montantPaye = 0;

        foreach ($echeances as $e) {
            $montantTotal += (float) ($e['montant_echeance'] ?? 0);
            $montantPaye += (float) ($e['montant_verser'] ?? 0);

            $statut = strtolower($e['statut_echeance'] ?? '');
            if (in_array($statut, ['payée', 'paye', 'payee', 'validé', 'valide'])) {
                $payees++;
            } elseif (in_array($statut, ['en_retard', 'impayée', 'impaye'])) {
                $impayees++;
            } else {
                $enAttente++;
            }
        }

        return [
            'total'         => $total,
            'payees'        => $payees,
            'impayees'      => $impayees,
            'en_attente'    => $enAttente,
            'montant_total' => $montantTotal,
            'montant_paye'  => $montantPaye,
            'taux_paiement' => $total > 0 ? round(($payees / $total) * 100, 1) : 0,
        ];
    }

    private function frenchDayName(int $timestamp): string
    {
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        return $days[(int) date('w', $timestamp)] ?? '';
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
