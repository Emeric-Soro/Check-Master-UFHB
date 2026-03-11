<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/GestionScolariteService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\GestionScolariteService;

class GestionScolariteController
{
    /** @var GestionScolariteService */
    private $service;

    public function __construct()
    {
        $this->service = new GestionScolariteService(Database::getConnection());
    }

    public function index()
    {
        // Récupérer toutes les listes de référence
        $referenceLists = $this->service->getReferenceLists();
        foreach ($referenceLists as $key => $value) {
            $GLOBALS[$key] = $value;
        }

        // Si un numéro d'étudiant est fourni, récupérer ses informations
        if (isset($_GET['num_etu'])) {
            $GLOBALS['etudiantInfo'] = $this->service->getInfoEtudiant($_GET['num_etu']);
        }

        // Si on est en mode modification, récupérer les informations du versement
        if (isset($_GET['action']) && $_GET['action'] === 'mettre_a_jour_versement' && isset($_GET['id'])) {
            $result = $this->service->getVersementModifiable($_GET['id']);
            if ($result['success']) {
                $GLOBALS['versementAModifier'] = $result['versement'];
            } else {
                $GLOBALS['messageErreur'] = $result['message'];
            }
        }

        // Traiter la soumission du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'enregistrer_versement':
                        $this->enregistrerVersement();
                        break;
                    case 'enregistrer_paiement':
                        $this->enregistrerPaiement();
                        break;
                    case 'mettre_a_jour_versement':
                        $this->mettreAJourVersement();
                        break;
                    case 'upload_fiche':
                        $this->uploadFicheInscription();
                        break;
                }
            }
        }

        // Récupérer la liste des versements (rafraîchir après un éventuel POST)
        $GLOBALS['listeVersement'] = $this->service->getReferenceLists()['listeVersement'];
    }

    public function enregistrerVersement()
    {
        if (!canCreate('gestion_scolarite') && !canEdit('gestion_scolarite')) {
            $GLOBALS['messageErreur'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            return;
        }
        $result = $this->service->enregistrerVersement($_POST, $_SESSION['id_utilisateur']);

        if ($result['success']) {
            $GLOBALS['messageSuccess'] = $result['message'];
            if ($result['data']) {
                $GLOBALS['montantTotal'] = $result['data']['montantTotal'];
                $GLOBALS['montantPaye'] = $result['data']['montantPaye'];
                $GLOBALS['resteAPayer'] = $result['data']['resteAPayer'];
            }
        } else {
            $GLOBALS['messageErreur'] = $result['message'];
        }
    }

    public function mettreAJourVersement()
    {
        if (!canEdit('gestion_scolarite')) {
            $GLOBALS['messageErreur'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            return;
        }
        $result = $this->service->mettreAJourVersement($_POST, $_SESSION['id_utilisateur']);

        if ($result['success']) {
            $GLOBALS['messageSuccess'] = $result['message'];
            if ($result['data']) {
                $GLOBALS['montantTotal'] = $result['data']['montantTotal'];
                $GLOBALS['montantPaye'] = $result['data']['montantPaye'];
                $GLOBALS['resteAPayer'] = $result['data']['resteAPayer'];
            }
        } else {
            $GLOBALS['messageErreur'] = $result['message'];
        }
    }

    public function enregistrerPaiement()
    {
        if (!canCreate('gestion_scolarite') && !canEdit('gestion_scolarite')) {
            $GLOBALS['messageErreur'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            return;
        }
        $result = $this->service->enregistrerPaiement($_POST, $_SESSION['id_utilisateur']);

        if ($result['success']) {
            $GLOBALS['messageSuccess'] = $result['message'];
            // Rafraîchir les listes si nécessaire (après inscription ou versement réussi)
            if ($result['refreshLists']) {
                $refreshed = $this->service->getReferenceLists();
                $GLOBALS['etudiantsInscrits'] = $refreshed['etudiantsInscrits'];
                $GLOBALS['etudiantsNonInscrits'] = $refreshed['etudiantsNonInscrits'];
                $GLOBALS['listeAllEtudiant'] = $refreshed['listeAllEtudiant'];
            }
        } else {
            $GLOBALS['messageErreur'] = $result['message'];
        }
    }

    public function uploadFicheInscription()
    {
        header('Content-Type: application/json');
        if (!canCreate('gestion_scolarite') && !canEdit('gestion_scolarite')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
            exit;
        }
        $result = $this->service->uploadFicheInscription($_POST, $_FILES, $_SESSION['id_utilisateur']);

        echo json_encode($result);
        exit;
    }
}
