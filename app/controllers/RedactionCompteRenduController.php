<?php

namespace App\Controllers;

use PDO;
use App\Models\Valider;
use App\Models\CompteRendu;
use App\Models\Enseignant;
use App\Models\RapportEtudiant;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use App\Utils\EmailService;
use Psr\Log\LoggerInterface;
use Dompdf\Dompdf;
use Exception;

/**
 * RedactionCompteRenduController - Génération du PV final après soutenance
 * 
 * Ce contrôleur gère la rédaction des comptes rendus :
 * - Affichage des rapports validés
 * - Création et enregistrement des comptes rendus
 * - Export en PDF
 * - Notification par email
 * 
 * @package App\Controllers
 */
class RedactionCompteRenduController
{
    private PDO $pdo;
    private Valider $valider;
    private CompteRendu $compteRendu;
    private Enseignant $enseignant;
    private RapportEtudiant $rapportEtudiant;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private EmailService $emailService;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        Valider $valider,
        CompteRendu $compteRendu,
        Enseignant $enseignant,
        RapportEtudiant $rapportEtudiant,
        AuditLog $auditLog,
        SecurityUtils $security,
        EmailService $emailService,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->valider = $valider;
        $this->compteRendu = $compteRendu;
        $this->enseignant = $enseignant;
        $this->rapportEtudiant = $rapportEtudiant;
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->emailService = $emailService;
        $this->logger = $logger;
    }

    /**
     * Vérification centralisée des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'redaction_compte_rendu', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur redaction_compte_rendu"
            );
            
            $GLOBALS['messageErreur'] = "Vous n'avez pas les droits nécessaires.";
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                http_response_code(403);
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            return false;
        }
        return true;
    }

    /**
     * Action : Afficher la page de rédaction des comptes rendus (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $GLOBALS['rapports_valides'] = $this->valider->getRapportsValides();
            $GLOBALS['enseignants'] = $this->enseignant->getAllEnseignants();
        } catch (Exception $e) {
            $this->logger->error("Erreur index RedactionCompteRendu: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Erreur lors du chargement des données.";
        }
    }

    /**
     * Action : Enregistrer un compte rendu (CREATE)
     */
    public function enregistrer(): void
    {
        if (!$this->checkPermission('create')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        try {
            $num_etu = $this->security->sanitizeInput($_POST['num_etu'] ?? null);
            $nom_CR = $this->security->sanitizeInput($_POST['nom_CR'] ?? '');
            $contenu_CR = $_POST['contenu_CR'] ?? '';
            $rapports = isset($_POST['rapports']) ? $_POST['rapports'] : [];
            $date_CR = date('Y-m-d H:i:s');
            $encadrants = $_POST['encadrant_pedagogique'] ?? [];
            $directeurs = $_POST['directeur_memoire'] ?? [];

            if (empty($num_etu)) {
                $_SESSION['error'] = "Aucun étudiant sélectionné.";
                header('Location: layout.php?page=redaction_compte_rendu');
                exit;
            }

            // Générer le PDF avec Dompdf
            $html = '<html><head><meta charset="UTF-8"></head><body>' . $contenu_CR . '</body></html>';
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $output = $dompdf->output();

            // Sauvegarde du PDF
            $pdf_dir = __DIR__ . '/../../ressources/uploads/comptes_rendus/';
            if (!is_dir($pdf_dir)) {
                mkdir($pdf_dir, 0777, true);
            }
            $pdf_name = 'CR_' . date('Ymd_His') . '.pdf';
            $pdf_path = $pdf_dir . $pdf_name;
            file_put_contents($pdf_path, $output);
            $chemin_pdf = 'ressources/uploads/comptes_rendus/' . $pdf_name;

            // Enregistrement en BD
            $id_CR = $this->compteRendu->creer($num_etu, $nom_CR, $contenu_CR, $chemin_pdf, $date_CR, $rapports);
            
            if ($id_CR) {
                // Enregistrement des affectations encadrant/directeur
                foreach ($rapports as $id_rapport) {
                    // Encadrant pédagogique
                    if (!empty($encadrants[$id_rapport])) {
                        $id_enseignant = (int)$encadrants[$id_rapport];
                        $stmt = $this->pdo->prepare("INSERT INTO affecter (id_enseignant, id_rapport, id_jury, role) VALUES (?, ?, NULL, 'encadrant')");
                        $stmt->execute([$id_enseignant, $id_rapport]);
                    }
                    // Directeur de mémoire
                    if (!empty($directeurs[$id_rapport])) {
                        $id_enseignant = (int)$directeurs[$id_rapport];
                        $stmt = $this->pdo->prepare("INSERT INTO affecter (id_enseignant, id_rapport, id_jury, role) VALUES (?, ?, NULL, 'directeur')");
                        $stmt->execute([$id_enseignant, $id_rapport]);
                    }
                }

                $_SESSION['success'] = 'Compte rendu enregistré avec succès !';
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'compte_rendu', 'Succès');
                $this->logger->info("Compte rendu {$id_CR} créé");

                // Envoi d'un email à chaque étudiant concerné
                foreach ($rapports as $id_rapport) {
                    $rapport = $this->rapportEtudiant->getRapportById($id_rapport);
                    if ($rapport && !empty($rapport['email_etu'])) {
                        $to = $rapport['email_etu'];
                        $nom = $rapport['prenom_etu'] . ' ' . $rapport['nom_etu'];
                        $subject = "Notification de compte rendu de soutenance";
                        $message = "Bonjour $nom,<br><br>Votre rapport (« " . htmlspecialchars($rapport['nom_rapport']) . " ») a été inclus dans le compte rendu « " . htmlspecialchars($nom_CR) . " » le " . date('d/m/Y H:i') . ".<br><br>Vous trouverez en pièce jointe le compte rendu complet de la séance d'évaluation.<br><br>Cordialement,<br>L'équipe pédagogique";
                        
                        $attachmentName = 'Compte_rendu_' . date('Y-m-d') . '.pdf';
                        $this->emailService->sendEmailWithAttachment($to, $subject, $message, $pdf_path, $attachmentName, true);
                    }
                }
            } else {
                $_SESSION['error'] = 'Erreur lors de l\'enregistrement du compte rendu.';
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'compte_rendu', 'Erreur');
            }
            
            header('Location: layout.php?page=redaction_compte_rendu');
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur enregistrer compte rendu: " . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors de l\'enregistrement: ' . $e->getMessage();
            header('Location: layout.php?page=redaction_compte_rendu');
            exit;
        }
    }

    /**
     * Action : Exporter le compte rendu en PDF (READ)
     */
    public function exporterPDF(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $contenu = $_POST['contenu_CR'] ?? '';
            $nom_CR = $this->security->sanitizeInput($_POST['nom_CR'] ?? 'compte_rendu');
            
            if (empty($contenu)) {
                throw new Exception('Le contenu du compte rendu est vide.');
            }

            $html = '<html><head><meta charset="UTF-8"><style>body{font-family:Times New Roman,serif;line-height:1.6;margin:40px;}</style></head><body>' . $contenu . '</body></html>';
            
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdf = $dompdf->output();
            
            $pdfName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nom_CR) . '.pdf';
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $pdfName . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            echo $pdf;
            
            $this->auditLog->logImpression($_SESSION['id_utilisateur'], 'compte_rendu', 'Succès');
            $this->logger->info("PDF compte rendu exporté");
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur exporterPDF: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF : ' . $e->getMessage()
            ]);
            exit;
        }
    }
}
