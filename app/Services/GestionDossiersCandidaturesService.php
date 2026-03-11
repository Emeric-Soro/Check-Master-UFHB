<?php

namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Approuver.php';
require_once __DIR__ . '/../models/PersAdmin.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/AcademicYear.php';

use RapportEtudiant;
use Etudiant;
use Approuver;
use PersAdmin;
use AuditLog;
use PDO;
use Throwable;

/**
 * Service de gestion des dossiers de candidatures
 *
 * Contient la logique métier pour :
 * - Vérification de l'existence de tables/colonnes
 * - Récupération des rapports vérifiés (approuvés/désapprouvés)
 * - Statistiques des rapports
 * - Détails d'un rapport spécifique
 * - Génération de PDF et consultation de rapports
 */
class GestionDossiersCandidaturesService
{
    private $db;
    private $rapportModel;
    private $etudiant;
    private $approuver;
    private $persAdmin;
    private $auditLog;
    private $tableExistsCache = [];
    private $columnExistsCache = [];

    public function __construct($db)
    {
        $this->db = $db;
        $this->rapportModel = new RapportEtudiant($db);
        $this->etudiant = new Etudiant($db);
        $this->approuver = new Approuver($db);
        $this->persAdmin = new PersAdmin($db);
        $this->auditLog = new AuditLog($db);
    }

    /**
     * Vérifie si une table existe dans la base de données
     * @param string $tableName
     * @return bool
     */
    public function tableExists($tableName)
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

    /**
     * Vérifie si une colonne existe dans une table
     * @param string $tableName
     * @param string $columnName
     * @return bool
     */
    public function columnExists($tableName, $columnName)
    {
        $cacheKey = strtolower((string) $tableName . '.' . (string) $columnName);
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        $cleanTable = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $tableName);
        if ($cleanTable === '' || $columnName === '') {
            $this->columnExistsCache[$cacheKey] = false;
            return false;
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `" . $cleanTable . "` LIKE ?");
            $stmt->execute([(string) $columnName]);
            $exists = (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            $exists = false;
        }

        $this->columnExistsCache[$cacheKey] = $exists;
        return $exists;
    }

    private function yearCondition(string $alias = 'e'): array
    {
        $selectedYearId = \AcademicYear::getSelectedIdFromSession();
        if ($selectedYearId === null || $selectedYearId <= 0) {
            return ['sql' => '', 'params' => []];
        }

        return [
            'sql' => " AND EXISTS (SELECT 1 FROM inscriptions i WHERE i.num_carte_etud = {$alias}.num_carte_etud AND i.id_annee_acad = :id_annee_acad)",
            'params' => [':id_annee_acad' => $selectedYearId],
        ];
    }

    /**
     * Récupère les rapports vérifiés (approuvés ou désapprouvés)
     * @return array
     */
    public function getRapportsVerifies()
    {
        $titleColumn = $this->columnExists('rapport_etudiants', 'nom_rapport') ? 'r.nom_rapport' : 'r.theme_rapport';
        $dateColumn = $this->columnExists('rapport_etudiants', 'date_rapport')
            ? 'r.date_rapport'
            : ($this->columnExists('rapport_etudiants', 'date_redaction_rapport') ? 'r.date_redaction_rapport' : 'NULL');
        $yearFilter = $this->yearCondition('e');

        if ($this->tableExists('approuver')) {
            $sql = "
                SELECT 
                    r.id_rapport,
                    {$titleColumn} as titre_rapport,
                    r.theme_rapport,
                    {$dateColumn} as date_depot,
                    r.statut_rapport,
                    e.num_carte_etud as num_etu,
                    e.nom_etu,
                    e.prenom_etu,
                    e.email_etu,
                    (
                        SELECT i.id_annee_acad
                        FROM inscriptions i
                        WHERE i.num_carte_etud = e.num_carte_etud
                        ORDER BY i.date_inscription DESC, i.id_annee_acad DESC, i.num_versement DESC
                        LIMIT 1
                    ) AS id_annee_acad,
                    e.promotion_etu,
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
                {$yearFilter['sql']}
                ORDER BY a.date_approv DESC
            ";
        } elseif ($this->tableExists('valider')) {
            $sql = "
                SELECT 
                    r.id_rapport,
                    {$titleColumn} as titre_rapport,
                    r.theme_rapport,
                    {$dateColumn} as date_depot,
                    r.statut_rapport,
                    e.num_carte_etud as num_etu,
                    e.nom_etu,
                    e.prenom_etu,
                    e.email_etu,
                    (
                        SELECT i.id_annee_acad
                        FROM inscriptions i
                        WHERE i.num_carte_etud = e.num_carte_etud
                        ORDER BY i.date_inscription DESC, i.id_annee_acad DESC, i.num_versement DESC
                        LIMIT 1
                    ) AS id_annee_acad,
                    e.promotion_etu,
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
                {$yearFilter['sql']}
                ORDER BY v.date_validation DESC
            ";
        } else {
            return [];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($yearFilter['params']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les statistiques des rapports vérifiés
     * @return array
     */
    public function getStatistiques()
    {
        $yearFilter = $this->yearCondition('e');
        if ($this->tableExists('approuver')) {
            // Total des rapports vérifiés
            $sql = "
                SELECT COUNT(*) as total
                FROM rapport_etudiants r
                INNER JOIN etudiants e ON r.num_etu = e.num_carte_etud
                INNER JOIN approuver a ON r.id_rapport = a.id_rapport
                WHERE a.decision IN ('approuve', 'desapprouve')
                {$yearFilter['sql']}
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($yearFilter['params']);
            $total = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // Rapports approuvés
            $sql = "
                SELECT COUNT(*) as approuves
                FROM rapport_etudiants r
                INNER JOIN etudiants e ON r.num_etu = e.num_carte_etud
                INNER JOIN approuver a ON r.id_rapport = a.id_rapport
                WHERE a.decision = 'approuve'
                {$yearFilter['sql']}
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($yearFilter['params']);
            $approuves = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['approuves'] ?? 0);

            // Rapports désapprouvés
            $sql = "
                SELECT COUNT(*) as desapprouves
                FROM rapport_etudiants r
                INNER JOIN etudiants e ON r.num_etu = e.num_carte_etud
                INNER JOIN approuver a ON r.id_rapport = a.id_rapport
                WHERE a.decision = 'desapprouve'
                {$yearFilter['sql']}
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($yearFilter['params']);
            $desapprouves = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['desapprouves'] ?? 0);
        } elseif ($this->tableExists('valider')) {
            $sql = "
                SELECT COUNT(*) as total
                FROM rapport_etudiants r
                INNER JOIN etudiants e ON r.num_etu = e.num_carte_etud
                INNER JOIN valider v ON r.id_rapport = v.id_rapport
                WHERE v.decision_validation IN ('valider', 'rejeter')
                {$yearFilter['sql']}
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($yearFilter['params']);
            $total = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            $sql = "
                SELECT COUNT(*) as approuves
                FROM rapport_etudiants r
                INNER JOIN etudiants e ON r.num_etu = e.num_carte_etud
                INNER JOIN valider v ON r.id_rapport = v.id_rapport
                WHERE v.decision_validation = 'valider'
                {$yearFilter['sql']}
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($yearFilter['params']);
            $approuves = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['approuves'] ?? 0);

            $sql = "
                SELECT COUNT(*) as desapprouves
                FROM rapport_etudiants r
                INNER JOIN etudiants e ON r.num_etu = e.num_carte_etud
                INNER JOIN valider v ON r.id_rapport = v.id_rapport
                WHERE v.decision_validation = 'rejeter'
                {$yearFilter['sql']}
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($yearFilter['params']);
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

    /**
     * Récupère les détails d'un rapport spécifique
     * @param int $id_rapport
     * @return array|false|null
     */
    public function getDetailsRapport($id_rapport)
    {
        $titleColumn = $this->columnExists('rapport_etudiants', 'nom_rapport') ? 'r.nom_rapport' : 'r.theme_rapport';
        $dateColumn = $this->columnExists('rapport_etudiants', 'date_rapport')
            ? 'r.date_rapport'
            : ($this->columnExists('rapport_etudiants', 'date_redaction_rapport') ? 'r.date_redaction_rapport' : 'NULL');
        $yearFilter = $this->yearCondition('e');

        if ($this->tableExists('approuver')) {
            $sql = "
                SELECT 
                    r.*,
                    {$titleColumn} as nom_rapport,
                    {$dateColumn} as date_rapport,
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
                WHERE r.id_rapport = :id_rapport
                {$yearFilter['sql']}
            ";
        } elseif ($this->tableExists('valider')) {
            $sql = "
                SELECT 
                    r.*,
                    {$titleColumn} as nom_rapport,
                    {$dateColumn} as date_rapport,
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
                WHERE r.id_rapport = :id_rapport
                {$yearFilter['sql']}
            ";
        } else {
            return null;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([':id_rapport' => $id_rapport], $yearFilter['params']));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Prépare les données pour le téléchargement PDF d'un rapport
     * @param int $id_rapport
     * @return array{rapport: array, contenu: string, nomFichier: string}|null Null si le rapport/fichier n'existe pas
     */
    public function preparerDonneesPdf($id_rapport)
    {
        $rapport = $this->getDetailsRapport($id_rapport);

        if (!$rapport) {
            return null;
        }

        $chemin = $rapport['chemin_fichier'] ?? '';
        if (empty($chemin)) {
            $chemin = 'rapport_' . $id_rapport . '.html';
        }
        $fichierContenu = __DIR__ . "/../../ressources/uploads/rapports/" . $chemin;

        if (!file_exists($fichierContenu)) {
            return ['error' => 'file_not_found', 'rapport' => $rapport];
        }

        $contenu = file_get_contents($fichierContenu);

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

        $nomFichier = 'rapport_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $rapport['nom_rapport']) . '_' . date('Y-m-d_H-i-s') . '.pdf';

        return [
            'rapport' => $rapport,
            'html' => $html,
            'nomFichier' => $nomFichier
        ];
    }

    /**
     * Prépare les données pour la consultation d'un rapport
     * @param int $id_rapport
     * @return array{rapport: array, contenu: string}|null Null si le rapport/fichier n'existe pas
     */
    public function preparerDonneesConsultation($id_rapport)
    {
        $rapport = $this->getDetailsRapport($id_rapport);

        if (!$rapport) {
            return null;
        }

        $chemin = $rapport['chemin_fichier'] ?? '';
        if (empty($chemin)) {
            $chemin = 'rapport_' . $id_rapport . '.html';
        }
        $fichierContenu = __DIR__ . "/../../ressources/uploads/rapports/" . $chemin;

        if (!file_exists($fichierContenu)) {
            return ['error' => 'file_not_found', 'rapport' => $rapport];
        }

        $contenu = file_get_contents($fichierContenu);

        return [
            'rapport' => $rapport,
            'contenu' => $contenu
        ];
    }

    /**
     * Enregistre un log d'audit pour l'impression
     * @param int $userId
     */
    public function logImpression($userId)
    {
        $this->auditLog->logImpression($userId, 'rapport_etudiants', 'Succès');
    }

    /**
     * Enregistre un log d'audit pour la consultation
     * @param int $userId
     */
    public function logConsultation($userId)
    {
        $this->auditLog->logAction($userId, 'Consultation', 'rapport_etudiants', 'Succès');
    }
}
