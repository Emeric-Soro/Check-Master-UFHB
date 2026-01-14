<?php

namespace App\Controllers;

use PDO;
use App\Models\Scolarite;
use App\Models\AnneeAcademique;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Valitron\Validator;

/**
 * GestionScolariteController - Gestion financière des étudiants
 * 
 * Ce contrôleur gère les versements et le suivi financier des étudiants :
 * - Enregistrement de nouveaux versements
 * - Mise à jour des versements existants
 * - Consultation des informations financières
 * 
 * @package App\Controllers
 */
class GestionScolariteController
{
    private PDO $pdo;
    private Scolarite $scolariteModel;
    private AnneeAcademique $anneeAcademique;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        Scolarite $scolariteModel,
        AnneeAcademique $anneeAcademique,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->scolariteModel = $scolariteModel;
        $this->anneeAcademique = $anneeAcademique;
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Vérification centralisée des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'gestion_scolarite', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur gestion_scolarite"
            );
            
            $GLOBALS['messageErreur'] = "Vous n'avez pas les droits nécessaires pour effectuer cette action.";
            http_response_code(403);
            
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            
            return false;
        }
        
        return true;
    }

    /**
     * Action principale : Affichage de la page de gestion scolarité (READ)
     */
    public function index(): void
    {
        // Vérification des permissions de lecture
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
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
                $numEtu = $this->security->sanitizeInput($_GET['num_etu']);
                $GLOBALS['etudiantInfo'] = $this->scolariteModel->getInfoEtudiant($numEtu);
            }

            // Si on est en mode modification, récupérer les informations du versement
            if (isset($_GET['action']) && $_GET['action'] === 'mettre_a_jour_versement' && isset($_GET['id'])) {
                $this->handleModificationMode($_GET['id']);
            }

            // Traiter la soumission du formulaire
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->handlePostActions();
            }

            // Récupérer la liste des versements
            $GLOBALS['listeVersement'] = $this->scolariteModel->getAllVersements();
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur dans GestionScolariteController::index(): " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors du chargement des données.";
        }
    }

    /**
     * Gère le mode modification (récupération du versement à modifier)
     */
    private function handleModificationMode(string $hashedId): void
    {
        // Décoder l'ID si nécessaire (ou utiliser directement si non hashé)
        $idVersement = is_numeric($hashedId) ? (int)$hashedId : $this->security->decodeId($hashedId);
        
        if (!$idVersement) {
            $GLOBALS['messageErreur'] = "ID de versement invalide.";
            return;
        }
        
        $versement = $this->scolariteModel->getVersementById($idVersement);
        
        if ($versement && $versement['type_versement'] === 'Tranche') {
            $GLOBALS['versementAModifier'] = $versement;
        } else {
            $GLOBALS['messageErreur'] = "Ce versement ne peut pas être modifié.";
        }
    }

    /**
     * Gère toutes les actions POST
     */
    private function handlePostActions(): void
    {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'enregistrer_versement':
                $this->enregistrerVersement();
                break;
            case 'mettre_a_jour_versement':
                $this->mettreAJourVersement();
                break;
        }
    }

    /**
     * Action : Enregistrer un nouveau versement (CREATE)
     */
    public function enregistrerVersement(): void
    {
        // Vérification des permissions de création
        if (!$this->checkPermission('create')) {
            return;
        }

        try {
            // Validation avec Valitron
            $v = new Validator($_POST);
            $v->rule('required', ['id_etudiant', 'montant', 'methode_paiement']);
            $v->rule('numeric', 'montant');
            $v->rule('min', 'montant', 0);
            
            if (!$v->validate()) {
                $GLOBALS['messageErreur'] = "Tous les champs sont obligatoires.";
                return;
            }

            $idEtudiant = $this->security->sanitizeInput($_POST['id_etudiant']);
            $montant = floatval($_POST['montant']);
            $methodePaiement = $this->security->sanitizeInput($_POST['methode_paiement']);

            // Récupérer l'inscription de l'étudiant
            $inscription = $this->scolariteModel->getInscriptionByEtudiantId($idEtudiant);
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
            if ($montant > $inscription['reste_a_payer']) {
                $GLOBALS['messageErreur'] = "Le montant du versement ne peut pas dépasser le reste à payer (" . 
                    number_format($inscription['reste_a_payer'], 2) . " FCFA).";
                return;
            }

            // Préparer les données du versement
            $data = [
                'id_inscription' => $inscription['id_inscription'],
                'montant' => $montant,
                'methode_paiement' => $methodePaiement
            ];

            // Enregistrer le versement
            if ($this->scolariteModel->addVersement($data)) {
                $this->auditLog->logCreation($_SESSION['id_utilisateur'] ?? 0, 'versements', 'Succès');
                $this->logger->info("Versement créé: {$montant} FCFA pour étudiant {$idEtudiant}");
                
                // Récupérer les informations mises à jour
                $inscriptionMiseAJour = $this->scolariteModel->getInscriptionByEtudiantId($idEtudiant);
                if ($inscriptionMiseAJour) {
                    $GLOBALS['montantTotal'] = $inscriptionMiseAJour['montant_scolarite'];
                    $GLOBALS['montantPaye'] = $inscriptionMiseAJour['montant_inscription'];
                    $GLOBALS['resteAPayer'] = $inscriptionMiseAJour['reste_a_payer'];
                }
                
                $GLOBALS['messageSuccess'] = "Versement enregistré avec succès.";
            } else {
                $this->auditLog->logCreation($_SESSION['id_utilisateur'] ?? 0, 'versements', 'Erreur');
                $GLOBALS['messageErreur'] = "Erreur lors de l'enregistrement du versement.";
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur dans enregistrerVersement: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors de l'enregistrement du versement.";
        }
    }

    /**
     * Action : Mettre à jour un versement existant (UPDATE)
     */
    public function mettreAJourVersement(): void
    {
        // Vérification des permissions de modification
        if (!$this->checkPermission('update')) {
            return;
        }

        try {
            // Validation avec Valitron
            $v = new Validator($_POST);
            $v->rule('required', ['id_versement', 'montant', 'methode_paiement']);
            $v->rule('numeric', 'montant');
            
            if (!$v->validate()) {
                $GLOBALS['messageErreur'] = "Tous les champs sont obligatoires.";
                return;
            }

            $idVersement = (int)$_POST['id_versement'];
            $nouveauMontant = floatval($_POST['montant']);
            $methodePaiement = $this->security->sanitizeInput($_POST['methode_paiement']);

            // Récupérer le versement pour vérifier son type
            $versement = $this->scolariteModel->getVersementById($idVersement);
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
            $difference = $ancienMontant - $nouveauMontant;

            if ($ancienMontant != $nouveauMontant) {
                // Vérifier si le nouveau montant total ne dépasse pas le montant de scolarité
                $montantTotalPaye = floatval($inscription['montant_paye']) - $difference;
                $montantScolarite = floatval($inscription['montant_total']);
                
                if ($montantTotalPaye > $montantScolarite) {
                    $GLOBALS['messageErreur'] = "Le montant total des versements ne peut pas dépasser le montant de scolarité (" . 
                        number_format($montantScolarite, 2) . " FCFA).";
                    return;
                }
            }

            // Préparer les données de mise à jour
            $data = [
                'montant' => $nouveauMontant,
                'difference' => $difference,
                'methode_paiement' => $methodePaiement
            ];

            // Mettre à jour le versement
            if ($this->scolariteModel->updateVersement($idVersement, $data)) {
                $this->auditLog->logModification($_SESSION['id_utilisateur'] ?? 0, 'versements', 'Succès');
                $this->logger->info("Versement modifié: ID {$idVersement}, nouveau montant {$nouveauMontant} FCFA");
                
                // Récupérer les informations mises à jour
                $inscriptionMiseAJour = $this->scolariteModel->getInscriptionById($inscription['id_inscription']);
                if ($inscriptionMiseAJour) {
                    $GLOBALS['montantTotal'] = floatval($inscriptionMiseAJour['montant_scolarite'] ?? 0);
                    $GLOBALS['montantPaye'] = floatval($inscriptionMiseAJour['montant_paye'] ?? 0);
                    $GLOBALS['resteAPayer'] = floatval($inscriptionMiseAJour['reste_a_payer'] ?? 0);
                }
                
                $GLOBALS['messageSuccess'] = "Versement mis à jour avec succès.";
            } else {
                $this->auditLog->logModification($_SESSION['id_utilisateur'] ?? 0, 'versements', 'Erreur');
                $GLOBALS['messageErreur'] = "Erreur lors de la mise à jour du versement.";
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur dans mettreAJourVersement: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors de la mise à jour du versement.";
        }
    }
}