<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/RapportEtudiant.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Approuver.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/InfoStage.php';
require_once __DIR__ . '/../models/Entreprise.php';

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
        $this->rapportModel = new RapportEtudiant($db);
        $this->etudiant = new Etudiant($db);
        $this->auditLog = new AuditLog($db);
        $this->infoStageModel = new InfoStage($db);
        $this->entrepriseModel = new Entreprise($db);
        $this->uploadsPath = __DIR__ . '/../../ressources/uploads/rapports/';
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
        $rapports = $this->rapportModel->getRapportsByEtudiant($num_etu);
        return array_slice($rapports, 0, $limit);
    }

    /**
     * Récupère les rapports récents (tous utilisateurs)
     */
    public function getRecentRapports($limit = 5)
    {
        return $this->rapportModel->getRecentRapports($limit);
    }

    /**
     * Calcule les statistiques globales de tous les rapports
     */
    public function calculerStatistiquesGlobales()
    {
        $rapports = $this->rapportModel->getAllRapports();

        $stats = [
            'total' => count($rapports),
            'aujourd_hui' => 0,
            'semaine' => 0,
            'mois' => 0
        ];

        $debutJour = strtotime('today');
        $debutSemaine = strtotime('monday this week');
        $debutMois = strtotime('first day of this month');

        foreach ($rapports as $rapport) {
            $dateRapport = strtotime($rapport->date_rapport);

            if ($dateRapport >= $debutJour) {
                $stats['aujourd_hui']++;
            }
            if ($dateRapport >= $debutSemaine) {
                $stats['semaine']++;
            }
            if ($dateRapport >= $debutMois) {
                $stats['mois']++;
            }
        }

        return $stats;
    }

    /**
     * Calcule les statistiques selon le type d'utilisateur
     */
    public function calculerStatistiques($isEtudiant, $num_etu = null)
    {
        if ($isEtudiant && $num_etu) {
            $stats = $this->rapportModel->getStatsEtudiant($num_etu);
            return [
                'total' => $stats->total_rapports ?? 0,
                'aujourd_hui' => $stats->rapports_aujourd_hui ?? 0,
                'semaine' => $stats->rapports_semaine ?? 0,
                'mois' => $stats->rapports_mois ?? 0
            ];
        }
        return $this->calculerStatistiquesGlobales();
    }

    // ========================= INFOS DÉPÔT =========================

    /**
     * Récupère les informations de dépôt pour tous les rapports d'un étudiant
     */
    public function getInfosDepotRapports($num_etu)
    {
        $infos = [];
        $rapports = $this->rapportModel->getRapportsByEtudiant($num_etu);

        foreach ($rapports as $rapport) {
            $rapportId = $rapport->id_rapport;

            // Vérifier si ce rapport est déjà déposé
            $stmt = $this->rapportModel->pdo->prepare("SELECT COUNT(*) FROM deposer WHERE num_etu = ? AND id_rapport = ?");
            $stmt->execute([$num_etu, $rapportId]);
            $dejaDepose = $stmt->fetchColumn() > 0;

            $peutDeposer = true;
            $messageDepot = '';
            $nbMots = $this->compterMotsRapport($rapportId, $num_etu);

            if ($dejaDepose) {
                $peutDeposer = false;
                $messageDepot = 'Déjà déposé';
            } elseif ($nbMots < 5000) {
                $peutDeposer = false;
                $messageDepot = 'Minimum 5 000 mots requis (' . (int) $nbMots . ')';
            } else {
                // Vérifier si l'étudiant a un autre rapport en cours d'évaluation
                $stmt = $this->rapportModel->pdo->prepare("
                    SELECT d.id_rapport, d.date_depot 
                    FROM deposer d 
                    WHERE d.num_etu = ? 
                    ORDER BY d.date_depot DESC 
                    LIMIT 1
                ");
                $stmt->execute([$num_etu]);
                $dernierDepot = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($dernierDepot && $dernierDepot['id_rapport'] != $rapportId) {
                    // Vérifier le statut d'approbation du dernier rapport déposé
                    $stmt = $this->rapportModel->pdo->prepare("
                        SELECT a.*, n.lib_approb 
                        FROM approuver a
                        JOIN niveau_approbation n ON a.id_approb = n.id_approb
                        WHERE a.id_rapport = ?
                        ORDER BY a.date_approv DESC
                        LIMIT 1
                    ");
                    $stmt->execute([$dernierDepot['id_rapport']]);
                    $derniereApprobation = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if (!$derniereApprobation || strtolower($derniereApprobation['lib_approb']) !== 'rejeté') {
                        $peutDeposer = false;
                        $messageDepot = 'Vous avez déjà un rapport en cours d\'évaluation';
                    }
                }
            }

            $infos[$rapportId] = [
                'peutDeposer' => $peutDeposer,
                'messageDepot' => $messageDepot,
                'dejaDepose' => $dejaDepose
            ];
        }

        return $infos;
    }

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

        $entreprise = $this->entrepriseModel->getEntrepriseById($stage_info_raw->nom_entreprise);
        return [
            'nom_entreprise' => $entreprise ? $entreprise->lib_long_entreprise : '',
            'logo_entreprise' => $entreprise ? ((string) ($entreprise->logo ?? '')) : '',
            'date_debut_stage' => $stage_info_raw->date_debut_stage,
            'date_fin_stage' => $stage_info_raw->date_fin_stage,
            'sujet_stage' => $stage_info_raw->sujet_stage,
            'encadrant_entreprise' => $stage_info_raw->encadrant_entreprise,
            'email_encadrant' => $stage_info_raw->email_encadrant,
            'telephone_encadrant' => $stage_info_raw->telephone_encadrant
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
        $fichierContenu = $this->uploadsPath . "rapport_{$rapport_id}.html";
        if (file_exists($fichierContenu)) {
            return file_get_contents($fichierContenu);
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
     * Vérifie le quota minimum de mots avant dépôt.
     */
    private function verifierQuotaDepotRapport($rapport_id, $num_etu, $minimumMots = 5000)
    {
        return $this->compterMotsRapport($rapport_id, $num_etu) >= (int) $minimumMots;
    }

    private function normaliserStatutCandidature($statut)
    {
        $normalized = strtolower(trim((string) $statut));
        $trans = [
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'à' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'î' => 'i',
            'ï' => 'i',
            'ô' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
        ];
        $normalized = strtr($normalized, $trans);
        $normalized = str_replace([' ', '-'], '_', $normalized);
        return $normalized;
    }

    /**
     * Crée automatiquement une candidature de soutenance lors du dépôt si nécessaire.
     */
    private function assurerCandidatureAutomatique($num_etu)
    {
        $stmt = $this->rapportModel->pdo->prepare("
            SELECT statut_candidature
            FROM candidature_soutenance
            WHERE num_etu = ?
            ORDER BY date_candidature DESC
            LIMIT 1
        ");
        $stmt->execute([$num_etu]);
        $lastStatus = $stmt->fetchColumn();

        if ($lastStatus !== false) {
            $status = $this->normaliserStatutCandidature($lastStatus);
            $statusBloquants = ['en_attente', 'validee', 'valide', 'acceptee', 'accepte'];
            if (in_array($status, $statusBloquants, true)) {
                return true;
            }
        }

        $insert = $this->rapportModel->pdo->prepare("
            INSERT INTO candidature_soutenance (num_etu, date_candidature, statut_candidature)
            VALUES (?, NOW(), 'En attente')
        ");
        return $insert->execute([$num_etu]);
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
        // Validation
        $erreurs = $this->validerDonneesRapport($donneesRapport);
        if (!empty($erreurs)) {
            return ['success' => false, 'message' => 'Erreurs de validation', 'errors' => $erreurs];
        }

        // Vérifier unicité du nom
        if ($this->rapportModel->isRapportNomExist($donneesRapport['nom_rapport'], $num_etu, $donneesRapport['edit_id'])) {
            return ['success' => false, 'message' => 'Vous avez déjà un rapport avec ce nom.'];
        }

        error_log("Données pour sauvegarde: " . print_r($donneesRapport, true));
        error_log("Num étudiant: " . $num_etu);

        if ($donneesRapport['edit_id']) {
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
            'donneesRapport' => $donneesRapport,
            'num_etu' => $num_etu,
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

            if (!$this->assurerCandidatureAutomatique($num_etu)) {
                if ($transactionStarted && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                return false;
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

        // Vérifier le statut d'approbation du dernier rapport déposé
        $stmt = $this->rapportModel->pdo->prepare("
            SELECT a.*, n.lib_approb 
            FROM approuver a
            JOIN niveau_approbation n ON a.id_approb = n.id_approb
            WHERE a.id_rapport = ?
            ORDER BY a.date_approv DESC
            LIMIT 1
        ");
        $stmt->execute([$dernierDepot['id_rapport']]);
        $derniereApprobation = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$derniereApprobation || strtolower($derniereApprobation['lib_approb']) !== 'rejeté') {
            return true;
        }

        return false;
    }

    // ========================= SUIVI =========================

    /**
     * Récupère les rapports d'un étudiant avec l'historique des décisions
     */
    public function getRapportsAvecDecisions($num_etu)
    {
        $rapports = $this->rapportModel->getRapportsByEtudiant($num_etu);

        foreach ($rapports as &$rapport) {
            $rapport = (array) $rapport;
            $rapport['decisions'] = Approuver::getByRapport($rapport['id_rapport']);
        }

        return $rapports;
    }

    // ========================= COMMENTAIRES / COMPTE RENDU =========================

    /**
     * Récupère les rapports filtrés pour le compte rendu
     *
     * @param bool   $isEtudiant
     * @param string $num_etu
     * @param string $statut     Filtre par statut
     * @param string $search     Recherche textuelle
     * @return array
     */
    public function getRapportsFiltres($isEtudiant, $num_etu, $statut = '', $search = '')
    {
        if ($isEtudiant) {
            $rapports = array_map(function ($rapport) {
                return (array) $rapport;
            }, $this->rapportModel->getRapportsByEtudiant($num_etu));
        } else {
            $rapports = array_map(function ($rapport) {
                return (array) $rapport;
            }, $this->rapportModel->getAllRapports());
        }

        // Appliquer les filtres
        if (!empty($statut) || !empty($search)) {
            $rapports = array_filter($rapports, function ($rapport) use ($statut, $search) {
                $matchStatut = empty($statut) || $rapport['statut_rapport'] === $statut;
                $matchSearch = empty($search) ||
                    stripos($rapport['nom_rapport'], $search) !== false ||
                    stripos($rapport['theme_rapport'], $search) !== false ||
                    stripos($rapport['nom_etu'] . ' ' . $rapport['prenom_etu'], $search) !== false;

                return $matchStatut && $matchSearch;
            });
        }

        return $rapports;
    }

    /**
     * Récupère les commentaires des évaluateurs pour un rapport
     */
    public function getCommentairesEvaluateurs($rapportId)
    {
        $commentaires = [];

        try {
            $stmt = $this->rapportModel->pdo->prepare("
                SELECT 
                    e.commentaire,
                    e.date_evaluation,
                    e.note,
                    CASE 
                        WHEN e.type_evaluateur = 'enseignant' THEN ens.nom_enseignant
                        ELSE pa.nom_pers_admin 
                    END as nom_evaluateur,
                    CASE 
                        WHEN e.type_evaluateur = 'enseignant' THEN ens.prenom_enseignant
                        ELSE pa.prenom_pers_admin 
                    END as prenom_evaluateur,
                    CASE 
                        WHEN e.type_evaluateur = 'enseignant' THEN 'Enseignant'
                        ELSE 'Personnel administratif'
                    END as fonction_evaluateur
                FROM evaluations_rapports e
                LEFT JOIN enseignants ens ON e.id_evaluateur = ens.id_enseignant AND e.type_evaluateur = 'enseignant'
                LEFT JOIN personnel_admin pa ON e.id_evaluateur = pa.id_pers_admin AND e.type_evaluateur = 'personnel_admin'
                WHERE e.id_rapport = ? AND e.commentaire IS NOT NULL AND e.commentaire != '' AND e.statut_evaluation = 'terminee'
                ORDER BY e.date_evaluation DESC
            ");
            $stmt->execute([$rapportId]);
            $commentaires = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des commentaires: " . $e->getMessage());
        }

        return $commentaires;
    }

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
        // Vérifier que DOMPDF est disponible
        if (!class_exists('\Dompdf\Dompdf')) {
            require_once __DIR__ . '/../../vendor/autoload.php';
        }

        if (!class_exists('\Dompdf\Dompdf')) {
            throw new \Exception('DOMPDF n\'est pas installé ou accessible.');
        }

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

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $options->set('defaultPaperSize', 'A4');
        $options->set('defaultPaperOrientation', 'portrait');
        $options->set('isFontSubsettingEnabled', true);
        $options->set('isCssFloatEnabled', true);
        $options->set('isJavascriptEnabled', false);
        $options->set('chroot', __DIR__ . '/../../public/');

        $dompdf = new \Dompdf\Dompdf($options);

        if (!$dompdf) {
            throw new \Exception('Impossible d\'instancier DOMPDF.');
        }

        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait');

        error_log("Starting PDF rendering...");
        $dompdf->render();
        error_log("PDF rendering completed.");

        $pdfName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nom_rapport) . '.pdf';

        return [
            'pdf_output' => $dompdf->output(),
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

    // ========================= SUPPRESSION =========================

    /**
     * Supprime un rapport (avec vérifications)
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function supprimerRapport($rapportId, $num_etu)
    {
        // Vérifier que le rapport appartient à l'étudiant
        $rapport = $this->rapportModel->getRapportById($rapportId);
        if (!$rapport || $rapport['num_etu'] != $num_etu) {
            return ['success' => false, 'message' => 'Rapport non trouvé ou accès non autorisé'];
        }

        // Vérifier que le rapport n'est pas déjà déposé
        if ($this->isRapportDepose($num_etu, $rapportId)) {
            return ['success' => false, 'message' => 'Impossible de supprimer un rapport déjà déposé'];
        }

        $success = $this->rapportModel->deleteRapport($rapportId, $num_etu);

        if ($success) {
            // Supprimer le fichier de contenu
            $filename = $this->uploadsPath . "rapport_{$rapportId}.html";
            if (file_exists($filename)) {
                unlink($filename);
            }
            return ['success' => true, 'message' => 'Rapport supprimé avec succès'];
        }

        return ['success' => false, 'message' => 'Erreur lors de la suppression du rapport'];
    }

    /**
     * Supprime un rapport via AJAX (vérification simplifiée)
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteRapport($rapport_id, $num_etu)
    {
        $result = $this->rapportModel->deleteRapport($rapport_id, $num_etu);

        if ($result) {
            $filename = $this->uploadsPath . "rapport_{$rapport_id}.html";
            if (file_exists($filename)) {
                unlink($filename);
            }
            return ['success' => true, 'message' => 'Rapport supprimé avec succès'];
        }

        return ['success' => false, 'message' => 'Rapport non trouvé ou non autorisé'];
    }

    /**
     * Récupère un rapport appartenant à un étudiant avec son contenu
     *
     * @return array|null
     */
    public function getRapportAvecContenu($rapport_id, $num_etu)
    {
        $rapport = $this->rapportModel->getRapportByIdAndEtudiant($rapport_id, $num_etu);

        if (!$rapport) {
            return null;
        }

        $rapport_array = (array) $rapport;
        $rapport_array['contenu'] = $this->chargerContenuRapport($rapport_id);
        return $rapport_array;
    }

    // ========================= EXPORT CSV =========================

    /**
     * Récupère tous les rapports pour l'export CSV
     */
    public function getAllRapports()
    {
        return $this->rapportModel->getAllRapports();
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
        if (!$this->verifierQuotaDepotRapport($id_rapport, $num_etu, 5000)) {
            return ['success' => false, 'redirect' => '?page=gestion_rapports&message=depot_quota'];
        }

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

    // ========================= HTML COMMENTAIRES =========================

    /**
     * Génère le HTML des commentaires évaluateurs pour un rapport.
     *
     * @param int $rapportId
     * @return string HTML
     */
    public function renderCommentairesHtml($rapportId)
    {
        $commentaires = $this->getCommentairesEvaluateurs($rapportId);

        if (empty($commentaires)) {
            return '<div class="text-center py-8 text-gray-500">'
                . '<i class="fas fa-comment-slash text-4xl mb-4"></i>'
                . '<p>Aucun commentaire disponible pour ce rapport</p>'
                . '</div>';
        }

        $html = '<div class="space-y-6">';
        foreach ($commentaires as $commentaire) {
            $nom = htmlspecialchars($commentaire['prenom_evaluateur'] . ' ' . $commentaire['nom_evaluateur']);
            $fonction = htmlspecialchars($commentaire['fonction_evaluateur']);
            $date = date('d M Y - H:i', strtotime($commentaire['date_evaluation']));
            $texte = nl2br(htmlspecialchars($commentaire['commentaire']));

            $html .= '<div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">';
            $html .= '<div class="flex items-center justify-between mb-3">';
            $html .= '<div class="flex items-center space-x-3">';
            $html .= '<div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">';
            $html .= '<i class="fas fa-user-tie text-blue-600"></i>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<h4 class="font-semibold text-gray-800">' . $nom . '</h4>';
            $html .= '<p class="text-sm text-gray-600"><i class="fas fa-briefcase mr-1"></i>' . $fonction . '</p>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="text-sm text-gray-500"><i class="fas fa-calendar mr-1"></i>' . $date . '</div>';
            $html .= '</div>';
            $html .= '<div class="bg-gray-50 rounded-lg p-3">';
            $html .= '<p class="text-gray-700 leading-relaxed">' . $texte . '</p>';

            if (!empty($commentaire['note'])) {
                $html .= '<div class="mt-3 pt-3 border-t border-gray-200">';
                $html .= '<p class="text-sm text-gray-600"><strong>Note:</strong> ' . $commentaire['note'] . '/20</p>';
                $html .= '</div>';
            }

            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    // ========================= EXPORT CSV FORMAT =========================

    /**
     * Construit les en-têtes et lignes pour l'export CSV.
     *
     * @return array ['headers' => string[], 'rows' => array[]]
     */
    public function buildCsvData()
    {
        $rapports = $this->getAllRapports();

        $headers = [
            'ID',
            'Nom du rapport',
            'Thème',
            'Date création',
            'Étudiant',
            'Email étudiant'
        ];

        $rows = [];
        foreach ($rapports as $rapport) {
            $rows[] = [
                $rapport->id_rapport,
                $rapport->nom_rapport,
                $rapport->theme_rapport,
                $rapport->date_rapport,
                $rapport->nom_etu . ' ' . $rapport->prenom_etu,
                $rapport->email_etu
            ];
        }

        return ['headers' => $headers, 'rows' => $rows];
    }
}
