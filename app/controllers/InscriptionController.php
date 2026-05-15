<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/InscriptionService.php';
require_once __DIR__ . '/../Support/Database.php';
require_once __DIR__ . '/../Services/Document/RecuGeneratorService.php';
require_once __DIR__ . '/../utils/RecuDataUtils.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\InscriptionService;

class InscriptionController
{
    private $service;

    public function __construct()
    {
        $db = Database::getConnection();
        $this->service = new InscriptionService($db);
    }

    public function index()
    {
        // Vérification permission d'accès à la page inscription/scolarité
        if (!canView('gestion_scolarite')) {
            $_SESSION['error'] = "Accès non autorisé à la gestion des inscriptions.";
            header('Location: layout.php?page=dashboard');
            exit;
        }

        // Populate page data from service
        $data = $this->service->getIndexData($_GET);
        $GLOBALS['etudiantsNonInscrits'] = $data['etudiantsNonInscrits'];
        $GLOBALS['niveaux'] = $data['niveaux'];
        $GLOBALS['etudiantsInscrits'] = $data['etudiantsInscrits'];
        $GLOBALS['listeAnnees'] = $data['listeAnnees'];
        if ($data['etudiantInfo'] !== null) {
            $GLOBALS['etudiantInfo'] = $data['etudiantInfo'];
        }
        if ($data['inscriptionAModifier'] !== null) {
            $GLOBALS['inscriptionAModifier'] = $data['inscriptionAModifier'];
        }

        // Si on est en mode impression de recu, récupérer les informations de l'inscription
        if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'imprimer_recu' && isset($_GET['id_inscription'])) {
            $inscription = $this->service->getInscriptionForReceipt($_GET['id_inscription']);
            if ($inscription) {
                // Générer un recu simple en utilisant les services de documents
                try {
                    $db = new \App\Support\Database();
                    $recuDataUtils = new \App\Utils\RecuDataUtils($db);
                    $pdfGenerator = new \App\Services\Document\PdfGeneratorService(
                        __DIR__ . '/../../storage',
                        __DIR__ . '/../../public/assets/img/logo.png'
                    );
                    $recuService = new \App\Services\Document\RecuGeneratorService($pdfGenerator, $recuDataUtils, $db);

                    // Parser l'ID composite (format: num_carte_etud-id_annee_acad-num_versement)
                    $id_inscription = (string) $_GET['id_inscription'];
                    $parts = explode('-', $id_inscription);
                    if (count($parts) >= 3) {
                        $result = $recuService->generate($id_inscription, (int) $_SESSION['id_utilisateur']);
                        if ($result['success'] && !empty($result['path']) && file_exists($result['path'])) {
                            header('Content-Type: application/pdf');
                            header('Content-Disposition: inline; filename="recu_' . ($inscription['num_carte_etud'] ?? 'inconnu') . '_' . ($inscription['id_annee_acad'] ?? 'inconnu') . '.pdf"');
                            header('Content-Length: ' . filesize($result['path']));
                            readfile($result['path']);
                            $this->service->logPrint($_SESSION['id_utilisateur'], 'Succès');
                            exit;
                        }
                    }

                    // Si pas de versement ou erreur, logger l'erreur
                    throw new Exception('Impossible de générer le recu PDF: versement non trouvé ou erreur');

                } catch (Exception $e) {
                    error_log('Erreur lors de la génération du recu: ' . $e->getMessage());
                    $GLOBALS['messageErreur'] = 'Erreur lors de la génération du recu: ' . $e->getMessage();
                    $this->service->logPrint($_SESSION['id_utilisateur'], 'Erreur');
                }
            } else {
                $GLOBALS['messageErreur'] = "Inscription non trouvée.";
                $this->service->logPrint($_SESSION['id_utilisateur'], 'Erreur');
            }
        }

        // Gestion de la suppression d'inscription
        if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'supprimer' && isset($_GET['id'])) {
            if (!canDelete('gestion_scolarite')) {
                $_SESSION['error'] = "Accès non autorisé pour supprimer une inscription.";
                header('Location: layout.php?page=gestion_scolarite');
                exit;
            }
            $result = $this->service->supprimerInscription($_GET['id'], $_SESSION['id_utilisateur']);
            if ($result['success']) {
                $GLOBALS['messageSuccess'] = $result['message'];
            } else {
                $GLOBALS['messageErreur'] = $result['message'];
            }
        }

        // Traiter la soumission du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['modalAction'])) {
                switch ($_POST['modalAction']) {
                    case 'inscrire':
                        if (!canCreate('gestion_scolarite')) {
                            $_SESSION['error'] = "Accès non autorisé pour inscrire un étudiant.";
                            header('Location: layout.php?page=gestion_scolarite');
                            exit;
                        }
                        $this->traiterInscription();
                        break;
                    case 'modifier':
                        if (!canEdit('gestion_scolarite')) {
                            $_SESSION['error'] = "Accès non autorisé pour modifier une inscription.";
                            header('Location: layout.php?page=gestion_scolarite');
                            exit;
                        }
                        $this->modifierInscription();
                        break;
                }
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'get_etudiant_info':
                        $this->getEtudiantInfo();
                        break;
                    // Add other GET actions here if needed
                }
            }


            // Si un ID est passé pour modification, récupérer les données de l'inscription
            if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'modifier' && isset($_GET['id'])) {
                $indexData = $this->service->getIndexData($_GET);
                if ($indexData['inscriptionAModifier']) {
                    $GLOBALS['etudiantInfo'] = $indexData['etudiantInfo'];
                }
                $GLOBALS['inscriptionAModifier'] = $indexData['inscriptionAModifier'];
            }


        }

        // Récupérer la liste mise à jour des étudiants inscrits après chaque action
        $GLOBALS['etudiantsInscrits'] = $this->service->getEtudiantsInscrits();
    }

    private function traiterInscription()
    {
        $result = $this->service->traiterInscription($_POST, $_SESSION['id_utilisateur']);
        if ($result['success']) {
            $GLOBALS['messageSuccess'] = $result['message'];
        } else {
            $GLOBALS['messageErreur'] = $result['message'];
        }
    }

    private function modifierInscription()
    {
        $result = $this->service->modifierInscription($_POST, $_SESSION['id_utilisateur']);
        if ($result['success']) {
            $GLOBALS['messageSuccess'] = $result['message'];
        } else {
            $GLOBALS['messageErreur'] = $result['message'];
        }
    }

    private function getEtudiantInfo()
    {
        if (isset($_GET['num_etu'])) {
            $result = $this->service->getEtudiantInfo($_GET['num_etu']);
            echo json_encode($result);
            exit;
        }
    }
}
