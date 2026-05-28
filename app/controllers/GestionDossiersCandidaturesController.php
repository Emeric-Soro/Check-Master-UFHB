<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/GestionDossiersCandidaturesService.php';
require_once __DIR__ . '/../Services/VerificationRapportsService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\GestionDossiersCandidaturesService;

class GestionDossiersCandidaturesController
{
    private $service;
    private $verificationService;

    public function __construct()
    {
        $db = Database::getConnection();
        $this->service = new GestionDossiersCandidaturesService($db);
        $this->verificationService = new VerificationRapportsService($db);
    }

    public function index()
    {
        if (!canView('gestion_dossiers_candidatures')) {
            $_SESSION['error'] = "Accès non autorisé.";
            header('Location: layout.php?page=dashboard');
            exit;
        }
        $data = $this->verificationService->getIndexData();
        $GLOBALS['rapports'] = $data['rapports'] ?? [];
        $GLOBALS['nbRapports'] = $data['nbRapports'] ?? 0;
        $GLOBALS['statsRapports'] = $data['statsRapports'] ?? [];
    }

    public function validerRapport()
    {
        if (!canEdit('gestion_dossiers_candidatures')) {
            return ['success' => false, 'message' => 'Accès non autorisé pour valider un rapport.'];
        }
        return $this->verificationService->validerRapport($_POST['id_rapport'] ?? 0, $_POST['commentaire'] ?? '');
    }

    public function rejeterRapport()
    {
        if (!canEdit('gestion_dossiers_candidatures')) {
            return ['success' => false, 'message' => 'Accès non autorisé pour rejeter un rapport.'];
        }
        return $this->verificationService->rejeterRapport($_POST['id_rapport'] ?? 0, $_POST['commentaire'] ?? '');
    }

    public function getDetailsRapport($id_rapport)
    {
        return $this->service->getDetailsRapport($id_rapport);
    }

    public function telechargerPdf($id_rapport)
    {
        // Nettoyer tout output précédent
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        // Vérifier si c'est un fichier uploadé (PDF/DOC/DOCX) → rediriger vers DocViewer download
        $rapportModel = new \RapportEtudiant(\Database::getConnection());
        $rapportCheck = $rapportModel->getRapportById((int) $id_rapport);
        if ($rapportCheck && !empty($rapportCheck['chemin_fichier'])) {
            $ext = strtolower(pathinfo((string) $rapportCheck['chemin_fichier'], PATHINFO_EXTENSION));
            if ($ext !== 'html') {
                header('Location: ?page=docviewer&type=rapport&id=' . (int) $id_rapport . '&action=download');
                exit;
            }
        }

        $donnees = $this->service->preparerDonneesPdf($id_rapport);

        if ($donnees === null) {
            ob_end_clean();
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">';
            echo '<h2 style="color: #e74c3c;">Erreur</h2>';
            echo '<p>Rapport non trouvé.</p>';
            echo '<a href="javascript:history.back()" style="color: #3498db; text-decoration: none;">← Retour</a>';
            echo '</div>';
            exit;
        }

        if (isset($donnees['error']) && $donnees['error'] === 'file_not_found') {
            ob_end_clean();
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">';
            echo '<h2 style="color: #e74c3c;">Erreur</h2>';
            echo '<p>Le fichier du rapport n\'existe pas.</p>';
            echo '<a href="javascript:history.back()" style="color: #3498db; text-decoration: none;">← Retour</a>';
            echo '</div>';
            exit;
        }

        // Créer le PDF avec PdfGeneratorService (TCPDF)
        require_once __DIR__ . '/../Services/Document/PdfGeneratorService.php';
        $pdfGen = new \App\Services\Document\PdfGeneratorService(
            __DIR__ . '/../../storage',
            __DIR__ . '/../../public/image/logo_ufhb.png'
        );
        $pdf = $pdfGen->createDocument('P', 'A4', 'Rapport');
        $pdf->AddPage();
        $pdfGen->writeHtml($pdf, $donnees['html']);

        // Audit logging pour le téléchargement
        $this->service->logImpression($_SESSION['id_utilisateur']);

        // Nettoyer tout output et envoyer le PDF
        ob_end_clean();
        $pdf->Output($donnees['nomFichier'], 'D');
        exit;
    }

    public function consulterRapport($id_rapport)
    {
        // Nettoyer tout output précédent
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        // Vérifier si c'est un fichier uploadé (PDF/DOC/DOCX) → rediriger vers DocViewer
        $rapportModel = new \RapportEtudiant(\Database::getConnection());
        $rapportCheck = $rapportModel->getRapportById((int) $id_rapport);
        if ($rapportCheck && !empty($rapportCheck['chemin_fichier'])) {
            $ext = strtolower(pathinfo((string) $rapportCheck['chemin_fichier'], PATHINFO_EXTENSION));
            if ($ext !== 'html') {
                header('Location: ?page=docviewer&type=rapport&id=' . (int) $id_rapport . '&action=preview');
                exit;
            }
        }

        $donnees = $this->service->preparerDonneesConsultation($id_rapport);

        if ($donnees === null) {
            ob_end_clean();
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">';
            echo '<h2 style="color: #e74c3c;">Erreur</h2>';
            echo '<p>Rapport non trouvé.</p>';
            echo '<a href="javascript:history.back()" style="color: #3498db; text-decoration: none;">← Retour</a>';
            echo '</div>';
            exit;
        }

        if (isset($donnees['error']) && $donnees['error'] === 'file_not_found') {
            ob_end_clean();
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">';
            echo '<h2 style="color: #e74c3c;">Erreur</h2>';
            echo '<p>Le fichier du rapport n\'existe pas.</p>';
            echo '<a href="javascript:history.back()" style="color: #3498db; text-decoration: none;">← Retour</a>';
            echo '</div>';
            exit;
        }

        $rapport = $donnees['rapport'];
        $contenu = $donnees['contenu'];

        // Audit logging pour la consultation
        $this->service->logConsultation($_SESSION['id_utilisateur']);

        // Nettoyer tout output et afficher le rapport
        ob_end_clean();
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Consultation - ' . htmlspecialchars($rapport['nom_rapport']) . '</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0; 
                    padding: 20px; 
                    background-color: #f5f5f5; 
                }
                .container { 
                    max-width: 1200px; 
                    margin: 0 auto; 
                    background: white; 
                    padding: 30px; 
                    border-radius: 10px; 
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 30px; 
                    border-bottom: 2px solid #333; 
                    padding-bottom: 20px; 
                }
                .info { 
                    margin-bottom: 30px; 
                    padding: 20px; 
                    background-color: #f8f9fa; 
                    border-radius: 8px; 
                    border-left: 4px solid #007bff; 
                }
                .info div { 
                    margin: 8px 0; 
                    line-height: 1.5; 
                }
                .content { 
                    margin-top: 30px; 
                    line-height: 1.6; 
                }
                .content h1, .content h2, .content h3 { 
                    color: #333; 
                    margin-top: 25px; 
                    margin-bottom: 15px; 
                }
                .content p { 
                    margin-bottom: 15px; 
                }
                .status { 
                    display: inline-block; 
                    padding: 8px 16px; 
                    border-radius: 20px; 
                    font-weight: bold; 
                    margin-top: 10px; 
                }
                .status.approuve { 
                    background-color: #d4edda; 
                    color: #155724; 
                }
                .status.desapprouve { 
                    background-color: #f8d7da; 
                    color: #721c24; 
                }
                .back-btn { 
                    display: inline-block; 
                    margin-bottom: 20px; 
                    padding: 10px 20px; 
                    background-color: #007bff; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    transition: background-color 0.3s; 
                }
                .back-btn:hover { 
                    background-color: #0056b3; 
                }
                @media print {
                    .back-btn { display: none; }
                    body { background: white; }
                    .container { box-shadow: none; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                
                
                <div class="header">
                    <h1>Rapport de Soutenance</h1>
                </div>
                
                <div class="info">
                    <div><strong>Étudiant:</strong> ' . htmlspecialchars($rapport['nom_etu'] . ' ' . $rapport['prenom_etu']) . '</div>
                    <div><strong>Numéro étudiant:</strong> ' . htmlspecialchars($rapport['num_etu']) . '</div>
                    <div><strong>Email:</strong> ' . htmlspecialchars($rapport['email_etu']) . '</div>
                    <div><strong>Nom du rapport:</strong> ' . htmlspecialchars($rapport['nom_rapport']) . '</div>
                    <div><strong>Thème:</strong> ' . htmlspecialchars($rapport['theme_rapport']) . '</div>
                    <div><strong>Date de dépôt:</strong> ' . ($rapport['date_rapport'] ? date('d/m/Y H:i', strtotime($rapport['date_rapport'])) : 'Non déposé') . '</div>
                    <div><strong>Vérifié par:</strong> ' . htmlspecialchars($rapport['nom_pers_admin'] . ' ' . $rapport['prenom_pers_admin']) . '</div>
                    <div><strong>Date de vérification:</strong> ' . ($rapport['date_approbation'] ? date('d/m/Y H:i', strtotime($rapport['date_approbation'])) : 'Non vérifié') . '</div>
                    <div class="status ' . $rapport['statut_approbation'] . '">
                        Statut: ' . ($rapport['statut_approbation'] === 'approuve' ? 'Approuvé' : 'Désapprouvé') . '
                    </div>
                    ' . ($rapport['commentaire'] ? '<div style="margin-top: 15px;"><strong>Commentaire:</strong><br><em>' . htmlspecialchars($rapport['commentaire']) . '</em></div>' : '') . '
                </div>
                
                <div class="content">
                    ' . $contenu . '
                </div>
            </div>
        </body>
        </html>';
        exit;
    }
}
