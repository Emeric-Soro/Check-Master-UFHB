<?php

namespace App\Controllers;

use PDO;
use App\Models\Scolarite;
use App\Models\AnneeAcademique;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;

/**
 * InscriptionController - Inscription administrative annuelle (Paiement 1er versement)
 * 
 * Ce contrôleur gère les inscriptions administratives :
 * - Liste des étudiants inscrits et non inscrits
 * - Inscription administrative avec premier versement
 * - Modification et suppression d'inscriptions
 * - Impression des reçus
 * 
 * @package App\Controllers
 */
class InscriptionController
{
    private PDO $db;
    private Scolarite $scolarite;
    private AnneeAcademique $anneeAcademique;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $db,
        Scolarite $scolarite,
        AnneeAcademique $anneeAcademique,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->db = $db;
        $this->scolarite = $scolarite;
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
        
        if (!$this->security->can($idGroupe, 'gestion_etudiants', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur gestion_etudiants (inscription)"
            );
            
            $GLOBALS['messageErreur'] = "Vous n'avez pas les droits nécessaires pour effectuer cette action.";
            
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                http_response_code(403);
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            
            return false;
        }
        
        return true;
    }

    /**
     * Action : Afficher la page des inscriptions (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
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
                $numEtu = $this->security->sanitizeInput($_GET['num_etu']);
                $GLOBALS['etudiantInfo'] = $this->scolarite->getInfoEtudiant($numEtu);
            }

            // Si on est en mode modification, récupérer les informations de l'inscription
            if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'modifier' && isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $GLOBALS['inscriptionAModifier'] = $this->scolarite->getInscriptionById($id);
                if ($GLOBALS['inscriptionAModifier']) {
                    $GLOBALS['etudiantInfo'] = $this->scolarite->getInfoEtudiant($GLOBALS['inscriptionAModifier']['id_etudiant']);
                }
            }

            // Gérer l'impression de reçu
            if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'imprimer_recu' && isset($_GET['id_inscription'])) {
                $this->imprimerRecu((int)$_GET['id_inscription']);
                return;
            }

            // Gestion de la suppression d'inscription
            if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'supprimer' && isset($_GET['id'])) {
                $this->supprimerInscription((int)$_GET['id']);
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
                if (isset($_GET['action']) && $_GET['action'] === 'get_etudiant_info') {
                    $this->getEtudiantInfo();
                    return;
                }

                // Si un ID est passé pour modification
                if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'modifier' && isset($_GET['id'])) {
                    $id = (int)$_GET['id'];
                    $inscriptionAModifier = $this->scolarite->getInscriptionById($id);
                    if ($inscriptionAModifier) {
                        $GLOBALS['etudiantInfo'] = $this->scolarite->getInfoEtudiant($inscriptionAModifier['id_etudiant']);
                    }
                    $GLOBALS['inscriptionAModifier'] = $inscriptionAModifier;
                }
            }

            // Récupérer la liste mise à jour des étudiants inscrits après chaque action
            $GLOBALS['etudiantsInscrits'] = $this->scolarite->getEtudiantsInscrits();
            
        } catch (Exception $e) {
            $this->logger->error("Erreur dans InscriptionController::index: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors du chargement des données.";
        }
    }

    /**
     * Supprime une inscription (DELETE)
     */
    private function supprimerInscription(int $id): void
    {
        if (!$this->checkPermission('delete')) {
            return;
        }

        try {
            if ($this->scolarite->supprimerInscription($id)) {
                $GLOBALS['messageSuccess'] = "Inscription supprimée avec succès.";
                $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'inscriptions', 'Succès');
                $this->logger->info("Inscription {$id} supprimée");
            } else {
                $GLOBALS['messageErreur'] = "Erreur lors de la suppression de l'inscription.";
                $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'inscriptions', 'Erreur');
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur suppression inscription: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors de la suppression.";
        }
    }

    /**
     * Imprime le reçu d'inscription (READ)
     */
    private function imprimerRecu(int $idInscription): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $inscription = $this->scolarite->getInscriptionById($idInscription);
            
            if (!$inscription) {
                $GLOBALS['messageErreur'] = "Inscription non trouvée.";
                $this->auditLog->logImpression($_SESSION['id_utilisateur'], 'inscriptions', 'Erreur');
                return;
            }

            $GLOBALS['inscriptionAModifier'] = $inscription;

            // Démarrer la mise en mémoire tampon de sortie
            ob_start();

            // Inclure le fichier du modèle de reçu
            include __DIR__ . '/../../ressources/views/gestion_etudiants/recu_inscription.php';

            // Capturer le contenu de la mémoire tampon
            $html = ob_get_clean();

            // Instancier Dompdf avec options
            if (class_exists(Options::class)) {
                $options = new Options();
                $options->set('isRemoteEnabled', true);
                $dompdf = new Dompdf($options);
            } else {
                $dompdf = new Dompdf();
            }

            $basePathress = realpath(__DIR__ . '/../../public');
            if ($basePathress) {
                $dompdf->setBasePath($basePathress);
            }

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');

            $dompdf->render();
            $dompdf->stream("recu_paiement_" . $inscription['id_inscription'] . ".pdf", array("Attachment" => false));
            
            $this->auditLog->logImpression($_SESSION['id_utilisateur'], 'inscriptions', 'Succès');
            $this->logger->info("Reçu imprimé pour inscription {$idInscription}");
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur impression reçu: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Erreur lors de la génération du PDF : " . $e->getMessage();
            $this->auditLog->logImpression($_SESSION['id_utilisateur'], 'inscriptions', 'Erreur');
        }
    }

    /**
     * Traite une nouvelle inscription (CREATE)
     */
    private function traiterInscription(): void
    {
        if (!$this->checkPermission('create')) {
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

            $id_etudiant = $this->security->sanitizeInput($_POST['etudiant']);
            $id_niveau = (int)$_POST['niveau'];
            $id_annee_acad = (int)$_POST['annee_academique'];
            $montant_premier_versement = floatval($_POST['premier_versement']);
            $nombre_tranches = isset($_POST['nombre_tranches']) ? intval($_POST['nombre_tranches']) : 1;
            $reste_a_payer = str_replace([' ', ' ', ' ', ' '], '', $_POST['reste_payer']);
            $methode_paiement = $this->security->sanitizeInput($_POST['methode_paiement']);

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

                    $date_echeance = date('Y-m-d', strtotime('+3 months'));

                    for ($i = 1; $i < $nombre_tranches; $i++) {
                        $this->scolarite->creerEcheance($id_inscription, $montant_tranche, $date_echeance);
                        $date_echeance = date('Y-m-d', strtotime($date_echeance . ' +3 months'));
                    }
                }

                $GLOBALS['messageSuccess'] = "Inscription créée avec succès.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "inscriptions", 'Succès');
                $this->logger->info("Inscription créée pour l'étudiant {$id_etudiant}");
            } else {
                $GLOBALS['messageErreur'] = "Erreur lors de la création de l'inscription.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], "inscriptions", 'Erreur');
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur création inscription: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue : " . $e->getMessage();
        }
    }

    /**
     * Modifie une inscription existante (UPDATE)
     */
    private function modifierInscription(): void
    {
        if (!$this->checkPermission('update')) {
            return;
        }

        try {
            if (empty($_POST['id_inscription']) || empty($_POST['niveau']) || empty($_POST['premier_versement'])) {
                $GLOBALS['messageErreur'] = "Tous les champs sont obligatoires.";
                return;
            }

            $id_inscription = (int)$_POST['id_inscription'];
            $id_annee_acad = (int)$_POST['annee_academique'];
            $id_niveau = (int)$_POST['niveau'];
            $montant_premier_versement = floatval($_POST['premier_versement']);
            $nombre_tranches = isset($_POST['nombre_tranches']) ? intval($_POST['nombre_tranches']) : 1;
            $methode_paiement = $this->security->sanitizeInput($_POST['methode_paiement']);

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
                $this->logger->info("Inscription {$id_inscription} modifiée");
            } else {
                $GLOBALS['messageErreur'] = "Erreur lors de la modification de l'inscription.";
                $this->auditLog->logModification($_SESSION['id_utilisateur'], "inscriptions", 'Erreur');
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur modification inscription: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue : " . $e->getMessage();
        }
    }

    /**
     * Récupère les informations d'un étudiant (READ - AJAX)
     */
    private function getEtudiantInfo(): void
    {
        if (!$this->checkPermission('read')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Accès refusé']);
            exit;
        }

        try {
            if (isset($_GET['num_etu'])) {
                $numEtu = $this->security->sanitizeInput($_GET['num_etu']);
                $etudiant = $this->scolarite->getInfoEtudiant($numEtu);
                
                if ($etudiant) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'etudiant' => $etudiant
                    ]);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Étudiant non trouvé'
                    ]);
                }
                exit;
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur getEtudiantInfo: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
            exit;
        }
    }
}
