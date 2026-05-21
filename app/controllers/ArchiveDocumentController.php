<?php
/**
 * ArchiveDocumentController - Archives documents
 */
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/CompteRendu.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../Services/Document/DocumentRegistry.php';
require_once __DIR__ . '/../Services/Document/DocumentStorageService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

class ArchiveDocumentController
{
    private $db;
    private $anneeModel;
    private DocumentRegistry $registry;
    private \App\Services\Document\DocumentStorageService $documentStorage;
    private const ALLOWED_TYPES = ['rapport', 'compte_rendu', 'pv_final'];

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getConnection();
        $this->anneeModel = new AnneeAcademique($this->db);
        $this->registry = new DocumentRegistry($this->db);
        $this->documentStorage = new \App\Services\Document\DocumentStorageService($this->db, dirname(__DIR__, 2));
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
        $anneeLabel = trim((string) ($_SESSION['archive_annee_libelle'] ?? ''));
        if (!is_numeric($anneeId)) {
            $globalYearId = $_SESSION['global_annee_id'] ?? null;
            if (is_numeric($globalYearId)) {
                $annee = $this->anneeModel->getAnneeAcademiqueById((int) $globalYearId);
            } else {
                $annee = $this->anneeModel->getAnneeAcademiqueActive();
            }

            if ($annee) {
                $anneeId = (int) $annee->id_annee_acad;
                $anneeLabel = date('Y', strtotime((string) $annee->date_deb)) . '-' . date('Y', strtotime((string) $annee->date_fin));
                $_SESSION['archive_annee_acad'] = $anneeId;
                $_SESSION['archive_annee_libelle'] = $anneeLabel;
            }
        }

        $type = $_GET['type'] ?? null;
        if (!is_string($type) || !in_array($type, self::ALLOWED_TYPES, true)) {
            $type = null;
        }

        $documents = $this->getDocumentsArchives($anneeId, null);

        return [
            'documents' => $documents,
            'type_filter' => $type,
            'annee_label' => $anneeLabel,
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

        $chemin = $this->getDatabaseBackedDocumentPath($type, $id);

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

        $chemin = $this->getDatabaseBackedDocumentPath($type, $id);

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
            $chemin = $this->getDatabaseBackedDocumentPath((string) ($doc['type'] ?? ''), (string) ($doc['id'] ?? ''));
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
                        COALESCE(e.num_ident_etud, e.num_carte_etud) AS num_carte_etud
                    FROM rapport_etudiants re
                    JOIN etudiants e ON (re.num_etu = e.num_carte_etud OR re.num_etu = e.num_ident_etud)
                    WHERE (
                        (re.chemin_fichier IS NOT NULL AND re.chemin_fichier <> '')
                        OR EXISTS (
                            SELECT 1
                            FROM documents d
                            WHERE d.entite_type = 'rapport_etudiants'
                              AND d.entite_id = CAST(re.id_rapport AS CHAR)
                              AND d.statut = 'actif'
                              AND d.type_document IN ('rapport', 'html_doc')
                        )
                    )";

            $params = [];
            if (is_numeric($anneeId)) {
                $sql .= " AND (
                    EXISTS (
                        SELECT 1
                        FROM inscriptions i
                        WHERE i.num_carte_etud = re.num_etu
                          AND i.id_annee_acad = :annee_id
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM inscriptions i
                        WHERE i.id_annee_acad = :annee_id_alt
                          AND (
                            i.num_carte_etud = e.num_carte_etud
                            OR i.num_carte_etud = e.num_ident_etud
                          )
                    )
                )";
                $params['annee_id'] = (int) $anneeId;
                $params['annee_id_alt'] = (int) $anneeId;
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
                        COALESCE(e.num_ident_etud, e.num_carte_etud) AS num_carte_etud
                    FROM compte_rendu cr
                    JOIN etudiants e ON (cr.num_etu = e.num_carte_etud OR cr.num_etu = e.num_ident_etud)
                    WHERE " . $this->buildCompteRenduAvailabilitySql('cr') . "
                      AND cr.nom_CR NOT LIKE 'BULLETIN_%'";

            $params = [];
            if (is_numeric($anneeId)) {
                $sql .= " AND (
                    EXISTS (
                        SELECT 1
                        FROM inscriptions i
                        WHERE i.num_carte_etud = cr.num_etu
                          AND i.id_annee_acad = :annee_id
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM inscriptions i
                        WHERE i.id_annee_acad = :annee_id_alt
                          AND (
                            i.num_carte_etud = e.num_carte_etud
                            OR i.num_carte_etud = e.num_ident_etud
                          )
                    )
                )";
                $params['annee_id'] = (int) $anneeId;
                $params['annee_id_alt'] = (int) $anneeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $documents = array_merge($documents, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        // PV finaux (uniquement référencés en base)
        if (!$type || $type === 'pv_final') {
            $documents = array_merge($documents, $this->getPvFinalDocuments($anneeId));
        }

        $documents = array_values(array_filter($documents, function (array $document): bool {
            $typeDoc = (string) ($document['type_doc'] ?? '');
            $idDoc = (string) ($document['id_doc'] ?? '');
            return $typeDoc !== '' && $idDoc !== '' && $this->getDatabaseBackedDocumentPath($typeDoc, $idDoc) !== null;
        }));

        // Trier par date
        usort($documents, function($a, $b) {
            $left = strtotime((string) ($b['date_depot'] ?? '')) ?: 0;
            $right = strtotime((string) ($a['date_depot'] ?? '')) ?: 0;
            return $left <=> $right;
        });

        return $documents;
    }

    private function getPvFinalDocuments($anneeId): array
    {
        $sql = "SELECT
                    'pv_final' AS type_doc,
                    COALESCE(d.reference, dg.reference, ps.num_soutenance) AS id_doc,
                    COALESCE(d.chemin_original, dg.chemin_fichier) AS chemin,
                    CONCAT('PV Final - ', COALESCE(CONCAT(e.nom_etu, ' ', e.prenom_etu), ps.num_etud, ps.num_soutenance)) AS titre,
                    COALESCE(d.date_creation, dg.date_generation, ps.date_soutenance) AS date_depot,
                    COALESCE(d.taille_fichier, dg.taille_fichier, 0) AS taille,
                    COALESCE(CONCAT(e.nom_etu, ' ', e.prenom_etu), ps.num_etud, 'N/A') AS etudiant,
                    COALESCE(e.num_ident_etud, e.num_carte_etud, ps.num_etud) AS num_carte_etud
                FROM programmer_soutenance ps
                LEFT JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
                LEFT JOIN documents d
                    ON d.entite_type = 'programmer_soutenance'
                   AND d.entite_id = ps.num_soutenance
                   AND d.type_document = 'pv_final'
                   AND d.statut = 'actif'
                LEFT JOIN document_genere dg
                    ON dg.id_source = ps.num_soutenance
                   AND dg.type_document IN ('PVF', 'PV_FINAL')
                WHERE (d.id_document IS NOT NULL OR dg.id_document IS NOT NULL)";

        $params = [];
        if (is_numeric($anneeId)) {
            $sql .= " AND EXISTS (
                SELECT 1
                FROM inscriptions i
                WHERE i.id_annee_acad = :annee_id
                  AND (
                    i.num_carte_etud = ps.num_etud
                    OR i.num_carte_etud = e.num_carte_etud
                    OR i.num_carte_etud = e.num_ident_etud
                  )
            )";
            $params['annee_id'] = (int) $anneeId;
        }

        $sql .= " ORDER BY COALESCE(d.date_creation, dg.date_generation, ps.date_soutenance) DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!is_array($rows)) {
            return [];
        }

        return array_values($rows);
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
        $placeholder = implode(', ', array_fill(0, count($ids), '?'));
        $sql = "SELECT num_carte_etud, num_ident_etud, nom_etu, prenom_etu FROM etudiants WHERE num_carte_etud IN ($placeholder) OR num_ident_etud IN ($placeholder)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($ids, $ids));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        foreach ($rows as $row) {
            $fullName = trim((string) ($row['nom_etu'] ?? '') . ' ' . (string) ($row['prenom_etu'] ?? ''));
            foreach (['num_carte_etud', 'num_ident_etud'] as $field) {
                $key = trim((string) ($row[$field] ?? ''));
                if ($key !== '') {
                    $labels[$key] = $fullName;
                }
            }
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

    private function getDatabaseBackedDocumentPath(string $type, string $id): ?string
    {
        $type = trim($type);
        $id = trim($id);
        if ($type === '' || $id === '') {
            return null;
        }

        $storedDocument = $this->documentStorage->findForViewer($type, $id);
        if (is_array($storedDocument)) {
            $cachedPath = $this->documentStorage->materializeToCache($storedDocument);
            if (is_string($cachedPath) && $cachedPath !== '' && is_file($cachedPath)) {
                return $cachedPath;
            }
        }

        return match ($type) {
            'rapport' => $this->getRapportDatabasePath($id),
            'compte_rendu' => $this->getCompteRenduDatabasePath($id),
            'pv_final' => $this->getPvFinalDatabasePath($id),
            default => null,
        };
    }

    private function getRapportDatabasePath(string $id): ?string
    {
        if (ctype_digit($id)) {
            $stmt = $this->db->prepare('SELECT chemin_fichier FROM rapport_etudiants WHERE id_rapport = :id LIMIT 1');
            $stmt->execute(['id' => (int) $id]);
            $path = $this->resolveStoredPdfPath((string) ($stmt->fetchColumn() ?: ''));
            if ($path !== null) {
                return $path;
            }
        }

        if ($this->tableExists('document_genere')) {
            $stmt = $this->db->prepare("SELECT chemin_fichier FROM document_genere WHERE (reference = :id OR id_source = :id) AND type_document = 'RAP' ORDER BY date_generation DESC, id_document DESC LIMIT 1");
            $stmt->execute(['id' => $id]);
            return $this->resolveStoredPdfPath((string) ($stmt->fetchColumn() ?: ''));
        }

        return null;
    }

    private function getCompteRenduDatabasePath(string $id): ?string
    {
        if (!ctype_digit($id)) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT chemin_fichier_pdf FROM compte_rendu WHERE id_CR = :id LIMIT 1');
        $stmt->execute(['id' => (int) $id]);
        return $this->resolveStoredPdfPath((string) ($stmt->fetchColumn() ?: ''));
    }

    private function getPvFinalDatabasePath(string $id): ?string
    {
        if ($this->tableExists('document_genere')) {
            $stmt = $this->db->prepare("SELECT chemin_fichier FROM document_genere WHERE (reference = :id OR id_source = :id) AND type_document IN ('PVF', 'PV_FINAL') ORDER BY date_generation DESC, id_document DESC LIMIT 1");
            $stmt->execute(['id' => $id]);
            $path = $this->resolveStoredPdfPath((string) ($stmt->fetchColumn() ?: ''));
            if ($path !== null) {
                return $path;
            }
        }

        return null;
    }

    private function resolveStoredPdfPath(string $storedPath): ?string
    {
        $storedPath = trim($storedPath);
        if ($storedPath === '' || strcasecmp((string) pathinfo($storedPath, PATHINFO_EXTENSION), 'pdf') !== 0) {
            return null;
        }

        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath);
        $candidates = [];
        if ($this->isAbsolutePath($normalized)) {
            $candidates[] = $normalized;
        } else {
            $relative = ltrim($normalized, DIRECTORY_SEPARATOR);
            $projectRoot = dirname(__DIR__, 2);
            $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . $relative;
            $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $relative;
            $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $relative;
        }

        foreach ($candidates as $candidate) {
            $realPath = realpath($candidate);
            if ($realPath !== false && is_file($realPath) && strcasecmp((string) pathinfo($realPath, PATHINFO_EXTENSION), 'pdf') === 0) {
                return $realPath;
            }
        }

        return null;
    }

    private function tableExists(string $tableName): bool
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name'
            );
            $stmt->execute(['table_name' => $tableName]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function isAbsolutePath(string $path): bool
    {
        return $path !== '' && (
            $path[0] === '/'
            || $path[0] === '\\'
            || (strlen($path) >= 2 && $path[1] === ':')
        );
    }

    private function buildCompteRenduAvailabilitySql(string $alias): string
    {
        return "((
                    {$alias}.chemin_fichier_pdf IS NOT NULL
                    AND {$alias}.chemin_fichier_pdf <> ''
                ) OR EXISTS (
                    SELECT 1
                    FROM documents d
                    WHERE d.entite_type = 'compte_rendu'
                      AND d.entite_id = CAST({$alias}.id_CR AS CHAR)
                      AND d.statut = 'actif'
                      AND d.type_document = 'compte_rendu'
                ))";
    }


}
