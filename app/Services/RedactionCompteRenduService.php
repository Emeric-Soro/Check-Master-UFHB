<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Valider.php';
require_once __DIR__ . '/../models/CompteRendu.php';
require_once __DIR__ . '/../models/Enseignant.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../utils/EmailService.php';
require_once __DIR__ . '/../Services/Document/PdfGeneratorService.php';
require_once __DIR__ . '/../Services/Document/DocumentStorageService.php';
require_once __DIR__ . '/../utils/AcademicYear.php';


class RedactionCompteRenduService
{
    private $pdo;
    private $columnExistsCache = [];

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?: \Database::getConnection();
    }

    private function getSelectedYearId(): ?int
    {
        return \AcademicYear::getSelectedIdFromSession();
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $key = strtolower($tableName . '.' . $columnName);
        if (array_key_exists($key, $this->columnExistsCache)) {
            return $this->columnExistsCache[$key];
        }

        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
            $stmt->execute([$columnName]);
            $exists = (bool) $stmt->fetchColumn();
            $this->columnExistsCache[$key] = $exists;
            return $exists;
        } catch (\Throwable $e) {
            $this->columnExistsCache[$key] = false;
            return false;
        }
    }

    private function getRapportsValidesForSelectedYear(): array
    {
        try {
            $selectedYearId = $this->getSelectedYearId();
            $hasNumIdentEtud = $this->columnExists('etudiants', 'num_ident_etud');
            $studentJoinCondition = 'r.num_etu = e.num_carte_etud';
            if ($hasNumIdentEtud) {
                $studentJoinCondition .= ' OR r.num_etu = e.num_ident_etud';
            }
            $inscriptionMatchSql = $hasNumIdentEtud
                ? "(i2.num_carte_etud = r.num_etu OR i2.num_carte_etud = e.num_carte_etud OR i2.num_carte_etud = e.num_ident_etud)"
                : "(i2.num_carte_etud = r.num_etu OR i2.num_carte_etud = e.num_carte_etud)";
            $crStudentIdentCondition = $hasNumIdentEtud
                ? "OR cr_student.num_etu = e.num_ident_etud"
                : "";

            $sql = "
                SELECT
                    r.id_rapport,
                    r.num_etu,
                    r.theme_rapport,
                    COALESCE(e.prenom_etu, '') AS prenom_etu,
                    COALESCE(e.nom_etu, '') AS nom_etu,
                    COALESCE(e.promotion_etu, '') AS promotion_etu,
                    v2.decision_validation,
                    v2.date_validation,
                    ins.id_annee_acad,
                    COALESCE(cr_link.existing_cr_id, 0) AS existing_cr_id,
                    COALESCE(cr_link.existing_cr_count, 0) AS existing_cr_count,
                    COALESCE(aff.current_encadrant_id, '') AS current_encadrant_id,
                    COALESCE(aff.current_directeur_id, '') AS current_directeur_id
                FROM rapport_etudiants r
                LEFT JOIN etudiants e ON ({$studentJoinCondition})
                LEFT JOIN inscriptions ins ON (ins.num_carte_etud, ins.id_annee_acad, ins.num_versement) = (
                            SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement
                            FROM inscriptions i2 
                            WHERE {$inscriptionMatchSql}
                            ORDER BY i2.date_inscription DESC, i2.num_versement DESC LIMIT 1
                        )
                JOIN (
                    SELECT id_rapport, MAX(date_validation) AS last_validation
                    FROM valider
                    GROUP BY id_rapport
                ) v1 ON r.id_rapport = v1.id_rapport
                JOIN valider v2 ON v2.id_rapport = v1.id_rapport AND v2.date_validation = v1.last_validation
                LEFT JOIN (
                    SELECT
                        id_rapport,
                        MAX(id_CR) AS existing_cr_id,
                        COUNT(DISTINCT id_CR) AS existing_cr_count
                    FROM compte_rendu_rapport
                    GROUP BY id_rapport
                ) cr_link ON cr_link.id_rapport = r.id_rapport
                LEFT JOIN (
                    SELECT
                        num_etu,
                        MAX(id_CR) AS existing_student_cr_id,
                        COUNT(DISTINCT id_CR) AS existing_student_cr_count
                    FROM compte_rendu
                    GROUP BY num_etu
                ) cr_student ON (
                    cr_student.num_etu = r.num_etu
                    OR cr_student.num_etu = e.num_carte_etud
                    {$crStudentIdentCondition}
                )
                LEFT JOIN (
                    SELECT
                        id_rapport,
                        MAX(CASE WHEN role = 'encadrant' THEN id_enseignant END) AS current_encadrant_id,
                        MAX(CASE WHEN role = 'directeur' THEN id_enseignant END) AS current_directeur_id
                    FROM affecter
                    GROUP BY id_rapport
                ) aff ON aff.id_rapport = r.id_rapport
                WHERE v2.decision_validation IN ('valider', 'rejeter')
                  AND COALESCE(cr_link.existing_cr_count, 0) = 0
                  AND COALESCE(cr_student.existing_student_cr_count, 0) = 0
            ";

            $params = [];
            if ($selectedYearId !== null && $selectedYearId > 0) {
                $sql .= " AND ins.id_annee_acad = :id_annee_acad";
                $params[':id_annee_acad'] = $selectedYearId;
            }

            $sql .= " ORDER BY v2.decision_validation DESC, r.theme_rapport";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->deduplicateValidatedReports($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des rapports validés : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Réduit les doublons de dossiers issus des multiples versions d'un rapport
     * ou des doublons de jointure sur la dernière validation.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function deduplicateValidatedReports(array $rows): array
    {
        $byReportId = [];
        foreach ($rows as $row) {
            $reportId = (int) ($row['id_rapport'] ?? 0);
            if ($reportId <= 0) {
                continue;
            }

            if (!isset($byReportId[$reportId])) {
                $byReportId[$reportId] = $row;
                continue;
            }

            $currentDate = strtotime((string) ($row['date_validation'] ?? '')) ?: 0;
            $savedDate = strtotime((string) ($byReportId[$reportId]['date_validation'] ?? '')) ?: 0;
            if ($currentDate >= $savedDate) {
                $byReportId[$reportId] = $row;
            }
        }

        $byLogicalKey = [];
        foreach ($byReportId as $row) {
            $studentKey = mb_strtolower(trim((string) ($row['num_etu'] ?? '')));
            $themeKey = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) ($row['theme_rapport'] ?? ''))));
            $yearKey = (string) ((int) ($row['id_annee_acad'] ?? 0));
            $logicalKey = $studentKey . '|' . $themeKey . '|' . $yearKey;

            if ($studentKey === '' || $themeKey === '') {
                $logicalKey = 'report|' . (int) ($row['id_rapport'] ?? 0);
            }

            if (!isset($byLogicalKey[$logicalKey])) {
                $byLogicalKey[$logicalKey] = $row;
                continue;
            }

            $savedId = (int) ($byLogicalKey[$logicalKey]['id_rapport'] ?? 0);
            $currentId = (int) ($row['id_rapport'] ?? 0);
            if ($currentId >= $savedId) {
                $byLogicalKey[$logicalKey] = $row;
            }
        }

        $deduped = array_values($byLogicalKey);
        usort($deduped, static function (array $a, array $b): int {
            $decisionCompare = strcmp((string) ($b['decision_validation'] ?? ''), (string) ($a['decision_validation'] ?? ''));
            if ($decisionCompare !== 0) {
                return $decisionCompare;
            }

            $themeCompare = strcmp((string) ($a['theme_rapport'] ?? ''), (string) ($b['theme_rapport'] ?? ''));
            if ($themeCompare !== 0) {
                return $themeCompare;
            }

            return ((int) ($b['id_rapport'] ?? 0)) <=> ((int) ($a['id_rapport'] ?? 0));
        });

        return $deduped;
    }

    /**
     * Récupérer les données nécessaires à la vue index.
     *
     * @return array ['rapports_valides' => array, 'enseignants' => array]
     */
    public function getIndexData(): array
    {
        $rapportsValides = $this->getRapportsValidesForSelectedYear();
        $enseignantModel = new \Enseignant($this->pdo);
        $enseignants = $enseignantModel->getAllEnseignants();

        return [
            'rapports_valides' => $rapportsValides,
            'enseignants' => $enseignants,
        ];
    }

    /**
     * Prépare les données d'édition d'un compte rendu existant.
     *
     * @return array<string, mixed>|null
     */
    public function getEditableCompteRenduData(int $idCR): ?array
    {
        $compteRendu = $this->getCompteRenduById($idCR);
        if ($compteRendu === null) {
            return null;
        }

        $linkedReports = $this->getLinkedReportsForCompteRendu($idCR);
        if ($linkedReports === []) {
            $linkedReports = $this->getFallbackReportsForCompteRendu($compteRendu);
        }
        $linkedReports = $this->applyCompteRenduAssignmentFallbacks($linkedReports, $compteRendu);
        if ($linkedReports === []) {
            return [
                'compte_rendu' => $compteRendu,
                'report_ids' => [],
                'report_assignments' => [],
                'linked_reports' => [],
            ];
        }

        $reportIds = [];
        $assignments = [];
        foreach ($linkedReports as $report) {
            $reportId = (int) ($report['id_rapport'] ?? 0);
            if ($reportId <= 0) {
                continue;
            }

            $reportIds[] = $reportId;
            $assignments[$reportId] = [
                'encadrant' => (string) ($report['current_encadrant_id'] ?? ''),
                'directeur' => (string) ($report['current_directeur_id'] ?? ''),
            ];
        }

        return [
            'compte_rendu' => $compteRendu,
            'report_ids' => array_values(array_unique($reportIds)),
            'report_assignments' => $assignments,
            'linked_reports' => $linkedReports,
        ];
    }

    /**
     * Enregistrer un compte rendu complet : PDF, BD, affectations, emails.
     *
     * @param array $data Clés attendues : num_etu, nom_CR, contenu_CR, rapports,
     *                     encadrant_pedagogique, directeur_memoire
     * @return array ['success' => bool, 'message' => string]
     */
    public function enregistrer(array $data): array
    {
        $num_etu = $data['num_etu'] ?? null;
        $nom_CR = trim((string) ($data['nom_CR'] ?? ''));
        $contenu_CR = trim((string) ($data['contenu_CR'] ?? ''));
        $rapports = $this->normalizeRapportIds($data['rapports'] ?? []);
        $editingIdCR = (int) ($data['id_CR_edit'] ?? 0);
        $date_CR = date('Y-m-d H:i:s');
        $encadrants = $data['encadrant_pedagogique'] ?? [];
        $directeurs = $data['directeur_memoire'] ?? [];
        $submitAction = (string) ($data['submit_action'] ?? 'save');
        $notificationTargets = $this->resolveNotificationTargets($submitAction);

        if (empty($num_etu)) {
            return ['success' => false, 'message' => "Aucun étudiant sélectionné."];
        }
        if (empty($rapports)) {
            return ['success' => false, 'message' => "Aucun rapport sélectionné."];
        }
        if ($contenu_CR === '') {
            return ['success' => false, 'message' => "Le contenu du compte rendu est obligatoire."];
        }
        if ($nom_CR === '') {
            $nom_CR = 'CR_' . date('Y-m-d');
        }

        $writeGuard = \AcademicYear::ensureWritableYear($this->pdo, $this->getSelectedYearId(), 'un compte rendu');
        if (!$writeGuard['success']) {
            return ['success' => false, 'message' => $writeGuard['message']];
        }

        // Génération du PDF avec PdfGeneratorService (TCPDF)
        $html = '<html><head><meta charset="UTF-8"></head><body>' . $contenu_CR . '</body></html>';
        $pdfGen = new \App\Services\Document\PdfGeneratorService(
            __DIR__ . '/../../storage',
            __DIR__ . '/../../public/image/logo_ufhb.png'
        );
        $pdf = $pdfGen->createDocument('P', 'A4', 'Compte Rendu');
        $pdf->AddPage();
        $pdfGen->writeHtml($pdf, $html);
        $output = $pdf->Output('compte_rendu.pdf', 'S');

        // Sauvegarde du PDF sur disque
        $pdf_dir = __DIR__ . '/../../ressources/uploads/comptes_rendus/';
        if (!is_dir($pdf_dir)) {
            mkdir($pdf_dir, 0777, true);
        }
        $pdf_name = 'CR_' . date('Ymd_His') . '.pdf';
        $pdf_path = $pdf_dir . $pdf_name;
        file_put_contents($pdf_path, $output);
        $chemin_pdf = 'ressources/uploads/comptes_rendus/' . $pdf_name;

        // Enregistrement en BD
        $id_CR = 0;
        $oldFilePathsToDelete = [];
        $obsoleteCompteRendus = [];
        $modeLabel = 'enregistré';
        try {
            $this->pdo->beginTransaction();
            $context = $this->findCompteRenduContextForRapports($rapports);
            $id_CR = $editingIdCR > 0 && $this->getCompteRenduById($editingIdCR) !== null
                ? $editingIdCR
                : (int) ($context['primary_id'] ?? 0);

            if ($id_CR > 0) {
                $existing = $this->getCompteRenduById($id_CR);
                if ($existing !== null && !empty($existing['chemin_fichier_pdf']) && $existing['chemin_fichier_pdf'] !== $chemin_pdf) {
                    $oldPath = $this->toAbsoluteStoragePath((string) $existing['chemin_fichier_pdf']);
                    if ($oldPath !== null) {
                        $oldFilePathsToDelete[] = $oldPath;
                    }
                }
                $this->updateCompteRendu($id_CR, $num_etu, $nom_CR, $contenu_CR, $chemin_pdf, $date_CR);
                $modeLabel = 'mis à jour';
            } else {
                $id_CR = $this->createCompteRendu($num_etu, $nom_CR, $contenu_CR, $chemin_pdf, $date_CR);
            }

            if ($id_CR <= 0) {
                throw new \RuntimeException("Impossible d'enregistrer le compte rendu.");
            }

            $this->persistCompteRenduHtmlDocument($id_CR, $html);
            $this->persistCompteRenduDocument($id_CR, $nom_CR, $pdf_path);
            $obsoleteCompteRendus = $this->syncCompteRenduRapports($id_CR, $rapports);
            $this->saveAffectations($rapports, $encadrants, $directeurs);
            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            if (is_file($pdf_path)) {
                @unlink($pdf_path);
            }
            error_log("Erreur lors de l'enregistrement du CR : " . $e->getMessage());
            return ['success' => false, 'message' => "Erreur lors de l'enregistrement du compte rendu."];
        }

        foreach ($obsoleteCompteRendus as $obsoleteCompteRendu) {
            $obsoletePath = $this->toAbsoluteStoragePath((string) ($obsoleteCompteRendu['chemin_fichier_pdf'] ?? ''));
            if ($obsoletePath !== null) {
                $oldFilePathsToDelete[] = $obsoletePath;
            }
        }

        foreach (array_unique($oldFilePathsToDelete) as $pathToDelete) {
            if (is_string($pathToDelete) && $pathToDelete !== '' && is_file($pathToDelete) && $pathToDelete !== $pdf_path) {
                @unlink($pathToDelete);
            }
        }

        $notificationSummary = $this->sendNotificationEmails($notificationTargets, $rapports, $nom_CR, $pdf_path, $id_CR, $modeLabel);

        $message = 'Compte rendu ' . $modeLabel . ' avec succès !';
        if ($notificationSummary !== '') {
            $message .= ' ' . $notificationSummary;
        }

        return ['success' => true, 'message' => $message, 'id_CR' => $id_CR];
    }

    /**
     * Exporter un contenu en PDF et retourner les données brutes.
     *
     * @param string $contenu_CR Contenu HTML
     * @param string $nom_CR Nom du compte rendu
     * @return array ['pdf' => string, 'filename' => string]
     * @throws \Exception Si le contenu est vide ou si Dompdf est indisponible
     */
    public function exporterPdf(string $contenu_CR, string $nom_CR): array
    {
        if (empty($contenu_CR)) {
            throw new \Exception('Le contenu du compte rendu est vide.');
        }

        // HTML complet ou enveloppement
        if (strpos($contenu_CR, '<!DOCTYPE html>') !== false || strpos($contenu_CR, '<html') !== false) {
            $html = $contenu_CR;
        } else {
            $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:"Times New Roman",serif;line-height:1.6;margin:40px;}</style></head><body>' . $contenu_CR . '</body></html>';
        }

        // Générer le PDF avec PdfGeneratorService (TCPDF)
        $pdfGen = new \App\Services\Document\PdfGeneratorService(
            __DIR__ . '/../../storage',
            __DIR__ . '/../../public/image/logo_ufhb.png'
        );
        $pdf = $pdfGen->createDocument('P', 'A4', $nom_CR);
        $pdf->AddPage();
        $pdfGen->writeHtml($pdf, $html);

        $pdfOutput = $pdf->Output($nom_CR . '.pdf', 'S');
        $pdfName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nom_CR) . '.pdf';

        return ['pdf' => $pdfOutput, 'filename' => $pdfName];
    }
    // -----------------------------------------------------------------------
    //  Private helpers
    // -----------------------------------------------------------------------

    /**
     * @param mixed $rapports
     * @return array<int, int>
     */
    private function normalizeRapportIds($rapports): array
    {
        if (!is_array($rapports)) {
            return [];
        }

        $normalized = [];
        foreach ($rapports as $idRapport) {
            $id = (int) $idRapport;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }

        return array_values($normalized);
    }

    /**
     * @return array<int, string>
     */
    private function resolveNotificationTargets(string $submitAction): array
    {
        return match ($submitAction) {
            'save_notify_students' => ['students'],
            'save_notify_commission' => ['commission'],
            'save_notify_responsables' => ['responsables'],
            'save_notify_all' => ['students', 'commission', 'responsables'],
            default => [],
        };
    }

    /**
     * @param array<int, int> $rapports
     * @return array{primary_id: int, linked_ids: array<int, int>}
     */
    private function findCompteRenduContextForRapports(array $rapports): array
    {
        if (empty($rapports)) {
            return ['primary_id' => 0, 'linked_ids' => []];
        }

        $placeholders = implode(', ', array_fill(0, count($rapports), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT cr.id_CR
             FROM compte_rendu_rapport crr
             INNER JOIN compte_rendu cr ON cr.id_CR = crr.id_CR
             WHERE crr.id_rapport IN ($placeholders)
             ORDER BY cr.date_CR DESC, cr.id_CR DESC"
        );
        $stmt->execute($rapports);
        $linkedIds = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
        $linkedIds = array_values(array_filter($linkedIds, static fn ($id) => $id > 0));

        return [
            'primary_id' => $linkedIds[0] ?? 0,
            'linked_ids' => $linkedIds,
        ];
    }

    private function getCompteRenduById(int $id_CR): ?array
    {
        if ($id_CR <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM compte_rendu WHERE id_CR = ? LIMIT 1");
        $stmt->execute([$id_CR]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function createCompteRendu(string $num_etu, string $nom_CR, string $contenu_CR, string $chemin_pdf, string $date_CR): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO compte_rendu (num_etu, nom_CR, contenu_CR, chemin_fichier_pdf, date_CR)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$num_etu, $nom_CR, $contenu_CR, $chemin_pdf, $date_CR]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLinkedReportsForCompteRendu(int $idCR): array
    {
        if ($idCR <= 0) {
            return [];
        }

        $hasNumIdentEtud = $this->columnExists('etudiants', 'num_ident_etud');
        $studentJoinCondition = 'r.num_etu = e.num_carte_etud';
        if ($hasNumIdentEtud) {
            $studentJoinCondition .= ' OR r.num_etu = e.num_ident_etud';
        }

        $stmt = $this->pdo->prepare(
            "SELECT
                r.id_rapport,
                r.num_etu,
                r.theme_rapport,
                COALESCE(e.prenom_etu, '') AS prenom_etu,
                COALESCE(e.nom_etu, '') AS nom_etu,
                COALESCE(e.promotion_etu, '') AS promotion_etu,
                COALESCE(aff.current_encadrant_id, '') AS current_encadrant_id,
                COALESCE(aff.current_directeur_id, '') AS current_directeur_id
            FROM compte_rendu_rapport crr
            INNER JOIN rapport_etudiants r ON r.id_rapport = crr.id_rapport
            LEFT JOIN etudiants e ON ({$studentJoinCondition})
            LEFT JOIN (
                SELECT
                    id_rapport,
                    MAX(CASE WHEN role = 'encadrant' THEN id_enseignant END) AS current_encadrant_id,
                    MAX(CASE WHEN role = 'directeur' THEN id_enseignant END) AS current_directeur_id
                FROM affecter
                GROUP BY id_rapport
            ) aff ON aff.id_rapport = r.id_rapport
            WHERE crr.id_CR = :id_cr
            ORDER BY r.id_rapport ASC"
        );
        $stmt->execute([':id_cr' => $idCR]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Certains anciens CR n'ont pas de ligne dans compte_rendu_rapport.
     * On restaure alors la sélection depuis l'étudiant principal du CR.
     *
     * @param array<string, mixed> $compteRendu
     * @return array<int, array<string, mixed>>
     */
    private function getFallbackReportsForCompteRendu(array $compteRendu): array
    {
        $numEtu = trim((string) ($compteRendu['num_etu'] ?? ''));
        if ($numEtu === '') {
            return [];
        }

        $hasNumIdentEtud = $this->columnExists('etudiants', 'num_ident_etud');
        $studentJoinCondition = 'r.num_etu = e.num_carte_etud';
        $reportMatchCondition = 'r.num_etu = ? OR e.num_carte_etud = ?';
        $params = [$numEtu, $numEtu];
        if ($hasNumIdentEtud) {
            $studentJoinCondition .= ' OR r.num_etu = e.num_ident_etud';
            $reportMatchCondition .= ' OR e.num_ident_etud = ?';
            $params[] = $numEtu;
        }

        $stmt = $this->pdo->prepare(
            "SELECT
                r.id_rapport,
                r.num_etu,
                r.theme_rapport,
                COALESCE(e.prenom_etu, '') AS prenom_etu,
                COALESCE(e.nom_etu, '') AS nom_etu,
                COALESCE(e.promotion_etu, '') AS promotion_etu,
                COALESCE(aff.current_encadrant_id, '') AS current_encadrant_id,
                COALESCE(aff.current_directeur_id, '') AS current_directeur_id
            FROM rapport_etudiants r
            LEFT JOIN etudiants e ON ({$studentJoinCondition})
            LEFT JOIN (
                SELECT
                    id_rapport,
                    MAX(CASE WHEN role = 'encadrant' THEN id_enseignant END) AS current_encadrant_id,
                    MAX(CASE WHEN role = 'directeur' THEN id_enseignant END) AS current_directeur_id
                FROM affecter
                GROUP BY id_rapport
            ) aff ON aff.id_rapport = r.id_rapport
            WHERE {$reportMatchCondition}
            ORDER BY r.date_modification DESC, r.date_redaction_rapport DESC, r.id_rapport DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<int, array<string, mixed>> $reports
     * @param array<string, mixed> $compteRendu
     * @return array<int, array<string, mixed>>
     */
    private function applyCompteRenduAssignmentFallbacks(array $reports, array $compteRendu): array
    {
        if ($reports === []) {
            return $reports;
        }

        $fallbacks = $this->extractAssignmentIdsFromCompteRenduContent((string) ($compteRendu['contenu_CR'] ?? ''));
        if ($fallbacks === []) {
            return $reports;
        }

        foreach ($reports as &$report) {
            if (empty($report['current_encadrant_id']) && !empty($fallbacks['encadrant'])) {
                $report['current_encadrant_id'] = $fallbacks['encadrant'];
            }
            if (empty($report['current_directeur_id']) && !empty($fallbacks['directeur'])) {
                $report['current_directeur_id'] = $fallbacks['directeur'];
            }
        }
        unset($report);

        return $reports;
    }

    /**
     * @return array{encadrant?: string, directeur?: string}
     */
    private function extractAssignmentIdsFromCompteRenduContent(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $text = html_entity_decode(
            trim((string) preg_replace('/\s+/', ' ', strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'], "\n", $html)))),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $assignments = [];
        if (preg_match('/Directeur\s+de\s+m[ée]moire\s*:\s*(.+?)(?:Encadr(?:eur|ant)\s+p[ée]dagogique\s*:|$)/iu', $text, $match)) {
            $id = $this->resolveTeacherIdByName((string) ($match[1] ?? ''));
            if ($id !== '') {
                $assignments['directeur'] = $id;
            }
        }
        if (preg_match('/Encadr(?:eur|ant)\s+p[ée]dagogique\s*:\s*(.+?)(?:Directeur\s+de\s+m[ée]moire\s*:|$)/iu', $text, $match)) {
            $id = $this->resolveTeacherIdByName((string) ($match[1] ?? ''));
            if ($id !== '') {
                $assignments['encadrant'] = $id;
            }
        }

        return $assignments;
    }

    private function resolveTeacherIdByName(string $rawName): string
    {
        $needle = $this->normalizeTeacherName($rawName);
        if ($needle === '') {
            return '';
        }

        $stmt = $this->pdo->query("SELECT id_enseignant, nom_enseignant, prenom_enseignant FROM enseignants ORDER BY nom_enseignant, prenom_enseignant");
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $teacher) {
            $id = trim((string) ($teacher['id_enseignant'] ?? ''));
            if ($id === '') {
                continue;
            }

            $firstLast = $this->normalizeTeacherName(
                trim((string) ($teacher['prenom_enseignant'] ?? '') . ' ' . (string) ($teacher['nom_enseignant'] ?? ''))
            );
            $lastFirst = $this->normalizeTeacherName(
                trim((string) ($teacher['nom_enseignant'] ?? '') . ' ' . (string) ($teacher['prenom_enseignant'] ?? ''))
            );

            if ($needle === $firstLast || $needle === $lastFirst) {
                return $id;
            }
            if (($firstLast !== '' && str_starts_with($needle, $firstLast . ' '))
                || ($lastFirst !== '' && str_starts_with($needle, $lastFirst . ' '))
            ) {
                return $id;
            }
        }

        return '';
    }

    private function normalizeTeacherName(string $name): string
    {
        $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $name = trim((string) preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            return '';
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        if (is_string($ascii) && $ascii !== '') {
            $name = $ascii;
        }

        $name = strtoupper($name);
        $name = (string) preg_replace('/[^A-Z0-9]+/', ' ', $name);
        return trim((string) preg_replace('/\s+/', ' ', $name));
    }

    private function updateCompteRendu(int $id_CR, string $num_etu, string $nom_CR, string $contenu_CR, string $chemin_pdf, string $date_CR): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE compte_rendu
             SET num_etu = ?, nom_CR = ?, contenu_CR = ?, chemin_fichier_pdf = ?, date_CR = ?
             WHERE id_CR = ?"
        );
        $stmt->execute([$num_etu, $nom_CR, $contenu_CR, $chemin_pdf, $date_CR, $id_CR]);
    }

    /**
     * Synchronise les liaisons rapport <-> compte rendu et retourne les CR devenus orphelins.
     *
     * @param array<int, int> $rapports
     * @return array<int, array<string, mixed>>
     */
    private function syncCompteRenduRapports(int $id_CR, array $rapports): array
    {
        if ($id_CR <= 0) {
            return [];
        }

        $obsoleteIds = [];

        if (!empty($rapports)) {
            $placeholders = implode(', ', array_fill(0, count($rapports), '?'));
            $stmt = $this->pdo->prepare(
                "SELECT DISTINCT id_CR
                 FROM compte_rendu_rapport
                 WHERE id_rapport IN ($placeholders) AND id_CR <> ?"
            );
            $params = $rapports;
            $params[] = $id_CR;
            $stmt->execute($params);
            $obsoleteIds = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);

            $deleteOtherLinksStmt = $this->pdo->prepare(
                "DELETE FROM compte_rendu_rapport
                 WHERE id_rapport IN ($placeholders) AND id_CR <> ?"
            );
            $deleteOtherLinksStmt->execute($params);
        }

        $stmtDeleteCurrent = $this->pdo->prepare("DELETE FROM compte_rendu_rapport WHERE id_CR = ?");
        $stmtDeleteCurrent->execute([$id_CR]);

        if (!empty($rapports)) {
            $stmtInsert = $this->pdo->prepare("INSERT INTO compte_rendu_rapport (id_CR, id_rapport) VALUES (?, ?)");
            foreach ($rapports as $idRapport) {
                $stmtInsert->execute([$id_CR, $idRapport]);
            }
        }

        return $this->deleteOrphanCompteRendus($obsoleteIds);
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, array<string, mixed>>
     */
    private function deleteOrphanCompteRendus(array $ids): array
    {
        $deleted = [];

        foreach (array_values(array_unique(array_filter(array_map('intval', $ids)))) as $id) {
            if ($id <= 0) {
                continue;
            }

            $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM compte_rendu_rapport WHERE id_CR = ?");
            $stmtCount->execute([$id]);
            $count = (int) $stmtCount->fetchColumn();
            if ($count > 0) {
                continue;
            }

            $row = $this->getCompteRenduById($id);
            if ($row === null) {
                continue;
            }

            $stmtDelete = $this->pdo->prepare("DELETE FROM compte_rendu WHERE id_CR = ?");
            $stmtDelete->execute([$id]);
            $deleted[] = $row;
        }

        return $deleted;
    }

    private function toAbsoluteStoragePath(string $storedPath): ?string
    {
        $storedPath = trim($storedPath);
        if ($storedPath === '') {
            return null;
        }

        if (preg_match('~^[A-Za-z]:\\\\~', $storedPath) === 1 || str_starts_with($storedPath, DIRECTORY_SEPARATOR)) {
            return $storedPath;
        }

        return realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath);
    }

    /**
     * Insérer les affectations encadrant/directeur pour chaque rapport.
     */
    private function saveAffectations(array $rapports, array $encadrants, array $directeurs): void
    {
        $deleteStmt = $this->pdo->prepare(
            "DELETE FROM affecter WHERE id_rapport = ? AND role IN ('encadrant', 'directeur')"
        );
        $insertStmt = $this->pdo->prepare(
            "INSERT INTO affecter (id_enseignant, id_rapport, id_jury, role) VALUES (?, ?, NULL, ?)"
        );

        foreach ($rapports as $id_rapport) {
            $deleteStmt->execute([$id_rapport]);

            if (!empty($encadrants[$id_rapport])) {
                $insertStmt->execute([$encadrants[$id_rapport], $id_rapport, 'encadrant']);
            }
            if (!empty($directeurs[$id_rapport])) {
                $insertStmt->execute([$directeurs[$id_rapport], $id_rapport, 'directeur']);
            }
        }
    }

    /**
     * Envoyer les notifications demandées.
     */
    private function sendNotificationEmails(array $targets, array $rapports, string $nom_CR, string $pdf_path, int $id_CR, string $modeLabel = 'enregistré'): string
    {
        if (empty($targets)) {
            return '';
        }

        $summaryParts = [];
        $alreadyNotified = [];
        $emailService = new \EmailService();

        if (in_array('students', $targets, true)) {
            $count = $this->notifyStudents($emailService, $rapports, $nom_CR, $pdf_path, $alreadyNotified, $modeLabel);
            if ($count > 0) {
                $summaryParts[] = $count . ' étudiant(s) notifié(s)';
            }
        }

        if (in_array('commission', $targets, true)) {
            $commissionRecipients = $this->getUserRecipientsByGroups([11, 5]);
            $count = $this->notifyGenericRecipients(
                $emailService,
                $commissionRecipients,
                'COMMISSION_NOTIFICATION',
                'Compte rendu de commission disponible : ' . $nom_CR,
                $pdf_path,
                $alreadyNotified,
                [
                    'nom_CR' => htmlspecialchars($nom_CR, ENT_QUOTES, 'UTF-8'),
                    'nbRapports' => count($rapports),
                    'id_CR' => $id_CR,
                ]
            );
            if ($count > 0) {
                $summaryParts[] = $count . ' membre(s) de commission notifié(s)';
            }
        }

        if (in_array('responsables', $targets, true)) {
            $responsableRecipients = $this->getUserRecipientsByGroups([9, 10]);
            $count = $this->notifyGenericRecipients(
                $emailService,
                $responsableRecipients,
                'RESPONSABLE_NOTIFICATION',
                'Compte rendu disponible : ' . $nom_CR,
                $pdf_path,
                $alreadyNotified,
                [
                    'nom_CR' => htmlspecialchars($nom_CR, ENT_QUOTES, 'UTF-8'),
                    'nbRapports' => count($rapports),
                    'id_CR' => $id_CR,
                ]
            );
            if ($count > 0) {
                $summaryParts[] = $count . ' responsable(s) notifié(s)';
            }
        }

        if (empty($summaryParts)) {
            return 'Aucun destinataire trouvé pour l’envoi.';
        }

        return 'Notifications envoyées : ' . implode(', ', $summaryParts) . '.';
    }

    private function notifyStudents(\EmailService $emailService, array $rapports, string $nom_CR, string $pdf_path, array &$alreadyNotified, string $modeLabel = 'enregistré'): int
    {
        $rapportModel = new \RapportEtudiant($this->pdo);
        $count = 0;

        foreach ($rapports as $id_rapport) {
            $rapport = $rapportModel->getRapportById($id_rapport);
            $to = trim((string) ($rapport['email_etu'] ?? ''));
            if ($to === '' || isset($alreadyNotified[strtolower($to)])) {
                continue;
            }

            $nom = trim((string) ($rapport['prenom_etu'] ?? '') . ' ' . (string) ($rapport['nom_etu'] ?? ''));
            
            $templateKey = 'REPORT_NOTIFICATION';
            $data = [
                'nom' => htmlspecialchars($nom !== '' ? $nom : 'Étudiant', ENT_QUOTES, 'UTF-8'),
                'nom_rapport' => htmlspecialchars((string) ($rapport['nom_rapport'] ?? 'Sans titre'), ENT_QUOTES, 'UTF-8'),
                'nom_CR' => htmlspecialchars($nom_CR, ENT_QUOTES, 'UTF-8'),
                'date_CR' => date('d/m/Y H:i'),
            ];

            if ($modeLabel === 'mis à jour' || $modeLabel === 'mis a jour') {
                $templateKey = 'CR_MODIFIE';
                $data['date_maj'] = date('d/m/Y H:i');
            }

            $sent = $emailService->sendTemplate($templateKey, $to, $data, [
                'path' => $pdf_path,
                'name' => 'Compte_rendu_' . date('Y-m-d') . '.pdf',
            ]);

            if ($sent) {
                $alreadyNotified[strtolower($to)] = true;
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<int, array{email: string, name: string}> $recipients
     */
    private function notifyGenericRecipients(\EmailService $emailService, array $recipients, string $templateKey, string $subject, string $pdf_path, array &$alreadyNotified, array $data = []): int
    {
        $count = 0;

        foreach ($recipients as $recipient) {
            $email = strtolower(trim((string) ($recipient['email'] ?? '')));
            if ($email === '' || isset($alreadyNotified[$email])) {
                continue;
            }

            $d = $data;
            if (!isset($d['nom'])) {
                $d['nom'] = trim((string) ($recipient['nom'] ?? ''));
            }

            $sent = $emailService->sendTemplate(
                $templateKey,
                $email,
                $d,
                [
                    'path' => $pdf_path,
                    'name' => 'Compte_rendu_' . date('Y-m-d') . '.pdf',
                ]
            );

            if ($sent) {
                $alreadyNotified[$email] = true;
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array<int, array{email: string, name: string}>
     */
    private function getUserRecipientsByGroups(array $groupIds): array
    {
        $groupIds = array_values(array_unique(array_filter(array_map('intval', $groupIds))));
        if (empty($groupIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($groupIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT
                TRIM(COALESCE(NULLIF(e.mail_enseignant, ''), NULLIF(pa.email_pers_admin, ''), NULLIF(u.login_utilisateur, ''))) AS email,
                TRIM(COALESCE(
                    NULLIF(CONCAT(COALESCE(e.prenom_enseignant, ''), ' ', COALESCE(e.nom_enseignant, '')), ' '),
                    NULLIF(CONCAT(COALESCE(pa.prenom_pers_admin, ''), ' ', COALESCE(pa.nom_pers_admin, '')), ' '),
                    NULLIF(u.nom_utilisateur, '')
                )) AS nom
             FROM utilisateur u
             LEFT JOIN enseignants e ON LOWER(e.mail_enseignant) = LOWER(u.login_utilisateur)
             LEFT JOIN personnel_admin pa ON LOWER(pa.email_pers_admin) = LOWER(u.login_utilisateur)
             WHERE u.statut_utilisateur = 'Actif'
               AND u.id_GU IN ($placeholders)"
        );
        $stmt->execute($groupIds);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $recipients = [];
        foreach ($rows as $row) {
            $email = trim((string) ($row['email'] ?? ''));
            if ($email === '') {
                continue;
            }
            $recipients[] = [
                'email' => $email,
                'name' => trim((string) ($row['nom'] ?? '')),
            ];
        }

        return $recipients;
    }

    private function buildGenericNotificationBody(string $target, string $nom_CR, int $nbRapports, int $id_CR): string
    {
        $recipientLabel = $target === 'commission'
            ? 'membres de la commission'
            : 'responsables concernés';

        return '
            <p>Bonjour,</p>
            <p>Le compte rendu <strong>' . htmlspecialchars($nom_CR, ENT_QUOTES, 'UTF-8') . '</strong> a été enregistré et est prêt à être consulté.</p>
            <p>Ce document concerne <strong>' . $nbRapports . '</strong> rapport(s) et porte la référence interne <strong>#' . $id_CR . '</strong>.</p>
            <p>Vous recevez ce message en tant que ' . htmlspecialchars($recipientLabel, ENT_QUOTES, 'UTF-8') . '.</p>
            <p>Le PDF est joint à cet email.</p>
            <p>Cordialement,<br>CheckMaster</p>
        ';
    }

    private function persistCompteRenduHtmlDocument(int $idCR, string $html): void
    {
        if ($idCR <= 0 || trim($html) === '') {
            return;
        }

        try {
            $storage = new \App\Services\Document\DocumentStorageService($this->pdo, dirname(__DIR__, 2));
            $storage->storeDocument(
                'html_doc',
                'compte_rendu_' . $idCR . '.html',
                $html,
                'text/html',
                'compte_rendu',
                (string) $idCR,
                isset($_SESSION['id_utilisateur']) ? (int) $_SESSION['id_utilisateur'] : null,
                null,
                'compte_rendu',
                null,
                true
            );
        } catch (\Throwable $e) {
            error_log('Erreur persistCompteRenduHtmlDocument: ' . $e->getMessage());
        }
    }

    private function persistCompteRenduDocument(int $idCR, string $nomCR, string $pdfPath): void
    {
        if ($idCR <= 0 || !is_file($pdfPath)) {
            return;
        }

        try {
            $storage = new \App\Services\Document\DocumentStorageService($this->pdo, dirname(__DIR__, 2));
            $storage->storeFileFromPath(
                'compte_rendu',
                $pdfPath,
                'compte_rendu',
                (string) $idCR,
                isset($_SESSION['id_utilisateur']) ? (int) $_SESSION['id_utilisateur'] : null,
                null,
                null,
                true
            );
        } catch (\Throwable $e) {
            error_log('Erreur persistCompteRenduDocument: ' . $e->getMessage());
        }
    }
}
