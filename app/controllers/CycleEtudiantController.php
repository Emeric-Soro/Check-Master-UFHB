<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/CycleEtudiantService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\CycleEtudiantService;

/**
 * CycleEtudiantController – Interface wizard du Parcours Etudiant Complet.
 *
 * Accessible uniquement aux groupes admin (5) et admin_responsable_filiere (14).
 * L'etudiant (13) garde sa page candidature_soutenance.
 */
class CycleEtudiantController
{
    private $service;

    public function __construct()
    {
        if (!isset($_SESSION['id_utilisateur'])) {
            header('Location: page_connexion.php');
            exit;
        }

        $this->service = new CycleEtudiantService(Database::getConnection());
    }

    /**
     * Page principale : recherche + tableau de bord des etudiants.
     */
    public function index()
    {
        if (!canView('cycle_etudiant')) {
            $GLOBALS['cycle_error'] = 'Acces non autorise.';
            return;
        }

        $anneeAcad = $this->resolveAnneeAcad();

        $GLOBALS['cycle_etudiants'] = [];
        if ($anneeAcad > 0) {
            $GLOBALS['cycle_etudiants'] = $this->service->getProgressionGlobale($anneeAcad);
        }
        $GLOBALS['cycle_annee_acad'] = $anneeAcad;
    }

    /**
     * Vue wizard pour un etudiant specifique.
     */
    public function show()
    {
        if (!canView('cycle_etudiant')) {
            $GLOBALS['cycle_error'] = 'Acces non autorise.';
            return;
        }

        $numEtu = trim((string) ($_GET['id'] ?? ''));
        if ($numEtu === '') {
            header('Location: ?page=cycle_etudiant');
            exit;
        }

        $anneeAcad = $this->resolveAnneeAcad();

        $GLOBALS['cycle_num_etu'] = $numEtu;
        $GLOBALS['cycle_annee_acad'] = $anneeAcad;
        $GLOBALS['cycle_progression'] = $this->service->getProgressionComplete($numEtu, $anneeAcad);
    }

    /**
     * AJAX JSON : progression d'un etudiant.
     */
    public function getProgression()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!canView('cycle_etudiant')) {
            echo json_encode(['success' => false, 'message' => 'Acces non autorise.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $numEtu = trim((string) ($_GET['id'] ?? ''));
        $anneeAcad = (int) ($_GET['annee'] ?? ($_SESSION['selected_academic_year_id'] ?? 0));

        if ($numEtu === '' || $anneeAcad === 0) {
            echo json_encode(['success' => false, 'message' => 'Parametres manquants.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $progression = $this->service->getProgressionComplete($numEtu, $anneeAcad);
        echo json_encode(['success' => true, 'data' => $progression], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * AJAX JSON : contenu d'un onglet module.
     */
    public function getModule()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!canView('cycle_etudiant')) {
            echo json_encode(['success' => false, 'message' => 'Acces non autorise.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $numEtu = trim((string) ($_GET['id'] ?? ''));
        $module = trim((string) ($_GET['module'] ?? ''));

        if ($numEtu === '' || $module === '') {
            echo json_encode(['success' => false, 'message' => 'Parametres manquants.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $allowedModules = [
            'identite', 'inscription', 'stage', 'candidature',
            'rapport', 'pv_commission', 'planning', 'jury',
            'notes', 'pv_final',
        ];

        if (!in_array($module, $allowedModules, true)) {
            echo json_encode(['success' => false, 'message' => 'Module inconnu.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $data = $this->service->getPhaseDetails($numEtu, $module);
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * AJAX JSON : recherche d'etudiants (autocompletion).
     */
    public function searchEtudiants()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!canView('cycle_etudiant')) {
            echo json_encode(['success' => false, 'message' => 'Acces non autorise.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $q = trim((string) ($_GET['q'] ?? ''));
        $results = $this->service->rechercherEtudiants($q);
        echo json_encode(['success' => true, 'results' => $results], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * AJAX JSON : sauvegarder les donnees d'un onglet.
     */
    public function saveModule()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!canEdit('cycle_etudiant')) {
            echo json_encode(['success' => false, 'message' => 'Action non autorisee.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $module = trim((string) ($_POST['module'] ?? ''));
        $numEtu = trim((string) ($_POST['num_etu'] ?? ''));

        if ($module === '' || $numEtu === '') {
            echo json_encode(['success' => false, 'message' => 'Parametres manquants.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            switch ($module) {
                case 'identite':
                    $result = $this->saveIdentite($numEtu);
                    break;
                case 'inscription':
                    $result = $this->saveInscription($numEtu);
                    break;
                case 'stage':
                    $result = $this->saveStage($numEtu);
                    break;
                case 'candidature':
                    $result = $this->saveCandidature($numEtu);
                    break;
                case 'notes':
                    $result = $this->saveNotes($numEtu);
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Module non supporte: ' . $module], JSON_UNESCAPED_UNICODE);
                    exit;
            }

            if ($result['success'] ?? false) {
                // Mettre à jour le cache de progression
                $anneeAcad = (int) ($_POST['id_annee_acad'] ?? $this->resolveAnneeAcad());
                if ($anneeAcad > 0) {
                    $this->service->mettreAJourStatutCycle($numEtu, $anneeAcad);
                }
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    private function saveIdentite(string $numEtu): array
    {
        $nom = trim((string) ($_POST['nom_etu'] ?? ''));
        $prenom = trim((string) ($_POST['prenom_etu'] ?? ''));
        $genre = trim((string) ($_POST['id_genre'] ?? ''));

        if ($nom === '' || $prenom === '') {
            return ['success' => false, 'message' => 'Le nom et le prenom sont obligatoires.'];
        }

        $this->service->updateIdentite($numEtu, [
            'nom_etu' => $nom,
            'prenom_etu' => $prenom,
            'id_genre' => $genre,
        ]);

        return ['success' => true, 'message' => 'Identite mise a jour.'];
    }

    private function saveInscription(string $numEtu): array
    {
        $montant = (float) ($_POST['montant_versement'] ?? 0);
        $mode = trim((string) ($_POST['mode_paiement'] ?? ''));
        $anneeAcad = (int) ($_POST['id_annee_acad'] ?? $this->resolveAnneeAcad());

        if ($montant <= 0 || $mode === '') {
            return ['success' => false, 'message' => 'Montant et mode de paiement obligatoires.'];
        }

        $this->service->addVersement($numEtu, $anneeAcad, $montant, $mode);

        return ['success' => true, 'message' => 'Versement ajoute.'];
    }

    private function saveStage(string $numEtu): array
    {
        $entreprise = trim((string) ($_POST['entreprise'] ?? ''));
        $dateDebut = trim((string) ($_POST['date_debut'] ?? ''));
        $dateFin = trim((string) ($_POST['date_fin'] ?? ''));
        $theme = trim((string) ($_POST['theme_stage'] ?? ''));

        $this->service->updateStage($numEtu, [
            'entreprise' => $entreprise,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'theme_stage' => $theme,
        ]);

        return ['success' => true, 'message' => 'Stage mis a jour.'];
    }

    private function saveCandidature(string $numEtu): array
    {
        $statut = trim((string) ($_POST['statut_candidature'] ?? ''));

        $this->service->updateCandidature($numEtu, $statut);

        return ['success' => true, 'message' => 'Candidature mise a jour.'];
    }

    private function saveNotes(string $numEtu): array
    {
        $moyenneM1 = (float) ($_POST['moyenne_M1'] ?? 0);
        $moyenneM2 = (float) ($_POST['moyenne_M2'] ?? 0);

        $this->service->saveNotesM1M2($numEtu, $moyenneM1, $moyenneM2);

        return ['success' => true, 'message' => 'Notes mises a jour.'];
    }

    /**
     * Resout l'annee academique depuis la session ou la base de donnees.
     */
    private function resolveAnneeAcad(): int
    {
        // Depuis la session (selectionnee par l'utilisateur)
        if (!empty($_SESSION['selected_academic_year_id'])) {
            return (int) $_SESSION['selected_academic_year_id'];
        }

        // Fallback : derniere annee academique
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query("SELECT MAX(id_annee_acad) FROM annee_academique");
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
