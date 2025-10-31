<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Approuver.php';
require_once __DIR__ . '/../models/PersAdmin.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/permissions.php';

class GestionDossiersCandidaturesController {
    private $db;
    private $rapportModel;
    private $etudiant;
    private $approuver;
    private $persAdmin;
    private $auditLog;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->rapportModel = new RapportEtudiant($this->db);
        $this->etudiant = new Etudiant($this->db);
        $this->approuver = new Approuver($this->db);
        $this->persAdmin = new PersAdmin($this->db);
        $this->auditLog = new AuditLog($this->db);
    }

    public function index() {
        // Récupérer l'historique des rapports vérifiés (approuvés ou désapprouvés)
        $rapportsVerifies = $this->getRapportsVerifies();
        $GLOBALS['rapports_verifies'] = $rapportsVerifies;
        
        // Récupérer les statistiques
        $statistiques = $this->getStatistiques();
        $GLOBALS['statistiques'] = $statistiques;
    }

    private function getRapportsVerifies() {
        $sql = "
            SELECT 
                r.id_rapport,
                r.nom_rapport as titre_rapport,
                r.theme_rapport,
                r.date_rapport as date_depot,
                r.statut_rapport,
                e.num_etu,
                e.nom_etu,
                e.prenom_etu,
                e.email_etu,
                a.date_approv as date_approbation,
                a.commentaire_approv as commentaire,
                a.decision as statut_approbation,
                pa.nom_pers_admin,
                pa.prenom_pers_admin
            FROM rapport_etudiants r
            INNER JOIN etudiants e ON r.num_etu = e.num_etu
            INNER JOIN approuver a ON r.id_rapport = a.id_rapport
            LEFT JOIN personnel_admin pa ON a.id_pers_admin = pa.id_pers_admin
            WHERE a.decision IN ('approuve', 'desapprouve')
            ORDER BY a.date_approv DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getStatistiques() {
        // Total des rapports vérifiés
        $sql = "
            SELECT COUNT(*) as total
            FROM rapport_etudiants r
            INNER JOIN approuver a ON r.id_rapport = a.id_rapport
            WHERE a.decision IN ('approuve', 'desapprouve')
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Rapports approuvés
        $sql = "
            SELECT COUNT(*) as approuves
            FROM rapport_etudiants r
            INNER JOIN approuver a ON r.id_rapport = a.id_rapport
            WHERE a.decision = 'approuve'
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $approuves = $stmt->fetch(PDO::FETCH_ASSOC)['approuves'];

        // Rapports désapprouvés
        $sql = "
            SELECT COUNT(*) as desapprouves
            FROM rapport_etudiants r
            INNER JOIN approuver a ON r.id_rapport = a.id_rapport
            WHERE a.decision = 'desapprouve'
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $desapprouves = $stmt->fetch(PDO::FETCH_ASSOC)['desapprouves'];

        return [
            'total' => $total,
            'approuves' => $approuves,
            'desapprouves' => $desapprouves
        ];
    }

    public function getDetailsRapport($id_rapport) {
        $sql = "
            SELECT 
                r.*,
                e.nom_etu,
                e.prenom_etu,
                e.email_etu,
                a.date_approv as date_approbation,
                a.commentaire_approv as commentaire,
                a.decision as statut_approbation,
                pa.nom_pers_admin,
                pa.prenom_pers_admin
            FROM rapport_etudiants r
            INNER JOIN etudiants e ON r.num_etu = e.num_etu
            INNER JOIN approuver a ON r.id_rapport = a.id_rapport
            LEFT JOIN personnel_admin pa ON a.id_pers_admin = pa.id_pers_admin
            WHERE r.id_rapport = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_rapport]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function telechargerPdf($id_rapport) {
        // Nettoyer tout output précédent
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        $rapport = $this->getDetailsRapport($id_rapport);
        
        if (!$rapport) {
            ob_end_clean();
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">';
            echo '<h2 style="color: #e74c3c;">Erreur</h2>';
            echo '<p>Rapport non trouvé.</p>';
            echo '<a href="javascript:history.back()" style="color: #3498db; text-decoration: none;">← Retour</a>';
            echo '</div>';
            exit;
        }

        // Récupérer le contenu du rapport
        $chemin = $rapport['chemin_fichier'] ?? '';
        if (empty($chemin)) {
            $chemin = 'rapport_' . $id_rapport . '.html';
        }
        $fichierContenu = __DIR__ . "/../../ressources/uploads/rapports/" . $chemin;
        
        if (!file_exists($fichierContenu)) {
            ob_end_clean();
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">';
            echo '<h2 style="color: #e74c3c;">Erreur</h2>';
            echo '<p>Le fichier du rapport n\'existe pas.</p>';
            echo '<a href="javascript:history.back()" style="color: #3498db; text-decoration: none;">← Retour</a>';
            echo '</div>';
            exit;
        }

        $contenu = file_get_contents($fichierContenu);
        
        require_once __DIR__ . '/../../vendor/autoload.php';
        require_once __DIR__ . '/../utils/DocumentGeneratorService.php';
        
        try {
            // Déterminer le statut d'approbation en français
            $statutTexte = ($rapport['statut_approbation'] === 'approuve') ? 'Approuvé' : 'Désapprouvé';
            
            // Préparer les données pour le template
            $templateData = [
                'nom_etu' => $rapport['nom_etu'] ?? '',
                'prenom_etu' => $rapport['prenom_etu'] ?? '',
                'num_etu' => $rapport['num_etu'] ?? '',
                'email_etu' => $rapport['email_etu'] ?? '',
                'nom_rapport' => $rapport['nom_rapport'] ?? '',
                'theme_rapport' => $rapport['theme_rapport'] ?? '',
                'date_depot' => $rapport['date_rapport'] ? date('d/m/Y H:i', strtotime($rapport['date_rapport'])) : 'Non déposé',
                'nom_pers_admin' => $rapport['nom_pers_admin'] ?? '',
                'prenom_pers_admin' => $rapport['prenom_pers_admin'] ?? '',
                'date_approbation' => $rapport['date_approbation'] ? date('d/m/Y H:i', strtotime($rapport['date_approbation'])) : 'Non vérifié',
                'statut_approbation' => $statutTexte,
                'commentaire' => $rapport['commentaire'] ?? '',
                'contenu' => $contenu
            ];
            
            // Utiliser DocumentGeneratorService
            $documentService = new DocumentGeneratorService();
            $pdfPath = $documentService->generateFromTemplate('rapport_etudiant', $templateData);
            
            // Générer le nom du fichier
            $nomFichier = 'rapport_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $rapport['nom_rapport']) . '_' . date('Y-m-d_H-i-s') . '.pdf';
            
            // Audit logging pour le téléchargement
            $this->auditLog->logImpression($_SESSION['id_utilisateur'], 'rapport_etudiants', 'Succès');
            
            // Nettoyer tout output et envoyer le PDF
            ob_end_clean();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $nomFichier . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Content-Length: ' . filesize($pdfPath));
            
            readfile($pdfPath);
            
            // Nettoyer le fichier temporaire
            $documentService->cleanupTempFile($pdfPath);
            exit;
            
        } catch (Exception $e) {
            error_log('Erreur génération PDF rapport: ' . $e->getMessage());
            $this->auditLog->logImpression($_SESSION['id_utilisateur'], 'rapport_etudiants', 'Erreur');
            ob_end_clean();
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">';
            echo '<h2 style="color: #e74c3c;">Erreur</h2>';
            echo '<p>Erreur lors de la génération du PDF : ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<a href="javascript:history.back()" style="color: #3498db; text-decoration: none;">← Retour</a>';
            echo '</div>';
            exit;
        }
    }

    public function consulterRapport($id_rapport) {
        // Nettoyer tout output précédent
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        $rapport = $this->getDetailsRapport($id_rapport);
        
        if (!$rapport) {
            ob_end_clean();
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">';
            echo '<h2 style="color: #e74c3c;">Erreur</h2>';
            echo '<p>Rapport non trouvé.</p>';
            echo '<a href="javascript:history.back()" style="color: #3498db; text-decoration: none;">← Retour</a>';
            echo '</div>';
            exit;
        }

        // Récupérer le contenu du rapport
        $chemin = $rapport['chemin_fichier'] ?? '';
        if (empty($chemin)) {
            $chemin = 'rapport_' . $id_rapport . '.html';
        }
        $fichierContenu = __DIR__ . "/../../ressources/uploads/rapports/" . $chemin;
        
        if (!file_exists($fichierContenu)) {
            ob_end_clean();
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">';
            echo '<h2 style="color: #e74c3c;">Erreur</h2>';
            echo '<p>Le fichier du rapport n\'existe pas.</p>';
            echo '<a href="javascript:history.back()" style="color: #3498db; text-decoration: none;">← Retour</a>';
            echo '</div>';
            exit;
        }

        $contenu = file_get_contents($fichierContenu);
        
        // Audit logging pour la consultation
        $this->auditLog->logAction($_SESSION['id_utilisateur'], 'Consultation', 'rapport_etudiants', 'Succès');
        
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