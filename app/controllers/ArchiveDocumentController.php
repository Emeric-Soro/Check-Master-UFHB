<?php
/**
 * ArchiveDocumentController - Archives documents
 */
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/CompteRendu.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

class ArchiveDocumentController
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getConnection();
    }

    /**
     * Liste des documents archivés
     */
    public function index()
    {
        if (!canView()) {
            $_SESSION['error_message'] = "Accès refusé aux archives documents.";
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $anneeId = $_SESSION['archive_annee_acad'] ?? null;
        $type = $_GET['type'] ?? null;

        $documents = $this->getDocumentsArchives($anneeId, $type);

        return [
            'documents' => $documents,
            'type_filter' => $type,
        ];
    }

    /**
     * Visionneuse de document
     */
    public function visionneuse()
    {
        if (!canView()) {
            http_response_code(403);
            echo "Accès refusé";
            exit;
        }

        $type = $_GET['type'] ?? '';
        $id = $_GET['id'] ?? '';

        $chemin = $this->getCheminDocument($type, $id);

        if (!$chemin || !file_exists($chemin)) {
            http_response_code(404);
            echo "Document non trouvé";
            exit;
        }

        // Servir le fichier PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="document.pdf"');
        readfile($chemin);
        exit;
    }

    /**
     * Téléchargement de document
     */
    public function telecharger()
    {
        if (!canView()) {
            http_response_code(403);
            exit;
        }

        $type = $_GET['type'] ?? '';
        $id = $_GET['id'] ?? '';

        $chemin = $this->getCheminDocument($type, $id);

        if (!$chemin || !file_exists($chemin)) {
            http_response_code(404);
            echo "Document non trouvé";
            exit;
        }

        $nomFichier = basename($chemin);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nomFichier . '"');
        header('Content-Length: ' . filesize($chemin));
        readfile($chemin);
        exit;
    }

    /**
     * Téléchargement groupé (ZIP)
     */
    public function telechargerGroupe()
    {
        if (!canCreate()) {
            http_response_code(403);
            exit;
        }

        $ids = $_POST['documents'] ?? [];
        if (empty($ids)) {
            echo "Aucun document sélectionné";
            exit;
        }

        $zip = new ZipArchive();
        $zipName = tempnam(sys_get_temp_dir(), 'archives_') . '.zip';

        if ($zip->open($zipName, ZipArchive::CREATE) !== TRUE) {
            echo "Impossible de créer l'archive";
            exit;
        }

        foreach ($ids as $doc) {
            $chemin = $this->getCheminDocument($doc['type'], $doc['id']);
            if ($chemin && file_exists($chemin)) {
                $zip->addFile($chemin, basename($chemin));
            }
        }

        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="archives_documents.zip"');
        header('Content-Length: ' . filesize($zipName));
        readfile($zipName);
        unlink($zipName);
        exit;
    }

    // Méthodes privées

    private function getDocumentsArchives($anneeId, $type = null)
    {
        $documents = [];

        // Rapports
        if (!$type || $type === 'rapport') {
            $sql = "SELECT 
                        'rapport' as type_doc,
                        re.id_rapport as id_doc,
                        re.chemin_fichier as chemin,
                        re.theme_rapport as titre,
                        re.date_redaction_rapport as date_depot,
                        re.taille_fichier as taille,
                        CONCAT(e.nom_etu, ' ', e.prenom_etu) as etudiant,
                        e.num_carte_etud
                    FROM rapport_etudiants re
                    JOIN etudiants e ON re.num_etu = e.num_carte_etud
                    JOIN inscriptions i ON e.num_carte_etud = i.id_etudiant
                    WHERE i.id_annee_acad = ? AND re.chemin_fichier IS NOT NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            $documents = array_merge($documents, $stmt->fetchAll(PDO::FETCH_OBJ));
        }

        // Comptes rendus
        if (!$type || $type === 'compte_rendu') {
            $sql = "SELECT 
                        'compte_rendu' as type_doc,
                        cr.id_CR as id_doc,
                        cr.chemin_fichier_pdf as chemin,
                        cr.nom_CR as titre,
                        cr.date_CR as date_depot,
                        NULL as taille,
                        CONCAT(e.nom_etu, ' ', e.prenom_etu) as etudiant,
                        e.num_carte_etud
                    FROM compte_rendu cr
                    JOIN etudiants e ON cr.num_etu = e.num_carte_etud
                    JOIN inscriptions i ON e.num_carte_etud = i.id_etudiant
                    WHERE i.id_annee_acad = ? AND cr.chemin_fichier_pdf IS NOT NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anneeId]);
            $documents = array_merge($documents, $stmt->fetchAll(PDO::FETCH_OBJ));
        }

        // Trier par date
        usort($documents, function($a, $b) {
            return strtotime($b->date_depot) - strtotime($a->date_depot);
        });

        return $documents;
    }

    private function getCheminDocument($type, $id)
    {
        switch ($type) {
            case 'rapport':
                $sql = "SELECT chemin_fichier FROM rapport_etudiants WHERE id_rapport = ?";
                break;
            case 'compte_rendu':
                $sql = "SELECT chemin_fichier_pdf as chemin_fichier FROM compte_rendu WHERE id_CR = ?";
                break;
            default:
                return null;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        return $result ? ($result->chemin_fichier ?? null) : null;
    }
}
