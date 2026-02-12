<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Approuver.php';
require_once __DIR__ . '/../models/PersAdmin.php';
require_once __DIR__ . '/../models/AuditLog.php';

class GestionDossiersCandidaturesController
{
    private $db;
    private $rapportModel;
    private $etudiant;
    private $approuver;
    private $persAdmin;
    private $auditLog;
    private $tableExistsCache = [];

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->rapportModel = new RapportEtudiant($this->db);
        $this->etudiant = new Etudiant($this->db);
        $this->approuver = new Approuver($this->db);
        $this->persAdmin = new PersAdmin($this->db);
        $this->auditLog = new AuditLog($this->db);
    }

    public function index()
    {
        // Récupérer l'historique des rapports vérifiés (approuvés ou désapprouvés)
        $rapportsVerifies = $this->getRapportsVerifies();
        $GLOBALS['rapports_verifies'] = $rapportsVerifies;

        // Récupérer les statistiques
        $statistiques = $this->getStatistiques();
        $GLOBALS['statistiques'] = $statistiques;
    }

    private function tableExists($tableName)
    {
        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return $this->tableExistsCache[$tableName];
        }

        $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        $exists = (bool) $stmt->fetchColumn();
        $this->tableExistsCache[$tableName] = $exists;
        return $exists;
    }

    private function getRapportsVerifies()
    {
        if ($this->tableExists('approuver')) {
            $sql = "
                SELECT 
                    r.id_rapport,
                    r.nom_rapport as titre_rapport,
                    r.theme_rapport,
                    r.date_rapport as date_depot,
                    r.statut_rapport,
                    e.num_carte_etud as num_etu,
                    e.nom_etu,
                    e.prenom_etu,
                    e.email_etu,
                    a.date_approv as date_approbation,
                    a.commentaire_approv as commentaire,
                    a.decision as statut_approbation,
                    pa.nom_pers_admin,
                    pa.prenom_pers_admin
                FROM rapport_etudiants r
                INNER JOIN etudiants e ON r.num_etu = e.num_carte_etud
                INNER JOIN approuver a ON r.id_rapport = a.id_rapport
                LEFT JOIN personnel_admin pa ON a.id_pers_admin = pa.id_pers_admin
                WHERE a.decision IN ('approuve', 'desapprouve')
                ORDER BY a.date_approv DESC
            ";
        } elseif ($this->tableExists('valider')) {
            $sql = "
                SELECT 
                    r.id_rapport,
                    r.nom_rapport as titre_rapport,
                    r.theme_rapport,
                    r.date_rapport as date_depot,
                    r.statut_rapport,
                    e.num_carte_etud as num_etu,
                    e.nom_etu,
                    e.prenom_etu,
                    e.email_etu,
                    v.date_validation as date_approbation,
                    v.commentaire_validation as commentaire,
                    CASE 
                        WHEN v.decision_validation = 'valider' THEN 'approuve'
                        WHEN v.decision_validation = 'rejeter' THEN 'desapprouve'
                        ELSE 'desapprouve'
                    END as statut_approbation,
                    en.nom_enseignant as nom_pers_admin,
                    en.prenom_enseignant as prenom_pers_admin
                FROM rapport_etudiants r
                INNER JOIN etudiants e ON r.num_etu = e.num_carte_etud
                INNER JOIN valider v ON r.id_rapport = v.id_rapport
                LEFT JOIN enseignants en ON v.id_enseignant = en.id_enseignant
                WHERE v.decision_validation IN ('valider', 'rejeter')
                ORDER BY v.date_validation DESC
            ";
        } else {
            return [];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getStatistiques()
    {
        if ($this->tableExists('approuver')) {
            // Total des rapports vérifiés
            $sql = "
                SELECT COUNT(*) as total
                FROM rapport_etudiants r
                INNER JOIN approuver a ON r.id_rapport = a.id_rapport
                WHERE a.decision IN ('approuve', 'desapprouve')
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $total = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // Rapports approuvés
            $sql = "
                SELECT COUNT(*) as approuves
                FROM rapport_etudiants r
                INNER JOIN approuver a ON r.id_rapport = a.id_rapport
                WHERE a.decision = 'approuve'
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $approuves = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['approuves'] ?? 0);

            // Rapports désapprouvés
            $sql = "
                SELECT COUNT(*) as desapprouves
                FROM rapport_etudiants r
                INNER JOIN approuver a ON r.id_rapport = a.id_rapport
                WHERE a.decision = 'desapprouve'
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $desapprouves = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['desapprouves'] ?? 0);
        } elseif ($this->tableExists('valider')) {
            $sql = "
                SELECT COUNT(*) as total
                FROM rapport_etudiants r
                INNER JOIN valider v ON r.id_rapport = v.id_rapport
                WHERE v.decision_validation IN ('valider', 'rejeter')
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $total = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            $sql = "
                SELECT COUNT(*) as approuves
                FROM rapport_etudiants r
                INNER JOIN valider v ON r.id_rapport = v.id_rapport
                WHERE v.decision_validation = 'valider'
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $approuves = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['approuves'] ?? 0);

            $sql = "
                SELECT COUNT(*) as desapprouves
                FROM rapport_etudiants r
                INNER JOIN valider v ON r.id_rapport = v.id_rapport
                WHERE v.decision_validation = 'rejeter'
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $desapprouves = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['desapprouves'] ?? 0);
        } else {
            $total = 0;
            $approuves = 0;
            $desapprouves = 0;
        }

        return [
            'total' => $total,
            'approuves' => $approuves,
            'desapprouves' => $desapprouves
        ];
    }

    public function getDetailsRapport($id_rapport)
    {
        if ($this->tableExists('approuver')) {
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
                INNER JOIN etudiants e ON r.num_etu = e.num_carte_etud
                INNER JOIN approuver a ON r.id_rapport = a.id_rapport
                LEFT JOIN personnel_admin pa ON a.id_pers_admin = pa.id_pers_admin
                WHERE r.id_rapport = ?
            ";
        } elseif ($this->tableExists('valider')) {
            $sql = "
                SELECT 
                    r.*,
                    e.nom_etu,
                    e.prenom_etu,
                    e.email_etu,
                    v.date_validation as date_approbation,
                    v.commentaire_validation as commentaire,
                    CASE 
                        WHEN v.decision_validation = 'valider' THEN 'approuve'
                        WHEN v.decision_validation = 'rejeter' THEN 'desapprouve'
                        ELSE 'desapprouve'
                    END as statut_approbation,
                    en.nom_enseignant as nom_pers_admin,
                    en.prenom_enseignant as prenom_pers_admin
                FROM rapport_etudiants r
                INNER JOIN etudiants e ON r.num_etu = e.num_carte_etud
                INNER JOIN valider v ON r.id_rapport = v.id_rapport
                LEFT JOIN enseignants en ON v.id_enseignant = en.id_enseignant
                WHERE r.id_rapport = ?
            ";
        } else {
            return null;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_rapport]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function telechargerPdf($id_rapport)
    {
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

        // Créer le PDF avec DOMPDF
        require_once __DIR__ . '/../../vendor/autoload.php';
        $dompdf = new Dompdf\Dompdf();

        // Préparer le HTML pour le PDF
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Rapport - ' . htmlspecialchars($rapport['nom_rapport']) . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                .info { margin-bottom: 20px; }
                .info div { margin: 5px 0; }
                .content { margin-top: 30px; }
                .content h1, .content h2, .content h3 { color: #333; }
                .content p { line-height: 1.6; }
                .status { margin-top: 20px; padding: 10px; border-radius: 5px; }
                .status.approuve { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
                .status.desapprouve { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            </style>
        </head>
        <body>
            
            <div class="content">
                ' . $contenu . '
            </div>
        </body>
        </html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

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

        echo $dompdf->output();
        exit;
    }

    public function consulterRapport($id_rapport)
    {
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
