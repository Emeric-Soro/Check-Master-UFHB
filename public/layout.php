<?php
session_start();
include '../app/config/database.php';
include '../app/controllers/AuthController.php';
include '../app/controllers/MenuController.php';
include 'menu.php';
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
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: page_connexion.php');
    exit;
} else {
    $menuController = new MenuController();
    $traitements = $menuController->genererMenu($_SESSION['id_GU']);
    $currentMenuSlug = '';
    $currentPageLabel = '';
    if (isset($_GET['page'])) {
        foreach ($traitements as $traitement) {
            if ($traitement['lib_traitement'] === $_GET['page']) {
                $currentMenuSlug = $traitement['lib_traitement'];
                $currentPageLabel = $traitement['label_traitement'];
                break;
            }
        }
        if (empty($currentMenuSlug)) {
            $specialPages = ['archive_comptes_rendus', 'redaction_compte_rendu'];
            if (in_array($_GET['page'], $specialPages)) {
                $currentMenuSlug = $_GET['page'];
                $currentPageLabel = ($_GET['page'] === 'archive_comptes_rendus') ? 'Archives des comptes rendus' : 'Rédaction de compte rendu';
            }
        }
    }
    if (empty($currentMenuSlug) && !empty($traitements)) {
        $currentMenuSlug = $traitements[0]['lib_traitement'];
        $currentPageLabel = $traitements[0]['label_traitement'];
        if (!isset($_GET['page'])) {
            header('Location: layout.php?page=' . urlencode($currentMenuSlug));
            exit;
        }
    }
    $menuView = new MenuView();
    $menuHTML = $menuView->afficherMenu($traitements, $currentMenuSlug);
    $currentAction = null;
    $contentFile = '';
    $partialsBasePath = '..' . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
    switch ($currentMenuSlug) {
        case 'parametres_generaux':
            include __DIR__ . '/../ressources/routes/parametreGenerauxRouteur.php';
            if (isset($_GET['action'])) {
                $allowedActions = [
                    'annees_academiques',
                    'grades',
                    'fonctions',
                    'fonction_utilisateur',
                    'specialites',
                    'niveaux_etude',
                    'ue',
                    'ecue',
                    'statut_jury',
                    'niveaux_approbation',
                    'semestres',
                    'niveaux_acces',
                    'traitements',
                    'entreprises',
                    'actions',
                    'fonctions_enseignants',
                    'messages',
                    'gestion_attribution',
                ];
                if (in_array($_GET['action'], $allowedActions)) {
                    $currentAction = $_GET['action'];
                    $contentFile = $partialsBasePath . 'parametres_generaux' . DIRECTORY_SEPARATOR . $currentAction . '.php';
                    $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
                }
            } else {
                $contentFile = $partialsBasePath . 'parametres_generaux_content.php';
                $currentPageLabel = 'Paramètres Généraux';
            }
            break;
        case 'gestion_reclamations':
            include __DIR__ . '/../ressources/routes/gestionReclamationsRouteur.php';
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
                $id_inscription = $_GET['id_inscription'];
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
        case 'gestion_scolarite':
            if (isset($_GET['action']) && $_GET['action'] === 'imprimer_recu' && isset($_GET['id'])) {
                require_once __DIR__ . '/../vendor/autoload.php';
                $id_versement = $_GET['id'];
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
                $id_etudiant = $_GET['student'];
                $niveau = $_GET['niveau'];
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
        case 'archive_comptes_rendus':
            $contentFile = $partialsBasePath . 'redaction_compte_rendu/archives_compte_rendu_content.php';
            $currentPageLabel = 'Archives des comptes rendus';
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
    if (isset($_GET['page'])) {
        error_log('PAGE DEMANDEE : ' . $_GET['page']);
        foreach ($traitements as $t) {
            error_log('TRAITEMENT AUTORISE : ' . $t['lib_traitement']);
        }
    }
    $cardPGeneraux = [
        [
            'title' => 'Années Académiques',
            'description' => 'Gérer les années académiques, les dates de début et de fin.',
            'link' => '?page=parametres_generaux&action=annees_academiques',
            'icon' => './images/date-du-calendrier.png'
        ],
        [
            'title' => 'Gestion des Grades',
            'description' => 'Définir et administrer les différents grades académiques.',
            'link' => '?page=parametres_generaux&action=grades',
            'icon' => './images/diplome.png'
        ],
        [
            'title' => 'Fonctions Utilisateurs',
            'description' => 'Configurer les rôles et fonctions des utilisateurs du système.',
            'link' => '?page=parametres_generaux&action=fonction_utilisateur&tab=groupes',
            'icon' => './images/equipe.png'
        ],
        [
            'title' => 'Spécialités des enseignants',
            'description' => 'Administrer les spécialités et filières proposées.',
            'link' => '?page=parametres_generaux&action=specialites',
            'icon' => './images/marche-de-niche.png'
        ],
        [
            'title' => 'Niveaux d\'Étude',
            'description' => 'Gérer les différents niveaux d\'étude (Licence, Master, etc.).',
            'link' => '?page=parametres_generaux&action=niveaux_etude',
            'icon' => './images/livre.png'
        ],
        [
            'title' => 'Unités d\'Enseignement (UE)',
            'description' => 'Définir les unités d\'enseignement et leurs crédits.',
            'link' => '?page=parametres_generaux&action=ue',
            'icon' => './images/livre-ouvert.png'
        ],
        [
            'title' => 'Éléments Constitutifs (ECUE)',
            'description' => 'Gérer les éléments constitutifs des unités d\'enseignement.',
            'link' => '?page=parametres_generaux&action=ecue',
            'icon' => './images/piece-de-puzzle.png'
        ],
        [
            'title' => 'Statuts du Jury',
            'description' => 'Configurer les différents statuts possibles pour les membres du jury.',
            'link' => '?page=parametres_generaux&action=statut_jury',
            'icon' => './images/droit.png'
        ],
        [
            'title' => 'Niveaux d\'Approbation',
            'description' => 'Définir les circuits et niveaux d\'approbation pour les documents.',
            'link' => '?page=parametres_generaux&action=niveaux_approbation',
            'icon' => './images/check.png'
        ],
        [
            'title' => 'Semestres',
            'description' => 'Définir les différents semestres et UE associées.',
            'link' => '?page=parametres_generaux&action=semestres',
            'icon' => './images/diplome.png'
        ],
        [
            'title' => 'Niveaux d\'Accès',
            'description' => 'Définir les différents niveaux d\'accès pour les utilisateurs',
            'link' => '?page=parametres_generaux&action=niveaux_acces',
            'icon' => './images/check.png',
        ],
        [
            'title' => 'Traitements',
            'description' => 'Définir les traitements à affecter aux différents utilisateurs.',
            'link' => '?page=parametres_generaux&action=traitements',
            'icon' => './images/bd.png'
        ],
        [
            'title' => 'Entreprises',
            'description' => 'Gérer les entreprises partenaires et leurs informations.',
            'link' => '?page=parametres_generaux&action=entreprises',
            'icon' => './images/valise.png'
        ],
        [
            'title' => 'Actions',
            'description' => 'Définir les actions possibles pour les utilisateurs dans le système.',
            'link' => '?page=parametres_generaux&action=actions',
            'icon' => './images/cible.png'
        ],
        [
            'title' => 'Fonctions',
            'description' => 'Définir les fonctions exercées par les enseignants dans le système.',
            'link' => '?page=parametres_generaux&action=fonctions',
            'icon' => './images/valise.png'
        ],
        [
            'title' => 'Messagerie',
            'description' => 'Définition des messages d\'erreur à afficher dans le système.',
            'link' => '?page=parametres_generaux&action=messages',
            'icon' => './images/enveloppe.png'
        ],
        [
            'title' => 'Gestion des Attributions',
            'description' => 'Gérer les attributions de traitement pour chacun des groupes utilisateurs dans le système.',
            'link' => '?page=parametres_generaux&action=gestion_attribution',
            'icon' => './images/attribution.png'
        ],
    ];
    $cardReclamation = [
        [
            'title' => 'Soumettre une Réclamation',
            'description' => 'Déposez une nouvelle réclamation en remplissant le formulaire dédié.',
            'link' => '?page=gestion_reclamations&action=soumettre_reclamation',
            'icon' => 'fa-solid fa-circle-exclamation ',
            'title_link' => 'Soumettre',
            'bg_color' => 'bg-accent-lighter',
            'text_color' => 'text-accent'
        ],
        [
            'title' => 'Suivi et historique des réclamations',
            'description' => 'Consultez l\'état actuel de vos réclamations en cours et accédez à l\'historique complet de vos réclamations passées.',
            'link' => '?page=gestion_reclamations&action=suivi_historique_reclamation',
            'icon' => 'fa-solid fa-eye ',
            'title_link' => 'Suivi et historique',
            'bg_color' => 'bg-warning/20',
            'text_color' => 'text-warning'
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
    <link rel="stylesheet" href="/assets/css/output.css">
    <link rel="shortcut icon" href="/assets/images/logo_cm_sbg.png" type="image/x-icon">
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
        .sidebar-logo { height: 56px; border-radius: 8px; }
        .topbar { height: 96px; padding: 0 1.5rem; background: var(--tw-bg-opacity, 1); }
        .card { background: white; border-radius: 12px; box-shadow: 0 8px 20px rgba(15, 20, 30, 0.06); }
        </style>
</head>
<body class="bg-base-200 font-poppins antialiased">
<div class="flex h-screen overflow-hidden">
    <div class="hidden md:flex md:flex-shrink-0">
        <div class="flex flex-col w-72 bg-primary text-white">
            <div class="flex items-center justify-center h-24 px-4">
                <div class="flex flex-col items-center text-center">
                    <img src="/assets/images/logo_cm_sbg.png" alt="Logo CheckMaster" class="sidebar-logo mb-2">
                    <span class="font-bold text-lg tracking-wide">CHECK MASTER</span>
                </div>
            </div>
            <div class="flex flex-col flex-grow px-4 py-4 overflow-y-auto">
                <div class="space-y-3 pb-3">
                    <?php echo $menuHTML; ?>
                </div>
                <div class="mt-auto px-4 py-3">
                    <form action="logout.php" method="POST" id="logoutForm" class="w-full">
                        <button type="submit" form="logoutForm" class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-lg bg-white/10 hover:bg-white/20 transition-colors">
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
                    <h1 class="text-2xl font-bold text-primary"><?php echo htmlspecialchars($currentPageLabel); ?></h1>
                </div>
            </div>
            <div class="flex items-center space-x-6">
                <div class="relative">
                    <i class="fas fa-bell text-primary/70 text-xl"></i>
                </div>
                <div class="w-px h-10 bg-base-300"></div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <span class="text-md font-bold text-primary block">Bienvenue, <?php echo htmlspecialchars($_SESSION['nom_utilisateur']) ?></span>
                        <span class="text-sm text-primary/60 block"><?php echo htmlspecialchars($_SESSION['lib_GU']) ?></span>
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
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const sidebar = document.querySelector('.hidden.md\\:flex.md\\:flex-shrink-0');
        if (mobileMenuButton && sidebar) {
            mobileMenuButton.addEventListener('click', function() {
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