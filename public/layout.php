<?php
require_once __DIR__ . '/../app/Core/Autoload.php';

use CheckMaster\Core\Csrf;
use CheckMaster\Core\Session;
use CheckMaster\Core\Bootstrap;

Bootstrap::init();
Session::start();

// Bufferiser la sortie pour injecter CSRF sur les formulaires legacy (migration progressive).
ob_start();

// [INJECTED_LOGGER]
register_shutdown_function(function() {
    $files = get_included_files();
    $logFile = __DIR__ . '/../../views_used.log';
    if (!file_exists($logFile)) {
        touch($logFile);
        chmod($logFile, 0777);
    }
    $usedViews = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($usedViews === false) $usedViews = [];
    
    $updated = false;
    foreach ($files as $f) {
        $f = str_replace(DIRECTORY_SEPARATOR, '/', $f);
        if (strpos($f, 'ressources/views') !== false) {
            if (!in_array($f, $usedViews)) {
                $usedViews[] = $f;
                $updated = true;
            }
        }
    }
    
    if ($updated) {
        file_put_contents($logFile, implode("\n", $usedViews) . "\n");
    }
});
// [/INJECTED_LOGGER]



include __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/utils/AcademicYear.php';

// ── Année académique globale ── résolution AVANT chargement des routes/contrôleurs
// afin que $_SESSION['global_annee_id'] soit disponible dans toutes les vues.
$globalAcademicYears = [];
$currentGlobalYear = '';
$currentGlobalYearId = null;
$currentGlobalYearIsAll = false;
$activeGlobalYear = '';
$activeGlobalYearId = null;
$writableGlobalYear = '';
$writableGlobalYearId = null;
try {
    $__context = AcademicYear::bootstrapSession(Database::getConnection(), $_GET, $_SESSION);

    foreach (($__context['years'] ?? []) as $__year) {
        $globalAcademicYears[(int) ($__year['id'] ?? 0)] = (string) ($__year['label'] ?? '');
    }

    $currentGlobalYear = (string) ($__context['selected']['label'] ?? '');
    $currentGlobalYearId = isset($__context['selected']['id']) ? (int) $__context['selected']['id'] : null;
    $currentGlobalYearIsAll = !empty($__context['all_selected']);
    $activeGlobalYear = (string) ($__context['active']['label'] ?? '');
    $activeGlobalYearId = isset($__context['active']['id']) ? (int) $__context['active']['id'] : null;
    $writableGlobalYear = (string) ($__context['writable']['label'] ?? '');
    $writableGlobalYearId = isset($__context['writable']['id']) ? (int) $__context['writable']['id'] : null;
} catch (\Throwable $e) {
    error_log('Layout: erreur chargement années académiques: ' . $e->getMessage());
}

include __DIR__ . '/../app/controllers/AuthController.php';
include __DIR__ . '/../app/controllers/MenuController.php';
include __DIR__ . '/../app/middlewares/PermissionMiddleware.php';
include __DIR__ . '/../app/utils/permissions_helper.php';
include_once __DIR__ . '/../app/utils/ComponentHelper.php';
include_once __DIR__ . '/../app/utils/FormHelper.php';
include_once __DIR__ . '/../app/utils/TableHelper.php';
include_once __DIR__ . '/../app/utils/PaginationHelper.php';

use CheckMaster\Security\RoutePermissionService;

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: page_connexion.php');
    exit;
} else {
    // Protection CSRF globale pour toutes les actions POST du legacy.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            // AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => 'Session expirée. Veuillez réessayer.']);
                exit;
            }

            $_SESSION['error_message'] = 'Session expirée. Veuillez réessayer.';
            $_SESSION['error_type'] = 'csrf';
            $fallback = 'layout.php?page=' . urlencode($_GET['page'] ?? 'dashboard');
            $redirect = $_SERVER['HTTP_REFERER'] ?? $fallback;
            header('Location: ' . $redirect);
            exit;
        }
    }

    // NOUVEAU : Initialiser le middleware de permissions
    $permissionMiddleware = new PermissionMiddleware();

    // NOUVEAU : Gate centralisé (Couche 1) - Sécurité backend globale pour les routes legacy
    $corePages = ['dashboard', 'dashboard_admin', 'profil', 'access_denied'];
    $currentMenuSlugForGate = isset($_GET['page']) ? $_GET['page'] : '';
    
    if (!empty($currentMenuSlugForGate) && !in_array($currentMenuSlugForGate, $corePages) && !isAdmin()) {
        $routePermissionService = new RoutePermissionService(Database::getConnection());
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        if (!$routePermissionService->canAccessLegacy((int) $_SESSION['id_GU'], $_GET, $_POST, $method)) {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => 'Accès refusé. Permissions insuffisantes.']);
                exit;
            }
            
            $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            
            $redirect = 'layout.php?page=access_denied';
            header('Location: ' . $redirect);
            exit;
        }
    }
}

include __DIR__ . '/menu.php';
include __DIR__ . '/../ressources/routes/gestionUtilisateurRoutes.php';
include __DIR__ . '/../ressources/routes/gestionRhRoutes.php';
include __DIR__ . '/../ressources/routes/gestionDashboardRoutes.php';
include __DIR__ . '/../ressources/routes/dashboardEnseignantRoutes.php';
include __DIR__ . '/../ressources/routes/gestionScolariteRoutes.php';
include __DIR__ . '/../ressources/routes/gestionNotesRoutes.php';
include __DIR__ . '/../ressources/routes/gestionCandidaturesRoutes.php';
include __DIR__ . '/../ressources/routes/listeEtudiantsRoutes.php';
include __DIR__ . '/../ressources/routes/dossierAcademiqueRoutes.php';
include __DIR__ . '/../ressources/routes/verificationRapportsRoutes.php';
include __DIR__ . '/../ressources/routes/gestionReclamationsScolariteRoutes.php';
include __DIR__ . '/../ressources/routes/evaluationDossiersRoutes.php';
include __DIR__ . '/../ressources/routes/gestionDossiersCandidaturesRoutes.php';
include __DIR__ . '/../ressources/routes/sauvegardeRestaurationRoutes.php';
include __DIR__ . '/../ressources/routes/notesResultatsRoutes.php';
include __DIR__ . '/../ressources/routes/archivesDossiersSoutenanceRoutes.php';
include __DIR__ . '/../ressources/routes/auditRoutes.php';
include __DIR__ . '/../ressources/routes/criteresEvaluationRoutes.php';
include __DIR__ . '/../ressources/routes/redactionCompteRenduRoutes.php';
include __DIR__ . '/../ressources/routes/archivesCompteRenduRoutes.php';
include __DIR__ . '/../ressources/routes/archiveHistoryRoutes.php';
include __DIR__ . '/../ressources/routes/archiveRoutes.php';

    $menuController = new MenuController();

    // NOUVEAU : Menu hiérarchique avec catégories
    $menuHierarchique = $menuController->genererMenuHierarchique($_SESSION['id_GU']);

    // Déterminer la page actuelle et le label
    $currentMenuSlug = isset($_GET['page']) ? $_GET['page'] : '';
    $currentPageLabel = '';

    // Canonicalisation désactivée pour les pages legacy migrées:
    // on garde l'URL courante pour éviter les doubles redirections et préserver la navigation AJAX.

    // Chercher le label dans le menu hiérarchique
    if (!empty($currentMenuSlug)) {
        foreach ($menuHierarchique as $item) {
            foreach ($item['fonctionnalites'] as $fonc) {
                // Extraire le paramètre page de l'URL
                $query = parse_url($fonc->url_fonctionnalite, PHP_URL_QUERY);
                if ($query) {
                    parse_str($query, $params);
                    if (isset($params['page']) && $params['page'] === $currentMenuSlug) {
                        $currentPageLabel = $fonc->label_fonctionnalite;
                        break 2;
                    }
                }
            }
        }
    }

    // Pages spéciales
    if (empty($currentPageLabel)) {
        $specialPages = [
                'archive_comptes_rendus' => 'Archives des comptes rendus',
                'redaction_compte_rendu' => 'Rédaction de compte rendu',
                'tableau_bord_enseignant' => 'Tableau de bord enseignant',
        ];
        if (isset($specialPages[$currentMenuSlug])) {
            $currentPageLabel = $specialPages[$currentMenuSlug];
        }
    }

    // Redirection si pas de page spécifiée
    if (empty($currentMenuSlug) && !empty($menuHierarchique)) {
        $firstCategorie = $menuHierarchique[0];
        if (!empty($firstCategorie['fonctionnalites'])) {
            $firstFonc = $firstCategorie['fonctionnalites'][0];
            parse_str(parse_url($firstFonc->url_fonctionnalite, PHP_URL_QUERY), $params);
            if (isset($params['page'])) {
                $currentMenuSlug = $params['page'];
                $currentPageLabel = $firstFonc->label_fonctionnalite;
                header('Location: layout.php?page=' . urlencode($currentMenuSlug));
                exit;
            }
        }
    }

    $menuView = new MenuView();
    $menuHTML = $menuView->afficherMenuHierarchique($menuHierarchique, $currentMenuSlug);

    // ANCIEN : Menu plat (commenté pour migration progressive)
    // $menuHTML = $menuView->afficherMenu($traitements, $currentMenuSlug);

    $currentAction = null;
    $contentFile = '';
    // IMPORTANT: chemin absolu (car layout peut être appelé via /public/app/layout.php)
    $partialsBasePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
    switch ($currentMenuSlug) {
        case 'parametres_generaux':
        case 'parametres_specifiques':
            // On charge le contrôleur manuellement pour être sûr qu'il s'exécute
            require_once __DIR__ . '/../app/controllers/ParametreController.php';
            $paramController = new ParametreController();

            if (isset($_GET['action'])) {
                $currentAction = $_GET['action'];

                // Mapping manuel des actions vers les méthodes du contrôleur
                // Cela remplace le routeur s'il fait défaut
                $actionsPédagogiques = [
                        'annees_academiques' => 'gestionAnnees',
                        'app_settings' => 'gestionReferentielSimple',
                        'genre' => 'gestionReferentielSimple',
                        'decisions_jury' => 'gestionReferentielSimple',
                        'etablissement_origine' => 'gestionReferentielSimple',
                        'session' => 'gestionReferentielSimple',
                        'mode_paiement' => 'gestionReferentielSimple',
                        'statut_reclamation' => 'gestionReferentielSimple',
                        'domaine' => 'gestionReferentielSimple',
                        'mentions' => 'gestionReferentielSimple',
                        'filieres' => 'gestionReferentielSimple',
                        'grades' => 'gestionGrade',
                        'fonctions' => 'gestionFonction',
                        'fonction_utilisateur' => 'gestionFonctionUtilisateur',
                        'specialites' => 'gestionSpecialite',
                        'niveaux_etude' => 'gestionNiveauEtude',
                        'ue' => 'gestionUe',
                        'ecue' => 'gestionEcue',
                        'statut_jury' => 'gestionStatutJury',
                        'qualite_jury' => 'gestionReferentielSimple',
                        'niveaux_approbation' => 'gestionNiveauApprobation',
                        'semestres' => 'gestionSemestre',
                        'niveaux_acces' => 'gestionNiveauAccesDonnees',
                        'bareme_critere' => 'gestionBaremeCritere',
                        'maitre_stage' => 'gestionReferentielSimple',
                        'type_enseignant' => 'gestionReferentielSimple',
                        'traitements' => 'gestionTraitement',
                        'entreprises' => 'gestionEntreprise',
                        'actions' => 'gestionAction',
                        'messages' => 'gestionMessagerie',
                        'gestion_attribution' => 'gestionAttribution',
                        'gestion_menus' => 'gestionMenus'
                ];

                if (array_key_exists($currentAction, $actionsPédagogiques)) {
                    $methode = $actionsPédagogiques[$currentAction];
                    $paramController->$methode();
                }

                // Le fichier de vue reste dans le dossier parametres_generaux
                $contentFile = $partialsBasePath . 'parametres_generaux' . DIRECTORY_SEPARATOR . $currentAction . '.php';
                $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
            } else {
                // Si pas d'action, on affiche le Hub correspondant
                if ($currentMenuSlug === 'parametres_generaux') {
                    $contentFile = $partialsBasePath . 'parametres_generaux_content.php';
                    $currentPageLabel = 'Paramètres Généraux';
                } else {
                    $contentFile = $partialsBasePath . 'parametres_specifiques_content.php';
                    $currentPageLabel = 'Paramètres Spécifiques';
                }
            }
            break;
        case 'gestion_reclamations':
            $allowedActions = ['soumettre_reclamation', 'suivi_historique_reclamation'];
            $ajaxActions = ['get_reclamation_details'];
            if (isset($_GET['action'])) {
                if (in_array($_GET['action'], $ajaxActions)) {
                    exit;
                } elseif (in_array($_GET['action'], $allowedActions)) {
                    $currentAction = $_GET['action'];
                    $contentFile = $partialsBasePath . 'gestion_reclamations/' . $currentAction . '.php';
                    $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
                } else {
                    $contentFile = $partialsBasePath . 'gestion_reclamations_content.php';
                    $currentPageLabel = 'Gestion des réclamations';
                }
            } else {
                $contentFile = $partialsBasePath . 'gestion_reclamations_content.php';
                $currentPageLabel = 'Gestion des réclamations';
            }
            break;
        case 'gestion_rapports':
            include __DIR__ . '/../ressources/routes/gestionRapportsRoutes.php';
            $allowedActions = ['creer_rapport', 'suivi_rapport', 'commentaire_rapport'];
            $ajaxActions = ['get_commentaires', 'get_rapport'];
            if (isset($_GET['action'])) {
                if (in_array($_GET['action'], $ajaxActions)) {
                    exit;
                } elseif (in_array($_GET['action'], $allowedActions)) {
                    $currentAction = $_GET['action'];
                    $contentFile = $partialsBasePath . 'gestion_rapports/' . $currentAction . '.php';
                    $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
                } else {
                    $contentFile = $partialsBasePath . 'gestion_rapports_content.php';
                    $currentPageLabel = 'Gestion des rapports';
                }
            } else {
                $contentFile = $partialsBasePath . 'gestion_rapports_content.php';
                $currentPageLabel = 'Gestion des rapports';
            }
            break;
        case 'candidature_soutenance':
            include __DIR__ . '/../ressources/routes/candidatureSoutenanceRoutes.php';
            $allowedActions = ['compte_rendu_etudiant'];
            if (isset($_GET['action']) && in_array($_GET['action'], $allowedActions)) {
                $currentAction = $_GET['action'];
                $contentFile = $partialsBasePath . 'candidature_soutenance/' . $currentAction . '.php';
                $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
            } else {
                $contentFile = $partialsBasePath . 'candidature_soutenance_content.php';
                $currentPageLabel = 'Candidater pour la soutenance';
            }
            break;
        case 'gestion_etudiants':
            include __DIR__ . '/../ressources/routes/gestionEtudiantRoutes.php';
            if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'imprimer_recu' && isset($_GET['id_inscription'])) {
                $id_inscription = (int) $_GET['id_inscription'];
                // Anti-IDOR: si étudiant, ne permettre que ses propres documents
                if (isset($_SESSION['id_GU']) && (int) $_SESSION['id_GU'] === 13) {
                    require_once __DIR__ . '/../app/models/Scolarite.php';
                    $scolarite = new Scolarite(Database::getConnection());
                    $inscription = $scolarite->getInscriptionById($id_inscription);
                    if (!$inscription || (int) $inscription['id_etudiant'] !== (int) ($_SESSION['num_etu'] ?? 0)) {
                        header('Location: layout.php?page=access_denied');
                        exit;
                    }
                }
                // RecuGeneratorService, PdfGeneratorService, Database, RecuDataUtils : autoloadés par Composer (PSR-4)
                $dbWrapper = new \App\Support\Database();
                $recuDataUtils = new \App\Utils\RecuDataUtils($dbWrapper);
                $pdfGen = new \App\Services\Document\PdfGeneratorService(
                    __DIR__ . '/../storage',
                    __DIR__ . '/../public/assets/img/logo.png'
                );
                $recuService = new \App\Services\Document\RecuGeneratorService($pdfGen, $recuDataUtils, $dbWrapper);
                // Chercher le versement lié à l'inscription
                $pdo = $dbWrapper->pdo();
                $stmtV = $pdo->prepare('SELECT id_versement FROM versement WHERE id_inscription = :id ORDER BY date_versement DESC LIMIT 1');
                $stmtV->execute([':id' => $id_inscription]);
                $versementRow = $stmtV->fetch(\PDO::FETCH_ASSOC);
                if ($versementRow) {
                    $result = $recuService->generate((int) $versementRow['id_versement'], (int) ($_SESSION['id_utilisateur'] ?? 0));
                    if ($result['success'] && !empty($result['path']) && file_exists($result['path'])) {
                        header('Content-Type: application/pdf');
                        header('Content-Disposition: inline; filename="recu_paiement_' . $id_inscription . '.pdf"');
                        header('Content-Length: ' . filesize($result['path']));
                        readfile($result['path']);
                        exit;
                    }
                }
                // Fallback : erreur silencieuse, on continue vers la page normale
                error_log('Erreur génération reçu inscription #' . $id_inscription . ': versement non trouvé ou échec PDF');
            }
            $allowedActions = ['ajouter_des_etudiants', 'inscrire_des_etudiants'];
            $actionLabels = [
                    'ajouter_des_etudiants' => 'Mise a jour etudiant',
                    'inscrire_des_etudiants' => 'Inscrire des etudiants'
            ];
            if (isset($_GET['action']) && in_array($_GET['action'], $allowedActions)) {
                $currentAction = $_GET['action'];
                $contentFile = $partialsBasePath . 'gestion_etudiants/' . $currentAction . '.php';
                $currentPageLabel = isset($actionLabels[$currentAction]) ? $actionLabels[$currentAction] : ucfirst(str_replace('_', ' ', $currentAction));
            } else {
                $contentFile = $partialsBasePath . 'gestion_etudiants_content.php';
                $currentPageLabel = 'Gestion des étudiants';
            }
            break;
        case 'liste_etudiants_resp':
            include __DIR__ . '/../ressources/routes/listeEtudiantsRoutes.php';
            $contentFile = $partialsBasePath . 'liste_etudiants_content.php';
            $currentPageLabel = 'Liste des Étudiants';
            break;
        case 'liste_etudiants_ens':
            include __DIR__ . '/../ressources/routes/listeEtudiantsRoutes.php';
            $contentFile = $partialsBasePath . 'liste_etudiants_content.php';
            $currentPageLabel = 'Liste des Étudiants';
            break;
        case 'rapport_a_valider':
            $contentFile = $partialsBasePath . 'rapport_a_valider_content.php';
            $currentPageLabel = 'Approuver Rapports';
            break;
        case 'reception_rapport_com':
            $contentFile = $partialsBasePath . 'rapport_a_valider_content.php';
            $currentPageLabel = 'Réception des rapports';
            break;
        case 'dashboard_commission':
            $contentFile = $partialsBasePath . 'dashboard_commission_content.php';
            $currentPageLabel = 'Tableau de bord commission';
            break;
        case 'gestion_candidatures':
            $contentFile = $partialsBasePath . 'gestion_candidatures_soutenance_content.php';
            $currentPageLabel = 'Gestion des Candidatures';
            break;
        case 'verification_candidatures':
            $contentFile = $partialsBasePath . 'verification_candidatures_soutenance_content.php';
            $currentPageLabel = 'Vérification des Candidatures';
            break;
        case 'evaluation_dossiers':
            $contentFile = $partialsBasePath . 'evaluations_dossiers_soutenance_content.php';
            $currentPageLabel = 'Évaluation des Dossiers';
            break;
        case 'programmation_soutenance':
            $contentFile = $partialsBasePath . 'Programation_soutenance_content.php';
            $currentPageLabel = 'Programmation Soutenance';
            break;
        case 'programation_soutenance':
            $contentFile = $partialsBasePath . 'Programation_soutenance_content.php';
            $currentPageLabel = 'Programmation Soutenance';
            break;
        case 'planification_soutenance':
            $contentFile = $partialsBasePath . 'plannificaiton_soutenance_content.php';
            $currentPageLabel = 'Planification Soutenance';
            break;
        case 'gestion_notes':
            $contentFile = $partialsBasePath . 'gestion_notes_evaluations_content.php';
            $currentPageLabel = 'Gestion des Notes';
            break;
        case 'gestion_scolarite':
            if (isset($_GET['action']) && $_GET['action'] === 'imprimer_recu' && isset($_GET['id'])) {
                $id_versement = (int) $_GET['id'];
                // Anti-IDOR: si étudiant, ne permettre que ses propres versements
                if (isset($_SESSION['id_GU']) && (int) $_SESSION['id_GU'] === 13) {
                    require_once __DIR__ . '/../app/models/Scolarite.php';
                    $scolarite = new Scolarite(Database::getConnection());
                    $versement = $scolarite->getVersementById($id_versement);
                    if (!$versement || $versement['id_etudiant'] !== ($_SESSION['num_etu'] ?? '')) {
                        header('Location: layout.php?page=access_denied');
                        exit;
                    }
                }
                // RecuGeneratorService, PdfGeneratorService, Database, RecuDataUtils : autoloadés par Composer (PSR-4)
                $dbWrapper = new \App\Support\Database();
                $recuDataUtils = new \App\Utils\RecuDataUtils($dbWrapper);
                $pdfGen = new \App\Services\Document\PdfGeneratorService(
                    __DIR__ . '/../storage',
                    __DIR__ . '/../public/assets/img/logo.png'
                );
                $recuService = new \App\Services\Document\RecuGeneratorService($pdfGen, $recuDataUtils, $dbWrapper);
                $result = $recuService->generate($id_versement, (int) ($_SESSION['id_utilisateur'] ?? 0));
                if ($result['success'] && !empty($result['path']) && file_exists($result['path'])) {
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: inline; filename="recu_paiement_' . $id_versement . '.pdf"');
                    header('Content-Length: ' . filesize($result['path']));
                    readfile($result['path']);
                    exit;
                }
                // Fallback : erreur silencieuse, on continue vers la page normale
                error_log('Erreur génération reçu versement #' . $id_versement . ': ' . ($result['error'] ?? 'inconnue'));
            }
            $contentFile = $partialsBasePath . 'gestion_scolarite_content.php';
            $currentPageLabel = 'Gestion de la scolarité';
            break;
        case 'gestion_notes_evaluations':
            if (isset($_GET['action']) && $_GET['action'] === 'imprimer_releve' && isset($_GET['student']) && isset($_GET['niveau'])) {
                $id_etudiant = $_GET['student'];
                $niveau = $_GET['niveau'];
                // Anti-IDOR: étudiant ne peut imprimer que son relevé
                if (isset($_SESSION['id_GU']) && (int) $_SESSION['id_GU'] === 13) {
                    if ($id_etudiant !== ($_SESSION['num_etu'] ?? '')) {
                        header('Location: layout.php?page=access_denied');
                        exit;
                    }
                }
                // NotesResultatsController et NotesResultatsService : autoloadés
                $notesService = new \CheckMaster\Services\NotesResultatsService(Database::getConnection());
                // Positionner num_etu pour le service
                $originalNumEtu = $_SESSION['num_etu'] ?? null;
                $_SESSION['num_etu'] = $id_etudiant;
                $notesService->generatePdf((string) $id_etudiant);
                // generatePdf() fait exit, mais au cas où:
                $_SESSION['num_etu'] = $originalNumEtu;
            }
            $contentFile = $partialsBasePath . 'gestion_notes_evaluations_content.php';
            $currentPageLabel = 'Gestion des notes et évaluations';
            break;
        case 'gestion_dossiers_candidatures':
            $contentFile = $partialsBasePath . 'gestion_dossiers_candidatures_content.php';
            $currentPageLabel = 'Gestion des dossiers de candidatures vérifiés';
            break;
        case 'evaluations_dossiers_soutenance':
            $contentFile = $partialsBasePath . 'evaluations_dossiers_soutenance_content.php';
            $currentPageLabel = 'Évaluation des Dossiers de Soutenance';
            break;

        case 'evaluation_soutenance':
            // Inclure les routes pour l'évaluation des soutenances
            include __DIR__ . '/../ressources/routes/evaluationSoutenanceRoutes.php';
            $contentFile = $partialsBasePath . 'evaluation_soutenance_content.php';
            $currentPageLabel = 'Évaluation des Soutenances';
            break;
        case 'consultation_cr_etud':
            $contentFile = $partialsBasePath . 'consultation_cr_etud_content.php';
            $currentPageLabel = 'Mon Compte Rendu';
            break;
        case 'redaction_compte_rendu':
            $contentFile = $partialsBasePath . 'redaction_compte_rendu_content.php';
            $currentPageLabel = 'Rédaction de compte rendu';
            break;
        case 'processus_validation':
            $contentFile = $partialsBasePath . 'processus_validation_content.php';
            $currentPageLabel = 'Suivi de validation';
            break;
        case 'edition_bulletin':
            $contentFile = $partialsBasePath . 'edition_bulletin_content.php';
            $currentPageLabel = 'Edition des bulletins';
            break;
        case 'archive_comptes_rendus':
            $contentFile = $partialsBasePath . 'redaction_compte_rendu/archives_compte_rendu_content.php';
            $currentPageLabel = 'Archives des comptes rendus';
            break;
        case 'hub_historique':
            $currentPageLabel = 'Historique et Archivage';
            $contentFile = $partialsBasePath . 'v2/archives/hub_historique.php';
            break;
        case 'archives_etudiants':
            $contentFile = $partialsBasePath . 'v2/archives/archives_etudiants.php';
            break;
        case 'fiche_etudiant_archive':
            $contentFile = $partialsBasePath . 'v2/archives/fiche_etudiant_archive.php';
            break;
        case 'parcours_etudiant':
            $contentFile = $partialsBasePath . 'v2/archives/parcours_etudiant.php';
            break;
        case 'archives_soutenances':
            $contentFile = $partialsBasePath . 'v2/archives/archives_soutenances.php';
            break;
        case 'fiche_soutenance':
            $contentFile = $partialsBasePath . 'v2/archives/fiche_soutenance.php';
            break;
        case 'archives_jurys':
            $contentFile = $partialsBasePath . 'v2/archives/archives_jurys.php';
            break;
        case 'archives_documents':
            $contentFile = $partialsBasePath . 'v2/archives/archives_documents.php';
            break;
        case 'archives_candidatures':
            $contentFile = $partialsBasePath . 'v2/archives/archives_candidatures.php';
            break;
        case 'archives_reclamations':
            $contentFile = $partialsBasePath . 'v2/archives/archives_reclamations.php';
            break;
        case 'admin_historique':
            $action = $_GET['action'] ?? 'index';
            $currentPageLabel = 'Historique et Archivage';
            if ($action === 'view_student') {
                $currentPageLabel = 'Fiche étudiant archive';
                $contentFile = $partialsBasePath . 'fiche_etudiant_archive.php';
            } elseif ($action === 'import_result') {
                $currentPageLabel = "Résultat de l'import";
                $contentFile = $partialsBasePath . 'import_result.php';
            } else {
                if (!class_exists('ArchiveHubController')) {
                    require_once __DIR__ . '/../app/controllers/ArchiveHubController.php';
                }
                $archiveHubController = new ArchiveHubController();
                $data = $archiveHubController->index();
                $contentFile = $partialsBasePath . 'v2/archives/hub_historique.php';
            }
            break;
        case 'repertoire_enseignant':
            // RepertoireEnseignantService : autoloadé par CheckMaster\ SPL
            $service = new \CheckMaster\Services\RepertoireEnseignantService(\Database::getConnection());
            $service->index();
            $contentFile = $partialsBasePath . 'repertoire_enseignant_content.php';
            $currentPageLabel = 'Repertoire documents';
            break;
        case 'maj_enseignant':
            $_GET['tab'] = 'enseignant';
            if (!class_exists('GestionRhController')) {
                require_once __DIR__ . '/../app/controllers/GestionRhController.php';
            }
            $gestionRhController = new GestionRhController();
            $gestionRhController->index();
            $contentFile = $partialsBasePath . 'gestion_rh_content.php';
            $currentPageLabel = 'Mise à jour enseignant';
            break;
        case 'maj_personnel_admin':
            $_GET['tab'] = 'pers_admin';
            if (!class_exists('GestionRhController')) {
                require_once __DIR__ . '/../app/controllers/GestionRhController.php';
            }
            $gestionRhController = new GestionRhController();
            $gestionRhController->index();
            $contentFile = $partialsBasePath . 'gestion_rh_content.php';
            $currentPageLabel = 'Mise à jour personnel administratif';
            break;
        default:
            $groupeUtilisateur = $_SESSION['lib_GU'];
            if ($groupeUtilisateur) {
                $contentFile = $partialsBasePath . $currentMenuSlug . '_content.php';
            }
            if (empty($contentFile) || !file_exists($contentFile)) {
                $contentFile = '';
            }
            break;
    }

    // Debug : log de la page demandée
    if (isset($_GET['page'])) {
        error_log('PAGE DEMANDEE : ' . $_GET['page']);
    }

    // 1. Paramètres GÉNÉRAUX
    $cardPGeneraux = [
            [
                    'title' => 'Années Académiques',
                    'description' => 'Gestion des périodes.',
                    'link' => '?page=parametres_generaux&action=annees_academiques',
                    'icon' => 'fa-calendar-alt'
            ],
            [
                    'title' => 'App Settings',
                    'description' => 'Configuration applicative.',
                    'link' => '?page=parametres_generaux&action=app_settings',
                    'icon' => 'fa-sliders'
            ],
            [
                    'title' => 'Niveaux d\'Étude',
                    'description' => 'L1, L2, M1, M2.',
                    'link' => '?page=parametres_generaux&action=niveaux_etude',
                    'icon' => 'fa-layer-group'
            ],
            [
                    'title' => 'Semestres',
                    'description' => 'S1, S2...',
                    'link' => '?page=parametres_generaux&action=semestres',
                    'icon' => 'fa-calendar-check'
            ],
            [
                    'title' => 'Genre',
                    'description' => 'Référentiel des genres.',
                    'link' => '?page=parametres_generaux&action=genre',
                    'icon' => 'fa-venus-mars'
            ],
            [
                    'title' => 'Décisions Jury',
                    'description' => 'Décisions de validation.',
                    'link' => '?page=parametres_generaux&action=decisions_jury',
                    'icon' => 'fa-gavel'
            ],
            [
                    'title' => 'Établissement Origine',
                    'description' => 'Écoles et universités.',
                    'link' => '?page=parametres_generaux&action=etablissement_origine',
                    'icon' => 'fa-school'
            ],
            [
                    'title' => 'Session',
                    'description' => 'Sessions académiques.',
                    'link' => '?page=parametres_generaux&action=session',
                    'icon' => 'fa-clock'
            ],
            [
                    'title' => 'Mode Paiement',
                    'description' => 'Moyens de règlement.',
                    'link' => '?page=parametres_generaux&action=mode_paiement',
                    'icon' => 'fa-credit-card'
            ],
            [
                    'title' => 'Statut Réclamation',
                    'description' => 'États des réclamations.',
                    'link' => '?page=parametres_generaux&action=statut_reclamation',
                    'icon' => 'fa-triangle-exclamation'
            ],
            [
                    'title' => 'Domaine',
                    'description' => 'Domaines de soutenance.',
                    'link' => '?page=parametres_generaux&action=domaine',
                    'icon' => 'fa-diagram-project'
            ],
            [
                    'title' => 'Mentions',
                    'description' => 'Mentions académiques.',
                    'link' => '?page=parametres_generaux&action=mentions',
                    'icon' => 'fa-award'
            ],
            [
                    'title' => 'Filières',
                    'description' => 'Référentiel des filières.',
                    'link' => '?page=parametres_generaux&action=filieres',
                    'icon' => 'fa-graduation-cap'
            ],
            [
                    'title' => 'Grades',
                    'description' => 'Grades enseignants.',
                    'link' => '?page=parametres_generaux&action=grades',
                    'icon' => 'fa-medal'
            ],
            [
                    'title' => 'Fonction',
                    'description' => 'Fonctions du personnel.',
                    'link' => '?page=parametres_generaux&action=fonctions',
                    'icon' => 'fa-briefcase'
            ],
            [
                    'title' => 'Fonctions Utilisateurs',
                    'description' => 'Groupes et types.',
                    'link' => '?page=parametres_generaux&action=fonction_utilisateur&tab=groupes',
                    'icon' => 'fa-users-cog'
            ],
            [
                    'title' => 'Niveaux d\'Accès',
                    'description' => 'Lecture/Écriture.',
                    'link' => '?page=parametres_generaux&action=niveaux_acces',
                    'icon' => 'fa-lock'
            ],
            [
                    'title' => 'Niveaux d\'Approbation',
                    'description' => 'Workflow de validation.',
                    'link' => '?page=parametres_generaux&action=niveaux_approbation',
                    'icon' => 'fa-sitemap'
            ],
            [
                    'title' => 'Qualité Jury',
                    'description' => 'Rôles et qualité du jury.',
                    'link' => '?page=parametres_generaux&action=qualite_jury',
                    'icon' => 'fa-user-shield'
            ]
    ];

    // 2. Paramètres SPÉCIFIQUES
    $cardPSpecifiques = [
            [
                    'title' => 'Critères',
                    'description' => 'Critères d\'évaluation.',
                    'link' => '?page=parametres_specifiques&action=criteres_evaluation',
                    'icon' => 'fa-list-check'
            ],
            [
                    'title' => 'Barème Critère',
                    'description' => 'Barèmes par année et critère.',
                    'link' => '?page=parametres_specifiques&action=bareme_critere',
                    'icon' => 'fa-scale-balanced'
            ],
            [
                    'title' => 'Salles',
                    'description' => 'Lieux de soutenance.',
                    'link' => '?page=parametres_specifiques&action=salles',
                    'icon' => 'fa-door-open'
            ],
            [
                    'title' => 'Entreprises',
                    'description' => 'Partenaires de stage.',
                    'link' => '?page=parametres_specifiques&action=entreprises',
                    'icon' => 'fa-building'
            ],
            [
                    'title' => 'Spécialités',
                    'description' => 'Options et spécialités.',
                    'link' => '?page=parametres_specifiques&action=specialites',
                    'icon' => 'fa-user-graduate'
            ],
            [
                    'title' => 'Maître de stage',
                    'description' => 'Référentiel des maîtres de stage.',
                    'link' => '?page=parametres_specifiques&action=maitre_stage',
                    'icon' => 'fa-user-tie'
            ],
            [
                    'title' => 'Type Enseignant',
                    'description' => 'Types d\'enseignants.',
                    'link' => '?page=parametres_specifiques&action=type_enseignant',
                    'icon' => 'fa-chalkboard-user'
            ],
            [
                    'title' => 'Gestion des Menus',
                    'description' => 'Structure de navigation.',
                    'link' => '?page=parametres_specifiques&action=gestion_menus',
                    'icon' => 'fa-sitemap'
            ],
            [
                    'title' => 'Habilitations',
                    'description' => 'Droits par groupe.',
                    'link' => '?page=parametres_specifiques&action=gestion_attribution',
                    'icon' => 'fa-key'
            ],
            [
                    'title' => 'Traitements',
                    'description' => 'Actions techniques.',
                    'link' => '?page=parametres_specifiques&action=traitements',
                    'icon' => 'fa-clipboard-list'
            ],
            [
                    'title' => 'Messages Système',
                    'description' => 'Libellés d\'erreurs.',
                    'link' => '?page=parametres_specifiques&action=messages',
                    'icon' => 'fa-envelope'
            ]
    ];
    $cardReclamation = [
            [
                    'title' => 'Soumettre une Réclamation',
                    'description' => 'Déposez une nouvelle réclamation en remplissant le formulaire dédié.',
                    'link' => '?page=gestion_reclamations&action=soumettre_reclamation',
                    'icon' => 'fa-solid fa-circle-exclamation ',
                    'title_link' => 'Soumettre',
                    'bg_color' => 'bg-primary',
                    'text_color' => 'text-white'
            ],
            [
                    'title' => 'Suivi et historique des réclamations',
                    'description' => 'Consultez l\'état actuel de vos réclamations en cours et accédez à l\'historique complet de vos réclamations passées.',
                    'link' => '?page=gestion_reclamations&action=suivi_historique_reclamation',
                    'icon' => 'fa-solid fa-eye ',
                    'title_link' => 'Suivi et historique',
                    'bg_color' => 'bg-primary-light',
                    'text_color' => 'text-white'
            ]
    ];

    $polarizedPages = [
            'gestion_etudiants',
            'gestion_scolarite',
            'gestion_notes_evaluations',
            'gestion_dossiers_candidatures',
            'gestion_reclamations_scolarite',
            'dashboard_commission',
            'rapport_a_valider',
            'reception_rapport_com',
            'evaluation_dossiers',
            'evaluations_dossiers_soutenance',
            'processus_validation',
            'redaction_compte_rendu',
            'programmation_soutenance',
            'programation_soutenance',
            'evaluation_soutenance',
            'edition_bulletin',
    ];
    $adminCrudPages = [
            'gestion_utilisateurs',
            'piste_audit',
            'sauvegarde_restauration',
            'admin_historique',
            'maj_enseignant',
            'maj_personnel_admin',
    ];
    $isAdminParamCrud = in_array((string) $currentMenuSlug, ['parametres_generaux', 'parametres_specifiques'], true)
            && !empty($_GET['action'])
            && ((string) $_GET['action'] !== 'gestion_menus');
    $isPolarizedPage = in_array((string) $currentMenuSlug, $polarizedPages, true)
            || in_array((string) $currentMenuSlug, $adminCrudPages, true)
            || $isAdminParamCrud;
    $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $publicPrefix = strpos($scriptPath, '/app/') !== false ? '../' : '';

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CheckMaster | <?php echo htmlspecialchars($currentPageLabel); ?></title>
    <link rel="stylesheet"
          href="<?php echo htmlspecialchars(function_exists('cm_asset') ? cm_asset('css/checkmaster-theme.css') : 'assets/css/checkmaster-theme.css', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet"
          href="<?php echo htmlspecialchars(function_exists('cm_asset') ? cm_asset('css/components.css') : 'assets/css/components.css', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet"
          href="<?php echo htmlspecialchars(function_exists('cm_asset') ? cm_asset('css/utilities.css') : 'assets/css/utilities.css', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet"
          href="<?php echo htmlspecialchars(function_exists('cm_asset') ? cm_asset('css/responsive.css') : 'assets/css/responsive.css', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($publicPrefix . 'image/logo_cm_sbg.png', ENT_QUOTES, 'UTF-8'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css" rel="stylesheet">
    <style>
        .cm-content-area .cm-form-group {
            min-width: 0;
        }

        .cm-content-area .cm-form-label {
            margin-bottom: 0.24rem;
            line-height: 1.25;
        }

        .cm-content-area .cm-form-group.cm-field--date,
        .cm-content-area .cm-form-group.cm-field--select,
        .cm-content-area .cm-form-group.cm-field--number {
            max-width: 14rem;
        }

        .cm-content-area .cm-form-group.cm-field--text {
            max-width: 18rem;
        }

        .cm-content-area .cm-form-group.cm-field--email,
        .cm-content-area .cm-form-group.cm-field--password {
            max-width: 20rem;
        }

        .cm-content-area .cm-form-group.cm-field--textarea,
        .cm-content-area .cm-form-group.cm-field--file,
        .cm-content-area .cm-form-group.cm-field--select-search {
            max-width: none;
        }

        .cm-content-area .cm-crud-wrapper form .cm-grid-2,
        .cm-content-area .cm-crud-wrapper form .cm-grid-3,
        .cm-content-area .cm-crud-wrapper form .cm-grid-4,
        .cm-content-area .cm-crud-wrapper form .cm-grid-5,
        .cm-content-area .cm-crud-wrapper form .cm-grid-auto,
        .cm-content-area .cm-pole-superieur form .cm-grid-2,
        .cm-content-area .cm-pole-superieur form .cm-grid-3,
        .cm-content-area .cm-pole-superieur form .cm-grid-4,
        .cm-content-area .cm-pole-superieur form .cm-grid-5,
        .cm-content-area .cm-pole-superieur form .cm-grid-auto {
            justify-content: start;
            gap: 0.55rem 0.75rem;
        }

        .cm-content-area form .cm-form-control:not(textarea),
        .cm-content-area form input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]):not(.cm-etu-input):not(.search-input),
        .cm-content-area form select:not(.cm-etu-select) {
            min-height: 32px;
            padding-top: 0.28rem;
            padding-bottom: 0.28rem;
            font-size: 0.84rem;
        }

        .cm-content-area form textarea.cm-form-control,
        .cm-content-area form textarea:not(.cm-etu-textarea):not(.verification-comment) {
            min-height: 70px;
            padding: 0.4rem 0.55rem;
            font-size: 0.84rem;
        }

        .cm-content-area form input[type="date"]:not(.cm-etu-input),
        .cm-content-area form select:not(.cm-etu-select) {
            max-width: 15rem;
        }

        .cm-content-area form input[type="number"]:not(.cm-etu-input) {
            max-width: 11rem;
        }

        /* Champs compacts pour l'historique/archives */
        .cm-content-area .cm-prd6-admin-screen form .cm-form-group,
        .cm-content-area .cm-archive-etudiants form .cm-form-group,
        .cm-content-area .cm-archive-soutenances form .cm-form-group,
        .cm-content-area .cm-archives-jurys form .cm-form-group,
        .cm-content-area .cm-archives-documents form .cm-form-group,
        .cm-content-area .cm-archives-candidatures form .cm-form-group,
        .cm-content-area .cm-archives-reclamations form .cm-form-group {
            width: auto;
        }

        .cm-content-area .cm-prd6-admin-screen form .cm-form-control:not(textarea),
        .cm-content-area .cm-prd6-admin-screen form input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]),
        .cm-content-area .cm-prd6-admin-screen form select,
        .cm-content-area .cm-archive-etudiants form .cm-form-control:not(textarea),
        .cm-content-area .cm-archive-etudiants form input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]),
        .cm-content-area .cm-archive-etudiants form select,
        .cm-content-area .cm-archive-soutenances form .cm-form-control:not(textarea),
        .cm-content-area .cm-archive-soutenances form input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]),
        .cm-content-area .cm-archive-soutenances form select {
            width: auto;
            min-width: 10ch;
            max-width: 24ch;
        }

        .cm-content-area .cm-prd6-admin-screen .cm-toolbar .cm-toolbar-field-lg {
            width: auto;
            min-width: 14ch;
            max-width: 24ch;
        }

        .cm-content-area .cm-prd6-admin-screen .cm-toolbar .cm-toolbar-field-md,
        .cm-content-area .cm-prd6-admin-screen .cm-toolbar .cm-toolbar-field-sm,
        .cm-content-area .cm-prd6-admin-screen .cm-toolbar .cm-toolbar-field-xs {
            width: auto;
            min-width: 8ch;
            max-width: 14ch;
        }

        .cm-content-area .cm-prd6-admin-screen form select[name*="niveau"],
        .cm-content-area .cm-prd6-admin-screen form input[name*="niveau"],
        .cm-content-area .cm-archive-etudiants form select[name*="niveau"],
        .cm-content-area .cm-archive-etudiants form input[name*="niveau"],
        .cm-content-area .cm-archive-soutenances form select[name*="niveau"],
        .cm-content-area .cm-archive-soutenances form input[name*="niveau"] {
            min-width: 7ch;
            max-width: 9ch;
        }

        /* ── ÉCRAN ÉVALUATION SOUTENANCE ── */

        /* Layout twin: grille à gauche, jury à droite */
        .cm-eval-twin-panel {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 12.5rem;
            gap: 0.85rem;
            align-items: start;
            margin-top: 0.45rem;
            margin-bottom: 0.45rem;
        }

        .cm-eval-criteria-col {
            min-width: 0;
        }

        /* Ligne minimale : Soutenance + Salle + Date/heure */
        .cm-eval-top-row {
            display: grid;
            grid-template-columns: minmax(14rem, 1fr) 8.2rem 10.5rem;
            gap: 0.45rem;
            align-items: end;
            margin-bottom: 0.2rem;
        }

        .cm-eval-top-row .cm-form-group {
            margin-bottom: 0;
            max-width: none;
        }

        .cm-eval-top-row .cm-form-label {
            font-size: 0.72rem;
            margin-bottom: 0.1rem;
        }

        .cm-eval-top-row .cm-form-control,
        .cm-eval-top-row select,
        .cm-eval-top-row input[type="text"] {
            min-height: 28px;
            height: 28px;
            padding: 0.18rem 0.4rem;
            font-size: 0.8rem;
        }

        .cm-eval-jury-col {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            width: 100%;
        }

        /* Encadré grille */
        .cm-eval-grille-box {
            border: 1px solid var(--cm-color-border, #d1d5db);
            border-radius: 6px;
            overflow: hidden;
            background: #fff;
        }

        .cm-eval-grille-header {
            background: var(--cm-color-bg-secondary, #f3f4f6);
            border-bottom: 1px solid var(--cm-color-border, #d1d5db);
            padding: 0.28rem 0.5rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: var(--cm-color-text, #111827);
        }

        /* Table critères */
        .cm-eval-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }

        .cm-eval-table thead tr {
            background: var(--cm-color-bg-secondary, #f9fafb);
        }

        .cm-eval-table th,
        .cm-eval-table td {
            padding: 0.22rem 0.4rem;
            border-bottom: 1px solid var(--cm-color-border, #e5e7eb);
            vertical-align: middle;
        }

        .cm-eval-table th {
            font-size: 0.68rem;
            font-weight: 600;
            color: var(--cm-color-text-muted, #6b7280);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }

        .cm-eval-th--critere { width: 60%; text-align: left; }
        .cm-eval-th--bareme  { width: 15%; text-align: center; }
        .cm-eval-th--note    { width: 25%; text-align: left; }

        .cm-eval-td--critere { color: var(--cm-color-text, #111827); }
        .cm-eval-td--bareme  { text-align: center; color: var(--cm-color-text-muted, #6b7280); }
        .cm-eval-td--note    { white-space: nowrap; }

        /* Input note dans la table */
        .cm-eval-note-input {
            width: 2rem;
            min-width: 2rem;
            padding: 0.06rem 0.2rem;
            font-size: 0.72rem;
            border: 1px solid var(--cm-color-border, #d1d5db);
            border-radius: 4px;
            text-align: right;
            background: #fff;
        }

        .cm-eval-note-input.is-invalid {
            border-color: var(--cm-color-danger, #ef4444);
            background: #fef2f2;
        }

        .cm-eval-note-max {
            margin-left: 0.2rem;
            font-size: 0.72rem;
            color: var(--cm-color-text-muted, #6b7280);
        }

        /* Ligne MOYENNE PONDÉRÉE (tfoot) */
        .cm-eval-moyenne-row td {
            background: var(--cm-color-bg-secondary, #f3f4f6);
            font-size: 0.82rem;
            border-top: 2px solid var(--cm-color-border, #d1d5db);
        }

        .cm-eval-moyenne-input {
            width: 3.1rem;
            padding: 0.12rem 0.25rem;
            font-size: 0.82rem;
            font-weight: 700;
            border: 1px solid var(--cm-color-border, #d1d5db);
            border-radius: 4px;
            text-align: right;
            background: var(--cm-color-bg-secondary, #f3f4f6);
            color: var(--cm-color-text, #111827);
        }

        /* Zone sous la table (décision + commentaire + boutons) */
        .cm-eval-below-table {
            padding: 0.4rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .cm-eval-decision-row .cm-form-group {
            margin-bottom: 0;
            max-width: 9rem;
        }

        .cm-eval-below-table .cm-form-group {
            margin-bottom: 0;
        }

        .cm-eval-below-table .cm-form-buttons {
            margin-top: 0.1rem;
            justify-content: flex-end;
        }

        /* Jury : champs ultra-compacts empilés */
        .cm-eval-section-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--cm-color-text-muted, #6b7280);
            margin: 0 0 0.2rem;
            padding-bottom: 0.12rem;
            border-bottom: 1px solid var(--cm-color-border, #e5e7eb);
        }

        .cm-eval-jury-col .cm-form-group {
            margin-bottom: 0;
            max-width: none;
        }

        .cm-eval-jury-col .cm-form-label {
            font-size: 0.66rem;
            margin-bottom: 0.1rem;
            color: var(--cm-color-text-muted, #6b7280);
        }

        .cm-eval-jury-col .cm-form-control,
        .cm-eval-jury-col input[type="text"] {
            width: 100%;
            font-size: 0.74rem;
            padding: 0.12rem 0.3rem;
            min-height: 24px;
            height: 24px;
            background: var(--cm-color-bg-secondary, #f9fafb);
        }

        @media (max-width: 860px) {
            .cm-eval-twin-panel {
                grid-template-columns: 1fr;
            }
            .cm-eval-top-row {
                grid-template-columns: repeat(2, minmax(10rem, 1fr));
            }
            .cm-eval-top-row .cm-form-group:first-child {
                grid-column: 1 / -1;
            }
            .cm-eval-jury-col {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.35rem 0.55rem;
            }
            .cm-eval-jury-col .cm-eval-section-label {
                grid-column: 1 / -1;
            }
        }

        /* Formulaire ajout étudiant : disposition et tailles */
        .cm-content-area .cm-ajout-etudiant-form {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        /* Ligne 1 : Niveau + Promotion alignés côte à côte, taille fixe */
        .cm-content-area .cm-ajout-etudiant-form .cm-grid-3 {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 0.75rem 1rem;
        }

        /* Ligne 2 : 4 champs égaux sur une rangée */
        .cm-content-area .cm-ajout-etudiant-form .cm-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem 1rem;
            align-items: end;
        }

        /* Taille confortable pour tous les champs */
        .cm-content-area .cm-ajout-etudiant-form .cm-form-control:not(textarea),
        .cm-content-area .cm-ajout-etudiant-form input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]),
        .cm-content-area .cm-ajout-etudiant-form select {
            width: 100%;
            min-height: 36px;
            font-size: 0.9rem;
            padding: 0.35rem 0.6rem;
        }

        /* Niveau : largeur calée sur son contenu */
        .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(select[name="id_niveau"]) {
            width: 9rem;
            flex-shrink: 0;
        }

        /* Promotion : un peu plus large */
        .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(select[name="promotion_etu"]) {
            width: 11rem;
            flex-shrink: 0;
        }

        /* Date naissance : largeur fixe lisible */
        .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(input[name="date_naiss_etu"]) {
            width: 13rem;
            flex-shrink: 0;
        }

        /* Genre : compact */
        .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(select[name="genre_etu"]) {
            width: 11rem;
            flex-shrink: 0;
        }

        /* Email : prend le reste */
        .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(input[name="email_etu"]) {
            flex: 1 1 16rem;
            min-width: 16rem;
        }

        /* Ligne 3 reprend le même modèle flex */
        .cm-content-area .cm-ajout-etudiant-form .cm-grid-3 .cm-form-group {
            margin-bottom: 0;
        }

        @media (max-width: 900px) {
            .cm-content-area .cm-ajout-etudiant-form .cm-grid-4 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 560px) {
            .cm-content-area .cm-ajout-etudiant-form .cm-grid-3,
            .cm-content-area .cm-ajout-etudiant-form .cm-grid-4 {
                flex-direction: column;
                grid-template-columns: 1fr;
            }
            .cm-content-area .cm-ajout-etudiant-form .cm-form-group,
            .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(select[name="id_niveau"]),
            .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(select[name="promotion_etu"]),
            .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(input[name="date_naiss_etu"]),
            .cm-content-area .cm-ajout-etudiant-form .cm-form-group:has(select[name="genre_etu"]) {
                width: 100%;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/l10n/fr.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.12.0/cdn.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

</head>

<body class="cm-app-body">
<aside class="cm-sidebar" id="cmSidebar">
    <div class="cm-sidebar__logo">
        <img src="<?php echo htmlspecialchars($publicPrefix . 'image/logo_cm_sbg.png', ENT_QUOTES, 'UTF-8'); ?>" alt="Logo CheckMaster" class="cm-sidebar__logo-img">
        <span class="cm-sidebar__logo-text">CHECK MASTER</span>
    </div>
    <nav class="cm-sidebar__nav">
        <?php echo $menuHTML; ?>
    </nav>
    <div class="cm-sidebar__footer">
        <form action="index.php?_path=/logout" method="POST" id="logoutForm" class="cm-w-full">
            <input type="hidden" name="csrf_token"
                   value="<?php echo htmlspecialchars(Csrf::token()); ?>">
            <button type="submit" form="logoutForm"
                    class="cm-btn cm-btn--sidebar-logout cm-w-full cm-flex-center cm-flex-gap-sm">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                <span class="cm-text-sm">Déconnexion</span>
            </button>
        </form>
    </div>
</aside>

<div class="cm-main-wrapper" id="mainWrapper">
    <header class="cm-navbar" id="cmNavbar">
        <div class="cm-navbar__left">
            <button class="cm-navbar__toggle" id="sidebarToggle" aria-label="Ouvrir/fermer le menu">
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
            <h1 class="cm-navbar__app-name"><?php echo htmlspecialchars($currentPageLabel); ?></h1>
        </div>
        <div class="cm-navbar__right">
            <div class="cm-navbar__user" id="userDropdown" tabindex="0" aria-label="Menu utilisateur">
                <div class="cm-navbar__user-info">
                    <span class="cm-navbar__username"><?php echo htmlspecialchars($_SESSION['nom_utilisateur']) ?></span>
                    <small class="cm-navbar__user-role cm-text-muted"><?php echo htmlspecialchars($_SESSION['lib_GU']) ?></small>
                </div>
                <i class="fas fa-chevron-down cm-navbar__chevron" aria-hidden="true"></i>
                <div class="cm-navbar__dropdown" id="userDropdownMenu">
                    <a href="?page=profil" class="cm-navbar__dropdown-item">
                        <i class="fas fa-user" aria-hidden="true"></i> Mon profil
                    </a>
                    <a href="#" class="cm-navbar__dropdown-item is-danger" onclick="document.getElementById('logoutForm').submit(); return false;">
                        <i class="fas fa-right-from-bracket" aria-hidden="true"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main id="cmLayoutMain"
          class="cm-content-area cm-layout-main <?php echo $isPolarizedPage ? 'cm-layout-main--locked' : 'cm-layout-main--scroll'; ?>"
          data-page="<?php echo htmlspecialchars((string) $currentMenuSlug, ENT_QUOTES, 'UTF-8'); ?>"
          data-action="<?php echo htmlspecialchars((string) ($currentAction ?? ($_GET['action'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
        <?php cm_component('ui/toast'); ?>
        <script src="<?php echo htmlspecialchars(function_exists('cm_asset') ? cm_asset('js/components/confirm-modal.js') : 'assets/js/components/confirm-modal.js', ENT_QUOTES, 'UTF-8'); ?>"></script>

        <?php // Les variables $globalAcademicYears, $currentGlobalYear, $currentGlobalYearId
              // sont calculées en haut du fichier (après database.php) et $_SESSION['global_annee_id'] est déjà défini. ?>

        
        <div class="cm-global-topbar cm-flex cm-flex-between cm-flex-center cm-px-lg cm-py-sm cm-bg-white" style="border-bottom: 1px solid var(--cm-border-color); margin-bottom: var(--cm-spacing-md);">
            <button type="button" class="cm-btn cm-btn--outline cm-btn--sm" onclick="history.back()">
                <i class="fas fa-arrow-left"></i> Retour
            </button>
            <div class="cm-flex cm-flex-center cm-flex-gap-sm">
                <label for="globalAnneeAcademique" class="cm-text-sm cm-text-semibold cm-m-0" style="margin-bottom:0;">Année Académique :</label>
                <?php
                $yearQueryBase = $_GET;
                unset(
                    $yearQueryBase['global_annee'],
                    $yearQueryBase['global_annee_id'],
                    $yearQueryBase['annee'],
                    $yearQueryBase['id_annee_acad'],
                    $yearQueryBase['bulletin_annee']
                );
                ?>
                <select id="globalAnneeAcademique" class="cm-form-control cm-select--sm" onchange="window.location.href=this.value" style="width: auto; padding: 2px 8px; font-size: 0.8rem; height: 30px;">
                    <?php $allYearsQuery = $yearQueryBase; $allYearsQuery['global_annee_id'] = AcademicYear::getAllQueryValue(); ?>
                    <option value="?<?= htmlspecialchars(http_build_query($allYearsQuery), ENT_QUOTES, 'UTF-8') ?>" <?= $currentGlobalYearIsAll ? 'selected' : '' ?>>
                        <?= htmlspecialchars(AcademicYear::getAllLabel(), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php foreach ($globalAcademicYears as $id => $label): ?>
                        <?php $yearQuery = $yearQueryBase; $yearQuery['global_annee_id'] = $id; ?>
                        <option value="?<?= htmlspecialchars(http_build_query($yearQuery), ENT_QUOTES, 'UTF-8') ?>" <?= (!$currentGlobalYearIsAll && (int) $currentGlobalYearId === (int) $id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($currentGlobalYearIsAll && $writableGlobalYear !== ''): ?>
                    <span class="cm-text-xs cm-text-muted" title="Affichage global sur toutes les années">
                        Affichage global. Écritures: <?= htmlspecialchars($writableGlobalYear, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php elseif ($currentGlobalYearId !== null && $activeGlobalYearId !== null && (int) $currentGlobalYearId !== (int) $activeGlobalYearId): ?>
                    <span class="cm-text-xs cm-text-muted" title="Consultation historique uniquement">
                        Consultation: <?= htmlspecialchars($currentGlobalYear, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php
        // Si $contentFile est explicitement null, le contenu a déjà été géré par un service
        if ($contentFile !== null) {
            if (!empty($contentFile) && file_exists($contentFile)) {
                include $contentFile;
            } else {
                echo "<div class='cm-card cm-p-lg'>";
                echo "<div class='cm-text-danger cm-text-semibold cm-mb-sm'>Erreur de chargement</div>";
                if (empty($contentFile)) {
                    echo "<div>Aucun fichier de contenu n'a été spécifié pour cette vue.</div>";
                } else {
                    echo "<div>Le fichier de contenu pour '" . htmlspecialchars($currentPageLabel) . "' est introuvable.</div>";
                }
                echo "</div>";
            }
        }
        ?>
    </main>
</div>

<?php cm_component('ui/confirm-modal'); ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggle  = document.getElementById('sidebarToggle');
        var sidebar = document.getElementById('cmSidebar');
        var wrapper = document.getElementById('mainWrapper');

        if (!toggle) return;

        toggle.addEventListener('click', function () {
            if (window.matchMedia('(max-width: 768px)').matches) {
                // Mobile : slide in/out via is-open
                if (sidebar) sidebar.classList.toggle('is-open');
            } else {
                // Desktop : collapse layout (hidden sidebar + full-width content)
                document.body.classList.toggle('is-collapsed');
            }
        });

        // Mobile : close sidebar when clicking outside
        document.addEventListener('click', function (e) {
            if (!window.matchMedia('(max-width: 768px)').matches) return;
            if (sidebar && toggle &&
                !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('is-open');
            }
        });
    });
</script>
<script>
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || typeof form.getAttribute !== 'function') {
            return;
        }

        var confirmMessage = form.getAttribute('data-cm-confirm-message');
        if (!confirmMessage) {
            return;
        }

        event.preventDefault();
        var confirmType = form.getAttribute('data-cm-confirm-type') || 'warning';
        var confirmText = form.getAttribute('data-cm-confirm-text') || 'Confirmer';

        if (window.CM && typeof window.CM.confirm === 'function') {
            window.CM.confirm({
                title: 'Confirmation',
                message: confirmMessage,
                type: confirmType,
                confirmText: confirmText,
            }).then(function (confirmed) {
                if (confirmed) {
                    form.submit();
                }
            });
            return;
        }

        if (window.confirm(confirmMessage)) {
            form.submit();
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var gridMap = {
            'cm-grid-2': 2,
            'cm-grid-3': 3,
            'cm-grid-4': 4,
            'cm-grid-5': 5,
            'cm-grid-6': 6
        };

        Object.keys(gridMap).forEach(function (legacyClass) {
            document.querySelectorAll('.' + legacyClass).forEach(function (grid) {
                grid.classList.add('cm-form-grid', 'cm-form-grid--' + gridMap[legacyClass]);
            });
        });

        document.querySelectorAll('input[type="date"]').forEach(function (field) {
            field.classList.add('cm-field--date');
        });

        document.querySelectorAll('input[type="email"]').forEach(function (field) {
            field.classList.add('cm-field--lg');
        });

        document.querySelectorAll('textarea').forEach(function (field) {
            field.classList.add('cm-field--full');
        });

        document.querySelectorAll('.cm-modal-overlay, .cm-etu-modal, .cm-etu-preview-modal, [id$="Modal"]').forEach(function (panel) {
            if (!panel || panel.id === 'cm-confirm-modal') {
                return;
            }
            if (!/^(DIV|SECTION|ASIDE|DIALOG)$/i.test(panel.tagName)) {
                return;
            }
            panel.classList.add('cm-legacy-panel');
            panel.classList.remove('cm-modal-overlay');
            panel.setAttribute('data-cm-legacy-modal', '1');
        });
    });
</script>
<script defer
        src="<?php echo htmlspecialchars(function_exists('cm_asset') ? cm_asset('js/app.js') : 'assets/js/app.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars($publicPrefix . 'js/suivi_reclamation.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars($publicPrefix . 'js/historique_reclamation.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>

</html>

<?php
/**
 * Injecte un champ CSRF dans les formulaires POST du legacy.
 * Objectif: sécuriser l'existant sans modifier toutes les vues d'un coup.
 */
function injectCsrfIntoPostForms(string $html): string
{
    $token = htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8');
    $field = '<input type="hidden" name="csrf_token" value="' . $token . '">';

    // Ajout juste après la balise <form ... method="post" ...>
    return preg_replace(
            '/(<form\\b[^>]*\\bmethod\\s*=\\s*(?:\"|\\\')?post(?:\"|\\\')?[^>]*>)/i',
            '$1' . $field,
            $html
    ) ?? $html;
}

$__out = ob_get_clean();
echo injectCsrfIntoPostForms($__out);
