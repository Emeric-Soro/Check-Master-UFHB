<?php
require_once __DIR__ . '/../utils/permissions_helper.php';
require_once __DIR__ . '/../Services/Document/DocumentRegistry.php';

/**
 * Contrôleur pour la bibliothèque personnelle de documents.
 * Route : ?page=documents
 */
class DocumentsController
{
    private $db;
    private string $projectRoot;
    private DocumentRegistry $registry;

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getConnection();
        $this->projectRoot = dirname(__DIR__, 2);
        $this->registry = new DocumentRegistry($this->db);
    }

    public function index(): array
    {
        if (!canView('documents')) {
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'acceder a cette page.";
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $userGroup = (int) ($_SESSION['id_GU'] ?? 0);
        $studentNum = trim((string) ($_SESSION['num_etu'] ?? ''));
        $login = trim((string) ($_SESSION['login_utilisateur'] ?? ''));
        $typeFilter = trim((string) ($_GET['type_doc'] ?? ''));
        $anneeFilter = isset($_GET['annee_id']) && $_GET['annee_id'] !== ''
            ? (int) $_GET['annee_id']
            : null;

        $documents = [];

        try {
            if ($userGroup === 13 && $studentNum !== '') {
                $documents = $this->getStudentDocuments($studentNum, $typeFilter, $anneeFilter);
            } elseif ($userGroup === 12) {
                $enseignantId = $this->getEnseignantId($login);
                $documents = $enseignantId !== null
                    ? $this->getEnseignantDocuments($enseignantId, $typeFilter, $anneeFilter)
                    : [];
            } else {
                $documents = $this->getAllDocuments($typeFilter, $anneeFilter);
            }
        } catch (\Throwable $e) {
            error_log('[DocumentsController] Error: ' . $e->getMessage());
        }

        $documents = $this->sortDocuments($this->filterDisplayableDocuments($documents));

        return [
            'documents' => $documents,
            'type_filter' => $typeFilter,
            'annee_filter' => $anneeFilter,
            'annees' => $this->getAnneesAcademiques(),
            'user_group' => $userGroup,
        ];
    }

    private function getStudentDocuments(string $numEtu, string $typeFilter, ?int $anneeFilter): array
    {
        $docs = [];

        if ($typeFilter === '' || $typeFilter === 'rapport') {
            $sql = "SELECT
                        'rapport' AS type_doc,
                        CAST(re.id_rapport AS CHAR) AS id_doc,
                        re.theme_rapport AS titre,
                        re.date_redaction_rapport AS date_depot,
                        re.statut_rapport AS statut
                    FROM rapport_etudiants re
                    LEFT JOIN etudiants e ON (e.num_carte_etud = re.num_etu OR e.num_ident_etud = re.num_etu)
                    WHERE (re.num_etu = :num_etu OR e.num_carte_etud = :num_etu OR e.num_ident_etud = :num_etu)";
            $params = [':num_etu' => $numEtu];
            if ($anneeFilter !== null) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM inscriptions i
                    WHERE i.num_carte_etud = re.num_etu
                      AND i.id_annee_acad = :annee_id
                )";
                $params[':annee_id'] = $anneeFilter;
            }
            $sql .= " ORDER BY re.date_redaction_rapport DESC";
            $docs = array_merge($docs, $this->fetchAll($sql, $params));
        }

        if ($typeFilter === '' || $typeFilter === 'compte_rendu') {
            $sql = "SELECT
                        'compte_rendu' AS type_doc,
                        CAST(cr.id_CR AS CHAR) AS id_doc,
                        cr.nom_CR AS titre,
                        cr.date_CR AS date_depot,
                        'Finalise' AS statut
                    FROM compte_rendu cr
                    LEFT JOIN etudiants e ON (e.num_carte_etud = cr.num_etu OR e.num_ident_etud = cr.num_etu)
                    WHERE (cr.num_etu = :num_etu OR e.num_carte_etud = :num_etu OR e.num_ident_etud = :num_etu)
                      AND " . $this->buildCompteRenduAvailabilitySql('cr', 'compte_rendu') . "
                      AND cr.nom_CR NOT LIKE 'BULLETIN_%'";
            $params = [':num_etu' => $numEtu];
            if ($anneeFilter !== null) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM inscriptions i
                    WHERE i.num_carte_etud = cr.num_etu
                      AND i.id_annee_acad = :annee_id
                )";
                $params[':annee_id'] = $anneeFilter;
            }
            $sql .= " ORDER BY cr.date_CR DESC";
            $docs = array_merge($docs, $this->fetchAll($sql, $params));
        }

        if ($typeFilter === '' || $typeFilter === 'bulletin') {
            $sql = "SELECT
                        'bulletin' AS type_doc,
                        CAST(cr.id_CR AS CHAR) AS id_doc,
                        cr.nom_CR AS titre,
                        cr.date_CR AS date_depot,
                        'Publie' AS statut
                    FROM compte_rendu cr
                    LEFT JOIN etudiants e ON (e.num_carte_etud = cr.num_etu OR e.num_ident_etud = cr.num_etu)
                    WHERE (cr.num_etu = :num_etu OR e.num_carte_etud = :num_etu OR e.num_ident_etud = :num_etu)
                      AND " . $this->buildCompteRenduAvailabilitySql('cr', 'bulletin') . "
                      AND cr.nom_CR LIKE 'BULLETIN_%'";
            $params = [':num_etu' => $numEtu];
            if ($anneeFilter !== null) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM inscriptions i
                    WHERE i.num_carte_etud = cr.num_etu
                      AND i.id_annee_acad = :annee_id
                )";
                $params[':annee_id'] = $anneeFilter;
            }
            $sql .= " ORDER BY cr.date_CR DESC";
            $docs = array_merge($docs, $this->fetchAll($sql, $params));
        }

        if ($typeFilter === '' || $typeFilter === 'recu') {
            $sql = "SELECT
                        'recu' AS type_doc,
                        CONCAT(i.num_carte_etud, '-', i.id_annee_acad, '-', i.num_versement) AS id_doc,
                        CONCAT('Recu de paiement - Versement ', i.num_versement) AS titre,
                        i.date_versement AS date_depot,
                        CASE
                            WHEN COALESCE(i.solde, 0) <= 0 THEN 'Solde'
                            ELSE 'Disponible'
                        END AS statut
                    FROM inscriptions i
                    LEFT JOIN etudiants e ON (e.num_carte_etud = i.num_carte_etud OR e.num_ident_etud = i.num_carte_etud)
                    WHERE (i.num_carte_etud = :num_etu OR e.num_carte_etud = :num_etu OR e.num_ident_etud = :num_etu)";
            $params = [':num_etu' => $numEtu];
            if ($anneeFilter !== null) {
                $sql .= " AND i.id_annee_acad = :annee_id";
                $params[':annee_id'] = $anneeFilter;
            }
            $sql .= " ORDER BY i.date_versement DESC, i.num_versement DESC";
            $docs = array_merge($docs, $this->fetchAll($sql, $params));
        }

        if ($typeFilter === '' || $typeFilter === 'pv_final') {
            $sql = "SELECT DISTINCT
                        'pv_final' AS type_doc,
                        ps.num_soutenance AS id_doc,
                        CONCAT('PV Final - ', ps.theme_soutenance) AS titre,
                        ps.date_soutenance AS date_depot,
                        'Disponible' AS statut
                    FROM programmer_soutenance ps
                    LEFT JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
                    WHERE (ps.num_etud = :num_etu OR e.num_carte_etud = :num_etu OR e.num_ident_etud = :num_etu)";
            $params = [':num_etu' => $numEtu];
            if ($anneeFilter !== null) {
                $sql .= " AND ps.id_annee_acad = :annee_id";
                $params[':annee_id'] = $anneeFilter;
            }
            $sql .= " ORDER BY ps.date_soutenance DESC, ps.heure_soutenance DESC";
            $docs = array_merge($docs, $this->fetchAll($sql, $params));
        }

        return $docs;
    }

    private function getEnseignantDocuments(string $enseignantId, string $typeFilter, ?int $anneeFilter): array
    {
        $docs = [];

        if ($typeFilter === '' || $typeFilter === 'rapport') {
            $sql = "SELECT DISTINCT
                        'rapport' AS type_doc,
                        CAST(re.id_rapport AS CHAR) AS id_doc,
                        re.theme_rapport AS titre,
                        re.date_redaction_rapport AS date_depot,
                        re.statut_rapport AS statut,
                        CONCAT(e.nom_etu, ' ', e.prenom_etu) AS etudiant
                    FROM affecter a
                    INNER JOIN rapport_etudiants re ON re.id_rapport = a.id_rapport
                    INNER JOIN etudiants e ON e.num_carte_etud = re.num_etu
                    WHERE a.id_enseignant = :id_ens";
            $params = [':id_ens' => $enseignantId];
            if ($anneeFilter !== null) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM inscriptions i
                    WHERE i.num_carte_etud = re.num_etu
                      AND i.id_annee_acad = :annee_id
                )";
                $params[':annee_id'] = $anneeFilter;
            }
            $sql .= " ORDER BY re.date_redaction_rapport DESC";
            $docs = array_merge($docs, $this->fetchAll($sql, $params));
        }

        if ($typeFilter === '' || $typeFilter === 'compte_rendu' || $typeFilter === 'pv_commission') {
            $sql = "SELECT DISTINCT
                        cr.id_CR,
                        cr.nom_CR,
                        cr.date_CR,
                        CONCAT(e.nom_etu, ' ', e.prenom_etu) AS etudiant
                    FROM compte_rendu cr
                    LEFT JOIN compte_rendu_rapport crr ON crr.id_CR = cr.id_CR
                    LEFT JOIN rapport_etudiants re ON re.id_rapport = crr.id_rapport
                    LEFT JOIN affecter a ON a.id_rapport = re.id_rapport
                    LEFT JOIN rendre rd ON rd.id_CR = cr.id_CR
                    LEFT JOIN etudiants e ON e.num_carte_etud = COALESCE(re.num_etu, cr.num_etu)
                    WHERE (a.id_enseignant = :id_ens OR rd.id_enseignant = :id_ens)
                      AND " . $this->buildCompteRenduAvailabilitySql('cr', 'compte_rendu', ['pv_commission']) . "
                      AND cr.nom_CR NOT LIKE 'BULLETIN_%'";
            $params = [':id_ens' => $enseignantId];
            if ($anneeFilter !== null) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM inscriptions i
                    WHERE i.num_carte_etud = COALESCE(re.num_etu, cr.num_etu)
                      AND i.id_annee_acad = :annee_id
                )";
                $params[':annee_id'] = $anneeFilter;
            }
            $sql .= " ORDER BY cr.date_CR DESC";
            $rows = $this->fetchAll($sql, $params);

            if ($typeFilter === '' || $typeFilter === 'compte_rendu') {
                foreach ($rows as $row) {
                    $docs[] = [
                        'type_doc' => 'compte_rendu',
                        'id_doc' => (string) ($row['id_CR'] ?? ''),
                        'titre' => (string) ($row['nom_CR'] ?? 'Compte-rendu'),
                        'date_depot' => $row['date_CR'] ?? null,
                        'statut' => 'Finalise',
                        'etudiant' => $row['etudiant'] ?? '—',
                    ];
                }
            }

            if ($typeFilter === '' || $typeFilter === 'pv_commission') {
                foreach ($rows as $row) {
                    $docs[] = [
                        'type_doc' => 'pv_commission',
                        'id_doc' => (string) ($row['id_CR'] ?? ''),
                        'titre' => 'PV Commission - ' . (string) ($row['nom_CR'] ?? 'Compte-rendu'),
                        'date_depot' => $row['date_CR'] ?? null,
                        'statut' => 'Disponible',
                        'etudiant' => $row['etudiant'] ?? '—',
                    ];
                }
            }
        }

        if ($typeFilter === '' || $typeFilter === 'pv_final') {
            $sql = "SELECT DISTINCT
                        'pv_final' AS type_doc,
                        ps.num_soutenance AS id_doc,
                        CONCAT('PV Final - ', ps.theme_soutenance) AS titre,
                        ps.date_soutenance AS date_depot,
                        'Disponible' AS statut,
                        CONCAT(e.nom_etu, ' ', e.prenom_etu) AS etudiant
                    FROM programmer_soutenance ps
                    INNER JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)
                    LEFT JOIN enseignant_jury ej ON ej.num_soutenance = ps.num_soutenance
                    LEFT JOIN rapport_etudiants re ON re.num_etu = COALESCE(e.num_carte_etud, e.num_ident_etud, ps.num_etud)
                    LEFT JOIN affecter a ON a.id_rapport = re.id_rapport
                    WHERE (ej.id_enseignant = :id_ens OR a.id_enseignant = :id_ens)";
            $params = [':id_ens' => $enseignantId];
            if ($anneeFilter !== null) {
                $sql .= " AND ps.id_annee_acad = :annee_id";
                $params[':annee_id'] = $anneeFilter;
            }
            $sql .= " ORDER BY ps.date_soutenance DESC, ps.heure_soutenance DESC";
            $docs = array_merge($docs, $this->fetchAll($sql, $params));
        }

        return $docs;
    }

    private function getAllDocuments(string $typeFilter, ?int $anneeFilter): array
    {
        $docs = [];

        if ($typeFilter === '' || $typeFilter === 'rapport') {
            $sql = "SELECT
                        'rapport' AS type_doc,
                        CAST(re.id_rapport AS CHAR) AS id_doc,
                        re.theme_rapport AS titre,
                        re.date_redaction_rapport AS date_depot,
                        re.statut_rapport AS statut,
                        CONCAT(e.nom_etu, ' ', e.prenom_etu) AS etudiant
                    FROM rapport_etudiants re
                    INNER JOIN etudiants e ON e.num_carte_etud = re.num_etu
                    WHERE 1 = 1";
            $params = [];
            if ($anneeFilter !== null) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM inscriptions i
                    WHERE i.num_carte_etud = re.num_etu
                      AND i.id_annee_acad = :annee_id
                )";
                $params[':annee_id'] = $anneeFilter;
            }
            $sql .= " ORDER BY re.date_redaction_rapport DESC LIMIT 300";
            $docs = array_merge($docs, $this->fetchAll($sql, $params));
        }

        if ($typeFilter === '' || $typeFilter === 'compte_rendu') {
            $sql = "SELECT
                        'compte_rendu' AS type_doc,
                        CAST(cr.id_CR AS CHAR) AS id_doc,
                        cr.nom_CR AS titre,
                        cr.date_CR AS date_depot,
                        'Finalise' AS statut,
                        CONCAT(e.nom_etu, ' ', e.prenom_etu) AS etudiant
                    FROM compte_rendu cr
                    INNER JOIN etudiants e ON e.num_carte_etud = cr.num_etu
                    WHERE " . $this->buildCompteRenduAvailabilitySql('cr', 'compte_rendu') . "
                      AND cr.nom_CR NOT LIKE 'BULLETIN_%'";
            $params = [];
            if ($anneeFilter !== null) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM inscriptions i
                    WHERE i.num_carte_etud = cr.num_etu
                      AND i.id_annee_acad = :annee_id
                )";
                $params[':annee_id'] = $anneeFilter;
            }
            $sql .= " ORDER BY cr.date_CR DESC LIMIT 300";
            $docs = array_merge($docs, $this->fetchAll($sql, $params));
        }

        if ($typeFilter === '' || $typeFilter === 'bulletin') {
            $sql = "SELECT
                        'bulletin' AS type_doc,
                        CAST(cr.id_CR AS CHAR) AS id_doc,
                        cr.nom_CR AS titre,
                        cr.date_CR AS date_depot,
                        'Publie' AS statut,
                        CONCAT(e.nom_etu, ' ', e.prenom_etu) AS etudiant
                    FROM compte_rendu cr
                    INNER JOIN etudiants e ON e.num_carte_etud = cr.num_etu
                    WHERE " . $this->buildCompteRenduAvailabilitySql('cr', 'bulletin') . "
                      AND cr.nom_CR LIKE 'BULLETIN_%'";
            $params = [];
            if ($anneeFilter !== null) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM inscriptions i
                    WHERE i.num_carte_etud = cr.num_etu
                      AND i.id_annee_acad = :annee_id
                )";
                $params[':annee_id'] = $anneeFilter;
            }
            $sql .= " ORDER BY cr.date_CR DESC LIMIT 300";
            $docs = array_merge($docs, $this->fetchAll($sql, $params));
        }

        if ($typeFilter === '' || $typeFilter === 'recu') {
            $sql = "SELECT
                        'recu' AS type_doc,
                        CONCAT(i.num_carte_etud, '-', i.id_annee_acad, '-', i.num_versement) AS id_doc,
                        CONCAT('Recu de paiement - ', COALESCE(e.nom_etu, i.num_carte_etud), ' / Versement ', i.num_versement) AS titre,
                        i.date_versement AS date_depot,
                        CASE
                            WHEN COALESCE(i.solde, 0) <= 0 THEN 'Solde'
                            ELSE 'Disponible'
                        END AS statut,
                        CONCAT(COALESCE(e.nom_etu, ''), ' ', COALESCE(e.prenom_etu, '')) AS etudiant
                    FROM inscriptions i
                    LEFT JOIN etudiants e ON (e.num_carte_etud = i.num_carte_etud OR e.num_ident_etud = i.num_carte_etud)";
            $params = [];
            if ($anneeFilter !== null) {
                $sql .= " WHERE i.id_annee_acad = :annee_id";
                $params[':annee_id'] = $anneeFilter;
            }
            $sql .= " ORDER BY i.date_versement DESC, i.num_versement DESC LIMIT 300";
            $docs = array_merge($docs, $this->fetchAll($sql, $params));
        }

        if ($typeFilter === '' || $typeFilter === 'pv_final') {
            $sql = "SELECT DISTINCT
                        'pv_final' AS type_doc,
                        ps.num_soutenance AS id_doc,
                        CONCAT('PV Final - ', ps.theme_soutenance) AS titre,
                        ps.date_soutenance AS date_depot,
                        'Disponible' AS statut,
                        CONCAT(e.nom_etu, ' ', e.prenom_etu) AS etudiant
                    FROM programmer_soutenance ps
                    INNER JOIN etudiants e ON (e.num_carte_etud = ps.num_etud OR e.num_ident_etud = ps.num_etud)";
            $params = [];
            if ($anneeFilter !== null) {
                $sql .= " WHERE ps.id_annee_acad = :annee_id";
                $params[':annee_id'] = $anneeFilter;
            }
            $sql .= " ORDER BY ps.date_soutenance DESC, ps.heure_soutenance DESC LIMIT 300";
            $docs = array_merge($docs, $this->fetchAll($sql, $params));
        }

        if ($typeFilter === '' || $typeFilter === 'planning') {
            $docs = array_merge($docs, $this->getPlanningDocuments($anneeFilter));
        }

        return $docs;
    }

    private function getEnseignantId(string $login): ?string
    {
        if ($login === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT id_enseignant
             FROM enseignants
             WHERE mail_enseignant = :login
             LIMIT 1'
        );
        $stmt->execute([':login' => $login]);
        $result = $stmt->fetchColumn();

        return $result !== false ? (string) $result : null;
    }

    private function getAnneesAcademiques(): array
    {
        $stmt = $this->db->query(
            "SELECT id_annee_acad, CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) AS libelle
             FROM annee_academique
             ORDER BY date_deb DESC
             LIMIT 10"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPlanningDocuments(?int $anneeFilter): array
    {
        $years = $this->resolvePlanningYears($anneeFilter);
        $baseDir = realpath($this->projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'planning');
        if ($baseDir === false || !is_dir($baseDir)) {
            return [];
        }

        $docs = [];
        foreach ($years as $year) {
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
                $reference = pathinfo($filename, PATHINFO_FILENAME);
                $docs[] = [
                    'type_doc' => 'planning',
                    'id_doc' => $reference,
                    'titre' => 'Planning des soutenances - ' . $reference,
                    'date_depot' => date('Y-m-d H:i:s', filemtime($filePath) ?: time()),
                    'statut' => 'Genere',
                    'etudiant' => '—',
                ];
            }
        }

        return $docs;
    }

    /**
     * @return array<int, string>
     */
    private function resolvePlanningYears(?int $anneeFilter): array
    {
        $baseDir = $this->projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'planning';
        if (!is_dir($baseDir)) {
            return [];
        }

        if ($anneeFilter !== null) {
            $stmt = $this->db->prepare(
                'SELECT YEAR(date_deb) AS year_start, YEAR(date_fin) AS year_end
                 FROM annee_academique
                 WHERE id_annee_acad = :annee_id
                 LIMIT 1'
            );
            $stmt->execute([':annee_id' => $anneeFilter]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($row)) {
                $years = [];
                foreach (['year_start', 'year_end'] as $key) {
                    if (!empty($row[$key])) {
                        $years[] = (string) (int) $row[$key];
                    }
                }
                if ($years !== []) {
                    return array_values(array_unique($years));
                }
            }
        }

        $entries = scandir($baseDir);
        if (!is_array($entries)) {
            return [];
        }

        return array_values(array_filter($entries, static fn(string $entry): bool => preg_match('/^\d{4}$/', $entry) === 1));
    }

    /**
     * @param array<int, array<string, mixed>> $documents
     * @return array<int, array<string, mixed>>
     */
    private function sortDocuments(array $documents): array
    {
        usort($documents, static function (array $left, array $right): int {
            $leftDate = strtotime((string) ($left['date_depot'] ?? '')) ?: 0;
            $rightDate = strtotime((string) ($right['date_depot'] ?? '')) ?: 0;

            return $rightDate <=> $leftDate;
        });

        return $documents;
    }

    /**
     * @param array<int, array<string, mixed>> $documents
     * @return array<int, array<string, mixed>>
     */
    private function filterDisplayableDocuments(array $documents): array
    {
        $generatorBackedTypes = ['rapport', 'recu', 'pv_commission', 'pv_final'];
        $filtered = [];

        foreach ($documents as $document) {
            $type = trim((string) ($document['type_doc'] ?? ''));
            $id = trim((string) ($document['id_doc'] ?? ''));
            if ($type === '' || $id === '') {
                continue;
            }

            if ($this->registry->hasDocument($type, $id) || in_array($type, $generatorBackedTypes, true)) {
                $filtered[] = $document;
            }
        }

        return $filtered;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    private function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<int, string> $additionalTypes
     */
    private function buildCompteRenduAvailabilitySql(string $alias, string $primaryType, array $additionalTypes = []): string
    {
        $types = array_values(array_unique(array_merge([$primaryType], $additionalTypes)));
        $quotedTypes = array_map(
            static fn(string $type): string => "'" . str_replace("'", "''", $type) . "'",
            $types
        );

        return "((
                    {$alias}.chemin_fichier_pdf IS NOT NULL
                    AND {$alias}.chemin_fichier_pdf <> ''
                ) OR EXISTS (
                    SELECT 1
                    FROM documents d
                    WHERE d.entite_type = 'compte_rendu'
                      AND d.entite_id = CAST({$alias}.id_CR AS CHAR)
                      AND d.statut = 'actif'
                      AND d.type_document IN (" . implode(', ', $quotedTypes) . ')
                ))';
    }
}
