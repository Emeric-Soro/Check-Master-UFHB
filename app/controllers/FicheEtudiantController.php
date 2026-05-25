<?php
/**
 * FicheEtudiantController – Gestion de la Fiche Etudiante Complete (10 onglets)
 * 
 * Point d'entree unique pour la page fiche_etudiant_complete.
 * Utilise EtudiantFicheService pour agreger les donnees des 10 onglets.
 * Supporte le chargement par onglet (AJAX ou redirection).
 */
require_once __DIR__ . '/../Services/EtudiantFicheService.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

class FicheEtudiantController
{
    private $db;
    private $service;
    private $etudiantModel;

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getConnection();
        $this->service = new EtudiantFicheService($this->db);
        $this->etudiantModel = new Etudiant($this->db);
    }

    /**
     * Point d'entree principal.
     *
     * Query params attendus :
     *   - id     : num_carte_etud (obligatoire)
     *   - onglet : onglet a afficher (optionnel, defaut: 'identite')
     *   - ajax   : si '1', retourne uniquement le contenu de l'onglet (JSON)
     *
     * @return array $data
     */
    public function index()
    {
        // Permission
        if (!canView('archives_etudiants')) {
            $_SESSION['error'] = "Acces refuse aux fiches etudiantes.";
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $matricule = $_GET['id'] ?? '';
        if ($matricule === '') {
            $_SESSION['error'] = "Matricule etudiant manquant.";
            header('Location: layout.php?page=archives_etudiants');
            exit;
        }

        $ongletActif = $_GET['onglet'] ?? 'identite';
        $anneeId = $_SESSION['archive_annee_acad'] ?? null;
        $isAjax = ($_GET['ajax'] ?? '') === '1';

        // Verification que l'etudiant existe
        $etudiant = $this->etudiantModel->getEtudiantById($matricule);
        if (!$etudiant) {
            $_SESSION['error'] = "Etudiant non trouve (matricule: $matricule).";
            header('Location: layout.php?page=archives_etudiants');
            exit;
        }

        // Chargement du profil complet
        $profile = $this->service->getCompleteProfile($matricule, $anneeId);

        if ($isAjax) {
            // Mode AJAX : retourne uniquement le contenu partiel de l'onglet
            $this->renderOngletAjax($profile, $matricule, $ongletActif);
            exit;
        }

        // Mode classique : retourne le tableau $data pour la vue
        $this->logAction('Consultation fiche complete: ' . $matricule, 'Succes');

        return [
            'profile'     => $profile,
            'matricule'   => $matricule,
            'onglet_actif' => $ongletActif,
        ];
    }

    /**
     * Charge les donnees pour un onglet specifique uniquement (AJAX).
     */
    private function renderOngletAjax($profile, $matricule, $onglet)
    {
        if (empty($profile)) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Profil non trouve.']);
            return;
        }

        // Seules les donnees de l'onglet demande
        $ongletKey = match ($onglet) {
            'identite'     => 'identite',
            'inscriptions' => 'inscriptions',
            'stage'        => 'stage',
            'rapport'      => 'rapport',
            'candidature'  => 'candidature',
            'soutenance'   => 'soutenance',
            'cr'           => 'cr',
            'notes'        => 'notes',
            'reclamations' => 'reclamations',
            'documents'    => 'documents',
            default        => null,
        };

        if ($ongletKey === null || !isset($profile[$ongletKey])) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Onglet invalide.']);
            return;
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success'  => true,
            'onglet'   => $onglet,
            'data'     => $profile[$ongletKey],
        ]);
    }

    /**
     * Log une action dans la table pister/audit.
     */
    private function logAction($action, $statut)
    {
        if (isset($_SESSION['id_utilisateur'])) {
            try {
                $sql = "INSERT INTO pister (id_utilisateur, action, statut_action, nom_table, date_creation)
                        VALUES (?, ?, ?, 'fiche_etudiant_complete', NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$_SESSION['id_utilisateur'], $action, $statut]);
            } catch (PDOException $e) {
                error_log("FicheEtudiantController::logAction erreur: " . $e->getMessage());
            }
        }
    }
}
