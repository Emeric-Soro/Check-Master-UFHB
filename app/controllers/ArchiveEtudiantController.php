<?php
/**
 * ArchiveEtudiantController - Archives étudiants
 */
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

class ArchiveEtudiantController
{
    private $db;
    private $etudiantModel;
    private $anneeModel;

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getConnection();
        $this->etudiantModel = new Etudiant($this->db);
        $this->anneeModel = new AnneeAcademique($this->db);
    }

    /**
     * Liste des étudiants pour archive
     */
    public function index()
    {
        if (!canView('archives_etudiants')) {
            $_SESSION['error_message'] = "Accès refusé aux archives étudiants.";
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $anneeId = $_SESSION['archive_annee_acad'] ?? null;
        $filters = $_GET['filters'] ?? [];

        $etudiants = $this->getEtudiantsArchives($anneeId, $filters);
        $annees = $this->anneeModel->getAllAnneeAcademiques();
        $specialites = $this->getSpecialites();

        return [
            'etudiants' => $etudiants,
            'annees' => $annees,
            'specialites' => $specialites,
            'filters' => $filters,
        ];
    }

    /**
     * Fiche détaillée d'un étudiant
     */
    public function fiche($matricule)
    {
        if (!canView('archives_etudiants')) {
            $_SESSION['error_message'] = "Accès refusé.";
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $anneeId = $_SESSION['archive_annee_acad'] ?? null;

        $etudiant = $this->getEtudiantComplet($matricule, $anneeId);
        if (!$etudiant) {
            $_SESSION['error_message'] = "Étudiant non trouvé.";
            header('Location: layout.php?page=archives_etudiants');
            exit;
        }

        // Charger les données des onglets
        $parcours = $this->getParcoursEtudiant($matricule);
        $soutenances = $this->getSoutenancesEtudiant($matricule);
        $documents = $this->getDocumentsEtudiant($matricule);
        $reclamations = $this->getReclamationsEtudiant($matricule);

        $this->logAction('Consultation fiche étudiant: ' . $matricule, 'Succès');

        return [
            'etudiant' => $etudiant,
            'parcours' => $parcours,
            'soutenances' => $soutenances,
            'documents' => $documents,
            'reclamations' => $reclamations,
        ];
    }

    /**
     * Timeline du parcours étudiant
     */
    public function parcours($matricule)
    {
        if (!canView('archives_etudiants')) {
            $_SESSION['error_message'] = "Accès refusé.";
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $evenements = $this->getParcoursChronologique($matricule);

        return [
            'matricule' => $matricule,
            'evenements' => $evenements,
        ];
    }

    /**
     * Export CSV des étudiants
     */
    public function exportCsv()
    {
        if (!canCreate('archives_etudiants')) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission refusée']);
            exit;
        }

        $anneeId = $_SESSION['archive_annee_acad'] ?? null;
        $etudiants = $this->getEtudiantsArchives($anneeId, []);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="archives_etudiants_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Matricule', 'Nom', 'Prénom', 'Email', 'Promotion', 'Spécialité', 'Entreprise', 'Thème', 'Statut']);

        foreach ($etudiants as $e) {
            fputcsv($output, [
                $e->num_carte_etud,
                $e->nom_etu,
                $e->prenom_etu,
                $e->email_etu,
                $e->promotion_etu,
                $e->lib_specialite ?? '',
                $e->entreprise ?? '',
                $e->theme_rapport ?? '',
                $e->statut ?? 'En cours'
            ]);
        }

        fclose($output);
        exit;
    }

    // Méthodes privées

    private function getEtudiantsArchives($anneeId, $filters)
    {
        $sql = "SELECT DISTINCT 
                    e.num_carte_etud,
                    e.nom_etu,
                    e.prenom_etu,
                    e.email_etu,
                    e.promotion_etu,
                    g.libelle_genre,
                    en.lib_long_entreprise as entreprise,
                    re.theme_rapport as theme,
                    n.moyenne_M1,
                    n.moyenne_M2,
                    CASE 
                        WHEN v.decision_validation = 'valider' THEN 'Validé'
                        WHEN v.decision_validation = 'rejeter' THEN 'Rejeté'
                        ELSE 'En cours'
                    END as statut
                FROM etudiants e
                LEFT JOIN genre g ON e.id_genre = g.id_genre
                JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud
                LEFT JOIN niveau_etude ne ON i.id_niv_etude = ne.id_niv_etude
                LEFT JOIN informations_stage inf ON e.num_carte_etud = inf.num_etu
                LEFT JOIN entreprises en ON inf.id_entreprise = en.id_entreprise
                LEFT JOIN rapport_etudiants re ON e.num_carte_etud = re.num_etu
                LEFT JOIN notes n ON e.num_carte_etud = n.num_etu AND n.id_annee_acad = i.id_annee_acad
                LEFT JOIN valider v ON re.id_rapport = v.id_rapport
                WHERE i.id_annee_acad = ?";

        $params = [$anneeId];

        // NOTE: Le filtre de spécialité est désactivé car la table niveau_etude n'a plus de lien avec enseignants/specialite
        /*
        if (!empty($filters['specialite'])) {
            $sql .= " AND s.id_specialite = ?";
            $params[] = $filters['specialite'];
        }
        */
        if (!empty($filters['statut'])) {
            $sql .= " AND v.decision_validation = ?";
            $params[] = $filters['statut'];
        }

        $sql .= " ORDER BY e.nom_etu, e.prenom_etu";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getEtudiantComplet($matricule, $anneeId)
    {
        $sql = "SELECT e.*, g.libelle_genre, i.id_annee_acad
                FROM etudiants e
                LEFT JOIN genre g ON e.id_genre = g.id_genre
                LEFT JOIN inscriptions i ON e.num_carte_etud = i.num_carte_etud AND i.id_annee_acad = ?
                WHERE e.num_carte_etud = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$anneeId, $matricule]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    private function getParcoursEtudiant($matricule)
    {
        $sql = "SELECT i.*, ne.lib_niv_etude, n.moyenne_M1, n.moyenne_M2
                FROM inscriptions i
                JOIN niveau_etude ne ON i.id_niv_etude = ne.id_niv_etude
                LEFT JOIN notes n ON i.num_carte_etud = n.num_etu AND n.id_annee_acad = i.id_annee_acad
                WHERE i.num_carte_etud = ?
                ORDER BY i.date_inscription DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matricule]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getSoutenancesEtudiant($matricule)
    {
        $sql = "SELECT ps.*, s.lib_salle
                FROM programmer_soutenance ps
                LEFT JOIN salles s ON ps.id_salle = s.id_salle
                WHERE ps.num_etud = ?
                ORDER BY ps.date_soutenance DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matricule]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getDocumentsEtudiant($matricule)
    {
        $docs = [];

        // Rapports
        $sql = "SELECT 'rapport' as type, id_rapport as id, theme_rapport as titre, 
                       chemin_fichier as chemin, date_redaction_rapport as date
                FROM rapport_etudiants WHERE num_etu = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matricule]);
        $docs = array_merge($docs, $stmt->fetchAll(PDO::FETCH_OBJ));

        // Comptes rendus
        $sql = "SELECT 'compte_rendu' as type, id_CR as id, nom_CR as titre,
                       chemin_fichier_pdf as chemin, date_CR as date
                FROM compte_rendu WHERE num_etu = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matricule]);
        $docs = array_merge($docs, $stmt->fetchAll(PDO::FETCH_OBJ));

        return $docs;
    }

    private function getReclamationsEtudiant($matricule)
    {
        $sql = "SELECT r.*, sr.libelle_statut_reclamation
                FROM reclamations r
                LEFT JOIN statut_reclamation sr ON r.statut_reclamation = sr.id_statut_reclamation
                WHERE r.num_carte_etud = ?
                ORDER BY r.date_creation DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matricule]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function getParcoursChronologique($matricule)
    {
        $events = [];

        // Inscriptions
        $sql = "SELECT i.date_inscription as date, 'inscription' as type,
                       CONCAT('Inscription ', ne.lib_niv_etude) as titre,
                       i.statut_inscription as description
                FROM inscriptions i
                JOIN niveau_etude ne ON i.id_niv_etude = ne.id_niv_etude
                WHERE i.num_carte_etud = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matricule]);
        $events = array_merge($events, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // Candidatures
        $sql = "SELECT cs.date_candidature as date, 'candidature' as type,
                       'Candidature soutenance' as titre,
                       cs.statut_candidature as description
                FROM candidature_soutenance cs WHERE cs.num_etu = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matricule]);
        $events = array_merge($events, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // Rapports
        $sql = "SELECT re.date_redaction_rapport as date, 'depot_rapport' as type,
                       CONCAT('Dépôt rapport v', re.version) as titre,
                       re.theme_rapport as description
                FROM rapport_etudiants re WHERE re.num_etu = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matricule]);
        $events = array_merge($events, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // Soutenances
        $sql = "SELECT ps.date_soutenance as date, 'soutenance' as type,
                       'Soutenance' as titre,
                       CONCAT('Salle: ', s.lib_salle) as description
                FROM programmer_soutenance ps
                LEFT JOIN salles s ON ps.id_salle = s.id_salle
                WHERE ps.num_etud = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matricule]);
        $events = array_merge($events, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // Trier par date
        usort($events, function ($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        return $events;
    }

    private function getSpecialites()
    {
        $stmt = $this->db->query("SELECT id_specialite, lib_specialite FROM specialite ORDER BY lib_specialite");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function logAction($action, $statut)
    {
        if (isset($_SESSION['user_id'])) {
            $sql = "INSERT INTO pister (id_utilisateur, action, statut_action, nom_table, date_creation)
                    VALUES (?, ?, ?, 'archives', NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $action, $statut]);
        }
    }
}
