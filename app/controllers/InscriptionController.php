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
                require_once __DIR__ . '/../../vendor/autoload.php';
                require_once __DIR__ . '/../utils/DocumentGeneratorService.php';
                require_once __DIR__ . '/../utils/ReceiptUtils.php';

                try {
                    // Calculer les montants et dates
                    $montantTotal = $inscription['montant_total'] ?? 0;
                    $montantPaye = $inscription['montant_premier_versement'] ?? 0;
                    $nombreTranches = $inscription['nombre_tranche'] ?? 1;
                    $resteAPayer = $montantTotal - $montantPaye;
                    $prochainVersement = ReceiptUtils::calculerProchainVersement($montantTotal, $montantPaye, $nombreTranches);
                    $dateProchainVersement = ReceiptUtils::calculerDateProchainVersement($inscription['date_inscription'] ?? date('Y-m-d'), $nombreTranches);
                    
                    // Formater l'année académique
                    $anneeAcademique = date('Y', strtotime($inscription['date_deb'] ?? date('Y-m-d'))) . '-' . 
                                      date('Y', strtotime($inscription['date_fin'] ?? date('Y-m-d', strtotime('+1 year'))));
                    
                    // Préparer les données pour le template
                    $templateData = [
                        'id_inscription' => $inscription['id_inscription'] ?? '',
                        'nom_etudiant' => $inscription['nom_etudiant'] ?? '',
                        'prenom_etudiant' => $inscription['prenom_etudiant'] ?? '',
                        'nom_niveau' => $inscription['nom_niveau'] ?? 'N/A',
                        'annee_academique' => $anneeAcademique,
                        'montant_total' => number_format($montantTotal, 0, ',', ' '),
                        'montant_paye' => number_format($montantPaye, 0, ',', ' '),
                        'reste_a_payer' => number_format($resteAPayer, 0, ',', ' '),
                        'methode_paiement' => $inscription['methode_paiement'] ?? '',
                        'date_inscription' => date('d/m/Y', strtotime($inscription['date_inscription'] ?? date('Y-m-d'))),
                        'nombre_tranche' => $nombreTranches,
                        'prochain_versement' => number_format($prochainVersement, 0, ',', ' '),
                        'date_prochain_versement' => $dateProchainVersement
                    ];
                    
                    // Utiliser DocumentGeneratorService
                    $documentService = new DocumentGeneratorService();
                    $pdfPath = $documentService->generateFromTemplate('recu_inscription', $templateData);
                    
                    // Envoyer le PDF au navigateur
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: inline; filename="recu_paiement_' . $inscription['id_inscription'] . '.pdf"');
                    header('Content-Length: ' . filesize($pdfPath));
                    readfile($pdfPath);
                    
                    // Nettoyer le fichier temporaire
                    $documentService->cleanupTempFile($pdfPath);
                    
                    $this->auditLog->logImpression($_SESSION['id_utilisateur'], 'inscriptions', 'Succès');
                    exit;
                    
                } catch (Exception $e) {
                    error_log("PDF generation error: " . $e->getMessage());
                    $GLOBALS['messageErreur'] = "Erreur lors de la génération du PDF : " . $e->getMessage();
                    $this->auditLog->logImpression($_SESSION['id_utilisateur'], 'inscriptions', 'Erreur');
                }
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