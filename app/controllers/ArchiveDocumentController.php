<?php
/**
 * ArchiveDocumentController - Archives documents
 */
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/CompteRendu.php';
require_once __DIR__ . '/../Services/Document/DocumentRegistry.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

class ArchiveDocumentController
{
    private $db;
    private DocumentRegistry $registry;
    private const ALLOWED_TYPES = ['rapport', 'compte_rendu', 'pv_final'];

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getConnection();
        $this->registry = new DocumentRegistry($this->db);
    }

    /**
     * Liste des documents archivés
     */
    public function index()
    {
        if (!canView('archives_documents')) {
            $_SESSION['error_message'] = "Accès refusé aux archives documents.";
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $anneeId = $_SESSION['archive_annee_acad'] ?? null;
        $type = $_GET['type'] ?? null;
        if (!is_string($type) || !in_array($type, self::ALLOWED_TYPES, true)) {
            $type = null;
        }

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
        if (!canView('archives_documents')) {
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
        if (!canView('archives_documents')) {
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
        if (!canView('archives_documents')) {
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
                    WHERE re.chemin_fichier IS NOT NULL AND re.chemin_fichier <> ''";

            $params = [];
            if (is_numeric($anneeId)) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM inscriptions i
                    WHERE i.num_carte_etud = re.num_etu
                      AND i.id_annee_acad = :annee_id
                )";
                $params['annee_id'] = (int) $anneeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $documents = array_merge($documents, $stmt->fetchAll(PDO::FETCH_ASSOC));
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
                    WHERE cr.chemin_fichier_pdf IS NOT NULL AND cr.chemin_fichier_pdf <> ''";

            $params = [];
            if (is_numeric($anneeId)) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM inscriptions i
                    WHERE i.num_carte_etud = cr.num_etu
                      AND i.id_annee_acad = :annee_id
                )";
                $params['annee_id'] = (int) $anneeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $documents = array_merge($documents, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        // PV finaux (fichiers générés dans storage/documents/pv_finaux)
        if (!$type || $type === 'pv_final') {
            $documents = array_merge($documents, $this->getPvFinalDocuments($anneeId));
        }

        // Trier par date
        usort($documents, function($a, $b) {
            $left = strtotime((string) ($b['date_depot'] ?? '')) ?: 0;
            $right = strtotime((string) ($a['date_depot'] ?? '')) ?: 0;
            return $left <=> $right;
        });

        return $documents;
    }

    private function getCheminDocument($type, $id)
    {
        return $this->registry->resolve((string) $type, (string) $id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPvFinalDocuments($anneeId): array
    {
        $baseDir = realpath(__DIR__ . '/../../storage/documents/pv_finaux');
        if ($baseDir === false || !is_dir($baseDir)) {
            return [];
        }

        $candidateYears = $this->resolveCandidateYears($anneeId, $baseDir);
        if ($candidateYears === []) {
            return [];
        }

        $documents = [];
        foreach ($candidateYears as $year) {
            $yearDir = $baseDir . DIRECTORY_SEPARATOR . $year;
            if (!is_dir($yearDir)) {
                continue;
            }

            $files = glob($yearDir . DIRECTORY_SEPARATOR . '*.pdf');
            if (!is_array($files)) {
                continue;
            }

            foreach ($files as $filePath) {
                if (!is_file($filePath)) {
                    continue;
                }

                $filename = basename($filePath);
                $matricule = $this->extractMatriculeFromPvFinalFilename($filename);

                $documents[] = [
                    'type_doc' => 'pv_final',
                    'id_doc' => $this->encodeFileId($filePath),
                    'chemin' => $filePath,
                    'titre' => 'PV Final - ' . ($matricule !== '' ? $matricule : $filename),
                    'date_depot' => date('Y-m-d H:i:s', filemtime($filePath) ?: time()),
                    'taille' => filesize($filePath) ?: 0,
                    'etudiant' => $matricule !== '' ? $matricule : 'N/A',
                    'num_carte_etud' => $matricule,
                ];
            }
        }

        $this->hydrateStudentLabels($documents);

        return $documents;
    }

    /**
     * @param array<int, array<string, mixed>> $documents
     */
    private function hydrateStudentLabels(array &$documents): void
    {
        $matricules = [];
        foreach ($documents as $doc) {
            $matricule = trim((string) ($doc['num_carte_etud'] ?? ''));
            if ($matricule !== '') {
                $matricules[$matricule] = true;
            }
        }

        if ($matricules === []) {
            return;
        }

        $ids = array_keys($matricules);
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = "SELECT num_carte_etud, nom_etu, prenom_etu FROM etudiants WHERE num_carte_etud IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        foreach ($rows as $row) {
            $key = (string) ($row['num_carte_etud'] ?? '');
            if ($key === '') {
                continue;
            }

            $labels[$key] = trim((string) ($row['nom_etu'] ?? '') . ' ' . (string) ($row['prenom_etu'] ?? ''));
        }

        foreach ($documents as &$doc) {
            $matricule = trim((string) ($doc['num_carte_etud'] ?? ''));
            if ($matricule === '') {
                continue;
            }

            if (isset($labels[$matricule]) && $labels[$matricule] !== '') {
                $doc['etudiant'] = $labels[$matricule];
            }
        }
        unset($doc);
    }

    /**
     * @return array<int, string>
     */
    private function resolveCandidateYears($anneeId, $baseDir)
    {
        $years = [];

        if (is_numeric($anneeId)) {
            $stmt = $this->db->prepare('SELECT YEAR(date_deb) AS year_start, YEAR(date_fin) AS year_end FROM annee_academique WHERE id_annee_acad = ? LIMIT 1');
            $stmt->execute([(int) $anneeId]);
            $yearRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($yearRow)) {
                foreach (['year_start', 'year_end'] as $key) {
                    if (isset($yearRow[$key]) && is_numeric($yearRow[$key])) {
                        $years[] = (string) (int) $yearRow[$key];
                    }
                }
            }
        }

        if ($years === []) {
            $entries = @scandir($baseDir);
            if (is_array($entries)) {
                foreach ($entries as $entry) {
                    if (preg_match('/^\d{4}$/', (string) $entry) === 1 && is_dir($baseDir . DIRECTORY_SEPARATOR . $entry)) {
                        $years[] = $entry;
                    }
                }
            }
        }

        $years = array_values(array_unique($years));
        rsort($years, SORT_STRING);

        return $years;
    }

    private function extractMatriculeFromPvFinalFilename($filename)
    {
        $name = (string) $filename;
        if (preg_match('/^PV_Final_([^_]+)_\d{8}_\d{6}\.pdf$/i', $name, $matches) === 1) {
            return (string) $matches[1];
        }

        return '';
    }

    private function encodeFileId($path)
    {
        $encoded = base64_encode((string) $path);
        return rtrim(strtr($encoded, '+/', '-_'), '=');
    }

    private function decodeFileId($id)
    {
        $token = trim((string) $id);
        if ($token === '') {
            return null;
        }

        $base64 = strtr($token, '-_', '+/');
        $padding = strlen($base64) % 4;
        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($base64, true);
        if (!is_string($decoded) || $decoded === '') {
            return null;
        }

        return $decoded;
    }

    private function isPathInside($path, $basePath)
    {
        $normalizedPath = str_replace('\\', '/', (string) $path);
        $normalizedBase = rtrim(str_replace('\\', '/', (string) $basePath), '/') . '/';

        return strncmp($normalizedPath, $normalizedBase, strlen($normalizedBase)) === 0;
    }
}
