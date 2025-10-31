<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Scolarite.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/permissions.php';

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
        // Préparer les données pour la vue
        $data = [
            'etudiantsNonInscrits' => $this->scolariteModel->getEtudiantsNonInscrits(),
            'niveaux' => $this->scolariteModel->getNiveauxEtudes(),
            'etudiantsInscrits' => $this->scolariteModel->getEtudiantsInscrits(),
            'listeAllEtudiant' => $this->scolariteModel->getAllEtudiants(),
            'listeAnnees' => $this->anneeAcademique->getAllAnneeAcademiques(),
            'messageErreur' => '',
            'messageSuccess' => ''
        ];

        // Si un numéro d'étudiant est fourni, récupérer ses informations
        if (isset($_GET['num_etu'])) {
            $data['etudiantInfo'] = $this->scolariteModel->getInfoEtudiant($_GET['num_etu']);
        }

        // Si on est en mode modification, récupérer les informations du versement
        if (isset($_GET['action']) && $_GET['action'] === 'mettre_a_jour_versement' && isset($_GET['id'])) {
            $versement = $this->scolariteModel->getVersementById($_GET['id']);
            if ($versement && $versement['type_versement'] === 'Tranche') {
                $data['versementAModifier'] = $versement;
            } else {
                $data['messageErreur'] = "Ce versement ne peut pas être modifié.";
            }
        }

        // Traiter la soumission du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'enregistrer_versement':
                        $result = $this->enregistrerVersement();
                        $data = array_merge($data, $result);
                        break;
                    case 'mettre_a_jour_versement':
                        $result = $this->mettreAJourVersement();
                        $data = array_merge($data, $result);
                        break;
                    
                }
            }
        }

        // Récupérer la liste des versements
        $data['listeVersement'] = $this->scolariteModel->getAllVersements();
        
        return $data;
    }

    public function enregistrerVersement() {
        // Préparer le résultat
        $result = [
            'messageErreur' => '',
            'messageSuccess' => ''
        ];
        
        // Vérifier la permission CREATE
        if (!hasPermission('gestion_scolarite', 'CREATE')) {
            $result['messageErreur'] = "Vous n'avez pas la permission d'enregistrer des versements.";
            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'versements', 'Erreur - Permission refusée');
            return $result;
        }
        
        try {
            // Validation des données
            if (empty($_POST['id_etudiant']) || empty($_POST['montant']) || empty($_POST['methode_paiement'])) {
                $result['messageErreur'] = "Tous les champs sont obligatoires.";
                return $result;
            }

            // Récupérer l'inscription de l'étudiant
            $inscription = $this->scolariteModel->getInscriptionByEtudiantId($_POST['id_etudiant']);
            if (!$inscription) {
                $result['messageErreur'] = "Aucune inscription trouvée pour cet étudiant.";
                return $result;
            }

            // Vérifier si l'étudiant a déjà soldé sa scolarité
            if ($inscription['reste_a_payer'] <= 0) {
                $result['messageErreur'] = "Cet étudiant a déjà soldé sa scolarité.";
                return $result;
            }

            // Vérifier si le montant du versement ne dépasse pas le reste à payer
            $montant = floatval($_POST['montant']);
            if ($montant > $inscription['reste_a_payer']) {
                $result['messageErreur'] = "Le montant du versement ne peut pas dépasser le reste à payer (" . number_format($inscription['reste_a_payer'], 2) . " FCFA).";
                return $result;
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
                    $result['montantTotal'] = $inscriptionMiseAJour['montant_scolarite'];
                    $result['montantPaye'] = $inscriptionMiseAJour['montant_inscription'];
                    $result['resteAPayer'] = $inscriptionMiseAJour['reste_a_payer'];
                }
                $result['messageSuccess'] = "Versement enregistré avec succès.";
            } else {
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'versements', 'Erreur');
                $result['messageErreur'] = "Erreur lors de l'enregistrement du versement.";
            }
        } catch (Exception $e) {
            error_log("Erreur dans enregistrerVersement : " . $e->getMessage());
            $result['messageErreur'] = "Une erreur est survenue lors de l'enregistrement du versement.";
        }
        
        return $result;
    }
    

    public function mettreAJourVersement() {
        // Préparer le résultat
        $result = [
            'messageErreur' => '',
            'messageSuccess' => ''
        ];
        
        // Vérifier la permission UPDATE
        if (!hasPermission('gestion_scolarite', 'UPDATE')) {
            $result['messageErreur'] = "Vous n'avez pas la permission de mettre à jour des versements.";
            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'versements', 'Erreur - Permission refusée');
            return $result;
        }
        
        try {
            // Validation des données
            if (empty($_POST['id_versement']) || empty($_POST['montant']) || empty($_POST['methode_paiement'])) {
                $result['messageErreur'] = "Tous les champs sont obligatoires.";
                return $result;
            }

            // Récupérer le versement pour vérifier son type
            $versement = $this->scolariteModel->getVersementById($_POST['id_versement']);
            if (!$versement) {
                $result['messageErreur'] = "Versement introuvable.";
                return $result;
            }

            // Vérifier si le versement est de type "Tranche"
            if ($versement['type_versement'] !== 'Tranche') {
                $result['messageErreur'] = "Seuls les versements de type 'Tranche' peuvent être modifiés.";
                return $result;
            }

            // Récupérer l'inscription associée au versement
            $inscription = $this->scolariteModel->getInscriptionById($versement['id_inscription']);
            if (!$inscription) {
                $result['messageErreur'] = "Inscription introuvable.";
                return $result;
            }

            $ancienMontant = floatval($versement['montant']);
            $nouveauMontant = floatval($_POST['montant']);
            $difference = $ancienMontant - $nouveauMontant;
        

            if ($ancienMontant != $nouveauMontant) {

                // Vérifier si le nouveau montant total ne dépasse pas le montant de scolarité
                $montantTotalPaye = floatval($inscription['montant_paye']) - $difference;
                $montantScolarite = floatval($inscription['montant_total']);
                
                if ($montantTotalPaye > $montantScolarite) {
                    $result['messageErreur'] = "Le montant total des versements ne peut pas dépasser le montant de scolarité (" . number_format($montantScolarite, 2) . " FCFA).";
                    return $result;
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
                        $result['montantTotal'] = floatval($inscriptionMiseAJour['montant_scolarite'] ?? 0);
                        $result['montantPaye'] = floatval($inscriptionMiseAJour['montant_paye'] ?? 0);
                        $result['resteAPayer'] = floatval($inscriptionMiseAJour['reste_a_payer'] ?? 0);
                    }
                    
                    $result['messageSuccess'] = "Versement mis à jour avec succès.";
                } else {
                    $this->auditLog->logModification($_SESSION['id_utilisateur'], 'versements', 'Erreur');
                    $result['messageErreur'] = "Erreur lors de la mise à jour du versement.";
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
                    $result['messageSuccess'] = "Méthode de paiement mise à jour avec succès.";
                } else {
                    $this->auditLog->logModification($_SESSION['id_utilisateur'], 'versements', 'Erreur');
                    $result['messageErreur'] = "Erreur lors de la mise à jour de la méthode de paiement.";
                }
            }
        } catch (Exception $e) {
            error_log("Erreur dans mettreAJourVersement : " . $e->getMessage());
            $result['messageErreur'] = "Une erreur est survenue lors de la mise à jour du versement.";
        }
        
        return $result;
    }


}