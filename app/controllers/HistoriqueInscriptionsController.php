<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Models/Inscription.php';
require_once __DIR__ . '/../Models/Etudiant.php';

/**
 * Controleur pour l'Historique des Inscriptions (P2.3)
 *
 * Timeline des inscriptions par etudiant
 */
class HistoriqueInscriptionsController
{
    private PDO $pdo;
    private Inscription $inscriptionModel;
    private Etudiant $etudiantModel;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->inscriptionModel = new Inscription($this->pdo);
        $this->etudiantModel = new Etudiant($this->pdo);
    }

    /**
     * Donnees pour la vue
     */
    public function index(): array
    {
        $numEtu = trim((string) ($_GET['num_etu'] ?? $_GET['id'] ?? $_SESSION['num_etu'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;

        if (empty($numEtu)) {
            return ['etudiant' => null, 'parcours' => [], 'notes' => []];
        }

        // Infos etudiant
        $etudiant = $this->etudiantModel->getEtudiantById($numEtu);
        if (!$etudiant) {
            try {
                $stmt = $this->pdo->prepare("SELECT *, num_ident_etud as identifiant_mesrs FROM etudiants WHERE num_ident_etud = ? LIMIT 1");
                $stmt->execute([$numEtu]);
                $etudiant = $stmt->fetch(PDO::FETCH_OBJ);
            } catch (\Exception $e) {
                error_log('HistoriqueInscriptionsController - recherche num_ident: ' . $e->getMessage());
            }
        }

        // Parcours complet des inscriptions
        $parcours = $this->getParcoursComplet($numEtu);

        // Notes
        $notes = [];
        try {
            $stmt = $this->pdo->prepare("
                SELECT n.*, aa.date_deb, aa.date_fin
                FROM notes n
                LEFT JOIN annee_academique aa ON n.id_annee_acad = aa.id_annee_acad
                WHERE n.num_etu = ?
                ORDER BY n.id_annee_acad DESC
            ");
            $stmt->execute([$numEtu]);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('HistoriqueInscriptionsController::index - notes: ' . $e->getMessage());
        }

        return [
            'etudiant' => $etudiant,
            'parcours' => $parcours,
            'notes' => $notes,
        ];
    }

    /**
     * Parcours complet : annee -> niveau -> montant -> verse -> solde -> echeances
     */
    private function getParcoursComplet(string $numEtu): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    i.id_annee_acad,
                    i.num_versement,
                    i.date_inscription,
                    i.date_versement,
                    i.id_niv_etude,
                    i.montant_verser,
                    i.solde,
                    i.methode_paiement,
                    n.lib_niv_etude,
                    a.date_deb AS annee_deb,
                    a.date_fin AS annee_fin,
                    f.montant AS frais_montant
                FROM inscriptions i
                JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
                JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
                LEFT JOIN frais_inscription f ON f.id_niv_etude = i.id_niv_etude AND f.id_annee_acad = i.id_annee_acad
                WHERE i.num_carte_etud = ?
                ORDER BY i.id_annee_acad DESC, i.date_inscription DESC, i.num_versement DESC
            ");
            $stmt->execute([$numEtu]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Grouper par annee academique
            $grouped = [];
            foreach ($rows as $row) {
                $anneeId = (int) ($row['id_annee_acad'] ?? 0);
                if (!isset($grouped[$anneeId])) {
                    $anneeLabel = date('Y', strtotime((string) ($row['annee_deb'] ?? ''))) . '-' . date('Y', strtotime((string) ($row['annee_fin'] ?? '')));
                    $grouped[$anneeId] = [
                        'id_annee' => $anneeId,
                        'annee_label' => $anneeLabel,
                        'niveau' => $row['lib_niv_etude'] ?? '',
                        'id_niv_etude' => $row['id_niv_etude'] ?? '',
                        'frais_montant' => (float) ($row['frais_montant'] ?? 0),
                        'total_verse' => 0,
                        'solde' => 0,
                        'versements' => [],
                    ];
                }
                $montantVerse = (float) ($row['montant_verser'] ?? 0);
                $grouped[$anneeId]['total_verse'] += $montantVerse;
                $grouped[$anneeId]['solde'] = (float) ($row['solde'] ?? 0);
                $grouped[$anneeId]['versements'][] = [
                    'num_versement' => (int) ($row['num_versement'] ?? 0),
                    'date_versement' => $row['date_versement'] ?? $row['date_inscription'] ?? '',
                    'montant' => $montantVerse,
                    'methode' => $row['methode_paiement'] ?? '',
                ];
            }

            return array_values($grouped);
        } catch (\Exception $e) {
            error_log('HistoriqueInscriptionsController::getParcoursComplet - ' . $e->getMessage());
            return [];
        }
    }
}
