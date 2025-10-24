<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Scolarite.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/permissions.php';


class InscriptionController
{
    private $db;
    private $scolarite;
    private $anneeAcademique;
    private $auditLog;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->scolarite = new Scolarite($this->db);
        $this->anneeAcademique = new AnneeAcademique($this->db);
        $this->auditLog = new AuditLog($this->db);
    }

    public function index()
    {
        // Récupérer les étudiants non inscrits
        $GLOBALS['etudiantsNonInscrits'] = $this->scolarite->getEtudiantsNonInscrits();

        // Récupérer les niveaux d'études
        $GLOBALS['niveaux'] = $this->scolarite->getNiveauxEtudes();

        // Récupérer les étudiants déjà inscrits
        $GLOBALS['etudiantsInscrits'] = $this->scolarite->getEtudiantsInscrits();

        // Récupérer les années académiques
        $GLOBALS['listeAnnees'] = $this->anneeAcademique->getAllAnneeAcademiques();

        // Si un numéro d'étudiant est fourni, récupérer ses informations
        if (isset($_GET['num_etu'])) {
            $GLOBALS['etudiantInfo'] = $this->scolarite->getInfoEtudiant($_GET['num_etu']);
        }

        // Si on est en mode modification, récupérer les informations de l'inscription
        if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'modifier' && isset($_GET['id'])) {
            $GLOBALS['inscriptionAModifier'] = $this->scolarite->getInscriptionById($_GET['id']);
            if ($GLOBALS['inscriptionAModifier']) {
                $GLOBALS['etudiantInfo'] = $this->scolarite->getInfoEtudiant($GLOBALS['inscriptionAModifier']['id_etudiant']);
            }
        }

        // Si on est en mode impression de recu, récupérer les informations de l'inscription
        if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'imprimer_recu' && isset($_GET['id_inscription'])) {
            $inscription = $this->scolarite->getInscriptionById($_GET['id_inscription']);
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
                    // Autoriser le chargement d'images distantes/HTTP (utile si vous utilisez des URLs absolues)
                    $options->set('isRemoteEnabled', true);
                    $dompdf = new \Dompdf\Dompdf($options);
                } else {
                    // Fallback si la classe Options n'est pas disponible
                    $dompdf = new \Dompdf\Dompdf();
                }

                // Définir le répertoire de base pour les ressources (chemin absolu)
                $basePathress = realpath(__DIR__ . '/../../public');
                if ($basePathress) {
                    $dompdf->setBasePath($basePathress);
                }

                // Charger le HTML
                $dompdf->loadHtml($html);

                // Définir la taille et l'orientation du papier (utiliser les valeurs anglaises attendues)
                // Utilisation d'A4 en paysage
                $dompdf->setPaper('A4', 'landscape');

                // Rendre le PDF avec gestion d'erreur pour logguer clairement les problèmes
                try {
                    $dompdf->render();
                    // Envoyer le PDF au navigateur (inline)
                    $dompdf->stream("recu_paiement_" . $inscription['id_inscription'] . ".pdf", array("Attachment" => false));
                } catch (Exception $e) {
                    // Logger l'erreur et afficher un message d'erreur convivial
                    error_log("Dompdf render error: " . $e->getMessage());
                    $GLOBALS['messageErreur'] = "Erreur lors de la génération du PDF : " . $e->getMessage();
                    $this->auditLog->logImpression($_SESSION['id_utilisateur'], 'inscriptions', 'Erreur');
                }

                $this->auditLog->logImpression($_SESSION['id_utilisateur'], 'inscriptions', 'Succès');

                exit;
            } else {
                $GLOBALS['messageErreur'] = "Inscription non trouvée.";
                $this->auditLog->logImpression($_SESSION['id_utilisateur'], 'inscriptions', 'Erreur');
            }
        }

        // Gestion de la suppression d'inscription
        if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'supprimer' && isset($_GET['id'])) {
            if ($this->scolarite->supprimerInscription($_GET['id'])) {
                $GLOBALS['messageSuccess'] = "Inscription supprimée avec succès.";
                $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'inscriptions', 'Succès');
            } else {
                $GLOBALS['messageErreur'] = "Erreur lors de la suppression de l'inscription.";
                $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'inscriptions', 'Erreur');
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
                $inscriptionAModifier = $this->scolarite->getInscriptionById($_GET['id']);
                // Peupler les informations de l'étudiant si l'inscription est trouvée
                if ($inscriptionAModifier) {
                    $GLOBALS['etudiantInfo'] = $this->scolarite->getInfoEtudiant($inscriptionAModifier['id_etudiant']);
                }
                $GLOBALS['inscriptionAModifier'] = $inscriptionAModifier;
            }


        }

        // Récupérer la liste mise à jour des étudiants inscrits après chaque action
        $GLOBALS['etudiantsInscrits'] = $this->scolarite->getEtudiantsInscrits();
    }

    private function traiterInscription()
    {
        // Vérifier la permission CREATE
        if (!hasPermission('gestion_scolarite', 'CREATE')) {
            $GLOBALS['messageErreur'] = "Vous n'avez pas la permission de créer des inscriptions.";
            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'inscriptions', 'Erreur - Permission refusée');
            return;
        }
        
        try {
            // Validation des données
            if (
                empty($_POST['etudiant']) || empty($_POST['niveau']) ||
                empty($_POST['premier_versement']) || empty($_POST['annee_academique']) ||
                empty($_POST['methode_paiement'])
            ) {
                $GLOBALS['messageErreur'] = "Tous les champs sont obligatoires.";
                return;
            }

            $id_etudiant = $_POST['etudiant'];
            $id_niveau = $_POST['niveau'];
            $id_annee_acad = $_POST['annee_academique'];
            $montant_premier_versement = floatval($_POST['premier_versement']);
            $nombre_tranches = isset($_POST['nombre_tranches']) ? intval($_POST['nombre_tranches']) : 1;
            // Nettoyer la valeur du reste à payer en supprimant tous les types d'espaces
            $reste_a_payer = str_replace([' ', ' ', ' ', ' '], '', $_POST['reste_payer']);
            $methode_paiement = $_POST['methode_paiement'];

            // Vérifier si l'étudiant est déjà inscrit pour cette année académique
            if ($this->scolarite->estEtudiantInscritPourAnnee($id_etudiant, $id_annee_acad)) {
                $GLOBALS['messageErreur'] = "Cet étudiant est déjà inscrit pour cette année académique.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "inscriptions", 'Erreur');
                return;
            }

            // Créer l'inscription avec le premier versement
            $id_inscription = $this->scolarite->creerInscription(
                $id_etudiant,
                $id_niveau,
                $id_annee_acad,
                $montant_premier_versement,
                $nombre_tranches,
                $reste_a_payer,
                $methode_paiement
            );

            if ($id_inscription) {
                // Si des tranches sont demandées, les créer
                if ($nombre_tranches > 1) {
                    $montant_total = $this->scolarite->getMontantScolarite($id_niveau);
                    $reste_a_payer = $montant_total - $montant_premier_versement;
                    $montant_tranche = $reste_a_payer / ($nombre_tranches - 1);

                    // Calculer la date de la première échéance (3 mois après l'inscription)
                    $date_echeance = date('Y-m-d', strtotime('+3 months'));

                    // Créer les échéances
                    for ($i = 1; $i < $nombre_tranches; $i++) {
                        $this->scolarite->creerEcheance($id_inscription, $montant_tranche, $date_echeance);
                        $date_echeance = date('Y-m-d', strtotime($date_echeance . ' +3 months'));
                    }
                }

                $GLOBALS['messageSuccess'] = "Inscription créée avec succès.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "inscriptions", 'Succès');
            } else {
                $GLOBALS['messageErreur'] = "Erreur lors de la création de l'inscription.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "inscriptions", 'Erreur');
            }
        } catch (Exception $e) {
            $GLOBALS['messageErreur'] = "Une erreur est survenue : " . $e->getMessage();
        }
    }

    private function modifierInscription()
    {
        // Vérifier la permission UPDATE
        if (!hasPermission('gestion_scolarite', 'UPDATE')) {
            $GLOBALS['messageErreur'] = "Vous n'avez pas la permission de modifier des inscriptions.";
            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'inscriptions', 'Erreur - Permission refusée');
            return;
        }
        
        try {
            if (empty($_POST['id_inscription']) || empty($_POST['niveau']) || empty($_POST['premier_versement'])) {
                $GLOBALS['messageErreur'] = "Tous les champs sont obligatoires.";
                return;
            }

            $id_inscription = $_POST['id_inscription'];
            $id_annee_acad = $_POST['annee_academique'];
            $id_niveau = $_POST['niveau'];
            $montant_premier_versement = floatval($_POST['premier_versement']);
            $nombre_tranches = isset($_POST['nombre_tranches']) ? intval($_POST['nombre_tranches']) : 1;
            $methode_paiement = $_POST['methode_paiement'];

            // Mettre à jour l'inscription
            if ($this->scolarite->modifierInscription($id_inscription, $id_niveau, $id_annee_acad, $montant_premier_versement, $nombre_tranches, $methode_paiement)) {
                // Supprimer les anciennes échéances
                $this->scolarite->supprimerEcheances($id_inscription);

                // Créer les nouvelles échéances si nécessaire
                if ($nombre_tranches > 1) {
                    $montant_total = $this->scolarite->getMontantScolarite($id_niveau);
                    $reste_a_payer = $montant_total - $montant_premier_versement;
                    $montant_tranche = $reste_a_payer / ($nombre_tranches - 1);

                    $date_echeance = date('Y-m-d', strtotime('+3 months'));
                    for ($i = 1; $i < $nombre_tranches; $i++) {
                        $this->scolarite->creerEcheance($id_inscription, $montant_tranche, $date_echeance);
                        $date_echeance = date('Y-m-d', strtotime($date_echeance . ' +3 months'));
                    }
                }

                $GLOBALS['messageSuccess'] = "Inscription modifiée avec succès.";
                $this->auditLog->logModification($_SESSION['id_utilisateur'], "inscriptions", 'Succès');
            } else {
                $GLOBALS['messageErreur'] = "Erreur lors de la modification de l'inscription.";
                $this->auditLog->logModification($_SESSION['id_utilisateur'], "inscriptions", 'Erreur');
            }
        } catch (Exception $e) {
            $GLOBALS['messageErreur'] = "Une erreur est survenue : " . $e->getMessage();
        }
    }
    private function getEtudiantInfo()
    {
        if (isset($_GET['num_etu'])) {
            $etudiant = $this->scolarite->getInfoEtudiant($_GET['num_etu']);
            if ($etudiant) {
                echo json_encode([
                    'success' => true,
                    'etudiant' => $etudiant
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Étudiant non trouvé'
                ]);
            }
            exit;
        }
    }
}