<?php

namespace App\Controllers;

use PDO;
use App\Models\Note;
use App\Models\Etudiant;
use App\Models\NiveauEtude;
use App\Models\Semestre;
use App\Models\Ue;
use App\Models\Ecue;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Dompdf\Dompdf;
use Exception;

/**
 * NotesResultatsController - Consultation des notes (Vue Étudiant)
 * 
 * Ce contrôleur gère l'affichage des notes pour les étudiants :
 * - Affichage des résultats de l'étudiant connecté
 * - Calcul des moyennes et classements
 * - Export PDF du relevé de notes
 * 
 * @package App\Controllers
 */
class NotesResultatsController
{
    private PDO $db;
    private Note $noteModel;
    private Etudiant $etudiantModel;
    private NiveauEtude $niveauModel;
    private Semestre $semestreModel;
    private Ue $ueModel;
    private Ecue $ecueModel;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $db,
        Note $noteModel,
        Etudiant $etudiantModel,
        NiveauEtude $niveauModel,
        Semestre $semestreModel,
        Ue $ueModel,
        Ecue $ecueModel,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->db = $db;
        $this->noteModel = $noteModel;
        $this->etudiantModel = $etudiantModel;
        $this->niveauModel = $niveauModel;
        $this->semestreModel = $semestreModel;
        $this->ueModel = $ueModel;
        $this->ecueModel = $ecueModel;
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
        
        if (!$this->security->can($idGroupe, 'notes_resultats', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur notes_resultats"
            );
            
            $GLOBALS['messageErreur'] = "Vous n'avez pas les droits nécessaires pour consulter vos notes.";
            
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                http_response_code(403);
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            
            return false;
        }
        
        return true;
    }

    /**
     * Action : Afficher les notes de l'étudiant connecté (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            // Vérifier que l'étudiant est connecté
            if (!isset($_SESSION['num_etu'])) {
                $this->logger->warning("Tentative d'accès aux notes sans numéro étudiant en session");
                $GLOBALS['messageErreur'] = "Session invalide. Veuillez vous reconnecter.";
                return;
            }

            $studentId = $_SESSION['num_etu'];

            // Récupérer les infos de l'étudiant
            $GLOBALS['etudiant'] = $this->etudiantModel->getEtudiantById($studentId);
            
            // Récupérer les notes détaillées
            $GLOBALS['notes'] = $this->noteModel->getByStudent($studentId);
            
            // Moyenne générale
            $moyenneResult = $this->noteModel->getMoyenneGenerale($studentId);
            $GLOBALS['moyenneGenerale'] = $moyenneResult->moyenne_generale ?? null;
            
            // Nombre d'UE validées
            $ueValideResult = $this->noteModel->getValidUe($studentId);
            $nbUeValide = 0;
            if (!empty($ueValideResult) && isset($ueValideResult[0])) {
                $nbUeValide = $ueValideResult[0]->nb_ue_valide ?? 0;
            }
            $GLOBALS['nbUeValide'] = $nbUeValide;
            
            // Classement (et total étudiants du niveau)
            $classementObj = $this->noteModel->getClassementStudent($studentId);
            $GLOBALS['classement'] = $classementObj->classement ?? null;
            $GLOBALS['totalEtudiants'] = $classementObj->total ?? 0;
            
            // Semestres
            $GLOBALS['semestres'] = $this->noteModel->getSemestreByEtudiant($studentId);
            
            $this->logger->info("Consultation des notes par l'étudiant " . $studentId);
            
        } catch (Exception $e) {
            $this->logger->error("Erreur dans NotesResultatsController::index: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors du chargement de vos notes.";
        }
    }

    /**
     * Action : Exporter le relevé de notes en PDF (READ)
     */
    public function exportPdf(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            if (!isset($_SESSION['num_etu'])) {
                throw new Exception("Session invalide");
            }

            $studentId = $_SESSION['num_etu'];
            
            // Récupérer les infos de l'étudiant
            $etudiant = $this->etudiantModel->getEtudiantById($studentId);
            $niveau = $this->etudiantModel->getNiveauByEtudiant($studentId);
            $notes = $this->noteModel->getByStudent($studentId);
            
            // Préparer les variables globales pour la vue
            $GLOBALS['selectedStudent'] = $etudiant;
            $GLOBALS['niveau'] = $niveau;
            $GLOBALS['studentGrades'] = $notes;
            
            // Générer le HTML de la vue
            ob_start();
            include __DIR__ . '/../../ressources/views/releve_notes.php';
            $html = ob_get_clean();
            
            // Générer le PDF avec Dompdf
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            $this->auditLog->logImpression($_SESSION['id_utilisateur'], 'notes_resultats', 'Succès');
            $this->logger->info("Export PDF des notes pour l'étudiant " . $studentId);
            
            $dompdf->stream('releve-notes.pdf', ['Attachment' => true]);
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur exportPdf: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Erreur lors de l'export du relevé de notes.";
        }
    }
}
