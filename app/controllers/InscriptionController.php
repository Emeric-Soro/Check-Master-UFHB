<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/InscriptionService.php';

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
                $GLOBALS['inscriptionAModifier'] = $inscription;

                // Inclure l'autoloader de Composer pour Dompdf
                require_once __DIR__ . '/../../vendor/autoload.php';

                // Démarrer la mise en mémoire tampon de sortie
                ob_start();

                // Inclure le fichier du modèle de reçu
                include __DIR__ . '/../../ressources/views/gestion_etudiants/recu_inscription.php';

                // Capturer le contenu de la mémoire tampon
                $html = ob_get_clean();

                // Instancier Dompdf avec options utiles
                if (class_exists('\Dompdf\Options')) {
                    $options = new \Dompdf\Options();
                    $options->set('isRemoteEnabled', true);
                    $dompdf = new \Dompdf\Dompdf($options);
                } else {
                    $dompdf = new \Dompdf\Dompdf();
                }

                // Définir le répertoire de base pour les ressources (chemin absolu)
                $basePathress = realpath(__DIR__ . '/../../public');
                if ($basePathress) {
                    $dompdf->setBasePath($basePathress);
                }

                // Charger le HTML
                $dompdf->loadHtml($html);

                // Définir la taille et l'orientation du papier
                $dompdf->setPaper('A4', 'landscape');

                // Rendre le PDF avec gestion d'erreur
                try {
                    $dompdf->render();
                    $dompdf->stream("recu_paiement_" . $inscription['id_inscription'] . ".pdf", array("Attachment" => false));
                } catch (Exception $e) {
                    error_log("Dompdf render error: " . $e->getMessage());
                    $GLOBALS['messageErreur'] = "Erreur lors de la génération du PDF : " . $e->getMessage();
                    $this->service->logPrint($_SESSION['id_utilisateur'], 'Erreur');
                }

                $this->service->logPrint($_SESSION['id_utilisateur'], 'Succès');

                exit;
            } else {
                $GLOBALS['messageErreur'] = "Inscription non trouvée.";
                $this->service->logPrint($_SESSION['id_utilisateur'], 'Erreur');
            }
        }

        // Gestion de la suppression d'inscription
        if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'supprimer' && isset($_GET['id'])) {
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
                        $this->traiterInscription();
                        break;
                    case 'modifier':
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
