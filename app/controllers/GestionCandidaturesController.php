<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/GestionCandidaturesService.php';
require_once __DIR__ . '/../Core/Autoload.php';

use CheckMaster\Core\Session;
use CheckMaster\Services\GestionCandidaturesService;

class GestionCandidaturesController {
    private $service;

    public function __construct() {
        $db = Database::getConnection();
        $this->service = new GestionCandidaturesService($db);
    }
    
    public function index() {
        $GLOBALS['candidatures_soutenance'] = $this->service->getAllCandidatures();
    }

    // Méthode pour gérer l'examen d'une candidature
    public function examinerCandidature() {
        $examiner = $_GET['examiner'] ?? null;
        $id_candidature = $_GET['id_candidature'] ?? null;
        $etape = intval($_GET['etape'] ?? 1);
        $action = $_GET['action'] ?? '';

        // Session (centralisée)
        Session::start();

        // Gestion des actions - DOIT être avant tout output HTML
        if ($action === 'valider_etape' && $examiner) {
            $etapeValidee = $_POST['etape'] ?? '';
            $_SESSION['etapes_validation'][$examiner][$etapeValidee] = 'validé';
            
            // Audit logging
            $this->service->logValidation($_SESSION['id_utilisateur']);
            
            // Si c'est la dernière étape, aller au résumé final
            if ($etapeValidee == 3) {
                header("Location: ?page=gestion_candidatures_soutenance&examiner=$examiner&etape=4");
                exit;
            } else {
                // Passer à l'étape suivante
                header("Location: ?page=gestion_candidatures_soutenance&examiner=$examiner&etape=" . ($etape + 1));
                exit;
            }
        }

        if ($action === 'rejeter_etape' && $examiner) {
            $etapeRejetee = $_POST['etape'] ?? '';
            $_SESSION['etapes_validation'][$examiner][$etapeRejetee] = 'rejeté';
            
            // Audit logging
            $this->service->logRejet($_SESSION['id_utilisateur']);
            
            // Passer à l'étape suivante même en cas de rejet
            if ($etapeRejetee < 3) {
                header("Location: ?page=gestion_candidatures_soutenance&examiner=$examiner&etape=" . ($etapeRejetee + 1));
            } else {
                // Si c'est la dernière étape, aller au résumé
                header("Location: ?page=gestion_candidatures_soutenance&examiner=$examiner&etape=4");
            }
            exit;
        }

        // Nouvelle action pour envoyer les résultats
        if ($action === 'envoyer_resultats' && $examiner) {
            $writeGuard = $this->service->ensureWritableCandidature($examiner);
            if (!$writeGuard['success']) {
                $_SESSION['error'] = $writeGuard['message'];
                header("Location: ?page=gestion_candidatures_soutenance&examiner=$examiner&etape=4");
                exit;
            }
            $etapesValidation = $_SESSION['etapes_validation'][$examiner] ?? [];
            $this->service->envoyerResultatsFinaux($examiner, $_SESSION['login_utilisateur'], $etapesValidation);
            $this->service->logAction($_SESSION['id_utilisateur'], 'Envoi résultats');
            $_SESSION['email_envoye'][$examiner] = true;
            header("Location: ?page=gestion_candidatures_soutenance&examiner=$examiner&etape=4&email_envoye=1");
            exit;
        }

        // Vérifier si l'étape précédente a été validée/rejetée
        $etapePrecedenteValidee = false;
        if ($etape > 1 && $etape < 4) {
            $etapePrecedenteValidee = isset($_SESSION['etapes_validation'][$examiner][$etape - 1]);
        }

        // Si l'étape précédente n'est pas validée et qu'on n'est pas au résumé final, rediriger vers l'étape précédente
        if ($etape > 1 && $etape < 4 && !$etapePrecedenteValidee) {
            header("Location: ?page=gestion_candidatures_soutenance&examiner=$examiner&etape=" . ($etape - 1));
            exit;
        }

        // Initialiser les étapes validées/rejetées pour cet étudiant
        if ($examiner && !isset($_SESSION['etapes_validation'][$examiner])) {
            $_SESSION['etapes_validation'][$examiner] = [];
        }

        // Données de l'étudiant à examiner
        $etudiantData = null;
        $etapeData = null;

        if ($examiner) {
            // Trouver l'étudiant dans les candidatures
            $etudiantData = $this->service->getEtudiantByNumEtu($examiner);
            
            // Charger les données selon l'étape depuis le service
            if ($etudiantData) {
                $etapesValidation = $_SESSION['etapes_validation'][$examiner] ?? [];
                $etapeData = $this->service->getEtapeData($examiner, $etape, $etapesValidation);
            }
        }

        // Passer les données à la vue
        $GLOBALS['examiner'] = $examiner;
        $GLOBALS['etape'] = $etape;
        $GLOBALS['etudiantData'] = $etudiantData;
        $GLOBALS['etapeData'] = $etapeData;
    }

    // Nouvelle méthode pour récupérer le résumé de candidature
    public function afficherResumeCandidature($num_etu) {
        return $this->service->getResumeCandidature($num_etu);
    }

    public function getLastCandidatureByNumEtu($num_etu) {
        return $this->service->getLastCandidatureByNumEtu($num_etu);
    }
}
