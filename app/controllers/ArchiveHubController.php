<?php
/**
 * ArchiveHubController - Hub central d'historique et archivage
 * Contrôleur unifié pour la consultation des archives avec onglets
 */
require_once __DIR__ . '/../models/Archive.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

class ArchiveHubController
{
    private $db;
    private $archiveModel;
    private $anneeModel;
    private const PER_PAGE = 10;

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getConnection();
        $this->archiveModel = new Archive($this->db);
        $this->anneeModel = new AnneeAcademique($this->db);
    }

    /**
     * Affiche le hub d'historique avec tous les onglets
     */
    public function index()
    {
        if (!canView('admin_historique')) {
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'accéder aux archives.";
            header('Location: layout.php?page=access_denied');
            exit;
        }

        // Récupérer l'année sélectionnée en session
        $anneeId = $_SESSION['archive_annee_acad'] ?? null;
        $anneeLibelle = $_SESSION['archive_annee_libelle'] ?? null;

        // Si aucune année sélectionnée, utiliser l'année active
        if (!$anneeId) {
            $activeYear = $this->anneeModel->getAnneeAcademiqueActive();
            if ($activeYear) {
                $anneeId = $activeYear->id_annee_acad;
                $anneeLibelle = date('Y', strtotime($activeYear->date_deb)) . '-' . date('Y', strtotime($activeYear->date_fin));
                $_SESSION['archive_annee_acad'] = $anneeId;
                $_SESSION['archive_annee_libelle'] = $anneeLibelle;
            }
        }

        // Récupérer toutes les années pour le sélecteur
        $annees = $this->anneeModel->getAllAnneeAcademiques();

        // Onglet actif
        $activeTab = $_GET['tab'] ?? 'vue_ensemble';
        $validTabs = ['vue_ensemble', 'etudiants', 'soutenances', 'jurys', 'documents', 'candidatures', 'reclamations', 'statistiques', 'import'];
        if (!in_array($activeTab, $validTabs, true)) {
            $activeTab = 'vue_ensemble';
        }

        // Données de base (compteurs pour tous les onglets)
        $stats = [
            'etudiants' => $this->getCountEtudiants($anneeId),
            'soutenances' => $this->getCountSoutenances($anneeId),
            'jurys' => $this->getCountJurys($anneeId),
            'documents' => $this->getCountDocuments($anneeId),
            'candidatures' => $this->getCountCandidatures($anneeId),
            'reclamations' => $this->getCountReclamations($anneeId),
        ];

        $data = [
            'annee_id' => $anneeId,
            'annee_libelle' => $anneeLibelle,
            'annees' => $annees,
            'active_tab' => $activeTab,
            'stats' => $stats,
        ];

        // Charger les données spécifiques à chaque onglet
        switch ($activeTab) {
            case 'vue_ensemble':
                $data = array_merge($data, $this->loadVueEnsemble($anneeId));
                break;
            case 'etudiants':
                $data = array_merge($data, $this->loadEtudiants($anneeId));
                break;
            case 'soutenances':
                $data = array_merge($data, $this->loadSoutenances($anneeId));
                break;
            case 'jurys':
                $data = array_merge($data, $this->loadJurys($anneeId));
                break;
            case 'documents':
                $data = array_merge($data, $this->loadDocuments($anneeId));
                break;
            case 'candidatures':
                $data = array_merge($data, $this->loadCandidatures($anneeId));
                break;
            case 'reclamations':
                $data = array_merge($data, $this->loadReclamations($anneeId));
                break;
            case 'statistiques':
                $data = array_merge($data, $this->loadStatistiques($anneeId));
                break;
            case 'import':
                // Pas de données supplémentaires nécessaires
                break;
        }

        // Logger l'accès
        $this->logAction('Consultation hub archives - onglet ' . $activeTab, 'Succès');

        return $data;
    }

    /**
     * Données pour l'onglet Vue d'ensemble
     */
    private function loadVueEnsemble($anneeId)
    {
        $quickStats = [
            'taux_reussite' => $this->getTauxReussite($anneeId),
            'moyenne_generale' => $this->getMoyenneGenerale($anneeId),
            'jours_soutenance' => $this->getJoursSoutenance($anneeId),
            'rapports_deposes' => $this->getCountDocuments($anneeId),
        ];

        $timeline = $this->getTimeline($anneeId);

        // Derniers étudiants ajoutés (5 derniers)
        $derniersEtudiants = $this->archiveModel->getStudentHistory($anneeId, null, null, 5, 0);

        return [
            'quick_stats' => $quickStats,
            'timeline' => $timeline,
            'derniers_etudiants' => $derniersEtudiants,
        ];
    }

    /**
     * Données pour l'onglet Étudiants
     */
    private function loadEtudiants($anneeId)
    {
        $search = trim($_GET['search'] ?? '');
        $statut = $_GET['statut'] ?? '';
        $page = max(1, intval($_GET['p'] ?? 1));
        $perPage = self::PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $searchParam = $search ?: null;
        $statutParam = $statut ?: null;

        $students = $this->archiveModel->getStudentHistory($anneeId, $statutParam, $searchParam, $perPage, $offset);
        $totalStudents = $this->archiveModel->countStudents($anneeId, $statutParam, $searchParam);
        $totalPages = max(1, ceil($totalStudents / $perPage));

        return [
            'students' => $students,
            'students_total' => $totalStudents,
            'students_page' => $page,
            'students_per_page' => $perPage,
            'students_total_pages' => $totalPages,
            'students_search' => $search,
            'students_statut' => $statut,
        ];
    }

    /**
     * Données pour l'onglet Soutenances
     */
    private function loadSoutenances($anneeId)
    {
        $page = max(1, intval($_GET['p'] ?? 1));
        $perPage = self::PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $soutenances = $this->archiveModel->getSoutenanceHistory($anneeId, $perPage, $offset);
        $totalSoutenances = $this->archiveModel->countSoutenances($anneeId);
        $totalPages = max(1, ceil($totalSoutenances / $perPage));

        return [
            'soutenances' => $soutenances,
            'soutenances_total' => $totalSoutenances,
            'soutenances_page' => $page,
            'soutenances_per_page' => $perPage,
            'soutenances_total_pages' => $totalPages,
        ];
    }

    /**
     * Données pour l'onglet Jurys
     */
    private function loadJurys($anneeId)
    {
        $page = max(1, intval($_GET['p'] ?? 1));
        $perPage = self::PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $juries = $this->archiveModel->getJuryHistory($anneeId, null, $perPage, $offset);

        // Compter total jurys pour pagination
        $totalJuries = $this->getCountJurysDetailed($anneeId);
        $totalPages = max(1, ceil($totalJuries / $perPage));

        return [
            'juries' => $juries,
            'juries_total' => $totalJuries,
            'juries_page' => $page,
            'juries_per_page' => $perPage,
            'juries_total_pages' => $totalPages,
        ];
    }

    /**
     * Données pour l'onglet Documents
     */
    private function loadDocuments($anneeId)
    {
        $page = max(1, intval($_GET['p'] ?? 1));
        $perPage = self::PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $documents = $this->archiveModel->getDocumentHistory($anneeId, $perPage, $offset);
        $totalDocuments = $this->getCountDocuments($anneeId);
        $totalPages = max(1, ceil($totalDocuments / $perPage));

        return [
            'documents' => $documents,
            'documents_total' => $totalDocuments,
            'documents_page' => $page,
            'documents_per_page' => $perPage,
            'documents_total_pages' => $totalPages,
        ];
    }

    /**
     * Données pour l'onglet Candidatures
     */
    private function loadCandidatures($anneeId)
    {
        $page = max(1, intval($_GET['p'] ?? 1));
        $perPage = self::PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $candidatures = $this->archiveModel->getCandidatureHistory($anneeId, $perPage, $offset);
        $totalCandidatures = $this->getCountCandidatures($anneeId);
        $totalPages = max(1, ceil($totalCandidatures / $perPage));

        return [
            'candidatures' => $candidatures,
            'candidatures_total' => $totalCandidatures,
            'candidatures_page' => $page,
            'candidatures_per_page' => $perPage,
            'candidatures_total_pages' => $totalPages,
        ];
    }

    /**
     * Données pour l'onglet Reclamations
     */
    private function loadReclamations($anneeId)
    {
        $page = max(1, intval($_GET['p'] ?? 1));
        $perPage = self::PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $reclamations = $this->archiveModel->getReclamationHistory($anneeId, $perPage, $offset);
        $totalReclamations = $this->getCountReclamations($anneeId);
        $totalPages = max(1, ceil($totalReclamations / $perPage));

        return [
            'reclamations' => $reclamations,
            'reclamations_total' => $totalReclamations,
            'reclamations_page' => $page,
            'reclamations_per_page' => $perPage,
            'reclamations_total_pages' => $totalPages,
        ];
    }

    /**
     * Données pour l'onglet Statistiques
     */
    private function loadStatistiques($anneeId)
    {
        $globalStats = $this->archiveModel->getGlobalStats();
        $yearlyEvolution = $this->archiveModel->getYearlyEvolution();
        $mentionsDistribution = $this->archiveModel->getMentionsDistribution();
        $topEntreprises = $this->archiveModel->getTopEntreprises(10);

        return [
            'global_stats' => $globalStats,
            'yearly_evolution' => $yearlyEvolution,
            'mentions_distribution' => $mentionsDistribution,
            'top_entreprises' => $topEntreprises,
        ];
    }

    /**
     * Change l'année académique sélectionnée
     */
    public function changeYear()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $anneeId = $data['annee_id'] ?? null;

        if (!$anneeId) {
            echo json_encode(['success' => false, 'message' => 'Année non spécifiée']);
            exit;
        }

        $annee = $this->anneeModel->getAnneeAcademiqueById($anneeId);
        if (!$annee) {
            echo json_encode(['success' => false, 'message' => 'Année invalide']);
            exit;
        }

        $anneeLibelle = date('Y', strtotime($annee->date_deb)) . '-' . date('Y', strtotime($annee->date_fin));

        $_SESSION['archive_annee_acad'] = $anneeId;
        $_SESSION['archive_annee_libelle'] = $anneeLibelle;

        $this->logAction('Changement année archive', 'Succès');

        echo json_encode([
            'success' => true,
            'annee_id' => $anneeId,
            'annee_libelle' => $anneeLibelle
        ]);
        exit;
    }

    // ===== Méthodes privées de comptage =====

    private function getCountEtudiants($anneeId)
    {
        try {
            $sql = "SELECT COUNT(DISTINCT e.num_carte_etud) 
                    FROM etudiants e
                    JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.id_annee_acad = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            return $stmt->fetchColumn() ?: 0;
        }
        catch (\PDOException $e) {
            return 0;
        }
    }

    private function getCountSoutenances($anneeId)
    {
        try {
            $sql = "SELECT COUNT(*) 
                    FROM programmer_soutenance ps
                    JOIN etudiants e ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                    JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.id_annee_acad = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            return $stmt->fetchColumn() ?: 0;
        }
        catch (\PDOException $e) {
            return 0;
        }
    }

    private function getCountJurys($anneeId)
    {
        try {
            $sql = "SELECT COUNT(DISTINCT ej.id_enseignant)
                    FROM enseignant_jury ej
                    JOIN programmer_soutenance ps ON ej.num_soutenance = ps.num_soutenance
                    JOIN etudiants e ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                    JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.id_annee_acad = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            return $stmt->fetchColumn() ?: 0;
        }
        catch (\PDOException $e) {
            return 0;
        }
    }

    private function getCountJurysDetailed($anneeId)
    {
        try {
            $sql = "SELECT COUNT(DISTINCT ps.num_soutenance)
                    FROM programmer_soutenance ps
                    JOIN etudiants e ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                    JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.id_annee_acad = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            return $stmt->fetchColumn() ?: 0;
        }
        catch (\PDOException $e) {
            return 0;
        }
    }

    private function getCountDocuments($anneeId)
    {
        try {
            $sql = "SELECT COUNT(*)
                    FROM rapport_etudiants re
                    JOIN etudiants e ON re.num_etu = e.num_carte_etud
                    JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.id_annee_acad = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            return $stmt->fetchColumn() ?: 0;
        }
        catch (\PDOException $e) {
            return 0;
        }
    }

    private function getCountCandidatures($anneeId)
    {
        try {
            $sql = "SELECT COUNT(*)
                    FROM candidature_soutenance cs
                    JOIN etudiants e ON cs.num_etu = e.num_carte_etud
                    JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.id_annee_acad = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            return $stmt->fetchColumn() ?: 0;
        }
        catch (\PDOException $e) {
            return 0;
        }
    }

    private function getCountReclamations($anneeId)
    {
        try {
            $sql = "SELECT COUNT(*)
                    FROM reclamations r
                    JOIN etudiants e ON r.num_carte_etud = e.num_carte_etud
                    JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.id_annee_acad = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            return $stmt->fetchColumn() ?: 0;
        }
        catch (\PDOException $e) {
            return 0;
        }
    }

    private function getTauxReussite($anneeId)
    {
        try {
            $sql = "SELECT 
                        COUNT(CASE WHEN v.decision_validation = 'valider' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0)
                    FROM valider v
                    JOIN rapport_etudiants re ON v.id_rapport = re.id_rapport
                    JOIN etudiants e ON re.num_etu = e.num_carte_etud
                    JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.id_annee_acad = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            return round($stmt->fetchColumn() ?: 0, 1);
        }
        catch (\PDOException $e) {
            return 0;
        }
    }

    private function getMoyenneGenerale($anneeId)
    {
        try {
            $sql = "SELECT AVG(ev.note)
                    FROM evaluer ev
                    JOIN programmer_soutenance ps ON ev.num_etudiant = ps.num_etud
                    JOIN etudiants e ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                    JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.id_annee_acad = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            return round($stmt->fetchColumn() ?: 0, 2);
        }
        catch (\PDOException $e) {
            return 0;
        }
    }

    private function getJoursSoutenance($anneeId)
    {
        try {
            $sql = "SELECT COUNT(DISTINCT ps.date_soutenance)
                    FROM programmer_soutenance ps
                    JOIN etudiants e ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                    JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.id_annee_acad = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            return $stmt->fetchColumn() ?: 0;
        }
        catch (\PDOException $e) {
            return 0;
        }
    }

    private function getTimeline($anneeId)
    {
        $events = [];

        try {
            // Dates de candidature
            $sql = "SELECT MIN(cs.date_candidature) as date, 'Ouverture candidatures' as event
                    FROM candidature_soutenance cs
                    JOIN etudiants e ON cs.num_etu = e.num_carte_etud
                    JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.id_annee_acad = ?
                    GROUP BY i.id_annee_acad";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $events[] = $row;
            }

            // Première soutenance
            $sql = "SELECT MIN(ps.date_soutenance) as date, 'Début des soutenances' as event
                    FROM programmer_soutenance ps
                    JOIN etudiants e ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                    JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.id_annee_acad = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['date']) {
                    $events[] = $row;
                }
            }

            // Dernière soutenance
            $sql = "SELECT MAX(ps.date_soutenance) as date, 'Fin des soutenances' as event
                    FROM programmer_soutenance ps
                    JOIN etudiants e ON (ps.num_etud = e.num_carte_etud OR ps.num_etud = e.num_ident_etud)
                    JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.id_annee_acad = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['date'] && $row['date'] !== $events[count($events) - 1]['date'] ?? null) {
                    $events[] = $row;
                }
            }

            // Premier dépôt de rapport
            $sql = "SELECT MIN(d.date_depot) as date, 'Premier dépôt de rapport' as event
                    FROM deposer d
                    JOIN etudiants e ON d.num_etu = e.num_carte_etud
                    JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.id_annee_acad = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['date']) {
                    $events[] = $row;
                }
            }
        }
        catch (\PDOException $e) {
        // Silently continue
        }

        // Trier par date
        usort($events, function ($a, $b) {
            return strtotime($a['date'] ?? '0') - strtotime($b['date'] ?? '0');
        });

        return $events;
    }

    private function logAction($action, $statut)
    {
        try {
            if (isset($_SESSION['user_id'])) {
                $sql = "INSERT INTO pister (id_utilisateur, action, statut_action, nom_table, date_creation)
                        VALUES (?, ?, ?, 'archives', NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$_SESSION['user_id'], $action, $statut]);
            }
        }
        catch (\PDOException $e) {
        // Non-critical, ignore
        }
    }
}
