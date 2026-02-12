<?php
require_once __DIR__ . '/../app/Core/Autoload.php';

use CheckMaster\Core\Session;
use CheckMaster\Core\Bootstrap;

Bootstrap::init();
Session::start();

// Bufferiser la sortie pour injecter CSRF sur les formulaires legacy (migration progressive).
ob_start();

include __DIR__ . '/../app/config/database.php';
include __DIR__ . '/../app/controllers/AuthController.php';
include __DIR__ . '/../app/controllers/MenuController.php';
include __DIR__ . '/../app/middlewares/PermissionMiddleware.php';
include __DIR__ . '/../app/utils/permissions_helper.php';

use CheckMaster\Security\RoutePermissionService;

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
include __DIR__ . '/../ressources/routes/redactionCompteRenduRoutes.php';
include __DIR__ . '/../ressources/routes/archivesCompteRenduRoutes.php';
include __DIR__ . '/../ressources/routes/archiveHistoryRoutes.php';
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: page_connexion.php');
    exit;
} else {
    // Protection CSRF globale pour toutes les actions POST du legacy.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!\CheckMaster\Core\Csrf::validate($_POST['csrf_token'] ?? null)) {
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

    $menuController = new MenuController();

    // NOUVEAU : Menu hiérarchique avec catégories
    $menuHierarchique = $menuController->genererMenuHierarchique($_SESSION['id_GU']);

    // Déterminer la page actuelle et le label
    $currentMenuSlug = isset($_GET['page']) ? $_GET['page'] : '';
    $currentPageLabel = '';

    // Canonicalisation progressive: quelques écrans critiques passent par le Router.
    // Le flag _r=1 évite les boucles (Router -> layout -> Router ...).
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && empty($_GET['_r'])) {
        $action = $_GET['action'] ?? '';
        // gestion_attribution est maintenant géré par layout.php directement (via ParametreController)
        // donc on ne redirige plus vers index.php
        if ($currentMenuSlug === 'sauvegarde_restauration') {
            header('Location: index.php?_path=/admin/backups');
            exit;
        }
        if ($currentMenuSlug === 'gestion_utilisateurs') {
            header('Location: index.php?_path=/admin/users');
            exit;
        }
    }

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
            'redaction_compte_rendu' => 'Rédaction de compte rendu'
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
                    'grades' => 'gestionGrade',
                    'fonctions' => 'gestionFonction',
                    'fonction_utilisateur' => 'gestionFonctionUtilisateur',
                    'specialites' => 'gestionSpecialite',
                    'niveaux_etude' => 'gestionNiveauEtude',
                    'ue' => 'gestionUe',
                    'ecue' => 'gestionEcue',
                    'statut_jury' => 'gestionStatutJury',
                    'niveaux_approbation' => 'gestionNiveauApprobation',
                    'semestres' => 'gestionSemestre',
                    'niveaux_acces' => 'gestionNiveauAccesDonnees',
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
                require_once __DIR__ . '/../vendor/autoload.php';
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
                ob_start();
                include __DIR__ . '../../ressources/views/gestion_etudiants/recu_inscription.php';
                $html = ob_get_clean();
                if (class_exists('\Dompdf\Options')) {
                    $options = new \Dompdf\Options();
                    $options->set('isRemoteEnabled', true);
                    $dompdf = new Dompdf\Dompdf($options);
                } else {
                    $dompdf = new Dompdf\Dompdf();
                }
                $publicPath = realpath(__DIR__ . '/../');
                if ($publicPath) {
                    $dompdf->setBasePath($publicPath);
                }
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $dompdf->stream("recu_paiement_" . $id_inscription . ".pdf", array("Attachment" => false));
                exit;
            }
            $allowedActions = ['ajouter_des_etudiants', 'inscrire_des_etudiants'];
            if (isset($_GET['action']) && in_array($_GET['action'], $allowedActions)) {
                $currentAction = $_GET['action'];
                $contentFile = $partialsBasePath . 'gestion_etudiants/' . $currentAction . '.php';
                $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
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
                require_once __DIR__ . '/../vendor/autoload.php';
                $id_versement = (int) $_GET['id'];
                // Anti-IDOR: si étudiant, ne permettre que ses propres versements
                if (isset($_SESSION['id_GU']) && (int) $_SESSION['id_GU'] === 13) {
                    require_once __DIR__ . '/../app/models/Scolarite.php';
                    $scolarite = new Scolarite(Database::getConnection());
                    $versement = $scolarite->getVersementById($id_versement);
                    if (!$versement || (int) $versement['num_etu'] !== (int) ($_SESSION['num_etu'] ?? 0)) {
                        header('Location: layout.php?page=access_denied');
                        exit;
                    }
                }
                ob_start();
                include __DIR__ . '../../ressources/views/recu_versement.php';
                $html = ob_get_clean();
                if (class_exists('\Dompdf\Options')) {
                    $options = new \Dompdf\Options();
                    $options->set('isRemoteEnabled', true);
                    $dompdf = new Dompdf\Dompdf($options);
                } else {
                    $dompdf = new Dompdf\Dompdf();
                }
                $publicPath = realpath(__DIR__ . '/../');
                if ($publicPath) {
                    $dompdf->setBasePath($publicPath);
                }
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $dompdf->stream("recu_paiement_" . $id_versement . ".pdf", array("Attachment" => false));
                exit;
            }
            $contentFile = $partialsBasePath . 'gestion_scolarite_content.php';
            $currentPageLabel = 'Gestion de la scolarité';
            break;
        case 'gestion_notes_evaluations':
            if (isset($_GET['action']) && $_GET['action'] === 'imprimer_releve' && isset($_GET['student']) && isset($_GET['niveau'])) {
                require_once __DIR__ . '/../vendor/autoload.php';
                $id_etudiant = (int) $_GET['student'];
                $niveau = $_GET['niveau'];
                // Anti-IDOR: étudiant ne peut imprimer que son relevé
                if (isset($_SESSION['id_GU']) && (int) $_SESSION['id_GU'] === 13) {
                    if ($id_etudiant !== (int) ($_SESSION['num_etu'] ?? 0)) {
                        header('Location: layout.php?page=access_denied');
                        exit;
                    }
                }
                ob_start();
                include __DIR__ . '../../ressources/views/releve_notes.php';
                $html = ob_get_clean();
                $dompdf = new Dompdf\Dompdf();
                $dompdf->setBasePath(__DIR__ . '../public/images/');
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $dompdf->stream("releve_notes_" . $id_etudiant . "_" . $niveau . ".pdf", array("Attachment" => false));
                exit;
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
        case 'archive_comptes_rendus':
            $contentFile = $partialsBasePath . 'redaction_compte_rendu/archives_compte_rendu_content.php';
            $currentPageLabel = 'Archives des comptes rendus';
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
                $contentFile = $partialsBasePath . 'admin_historique.php';
            }
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

    // 1. Paramètres GÉNÉRAUX (Structurels)
    $cardPGeneraux = [
        [
            'title' => 'Années Académiques',
            'description' => 'Gestion des périodes.',
            'link' => '?page=parametres_generaux&action=annees_academiques',
            'icon' => './images/date-du-calendrier.png'
        ],
        [
            'title' => 'Niveaux d\'Étude',
            'description' => 'L1, L2, M1, M2...',
            'link' => '?page=parametres_generaux&action=niveaux_etude',
            'icon' => './images/livre.png'
        ],
        [
            'title' => 'Semestres',
            'description' => 'S1, S2...',
            'link' => '?page=parametres_generaux&action=semestres',
            'icon' => './images/diplome.png'
        ],
        [
            'title' => 'Spécialités',
            'description' => 'Filières.',
            'link' => '?page=parametres_generaux&action=specialites',
            'icon' => './images/marche-de-niche.png'
        ],
        [
            'title' => 'Grades',
            'description' => 'Grades enseignants.',
            'link' => '?page=parametres_generaux&action=grades',
            'icon' => './images/diplome.png'
        ],
        [
            'title' => 'Fonctions Personnel',
            'description' => 'Rôles administratifs.',
            'link' => '?page=parametres_generaux&action=fonctions',
            'icon' => './images/valise.png'
        ],
        [
            'title' => 'Fonctions Utilisateurs',
            'description' => 'Groupes et types.',
            'link' => '?page=parametres_generaux&action=fonction_utilisateur&tab=groupes',
            'icon' => './images/equipe.png'
        ],
        [
            'title' => 'Niveaux d\'Accès',
            'description' => 'Lecture/Écriture.',
            'link' => '?page=parametres_generaux&action=niveaux_acces',
            'icon' => './images/check.png'
        ],
        [
            'title' => 'Niveaux d\'Approbation',
            'description' => 'Workflow.',
            'link' => '?page=parametres_generaux&action=niveaux_approbation',
            'icon' => './images/check.png'
        ],
        [
            'title' => 'Statuts du Jury',
            'description' => 'Rôles jury.',
            'link' => '?page=parametres_generaux&action=statut_jury',
            'icon' => './images/droit.png'
        ]
    ];

    // 2. Paramètres SPÉCIFIQUES (Opérationnels + Menus)
    $cardPSpecifiques = [
        [
            'title' => 'Unités d\'Enseignement (UE)',
            'description' => 'Gestion des matières.',
            'link' => '?page=parametres_specifiques&action=ue',
            'icon' => './images/livre-ouvert.png'
        ],
        [
            'title' => 'Éléments Constitutifs (ECUE)',
            'description' => 'Détail des cours.',
            'link' => '?page=parametres_specifiques&action=ecue',
            'icon' => './images/piece-de-puzzle.png'
        ],
        [
            'title' => 'Critères Évaluation',
            'description' => 'Barèmes de soutenance.',
            'link' => '?page=parametres_specifiques&action=criteres_evaluation',
            'icon' => 'fas fa-list-ol'
        ],
        [
            'title' => 'Salles',
            'description' => 'Lieux de soutenance.',
            'link' => '?page=parametres_specifiques&action=salles',
            'icon' => './images/door-open.png'
        ],
        [
            'title' => 'Entreprises',
            'description' => 'Partenaires de stage.',
            'link' => '?page=parametres_specifiques&action=entreprises',
            'icon' => './images/valise.png'
        ],
        [
            'title' => 'Gestion des Menus',
            'description' => 'Structure de navigation.',
            'link' => '?page=parametres_specifiques&action=gestion_menus',
            'icon' => './images/bd.png'
        ],
        [
            'title' => 'Habilitations (Attributions)',
            'description' => 'Droits par groupe.',
            'link' => '?page=parametres_specifiques&action=gestion_attribution',
            'icon' => './images/attribution.png'
        ],
        [
            'title' => 'Traitements',
            'description' => 'Actions techniques.',
            'link' => '?page=parametres_specifiques&action=traitements',
            'icon' => './images/bd.png'
        ],
        [
            'title' => 'Messages Système',
            'description' => 'Libellés d\'erreurs.',
            'link' => '?page=parametres_specifiques&action=messages',
            'icon' => './images/enveloppe.png'
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
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CheckMaster | <?php echo htmlspecialchars($currentPageLabel); ?></title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="shortcut icon" href="image/logo_cm_sbg.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a5276',
                        'primary-light': '#2980b9',
                        'primary-lighter': '#3498db',
                        secondary: '#ff8c00',
                        accent: '#4caf50',
                        success: '#4caf50',
                        warning: '#f39c12',
                        danger: '#e74c3c',
                        'base-100': '#FFFFFF',
                        'base-200': '#F8FAFC',
                        'base-300': '#E2E8F0'
                    },
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                        'montserrat': ['Montserrat', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/l10n/fr.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.12.0/cdn.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .sidebar-logo {
            height: 56px;
            border-radius: 8px;
        }

        .topbar {
            height: 96px;
            padding: 0 1.5rem;
            background-color: #ffffff;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(15, 20, 30, 0.06);
        }
    </style>
</head>

<body class="bg-base-200 font-poppins antialiased">
    <div class="flex h-screen overflow-hidden">
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-72 bg-primary text-white">
                <div class="flex items-center justify-center h-24 px-4">
                    <div class="flex flex-col items-center text-center">
                        <img src="image/logo_cm_sbg.png" alt="Logo CheckMaster" class="sidebar-logo mb-2">
                        <span class="font-bold text-lg tracking-wide">CHECK MASTER</span>
                    </div>
                </div>
                <div class="flex flex-col flex-grow px-4 py-4 overflow-y-auto">
                    <div class="space-y-3 pb-3">
                        <?php echo $menuHTML; ?>
                    </div>
                    <div class="mt-auto px-4 py-3">
                        <form action="index.php?_path=/logout" method="POST" id="logoutForm" class="w-full">
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars(\CheckMaster\Core\Csrf::token()); ?>">
                            <button type="submit" form="logoutForm"
                                class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-lg transition-colors">
                                <i class="fas fa-sign-out-alt text-white/80"></i>
                                <span class="text-sm">Déconnexion</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col flex-1 overflow-hidden">
            <div class="flex items-center justify-between topbar bg-base-100 border-b border-base-300">
                <div class="flex items-center">
                    <button id="mobileMenuButton" class="md:hidden text-primary/70 focus:outline-none mr-4">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                    <div>
                        <h1 class="text-2xl font-bold text-primary"><?php echo htmlspecialchars($currentPageLabel); ?>
                        </h1>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="relative">
                        <i class="fas fa-bell text-primary/70 text-xl"></i>
                    </div>
                    <div class="w-px h-10 bg-base-300"></div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <span class="text-md font-bold text-primary block">Bienvenue,
                                <?php echo htmlspecialchars($_SESSION['nom_utilisateur']) ?></span>
                            <span
                                class="text-sm text-primary/60 block"><?php echo htmlspecialchars($_SESSION['lib_GU']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <main class="flex-1 p-6 overflow-y-auto">
                <?php
                if (!empty($contentFile) && file_exists($contentFile)) {
                    include $contentFile;
                } else {
                    echo "<div class='card p-6'>";
                    echo "<div class='text-danger font-semibold mb-2'>Erreur de chargement</div>";
                    if (empty($contentFile)) {
                        echo "<div>Aucun fichier de contenu n'a été spécifié pour cette vue.</div>";
                    } else {
                        echo "<div>Le fichier de contenu pour '" . htmlspecialchars($currentPageLabel) . "' est introuvable.</div>";
                    }
                    echo "</div>";
                }
                ?>
            </main>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mobileMenuButton = document.getElementById('mobileMenuButton');
            const sidebar = document.querySelector('.hidden.md\\:flex.md\\:flex-shrink-0');
            if (mobileMenuButton && sidebar) {
                mobileMenuButton.addEventListener('click', function () {
                    sidebar.classList.toggle('hidden');
                    sidebar.classList.toggle('absolute');
                    sidebar.classList.toggle('z-20');
                    sidebar.classList.toggle('h-full');
                });
            }
        });
    </script>
    <script src="./js/suivi_reclamation.js"></script>
    <script src="./js/historique_reclamation.js"></script>
</body>

</html>

<?php
/**
 * Injecte un champ CSRF dans les formulaires POST du legacy.
 * Objectif: sécuriser l'existant sans modifier toutes les vues d'un coup.
 */
function injectCsrfIntoPostForms(string $html): string
{
    $token = htmlspecialchars(\CheckMaster\Core\Csrf::token(), ENT_QUOTES, 'UTF-8');
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
?>