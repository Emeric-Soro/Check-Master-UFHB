<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Scolarite.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/AuditLog.php';

require_once __DIR__ . '/../utils/CSRFProtection.php';
class GestionScolariteController {
    private $scolariteModel;
    private $anneeAcademique;
    private $auditLog;
    

    public function __construct() {
        $this->scolariteModel = new Scolarite(Database::getConnection());
        $this->anneeAcademique = new AnneeAcademique(Database::getConnection());
        $this->auditLog = new AuditLog(Database::getConnection());
    }

    public function index() {
        // Récupérer les étudiants non inscrits
        $GLOBALS['etudiantsNonInscrits'] = $this->scolariteModel->getEtudiantsNonInscrits();
        
        // Récupérer les niveaux d'études
        $GLOBALS['niveaux'] = $this->scolariteModel->getNiveauxEtudes();
        
        // Récupérer les étudiants déjà inscrits
        $GLOBALS['etudiantsInscrits'] = $this->scolariteModel->getEtudiantsInscrits();
        
        // Récupérer la liste complète des étudiants
        $GLOBALS['listeAllEtudiant'] = $this->scolariteModel->getAllEtudiants();
        
        // Récupérer les années académiques
        $GLOBALS['listeAnnees'] = $this->anneeAcademique->getAllAnneeAcademiques();

        // Si un numéro d'étudiant est fourni, récupérer ses informations
        if (isset($_GET['num_etu'])) {
            $GLOBALS['etudiantInfo'] = $this->scolariteModel->getInfoEtudiant($_GET['num_etu']);
        }

        // Si on est en mode modification, récupérer les informations du versement
        if (isset($_GET['action']) && $_GET['action'] === 'mettre_a_jour_versement' && isset($_GET['id'])) {
            $versement = $this->scolariteModel->getVersementById($_GET['id']);
            if ($versement && $versement['type_versement'] === 'Tranche') {
                $GLOBALS['versementAModifier'] = $versement;
            } else {
                $GLOBALS['messageErreur'] = "Ce versement ne peut pas être modifié.";
            }
        }

        // Traiter la soumission du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Valider le jeton CSRF
                CSRFProtection::verifyRequest();
                
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'enregistrer_versement':
                        $this->enregistrerVersement();
                        break;
                    case 'mettre_a_jour_versement':
                        $this->mettreAJourVersement();
                        break;
                    
                }
            }
        }

        // Récupérer la liste des versements
        $GLOBALS['listeVersement'] = $this->scolariteModel->getAllVersements();
    }

    public function enregistrerVersement() {
        try {
            // Validation des données
            if (empty($_POST['id_etudiant']) || empty($_POST['montant']) || empty($_POST['methode_paiement'])) {
                $GLOBALS['messageErreur'] = "Tous les champs sont obligatoires.";
                return;
            }

            // Récupérer l'inscription de l'étudiant
            $inscription = $this->scolariteModel->getInscriptionByEtudiantId($_POST['id_etudiant']);
            if (!$inscription) {
                $GLOBALS['messageErreur'] = "Aucune inscription trouvée pour cet étudiant.";
                return;
            }

            // Vérifier si l'étudiant a déjà soldé sa scolarité
            if ($inscription['reste_a_payer'] <= 0) {
                $GLOBALS['messageErreur'] = "Cet étudiant a déjà soldé sa scolarité.";
                return;
            }

            // Vérifier si le montant du versement ne dépasse pas le reste à payer
            $montant = floatval($_POST['montant']);
            if ($montant > $inscription['reste_a_payer']) {
                $GLOBALS['messageErreur'] = "Le montant du versement ne peut pas dépasser le reste à payer (" . number_format($inscription['reste_a_payer'], 2) . " FCFA).";
                return;
            }

            // Préparer les données du versement
            $data = [
                'id_inscription' => $inscription['id_inscription'],
                'montant' => $montant,
                'methode_paiement' => $_POST['methode_paiement']
            ];

            // Enregistrer le versement
            if ($this->scolariteModel->addVersement($data)) {
                // Audit logging
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'versements', 'Succès');
                
                // Récupérer les informations mises à jour
                $inscriptionMiseAJour = $this->scolariteModel->getInscriptionByEtudiantId($_POST['id_etudiant']);
                if ($inscriptionMiseAJour) {
                    $GLOBALS['montantTotal'] = $inscriptionMiseAJour['montant_scolarite'];
                    $GLOBALS['montantPaye'] = $inscriptionMiseAJour['montant_inscription'];
                    $GLOBALS['resteAPayer'] = $inscriptionMiseAJour['reste_a_payer'];
                }
                $GLOBALS['messageSuccess'] = "Versement enregistré avec succès.";
            } else {
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'versements', 'Erreur');
                $GLOBALS['messageErreur'] = "Erreur lors de l'enregistrement du versement.";
            }
        } catch (Exception $e) {
            error_log("Erreur dans enregistrerVersement : " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors de l'enregistrement du versement.";
        }
    }
    

    public function mettreAJourVersement() {
        try {
            // Validation des données
            if (empty($_POST['id_versement']) || empty($_POST['montant']) || empty($_POST['methode_paiement'])) {
                $GLOBALS['messageErreur'] = "Tous les champs sont obligatoires.";
                return;
            }

            // Récupérer le versement pour vérifier son type
            $versement = $this->scolariteModel->getVersementById($_POST['id_versement']);
            if (!$versement) {
                $GLOBALS['messageErreur'] = "Versement introuvable.";
                return;
            }

            // Vérifier si le versement est de type "Tranche"
            if ($versement['type_versement'] !== 'Tranche') {
                $GLOBALS['messageErreur'] = "Seuls les versements de type 'Tranche' peuvent être modifiés.";
                return;
            }

            // Récupérer l'inscription associée au versement
            $inscription = $this->scolariteModel->getInscriptionById($versement['id_inscription']);
            if (!$inscription) {
                $GLOBALS['messageErreur'] = "Inscription introuvable.";
                return;
            }

            $ancienMontant = floatval($versement['montant']);
            $nouveauMontant = floatval($_POST['montant']);
            $difference = $ancienMontant - $nouveauMontant;
        

            if ($ancienMontant != $nouveauMontant) {

                // Vérifier si le nouveau montant total ne dépasse pas le montant de scolarité
                $montantTotalPaye = floatval($inscription['montant_paye']) - $difference;
                $montantScolarite = floatval($inscription['montant_total']);
                
                if ($montantTotalPaye > $montantScolarite) {
                    $GLOBALS['messageErreur'] = "Le montant total des versements ne peut pas dépasser le montant de scolarité (" . number_format($montantScolarite, 2) . " FCFA).";
                    return;
                }

                // Préparer les données de mise à jour
                $data = [
                    'montant' => $nouveauMontant,
                    'difference' => $difference,
                    'methode_paiement' => $_POST['methode_paiement']
                ];

                // Mettre à jour le versement
                if ($this->scolariteModel->updateVersement($_POST['id_versement'], $data)) {
                    // Audit logging
                    $this->auditLog->logModification($_SESSION['id_utilisateur'], 'versement', 'Succès');
                    
                    // Récupérer les informations mises à jour
                    $inscriptionMiseAJour = $this->scolariteModel->getInscriptionById($inscription['id_inscription']);
                    if ($inscriptionMiseAJour) {
                        $GLOBALS['montantTotal'] = floatval($inscriptionMiseAJour['montant_scolarite'] ?? 0);
                        $GLOBALS['montantPaye'] = floatval($inscriptionMiseAJour['montant_paye'] ?? 0);
                        $GLOBALS['resteAPayer'] = floatval($inscriptionMiseAJour['reste_a_payer'] ?? 0);
                    }
                    
                    $GLOBALS['messageSuccess'] = "Versement mis à jour avec succès.";
                } else {
                    $this->auditLog->logModification($_SESSION['id_utilisateur'], 'versements', 'Erreur');
                    $GLOBALS['messageErreur'] = "Erreur lors de la mise à jour du versement.";
                }
            } else {
                // Si le montant n'a pas changé, on met juste à jour la méthode de paiement
                $data = [
                    'montant' => $_POST['montant'],
                    'difference' => $difference,
                    'methode_paiement' => $_POST['methode_paiement']
                ];

                if ($this->scolariteModel->updateVersement($_POST['id_versement'], $data)) {
                    $this->auditLog->logModification($_SESSION['id_utilisateur'], 'versements', 'Succès');
                    $GLOBALS['messageSuccess'] = "Méthode de paiement mise à jour avec succès.";
                } else {
                    $this->auditLog->logModification($_SESSION['id_utilisateur'], 'versements', 'Erreur');
                    $GLOBALS['messageErreur'] = "Erreur lors de la mise à jour de la méthode de paiement.";
                }
            }
        } catch (Exception $e) {
            error_log("Erreur dans mettreAJourVersement : " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors de la mise à jour du versement.";
        }
    }


}
