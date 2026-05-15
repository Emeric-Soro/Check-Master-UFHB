<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Valider.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/InfoStage.php';
require_once __DIR__ . '/../models/Entreprise.php';
require_once __DIR__ . '/../utils/AcademicYear.php';

/**
 * Service métier de la gestion des rapports
 *
 * Contient toute la logique métier extraite du GestionRapportController :
 * - Statistiques (étudiant et globales)
 * - Validation et sauvegarde des rapports
 * - Gestion des dépôts
 * - Export PDF
 * - Commentaires évaluateurs
 */
class GestionRapportService
{
    /** @var \PDO */
    private $db;

    /** @var RapportEtudiant */
    private $rapportModel;

    /** @var Etudiant */
    private $etudiant;

    /** @var AuditLog */
    private $auditLog;

    /** @var InfoStage */
    private $infoStageModel;

    /** @var Entreprise */
    private $entrepriseModel;

    /** @var string */
    private $uploadsPath;

    /**
     * @param \PDO $db Connexion à la base de données
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->rapportModel = new RapportEtudiant($db);
        $this->etudiant = new Etudiant($db);
        $this->auditLog = new AuditLog($db);
        $this->infoStageModel = new InfoStage($db);
        $this->entrepriseModel = new Entreprise($db);
        $this->uploadsPath = __DIR__ . '/../../ressources/uploads/rapports/';
    }

    private function filterReportsBySelectedYear(array $rapports): array
    {
        return \AcademicYear::filterRowsBySelectedYear($rapports, 'id_annee_acad');
    }

    private function getStudentAcademicYearId($num_etu): ?int
    {
        try {
            $stmt = $this->db->prepare('SELECT id_annee_acad FROM inscriptions WHERE num_carte_etud = ? ORDER BY date_inscription DESC, num_versement DESC LIMIT 1');
            $stmt->execute([(string) $num_etu]);
            $value = $stmt->fetchColumn();
            return is_numeric($value) ? (int) $value : null;
        } catch (\Throwable $e) {
            error_log('Erreur getStudentAcademicYearId: ' . $e->getMessage());
            return null;
        }
    }

    private function ensureWritableForStudent(string $num_etu, string $context): array
    {
        return \AcademicYear::ensureWritableYear($this->db, $this->getStudentAcademicYearId($num_etu), $context);
    }

    private function ensureWritableForRapport($rapportId, string $context): array
    {
        $rapport = $this->rapportModel->getRapportById($rapportId);
        $yearId = is_array($rapport) && !empty($rapport['id_annee_acad']) ? (int) $rapport['id_annee_acad'] : null;
        return \AcademicYear::ensureWritableYear($this->db, $yearId, $context);
    }

    private function getDerniereDecisionRapport(int $rapportId): ?array
    {
        $decisions = Valider::getByRapport($rapportId);
        if (empty($decisions)) {
            return null;
        }

        $derniereDecision = end($decisions);
        if ($derniereDecision === false) {
            return null;
        }

        return is_array($derniereDecision) ? $derniereDecision : (array) $derniereDecision;
    }

    private function isRapportRejete(?array $decision): bool
    {
        if ($decision === null) {
            return false;
        }

        $statut = strtolower((string) ($decision['decision_validation'] ?? ''));
        return $statut !== '' && (str_contains($statut, 'rejet') || $statut === 'desapprouve');
    }

    // ========================= STATISTIQUES =========================

    /**
     * Récupère les statistiques pour un étudiant
     */
    public function getStatsEtudiant($num_etu)
    {
        return $this->rapportModel->getStatsEtudiant($num_etu);
    }

    /**
     * Récupère les rapports récents d'un étudiant (limités)
     */
    public function getRapportsRecentsEtudiant($num_etu, $limit = 5)
    {
        $rapports = $this->filterReportsBySelectedYear($this->rapportModel->getRapportsByEtudiant($num_etu));
        return array_slice($rapports, 0, $limit);
    }

    /**
     * Récupère les rapports récents (tous utilisateurs)
     */
    
    // ========================= CRÉATION / MODIFICATION =========================

    /**
     * Récupère les informations de stage d'un étudiant
     *
     * @return array|null Infos de stage formatées ou null
     */
    public function getStageInfo($num_etu)
    {
        $stage_info_raw = $this->infoStageModel->getStageInfo($num_etu);
        if (!$stage_info_raw) {
            return null;
        }

        $entreprise = $this->entrepriseModel->getEntrepriseById($stage_info_raw['id_entreprise']);

        // Construire le nom complet du maître de stage depuis les nouvelles colonnes
        $encadrantNom = $stage_info_raw['encadrant_nom'] ?? '';
        $encadrantPrenom = $stage_info_raw['encadrant_prenom'] ?? '';
        $encadrantComplet = trim($encadrantNom . ' ' . $encadrantPrenom);

        return [
            'nom_entreprise' => $stage_info_raw['nom_entreprise'] ?? ($entreprise ? $entreprise->lib_long_entreprise : ''),
            'logo_entreprise' => $entreprise ? ((string) ($entreprise->logo ?? '')) : '',
            'date_debut_stage' => $stage_info_raw['date_debut_stage'] ?? '',
            'date_fin_stage' => $stage_info_raw['date_fin_stage'] ?? '',
            'sujet_stage' => $stage_info_raw['sujet_stage'] ?? '',
            'encadrant_nom' => $encadrantNom,
            'encadrant_prenom' => $encadrantPrenom,
            'encadrant_entreprise' => $encadrantComplet, // Pour compatibilité avec les vues existantes
            'encadrant_email' => $stage_info_raw['encadrant_email'] ?? '',
            'encadrant_telephone' => $stage_info_raw['encadrant_telephone'] ?? ''
        ];
    }

    /**
     * Récupère un rapport par ID
     */
    public function getRapportById($id)
    {
        return $this->rapportModel->getRapportById($id);
    }

    /**
     * Vérifie si un rapport est déjà déposé
     */
    public function isRapportDepose($num_etu, $id_rapport)
    {
        $stmt = $this->rapportModel->pdo->prepare("SELECT COUNT(*) FROM deposer WHERE num_etu = ? AND id_rapport = ?");
        $stmt->execute([$num_etu, $id_rapport]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Charge le contenu HTML d'un rapport depuis le fichier
     */
    public function chargerContenuRapport($rapport_id)
    {
        $rapport = $this->rapportModel->getRapportById($rapport_id);
        $candidats = [];

        if (is_array($rapport) && !empty($rapport['chemin_fichier'])) {
            $cheminFichier = (string) $rapport['chemin_fichier'];

            if (preg_match('/^[A-Za-z]:\\\\|^\\\\\\\\/', $cheminFichier) === 1) {
                $candidats[] = $cheminFichier;
            } else {
                $candidats[] = $this->uploadsPath . basename($cheminFichier);
            }
        }

        $candidats[] = $this->uploadsPath . "rapport_{$rapport_id}.html";

        foreach (array_unique($candidats) as $fichierContenu) {
            if (strtolower((string) pathinfo($fichierContenu, PATHINFO_EXTENSION)) !== 'html') {
                continue;
            }

            if (!is_file($fichierContenu) || !is_readable($fichierContenu)) {
                continue;
            }

            $contenu = file_get_contents($fichierContenu);
            if ($contenu !== false) {
                return $contenu;
            }
        }

        return '';
    }

    /**
     * Compte le nombre de mots d'un rapport (contenu HTML).
     */
    private function compterMotsRapport($rapport_id, $num_etu)
    {
        $rapport = $this->rapportModel->getRapportByIdAndEtudiant($rapport_id, $num_etu);
        if (!$rapport) {
            return 0;
        }

        $html = $this->chargerContenuRapport($rapport_id);
        if ($html === '') {
            return 0;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (trim($text) === '') {
            return 0;
        }

        if (preg_match_all('/[\\p{L}\\p{N}\\-]+/u', $text, $matches)) {
            return count($matches[0]);
        }

        return 0;
    }

    /**
     * Valide les données d'un rapport
     *
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    public function validerDonneesRapport($donnees)
    {
        $erreurs = [];

        if (empty($donnees['nom_rapport']) || strlen($donnees['nom_rapport']) < 5) {
            $erreurs['nom_rapport'] = 'Le nom du rapport doit contenir au moins 5 caractères.';
        }

        if (empty($donnees['theme_rapport']) || strlen($donnees['theme_rapport']) < 10) {
            $erreurs['theme_rapport'] = 'Le thème du rapport doit contenir au moins 10 caractères.';
        }

        if (empty($donnees['contenu_rapport']) || strlen(strip_tags($donnees['contenu_rapport'])) < 50) {
            $erreurs['contenu_rapport'] = 'Le contenu du rapport doit contenir au moins 50 caractères.';
        }

        return $erreurs;
    }

    /**
     * Vérifie si un nom de rapport existe déjà pour un étudiant
     */
    public function isRapportNomExist($nom_rapport, $num_etu, $edit_id = null)
    {
        return $this->rapportModel->isRapportNomExist($nom_rapport, $num_etu, $edit_id);
    }

    /**
     * Sauvegarde ou met à jour un rapport
     *
     * @param array $donneesRapport Données du rapport (nom_rapport, theme_rapport, contenu_rapport, edit_id)
     * @param string $num_etu Numéro étudiant
     * @return array Résultat ['success' => bool, 'message' => string, 'rapport_id' => int|null, 'debug' => array|null]
     */
    public function sauvegarderRapport($donneesRapport, $num_etu)
    {
        $writeGuard = !empty($donneesRapport['edit_id'])
            ? $this->ensureWritableForRapport($donneesRapport['edit_id'], 'un rapport')
            : $this->ensureWritableForStudent((string) $num_etu, 'un rapport');
        if (empty($writeGuard['success'])) {
            return ['success' => false, 'message' => (string) ($writeGuard['message'] ?? 'Opération interdite.')];
        }

        // Validation
        $erreurs = $this->validerDonneesRapport($donneesRapport);
        if (!empty($erreurs)) {
            return ['success' => false, 'message' => 'Erreurs de validation', 'errors' => $erreurs];
        }

        // Vérifier unicité du nom
        if ($this->rapportModel->isRapportNomExist($donneesRapport['nom_rapport'], $num_etu, $donneesRapport['edit_id'] ?? null)) {
            return ['success' => false, 'message' => 'Vous avez déjà un rapport avec ce nom.'];
        }

        $htmlContent = (string) ($donneesRapport['contenu_rapport'] ?? '');
        $htmlLength = strlen($htmlContent);
        $textLength = strlen(trim(strip_tags($htmlContent)));
        error_log(sprintf(
            '[GestionRapportService] save request: edit_id=%s num_etu=%s nom_len=%d theme_len=%d html_len=%d text_len=%d',
            (string) ($donneesRapport['edit_id'] ?? ''),
            (string) $num_etu,
            strlen((string) ($donneesRapport['nom_rapport'] ?? '')),
            strlen((string) ($donneesRapport['theme_rapport'] ?? '')),
            $htmlLength,
            $textLength
        ));

        if (!empty($donneesRapport['edit_id'])) {
            // Mode modification - vérifier que le rapport n'est pas déjà déposé
            if ($this->isRapportDepose($num_etu, $donneesRapport['edit_id'])) {
                return ['success' => false, 'message' => 'Ce rapport ne peut plus être modifié car il a déjà été déposé.'];
            }

            // Mode modification
            $result = $this->rapportModel->updateRapport(
                $donneesRapport['edit_id'],
                $num_etu,
                $donneesRapport['nom_rapport'],
                $donneesRapport['theme_rapport']
            );
            $rapport_id = $donneesRapport['edit_id'];
            $message = 'Rapport modifié avec succès!';
        } else {
            // Mode création
            $rapport_id = $this->rapportModel->ajouterRapport(
                $num_etu,
                $donneesRapport['nom_rapport'],
                $donneesRapport['theme_rapport']
            );
            $result = $rapport_id !== false;
            $message = 'Rapport enregistré avec succès!';
        }

        if ($result) {
            $this->sauvegarderContenuRapport($rapport_id, $donneesRapport['contenu_rapport']);
            return [
                'success' => true,
                'message' => $message,
                'rapport_id' => $rapport_id
            ];
        }

        $debug = [
            'edit_id' => $donneesRapport['edit_id'] ?? null,
            'num_etu' => $num_etu,
            'nom_rapport' => $donneesRapport['nom_rapport'] ?? '',
            'theme_rapport' => $donneesRapport['theme_rapport'] ?? '',
            'contenu_html_length' => $htmlLength,
            'contenu_texte_length' => $textLength,
            'result' => $result
        ];
        error_log("Erreur sauvegarde rapport: " . print_r($debug, true));

        return [
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement du rapport.',
            'debug' => $debug
        ];
    }

    /**
     * Sauvegarde le contenu HTML d'un rapport sur le disque
     */
    public function sauvegarderContenuRapport($rapport_id, $contenu)
    {
        $dossierRapports = $this->uploadsPath;

        if (!is_dir($dossierRapports)) {
            if (!mkdir($dossierRapports, 0755, true)) {
                throw new \Exception('Impossible de créer le dossier de stockage des rapports.');
            }
        }

        if (!is_writable($dossierRapports)) {
            throw new \Exception('Le dossier de stockage des rapports n\'est pas accessible en écriture.');
        }

        $nomFichier = 'rapport_' . $rapport_id . '.html';
        $cheminComplet = $dossierRapports . $nomFichier;

        if (!file_put_contents($cheminComplet, $contenu)) {
            throw new \Exception('Erreur lors de la sauvegarde du contenu du rapport.');
        }

        // Mettre à jour le chemin du fichier et sa taille dans la base
        $tailleFichier = filesize($cheminComplet);
        $this->rapportModel->updateCheminFichier($rapport_id, $nomFichier, $tailleFichier);
    }

    // ========================= DÉPÔT =========================

    /**
     * Enregistre le dépôt d'un rapport
     *
     * @return bool Succès du dépôt
     */
    public function enregistrerDepotRapport($id_rapport, $num_etu)
    {
        $date_depot = date('Y-m-d H:i:s');
        $writeGuard = $this->ensureWritableForRapport($id_rapport, 'un depot de rapport');
        if (empty($writeGuard['success'])) {
            return false;
        }

        // Vérifier si l'étudiant a déjà un rapport en cours d'évaluation
        if ($this->aUnRapportEnCours($num_etu)) {
            return false;
        }

        // Vérifier si le dépôt existe déjà (éviter les doublons)
        if ($this->isRapportDepose($num_etu, $id_rapport)) {
            return false;
        }

        $pdo = $this->rapportModel->pdo;
        $transactionStarted = false;

        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $transactionStarted = true;
            }

            $stmt = $pdo->prepare("INSERT INTO deposer (num_etu, id_rapport, date_depot) VALUES (?, ?, ?)");
            $depotSuccess = $stmt->execute([$num_etu, $id_rapport, $date_depot]);
            if (!$depotSuccess) {
                if ($transactionStarted && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                return false;
            }

            if (!$this->rapportModel->setRapportEnCours($id_rapport)) {
                if ($transactionStarted && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                return false;
            }

            // S'assurer que l'étudiant a une candidature — la créer si absente
            $findCandidature = $pdo->prepare("SELECT id_candidature FROM candidature_soutenance WHERE num_etu = ? ORDER BY date_candidature DESC LIMIT 1");
            $findCandidature->execute([$num_etu]);
            $idCandidature = $findCandidature->fetchColumn();

            if (!$idCandidature) {
                $createCandidature = $pdo->prepare("INSERT INTO candidature_soutenance (num_etu, date_candidature, statut_candidature) VALUES (?, NOW(), 'En attente')");
                $createCandidature->execute([$num_etu]);
                $idCandidature = $pdo->lastInsertId();
            }

            // Lier le rapport à la candidature
            if ($idCandidature) {
                $linkRapport = $pdo->prepare("UPDATE rapport_etudiants SET id_candidature = ? WHERE id_rapport = ?");
                $linkRapport->execute([$idCandidature, $id_rapport]);
            }

            if ($transactionStarted && $pdo->inTransaction()) {
                $pdo->commit();
            }

            return true;
        } catch (\Throwable $e) {
            if ($transactionStarted && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erreur enregistrerDepotRapport: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si l'étudiant a un rapport en cours d'évaluation (non rejeté)
     */
    public function aUnRapportEnCours($num_etu)
    {
        // Récupérer le dernier rapport déposé par l'étudiant
        $stmt = $this->rapportModel->pdo->prepare("
            SELECT d.id_rapport, d.date_depot 
            FROM deposer d 
            WHERE d.num_etu = ? 
            ORDER BY d.date_depot DESC 
            LIMIT 1
        ");
        $stmt->execute([$num_etu]);
        $dernierDepot = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$dernierDepot) {
            return false;
        }

        $derniereDecision = $this->getDerniereDecisionRapport((int) $dernierDepot['id_rapport']);
        if (!$this->isRapportRejete($derniereDecision)) {
            return true;
        }

        return false;
    }

    /**
     * Récupère le dernier rapport déposé par l'étudiant avec ses données complètes
     */
    public function getDernierRapportDepose($num_etu)
    {
        $stmt = $this->rapportModel->pdo->prepare("
            SELECT r.*, d.date_depot
            FROM deposer d
            JOIN rapport_etudiants r ON r.id_rapport = d.id_rapport
            WHERE d.num_etu = ?
            ORDER BY d.date_depot DESC
            LIMIT 1
        ");
        $stmt->execute([$num_etu]);
        return $stmt->fetch(\PDO::FETCH_OBJ) ?: null;
    }

    // ========================= SUIVI =========================

    // ========================= COMMENTAIRES / COMPTE RENDU =========================

    // ========================= EXPORT PDF =========================

    /**
     * Génère le contenu PDF d'un rapport
     *
     * @param string      $contenu_rapport Contenu HTML du rapport
     * @param string      $nom_rapport     Nom du rapport
     * @param string|null $edit_id         ID du rapport en mode édition
     * @param string      $num_etu         Numéro étudiant (pour vérification de permissions)
     * @return array ['pdf_output' => string, 'pdf_name' => string] en cas de succès
     * @throws \Exception En cas d'erreur
     */
    public function genererPdf($contenu_rapport, $nom_rapport, $edit_id, $num_etu)
    {
        // Si on est en mode édition, vérifier les permissions
        if ($edit_id) {
            $rapport = $this->rapportModel->getRapportById($edit_id);
            if (!$rapport) {
                throw new \Exception('Rapport non trouvé.');
            }
            if ($rapport['num_etu'] != $num_etu) {
                throw new \Exception('Accès non autorisé à ce rapport.');
            }
            // Si le contenu est vide, essayer de le récupérer depuis le fichier
            if (empty($contenu_rapport)) {
                $contenu_rapport = $this->chargerContenuRapport($edit_id);
            }
        }

        if (empty($contenu_rapport)) {
            throw new \Exception('Le contenu du rapport est vide.');
        }

        $css = $this->getCssPdf();

        $htmlContent = "<!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <title>" . htmlspecialchars($nom_rapport) . "</title>
            {$css}
        </head>
        <body>
            {$contenu_rapport}
        </body>
        </html>";

        error_log("HTML Content length: " . strlen($htmlContent));

        // Générer le PDF avec PdfGeneratorService (TCPDF)
        require_once __DIR__ . '/../Services/Document/PdfGeneratorService.php';
        $pdfGen = new \App\Services\Document\PdfGeneratorService(
            __DIR__ . '/../../storage',
            __DIR__ . '/../../public/assets/img/logo.png'
        );
        $pdf = $pdfGen->createDocument('P', 'A4', htmlspecialchars($nom_rapport));
        $pdf->AddPage();
        $pdfGen->writeHtml($pdf, $htmlContent);

        error_log("PDF rendering completed.");

        $pdfName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nom_rapport) . '.pdf';

        return [
            'pdf_output' => $pdf->Output($pdfName, 'S'),
            'pdf_name' => $pdfName
        ];
    }

    /**
     * Retourne le CSS utilisé pour l'export PDF
     */
    private function getCssPdf()
    {
        return "
            <style>
                /* Reset et base */
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }
                
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0; 
                    padding: 0;
                    line-height: 1.6; 
                    font-size: 12pt;
                    color: #333;
                }
                
                /* Page breaks */
                div[style*='page-break'] {
                    page-break-before: always !important;
                }
                
                /* Tableaux - Configuration essentielle pour DOMPDF */
                table {
                    border-collapse: collapse;
                    width: 100%;
                }
                
                td, th {
                    vertical-align: top;
                }
                
                /* Images */
                img {
                    max-width: 100%;
                    height: auto;
                    display: inline-block;
                }
                
                /* Titres */
                h1, h2, h3, h4, h5, h6 {
                    margin: 15px 0 10px 0;
                    padding: 0;
                    page-break-after: avoid;
                }
                
                /* Paragraphes */
                p {
                    margin: 0 0 10px 0;
                }
                
                /* Listes */
                ul, ol {
                    margin: 10px 0 10px 30px;
                    padding: 0;
                }
                
                li {
                    margin-bottom: 5px;
                }
                
                /* Liens */
                a {
                    color: #1a5f7a;
                    text-decoration: none;
                }
                
                /* Forcer la préservation des styles inline */
                [style] {
                    /* Les styles inline sont prioritaires */
                }
            </style>
        ";
    }

    // ========================= CANDIDATURES =========================

    /**
     * Récupère les candidatures d'un étudiant
     */
    public function getCandidatures($num_etu)
    {
        return $this->etudiant->getCandidatures($num_etu);
    }

    // ========================= AUDIT =========================

    /**
     * @return AuditLog
     */
    public function getAuditLog()
    {
        return $this->auditLog;
    }

    // ========================= DEPOT WORKFLOW =========================

    /**
     * Traite le dépôt d'un rapport et retourne un résultat structuré.
     *
     * @param string|int $id_rapport
     * @param string      $num_etu
     * @return array ['success' => bool, 'redirect' => string]
     */
    public function traiterDepotRapport($id_rapport, $num_etu)
    {
        $result = $this->enregistrerDepotRapport($id_rapport, $num_etu);

        if ($result) {
            return ['success' => true, 'redirect' => '?page=gestion_rapports&message=depot_ok'];
        }

        // Déterminer la raison de l'échec
        if ($this->aUnRapportEnCours($num_etu)) {
            return ['success' => false, 'redirect' => '?page=gestion_rapports&message=depot_en_cours'];
        }

        return ['success' => false, 'redirect' => '?page=gestion_rapports&message=depot_fail'];
    }

    // ======================== PRD 1 & 2 : Upload de fichier rapport ========================

    /**
     * Retourne le chemin du dossier d'upload des rapports
     */
    public function getUploadsPath()
    {
        return $this->uploadsPath;
    }

    /**
     * Retourne l'URL du modèle de rapport (configurable)
     * PRD 1 F1.1
     */
    public function getModeleRapportUrl()
    {
        // Cherche d'abord dans les paramètres de configuration
        try {
            $stmt = $this->db->prepare("SELECT valeur_parametre FROM parametres WHERE code_parametre = 'MODELE_RAPPORT_URL' LIMIT 1");
            $stmt->execute();
            $url = $stmt->fetchColumn();
            if ($url && $url !== '') {
                return $url;
            }
        } catch (\Throwable $e) {
            error_log('Erreur getModeleRapportUrl: ' . $e->getMessage());
        }

        // Chemin par défaut
        return 'ressources/uploads/modeles/modele_rapport_stage.pdf';
    }

    /**
     * Vérifie qu'un fichier uploadé est valide (type, taille)
     * PRD 1 F1.2
     *
     * @param array $file $_FILES entry
     * @return array ['success' => bool, 'message' => string]
     */
    public function validerFichierRapport($file)
    {
        $erreurs = [];

        // Vérifier qu'un fichier a été uploadé
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par le serveur.',
                UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée par le formulaire.',
                UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé.',
                UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé.',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant sur le serveur.',
                UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque.',
            ];
            $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
            $message = $errorMessages[$code] ?? 'Erreur lors du téléchargement du fichier.';
            return ['success' => false, 'message' => $message];
        }

        // Vérifier le type de fichier
        $typesAutorises = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $extensionsAutorisees = ['pdf', 'doc', 'docx'];

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $typeMime = $file['type'];

        if (!in_array($extension, $extensionsAutorisees) || !in_array($typeMime, $typesAutorises)) {
            return ['success' => false, 'message' => 'Format de fichier non autorisé. Veuillez uploader un fichier PDF ou Word (doc/docx).'];
        }

        // Vérifier la taille (max 20 MB par défaut)
        $tailleMax = 20 * 1024 * 1024; // 20 MB
        if ($file['size'] > $tailleMax) {
            $tailleEnMo = $tailleMax / (1024 * 1024);
            return ['success' => false, 'message' => "Le fichier dépasse la taille maximale autorisée de {$tailleEnMo} Mo."];
        }

        return ['success' => true, 'message' => 'Fichier valide.'];
    }

    /**
     * Génère un nom de fichier unique pour un rapport uploadé
     *
     * @param string $num_etu
     * @param string $extension
     * @return string
     */
    public function genererNomFichierRapport($num_etu, $extension)
    {
        $date = date('Ymd_His');
        $prefixe = preg_replace('/[^a-zA-Z0-9]/', '_', $num_etu);
        return "Rapport_{$prefixe}_{$date}.{$extension}";
    }

    /**
     * Détermine l'année académique via la dernière inscription
     *
     * @param string $num_etu
     * @return int|null
     */
    public function getAnneeAcademiqueForEtudiant($num_etu)
    {
        return $this->getStudentAcademicYearId($num_etu);
    }

    /**
     * Traite l'upload d'un rapport par l'étudiant
     * PRD 1 F1.3
     *
     * @param array $file $_FILES['rapport_fichier']
     * @param string $num_etu
     * @param string $theme_rapport
     * @return array ['success' => bool, 'message' => string, 'id_rapport' => int|null]
     */
    public function traiterUploadRapportEtudiant($file, $num_etu, $theme_rapport = '')
    {
        // Valider le fichier
        $validation = $this->validerFichierRapport($file);
        if (!$validation['success']) {
            return $validation;
        }

        // Vérifier les droits d'écriture
        $writeGuard = $this->ensureWritableForStudent($num_etu, 'un depot de rapport');
        if (empty($writeGuard['success'])) {
            return ['success' => false, 'message' => (string) ($writeGuard['message'] ?? 'Opération interdite pour l\'année académique sélectionnée.')];
        }

        // Déterminer l'année académique
        $id_annee_acad = $this->getAnneeAcademiqueForEtudiant($num_etu);

        // Créer le dossier si nécessaire
        $sousDossier = $id_annee_acad ? 'annee_' . $id_annee_acad : 'autres';
        $dossierUpload = $this->uploadsPath . $sousDossier . '/';
        if (!is_dir($dossierUpload)) {
            if (!mkdir($dossierUpload, 0755, true)) {
                return ['success' => false, 'message' => 'Impossible de créer le dossier de stockage.'];
            }
        }

        // Générer le nom du fichier
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $nomFichier = $this->genererNomFichierRapport($num_etu, $extension);
        $cheminFichier = $sousDossier . '/' . $nomFichier;
        $cheminComplet = $this->uploadsPath . $cheminFichier;

        // Déplacer le fichier uploadé
        if (!move_uploaded_file($file['tmp_name'], $cheminComplet)) {
            return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement du fichier sur le serveur.'];
        }

        // Vérifier si l'étudiant a déjà un rapport (uploadé)
        $rapportExistant = $this->rapportModel->getDernierRapportUploaded($num_etu);

        if ($rapportExistant && isset($rapportExistant->id_rapport)) {
            // Mettre à jour le rapport existant
            $updated = $this->rapportModel->mettreAJourRapportAvecFichier(
                $rapportExistant->id_rapport,
                $cheminFichier,
                $file['size'],
                date('Y-m-d H:i:s')
            );
            if ($updated) {
                // Audit
                $this->auditLog->logModification(
                    $_SESSION['id_utilisateur'] ?? 0,
                    'rapport_etudiants',
                    'Succès'
                );
                return ['success' => true, 'message' => 'Rapport mis à jour avec succès.', 'id_rapport' => $rapportExistant->id_rapport];
            }
        }

        // Créer un nouveau rapport
        $nomRapport = 'Rapport_' . $nomFichier;
        $id_rapport = $this->rapportModel->creerRapportAvecFichier(
            $num_etu,
            $nomRapport,
            $theme_rapport,
            $cheminFichier,
            $file['size'],
            $id_annee_acad,
            date('Y-m-d H:i:s')
        );

        if ($id_rapport) {
            // Audit
            $this->auditLog->logDepot(
                $_SESSION['id_utilisateur'] ?? 0,
                'rapport_etudiants',
                'Succès'
            );
            return ['success' => true, 'message' => 'Rapport téléchargé avec succès.', 'id_rapport' => $id_rapport];
        }

        return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement dans la base de données.'];
    }

    /**
     * Traite l'upload d'un rapport par l'administration
     * PRD 2 F2.3-F2.4
     *
     * @param array $file $_FILES['rapport_fichier']
     * @param string $num_etu
     * @param string $theme_rapport
     * @param string|null $date_operation Date métier (modifiable par admin)
     * @return array ['success' => bool, 'message' => string, 'id_rapport' => int|null]
     */
    public function traiterUploadRapportAdmin($file, $num_etu, $theme_rapport = '', $date_operation = null)
    {
        // Valider le fichier
        $validation = $this->validerFichierRapport($file);
        if (!$validation['success']) {
            return $validation;
        }

        // Déterminer l'année académique
        $id_annee_acad = $this->getAnneeAcademiqueForEtudiant($num_etu);

        // Créer le dossier si nécessaire
        $sousDossier = $id_annee_acad ? 'annee_' . $id_annee_acad : 'autres';
        $dossierUpload = $this->uploadsPath . $sousDossier . '/';
        if (!is_dir($dossierUpload)) {
            if (!mkdir($dossierUpload, 0755, true)) {
                return ['success' => false, 'message' => 'Impossible de créer le dossier de stockage.'];
            }
        }

        // Générer le nom du fichier
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $nomFichier = $this->genererNomFichierRapport($num_etu, $extension);
        $cheminFichier = $sousDossier . '/' . $nomFichier;
        $cheminComplet = $this->uploadsPath . $cheminFichier;

        // Déplacer le fichier uploadé
        if (!move_uploaded_file($file['tmp_name'], $cheminComplet)) {
            return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement du fichier sur le serveur.'];
        }

        // Vérifier si l'étudiant a déjà un rapport uploadé
        $rapportExistant = $this->rapportModel->getDernierRapportUploaded($num_etu);

        if ($rapportExistant && isset($rapportExistant->id_rapport)) {
            // Mettre à jour le rapport existant
            $updated = $this->rapportModel->mettreAJourRapportAvecFichier(
                $rapportExistant->id_rapport,
                $cheminFichier,
                $file['size'],
                $date_operation
            );
            if ($updated) {
                // Audit PRD 3
                $this->auditLog->logModification(
                    $_SESSION['id_utilisateur'] ?? 0,
                    'rapport_etudiants',
                    'Succès'
                );
                return ['success' => true, 'message' => 'Rapport mis à jour avec succès.', 'id_rapport' => $rapportExistant->id_rapport];
            }
        }

        // Créer un nouveau rapport
        $nomRapport = 'Rapport_' . $nomFichier;
        $id_rapport = $this->rapportModel->creerRapportAvecFichier(
            $num_etu,
            $nomRapport,
            $theme_rapport,
            $cheminFichier,
            $file['size'],
            $id_annee_acad,
            $date_operation
        );

        if ($id_rapport) {
            // Audit
            $this->auditLog->logAction(
                $_SESSION['id_utilisateur'] ?? 0,
                'Import rapport',
                'rapport_etudiants',
                'Succès'
            );
            return ['success' => true, 'message' => 'Rapport importé avec succès.', 'id_rapport' => $id_rapport];
        }

        return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement dans la base de données.'];
    }

    /**
     * Récupère la liste des étudiants sans rapport
     * PRD 2 F2.1
     *
     * @param int|null $id_annee_acad
     * @return array
     */
    public function getEtudiantsSansRapport($id_annee_acad = null)
    {
        if ($id_annee_acad === null) {
            $id_annee_acad = $this->getSelectedYearId();
        }
        return $this->rapportModel->getEtudiantsSansRapport($id_annee_acad);
    }

    /**
     * Récupère tous les rapports pour l'admin
     * PRD 2 / PRD 4
     *
     * @param int|null $id_annee_acad
     * @param string|null $search
     * @return array
     */
    public function getAllRapportsAdmin($id_annee_acad = null, $search = null)
    {
        if ($id_annee_acad === null) {
            $id_annee_acad = $this->getSelectedYearId();
        }
        return $this->rapportModel->getAllRapportsAdmin($id_annee_acad, $search);
    }

    /**
     * Récupère l'ID de l'année académique sélectionnée en session
     *
     * @return int|null
     */
    private function getSelectedYearId()
    {
        return !empty($_SESSION['selected_academic_year_id']) ? (int) $_SESSION['selected_academic_year_id'] : null;
    }

    /**
     * Met à jour la date d'opération d'un rapport avec journalisation
     * PRD 3 F3.4
     *
     * @param int $id_rapport
     * @param string $nouvelle_date
     * @param string $ancienne_date
     * @return bool
     */
    public function updateDateOperationWithAudit($id_rapport, $nouvelle_date, $ancienne_date)
    {
        $result = $this->rapportModel->updateDateOperation($id_rapport, $nouvelle_date);

        if ($result) {
            // Journalisation dans l'audit
            $details = "Ancienne date: {$ancienne_date}, Nouvelle date: {$nouvelle_date}";
            $this->auditLog->logAction(
                $_SESSION['id_utilisateur'] ?? 0,
                'Modification date opération',
                'rapport_etudiants',
                'Succès'
            );
        }

        return $result;
    }
}

