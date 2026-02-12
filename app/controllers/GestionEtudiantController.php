<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../Core/Autoload.php';

use CheckMaster\Core\Session;

class GestionEtudiantController
{
    private $etudiant;
    private $db;
    private $auditLog;
    private $auditUserExistsCache = [];

    public function __construct()
    {
        Session::start();
        $this->db = Database::getConnection();
        $this->etudiant = new Etudiant($this->db);
        $this->auditLog = new AuditLog($this->db);
    }

    public function index()
    {
        try {
            $this->handleGetActions();

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                $this->handlePostActions();
            }

            $this->prepareViewData();
        } catch (Throwable $e) {
            error_log("Erreur dans GestionEtudiantController::index : " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue. Veuillez réessayer.";
        }
    }

    private function handleGetActions()
    {
        $crudAction = $_GET['crud_action'] ?? '';

        if ($crudAction === 'export_csv') {
            $this->exportCsv();
            exit;
        }

        if ($crudAction === 'print_pdf') {
            $this->printPdfList();
            exit;
        }
    }

    private function handlePostActions()
    {
        $crudAction = $_POST['crud_action'] ?? '';

        // Compatibilité avec anciens formulaires.
        if ($crudAction === '') {
            if (isset($_POST['submit_add_etudiant'])) {
                $crudAction = 'create';
            } elseif (isset($_POST['submit_modifier_etudiant'])) {
                $crudAction = 'update';
            } elseif (isset($_POST['selected_ids'])) {
                $crudAction = 'archive';
            }
        }

        switch ($crudAction) {
            case 'create':
                $this->createEtudiant();
                break;
            case 'update':
                $this->updateEtudiant();
                break;
            case 'archive':
                $this->archiveEtudiant();
                break;
            case 'import_csv':
                $this->importCsv();
                break;
        }
    }

    private function prepareViewData()
    {
        $currentPage = max(1, (int) ($_GET['p'] ?? 1));
        $searchTerm = trim((string) ($_GET['search'] ?? ''));
        $perPage = (int) ($_GET['per_page'] ?? 25);
        $allowedPerPage = [10, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 25;
        }

        $listeNiveaux = $this->getNiveauxEtude();
        $listeAnneesAcad = $this->getAnneesAcademiques();

        $paginationData = $this->etudiant->getEtudiantsPagines($currentPage, $perPage, $searchTerm, false);
        $totalItems = (int) ($paginationData['totalItems'] ?? 0);
        $totalPages = max(1, (int) ($paginationData['totalPages'] ?? 1));
        $currentPage = max(1, min($currentPage, $totalPages));
        if ($currentPage !== (int) ($paginationData['currentPage'] ?? $currentPage)) {
            $paginationData = $this->etudiant->getEtudiantsPagines($currentPage, $perPage, $searchTerm, false);
        }
        $items = $paginationData['items'];
        $startIndex = $totalItems > 0 ? (($currentPage - 1) * $perPage) : 0;
        $endIndex = min($startIndex + $perPage, $totalItems);

        $etudiant_a_modifier = null;
        if (!empty($_GET['num_etu'])) {
            $etudiant_a_modifier = $this->etudiant->getEtudiantById($_GET['num_etu']);
        }

        $GLOBALS['listeEtudiants'] = $items;
        $GLOBALS['allEtudiants'] = $items;
        $GLOBALS['etudiant_a_modifier'] = $etudiant_a_modifier;
        $GLOBALS['currentPage'] = $currentPage;
        $GLOBALS['totalPages'] = $totalPages;
        $GLOBALS['totalItems'] = $totalItems;
        $GLOBALS['startIndex'] = $startIndex;
        $GLOBALS['endIndex'] = $endIndex;
        $GLOBALS['itemsPerPage'] = $perPage;
        $GLOBALS['searchTerm'] = $searchTerm;
        $GLOBALS['listeNiveaux'] = $listeNiveaux;
        $GLOBALS['listeAnneesAcad'] = $listeAnneesAcad;
    }

    private function createEtudiant()
    {
        $payload = $this->extractFormPayload();
        if ($payload['error'] !== null) {
            $GLOBALS['messageErreur'] = $payload['error'];
            return;
        }

        $numEtu = $this->genererNumeroEtudiant($payload['data']['promotion_etu']);
        $data = $payload['data'];

        if (!$this->etudiant->ajouterEtudiant(
            $numEtu,
            $data['nom_etu'],
            $data['prenom_etu'],
            $data['date_naiss_etu'],
            $data['genre_etu'],
            $data['email_etu'],
            $data['promotion_etu'],
            $data['id_niveau'],
            $data['id_annee_acad'],
            $data['identifiant_mesrs']
        )) {
            $GLOBALS['messageErreur'] = "Erreur lors de l'enregistrement de l'étudiant.";
            $this->safeLogAction('Création', 'etudiants', 'Erreur');
            return;
        }

        $this->safeLogAction(
            'Création',
            'etudiants',
            'Succès',
            json_encode(['apres' => array_merge(['num_carte_etud' => $numEtu], $data)], JSON_UNESCAPED_UNICODE)
        );
        $GLOBALS['messageSuccess'] = "Étudiant créé avec succès (matricule: {$numEtu}).";
    }

    private function updateEtudiant()
    {
        $numEtu = trim((string) ($_POST['num_etu'] ?? ''));
        if ($numEtu === '') {
            $GLOBALS['messageErreur'] = "Matricule manquant pour la modification.";
            return;
        }

        $ancien = $this->etudiant->getEtudiantById($numEtu);
        if (!$ancien) {
            $GLOBALS['messageErreur'] = "Étudiant introuvable.";
            return;
        }

        $payload = $this->extractFormPayload();
        if ($payload['error'] !== null) {
            $GLOBALS['messageErreur'] = $payload['error'];
            return;
        }

        $data = $payload['data'];
        $ok = $this->etudiant->modifierEtudiant(
            $numEtu,
            $data['nom_etu'],
            $data['prenom_etu'],
            $data['date_naiss_etu'],
            $data['genre_etu'],
            $data['email_etu'],
            $data['promotion_etu'],
            $data['id_niveau'],
            $data['id_annee_acad'],
            $data['identifiant_mesrs']
        );

        if (!$ok) {
            $GLOBALS['messageErreur'] = "Erreur lors de la mise à jour de l'étudiant.";
            $this->safeLogAction('Modification', 'etudiants', 'Erreur');
            return;
        }

        $apres = $this->etudiant->getEtudiantById($numEtu);
        $this->safeLogAction(
            'Modification',
            'etudiants',
            'Succès',
            json_encode(['avant' => $ancien, 'apres' => $apres], JSON_UNESCAPED_UNICODE)
        );
        $GLOBALS['messageSuccess'] = "Modifications appliquées avec succès.";
    }

    private function archiveEtudiant()
    {
        $numEtu = trim((string) ($_POST['num_etu'] ?? ''));

        // Compatibilité ancien formulaire multi-sélection.
        if ($numEtu === '' && !empty($_POST['selected_ids']) && is_array($_POST['selected_ids'])) {
            $numEtu = (string) reset($_POST['selected_ids']);
        }

        if ($numEtu === '') {
            $GLOBALS['messageErreur'] = "Aucun étudiant sélectionné.";
            return;
        }

        $ancien = $this->etudiant->getEtudiantById($numEtu);
        if (!$ancien) {
            $GLOBALS['messageErreur'] = "Étudiant introuvable.";
            return;
        }

        $ok = $this->etudiant->archiverEtudiant($numEtu);
        if (!$ok) {
            $GLOBALS['messageErreur'] = "Impossible d'archiver cet étudiant.";
            $this->safeLogAction('Suppression', 'etudiants', 'Erreur');
            return;
        }

        $this->safeLogAction(
            'Suppression',
            'etudiants',
            'Succès',
            json_encode(['avant' => $ancien, 'archive' => true], JSON_UNESCAPED_UNICODE)
        );
        $GLOBALS['messageSuccess'] = "Étudiant archivé avec succès.";
    }

    private function importCsv()
    {
        if (empty($_FILES['import_file']['tmp_name'])) {
            $GLOBALS['messageErreur'] = "Veuillez sélectionner un fichier CSV à importer.";
            return;
        }

        $tmpPath = $_FILES['import_file']['tmp_name'];
        $handle = fopen($tmpPath, 'r');
        if ($handle === false) {
            $GLOBALS['messageErreur'] = "Impossible de lire le fichier CSV.";
            return;
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            $GLOBALS['messageErreur'] = "Fichier CSV vide.";
            return;
        }
        $delimiter = $this->detectCsvDelimiter($firstLine);
        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers || count($headers) < 2) {
            fclose($handle);
            $GLOBALS['messageErreur'] = "En-têtes CSV invalides.";
            return;
        }

        $mapping = $this->buildCsvMapping($headers);
        $created = 0;
        $updated = 0;
        $ignored = 0;
        $errors = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count(array_filter($row, static function ($v) { return trim((string) $v) !== ''; })) === 0) {
                continue;
            }

            $data = $this->mapCsvRow($row, $mapping);
            $promotion = $data['promotion_etu'] ?: (date('Y') . '-' . (date('Y') + 1));
            $numEtu = trim((string) ($data['num_carte_etud'] ?? ''));
            if ($numEtu === '') {
                $numEtu = $this->genererNumeroEtudiant($promotion);
            }

            if (empty($data['nom_etu']) || empty($data['prenom_etu']) || empty($data['date_naiss_etu']) || empty($data['email_etu'])) {
                $ignored++;
                continue;
            }

            $genre = $this->mapGenreValue($data['genre_etu'] ?? '');
            $idNiveau = $this->toNullableInt($data['id_niveau'] ?? null);
            $idAnnee = $this->toNullableInt($data['id_annee_acad'] ?? null);

            $existing = $this->etudiant->getEtudiantById($numEtu);
            if ($existing) {
                $ok = $this->etudiant->modifierEtudiant(
                    $numEtu,
                    trim((string) $data['nom_etu']),
                    trim((string) $data['prenom_etu']),
                    trim((string) $data['date_naiss_etu']),
                    $genre,
                    trim((string) $data['email_etu']),
                    $promotion,
                    $idNiveau,
                    $idAnnee
                );
                if ($ok) {
                    $updated++;
                } else {
                    $errors++;
                }
                continue;
            }

            $ok = $this->etudiant->ajouterEtudiant(
                $numEtu,
                trim((string) $data['nom_etu']),
                trim((string) $data['prenom_etu']),
                trim((string) $data['date_naiss_etu']),
                $genre,
                trim((string) $data['email_etu']),
                $promotion,
                $idNiveau,
                $idAnnee
            );
            if ($ok) {
                $created++;
            } else {
                $errors++;
            }
        }

        fclose($handle);

        $this->safeLogAction(
            'Importation',
            'etudiants',
            $errors > 0 ? 'Erreur' : 'Succès',
            json_encode([
                'created' => $created,
                'updated' => $updated,
                'ignored' => $ignored,
                'errors' => $errors
            ], JSON_UNESCAPED_UNICODE)
        );

        $GLOBALS['messageSuccess'] = "Import terminé: {$created} créés, {$updated} mis à jour, {$ignored} ignorés, {$errors} erreurs.";
    }

    private function exportCsv()
    {
        $searchTerm = trim((string) ($_GET['search'] ?? ''));
        $rows = $this->etudiant->getEtudiantsForExport($searchTerm, false);

        $this->safeLogSimple('logExportation', 'etudiants', 'Succès');

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="etudiants_' . date('Y-m-d_H-i-s') . '.csv"');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Matricule', 'Nom', 'Prenom', 'Promotion', 'Email', 'Telephone', 'Genre'], ';');

        foreach ($rows as $row) {
            fputcsv($output, [
                $row->num_carte_etud ?? '',
                $row->nom_etu ?? '',
                $row->prenom_etu ?? '',
                $row->promotion_etu ?? '',
                $row->email_etu ?? '',
                $row->telephone_etu ?? '',
                $row->libelle_genre ?? $row->genre_etu ?? ''
            ], ';');
        }

        fclose($output);
    }

    private function printPdfList()
    {
        $searchTerm = trim((string) ($_GET['search'] ?? ''));
        $rows = $this->etudiant->getEtudiantsForExport($searchTerm, false);
        $html = $this->buildPrintableHtml($rows, $searchTerm);

        $this->safeLogSimple('logImpression', 'etudiants', 'Succès');

        if (class_exists('\Dompdf\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $dompdf->stream('liste_etudiants_' . date('Ymd_His') . '.pdf', ['Attachment' => false]);
            return;
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
    }

    private function buildPrintableHtml(array $rows, $searchTerm)
    {
        $body = '';
        foreach ($rows as $row) {
            $body .= '<tr>'
                . '<td>' . htmlspecialchars((string) ($row->num_carte_etud ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($row->nom_etu ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($row->prenom_etu ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($row->promotion_etu ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($row->email_etu ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($row->telephone_etu ?? '-')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($row->libelle_genre ?? $row->genre_etu ?? '')) . '</td>'
                . '</tr>';
        }

        return '<!doctype html><html lang="fr"><head><meta charset="UTF-8"><title>Liste Étudiants</title>'
            . '<style>body{font-family:Arial,sans-serif;font-size:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ccc;padding:6px}th{background:#f3f4f6}h1{font-size:18px}p{color:#555}</style>'
            . '</head><body>'
            . '<h1>Mise à jour Étudiant - Liste</h1>'
            . '<p>Recherche: ' . htmlspecialchars($searchTerm !== '' ? $searchTerm : 'Aucune') . ' | Date: ' . date('d/m/Y H:i') . '</p>'
            . '<table><thead><tr><th>Matricule</th><th>Nom</th><th>Prénom</th><th>Promotion</th><th>Email</th><th>Téléphone</th><th>Genre</th></tr></thead><tbody>'
            . $body
            . '</tbody></table></body></html>';
    }

    private function extractFormPayload()
    {
        $nom = trim((string) ($_POST['nom_etu'] ?? ''));
        $prenom = trim((string) ($_POST['prenom_etu'] ?? ''));
        $dateNaiss = trim((string) ($_POST['date_naiss_etu'] ?? ''));
        $genre = $this->mapGenreValue($_POST['genre_etu'] ?? '');
        $email = trim((string) ($_POST['email_etu'] ?? ''));
        $promotion = trim((string) ($_POST['promotion_etu'] ?? ''));
        if ($promotion === '') {
            $promotion = date('Y') . '-' . (date('Y') + 1);
        }

        if ($nom === '' || $prenom === '' || $dateNaiss === '' || $email === '') {
            return ['error' => "Les champs nom, prénom, date de naissance et email sont obligatoires.", 'data' => null];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => "Adresse email invalide.", 'data' => null];
        }

        return [
            'error' => null,
            'data' => [
                'nom_etu' => $nom,
                'prenom_etu' => $prenom,
                'date_naiss_etu' => $dateNaiss,
                'genre_etu' => $genre,
                'email_etu' => $email,
                'promotion_etu' => $promotion,
                'id_niveau' => $this->toNullableInt($_POST['id_niveau'] ?? null),
                'id_annee_acad' => $this->toNullableInt($_POST['id_annee_acad'] ?? null),
                'identifiant_mesrs' => trim((string) ($_POST['identifiant_mesrs'] ?? ''))
            ]
        ];
    }

    private function toNullableInt($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }

    private function mapGenreValue($value)
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 1;
        }

        if (is_numeric($raw)) {
            $id = (int) $raw;
            return in_array($id, [1, 2, 3], true) ? $id : 1;
        }

        $normalized = strtolower(strtr($raw, [
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'à' => 'a'
        ]));
        if (in_array($normalized, ['m', 'masculin', 'homme'], true)) {
            return 1;
        }
        if (in_array($normalized, ['f', 'feminin', 'femme'], true)) {
            return 2;
        }
        return 3;
    }

    private function detectCsvDelimiter($sampleLine)
    {
        $delimiters = [';', ',', "\t"];
        $best = ';';
        $maxCount = -1;
        foreach ($delimiters as $delimiter) {
            $count = substr_count((string) $sampleLine, $delimiter);
            if ($count > $maxCount) {
                $maxCount = $count;
                $best = $delimiter;
            }
        }
        return $best;
    }

    private function buildCsvMapping(array $headers)
    {
        $known = [
            'num_carte_etud' => ['matricule', 'num_carte_etud', 'num_etu', 'numero_etudiant'],
            'nom_etu' => ['nom', 'nom_etu'],
            'prenom_etu' => ['prenom', 'prenom_etu'],
            'email_etu' => ['email', 'mail', 'email_etu'],
            'date_naiss_etu' => ['date_naissance', 'date_naiss_etu', 'date_de_naissance'],
            'genre_etu' => ['genre', 'sexe', 'genre_etu'],
            'promotion_etu' => ['promotion', 'promotion_etu'],
            'id_niveau' => ['id_niveau', 'niveau', 'niveau_id'],
            'id_annee_acad' => ['id_annee_acad', 'annee_academique', 'annee_id']
        ];

        $normalizedHeaders = [];
        foreach ($headers as $index => $header) {
            $normalizedHeaders[$index] = $this->normalizeHeader((string) $header);
        }

        $mapping = [];
        foreach ($known as $target => $aliases) {
            $mapping[$target] = null;
            foreach ($normalizedHeaders as $index => $normalizedHeader) {
                if (in_array($normalizedHeader, $aliases, true)) {
                    $mapping[$target] = $index;
                    break;
                }
            }
        }

        return $mapping;
    }

    private function normalizeHeader($value)
    {
        $value = strtolower(trim((string) $value));
        $value = strtr($value, [
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'à' => 'a',
            'ù' => 'u',
            'ô' => 'o',
            'î' => 'i',
            'ï' => 'i',
            'ç' => 'c'
        ]);
        $value = preg_replace('/[^a-z0-9]+/i', '_', $value);
        return trim((string) $value, '_');
    }

    private function mapCsvRow(array $row, array $mapping)
    {
        $data = [];
        foreach ($mapping as $target => $index) {
            $data[$target] = ($index !== null && array_key_exists($index, $row)) ? trim((string) $row[$index]) : '';
        }
        return $data;
    }

    private function genererNumeroEtudiant($promotion_etu)
    {
        $annee = explode('-', $promotion_etu)[0];
        $query = "SELECT MAX(CAST(SUBSTRING(num_carte_etud, 5) AS UNSIGNED)) as max_num
                  FROM etudiants
                  WHERE num_carte_etud LIKE :prefix";
        $stmt = $this->db->prepare($query);
        $prefix = $annee . '%';
        $stmt->bindParam(':prefix', $prefix);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        $next_num = ($result->max_num ?? 0) + 1;
        return $annee . str_pad((string) $next_num, 4, '0', STR_PAD_LEFT);
    }

    private function getNiveauxEtude()
    {
        try {
            $query = "SELECT id_niv_etude, lib_niv_etude FROM niveau_etude ORDER BY lib_niv_etude";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération niveaux : " . $e->getMessage());
            return [];
        }
    }

    private function getAnneesAcademiques()
    {
        try {
            $query = "SELECT id_annee_acad, date_deb, date_fin FROM annee_academique ORDER BY date_deb DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur récupération années académiques : " . $e->getMessage());
            return [];
        }
    }

    private function getAuditUserId()
    {
        $id = $_SESSION['id_utilisateur'] ?? 0;
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        if (array_key_exists($id, $this->auditUserExistsCache)) {
            return $this->auditUserExistsCache[$id] ? $id : null;
        }

        try {
            $stmt = $this->db->prepare("SELECT 1 FROM utilisateur WHERE id_utilisateur = ? LIMIT 1");
            $stmt->execute([$id]);
            $exists = (bool) $stmt->fetchColumn();
            $this->auditUserExistsCache[$id] = $exists;
            return $exists ? $id : null;
        } catch (Throwable $e) {
            $this->auditUserExistsCache[$id] = false;
            return null;
        }
    }

    private function safeLogAction($action, $table, $status, $details = null)
    {
        try {
            $userId = $this->getAuditUserId();
            if ($userId === null) {
                return;
            }
            $this->auditLog->logAction($userId, $action, $table, $status, $details);
        } catch (Throwable $e) {
            error_log("Audit log error ({$action}/{$table}): " . $e->getMessage());
        }
    }

    private function safeLogSimple($method, $table, $status)
    {
        try {
            $userId = $this->getAuditUserId();
            if ($userId === null) {
                return;
            }
            if (method_exists($this->auditLog, $method)) {
                $this->auditLog->{$method}($userId, $table, $status);
            }
        } catch (Throwable $e) {
            error_log("Audit log error ({$method}/{$table}): " . $e->getMessage());
        }
    }
}
