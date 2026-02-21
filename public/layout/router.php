<?php
    switch ($currentMenuSlug) {
        case 'parametres_generaux':
        case 'parametres_specifiques':
            require_once __DIR__ . '/../../app/controllers/ParametreController.php';
            $paramController = new ParametreController();

            if (isset($_GET['action'])) {
                $currentAction = $_GET['action'];

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

                $contentFile = $partialsBasePath . 'parametres_generaux/' . $currentAction . '.php';
                $currentPageLabel = ucfirst(str_replace('_', ' ', $currentAction));
            } else {
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
            $allowedActions = [
                'soumettre_reclamation',
                'suivi_historique_reclamation',
            ];
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
            include __DIR__ . '/../../ressources/routes/gestionRapportsRoutes.php';
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
            include __DIR__ . '/../../ressources/routes/candidatureSoutenanceRoutes.php';
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
            include __DIR__ . '/../../ressources/routes/gestionEtudiantRoutes.php';

            if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'imprimer_recu' && isset($_GET['id_inscription'])) {
                require_once __DIR__ . '/../../vendor/autoload.php';
                $id_inscription = (int)$_GET['id_inscription'];

                if (isset($_SESSION['id_GU']) && (int)$_SESSION['id_GU'] === 13) {
                    require_once __DIR__ . '/../../app/models/Scolarite.php';
                    $scolarite = new Scolarite(Database::getConnection());
                    $inscription = $scolarite->getInscriptionById($id_inscription);

                    if (!$inscription || (int)$inscription['id_etudiant'] !== (int)($_SESSION['num_etu'] ?? 0)) {
                        header('Location: layout.php?page=access_denied');
                        exit;
                    }
                }

                ob_start();
                include __DIR__ . '/../../ressources/views/gestion_etudiants/recu_inscription.php';
                $html = ob_get_clean();

                if (class_exists('\Dompdf\Options')) {
                    $options = new \Dompdf\Options();
                    $options->set('isRemoteEnabled', true);
                    $dompdf = new Dompdf\Dompdf($options);
                } else {
                    $dompdf = new Dompdf\Dompdf();
                }

                $publicPath = realpath(__DIR__ . '/../../');
                if ($publicPath) {
                    $dompdf->setBasePath($publicPath);
                }

                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $dompdf->stream("recu_paiement_" . $id_inscription . ".pdf", array("Attachment" => false));
                exit;
            }

            $allowedActions = [
                'ajouter_des_etudiants',
                'inscrire_des_etudiants',
            ];

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
        case 'liste_etudiants_ens':
            include __DIR__ . '/../../ressources/routes/listeEtudiantsRoutes.php';
            $contentFile = $partialsBasePath . 'liste_etudiants_content.php';
            $currentPageLabel = 'Liste des Étudiants';
            break;

        case 'gestion_scolarite':
            include __DIR__ . '/../../ressources/routes/gestionScolariteRoutes.php';

            // Impression reçu PDF
            if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'imprimer_recu' && isset($_GET['id'])) {
                require_once __DIR__ . '/../../vendor/autoload.php';
                require_once __DIR__ . '/../../app/models/Scolarite.php';

                $scolarite = new Scolarite(Database::getConnection());
                $id = (int)$_GET['id'];

                ob_start();
                include __DIR__ . '/../../ressources/views/recu_versement.php';
                $html = ob_get_clean();

                if (class_exists('\Dompdf\Options')) {
                    $options = new \Dompdf\Options();
                    $options->set('isRemoteEnabled', true);
                    $dompdf = new Dompdf\Dompdf($options);
                } else {
                    $dompdf = new Dompdf\Dompdf();
                }

                $publicPath = realpath(__DIR__ . '/../../');
                if ($publicPath) {
                    $dompdf->setBasePath($publicPath);
                }

                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $dompdf->stream("recu_paiement.pdf", array("Attachment" => false));
                exit;
            }

            $contentFile = $partialsBasePath . 'gestion_scolarite_content.php';
            $currentPageLabel = 'Gestion de la Scolarité';
            break;

        case 'gestion_notes':
        case 'gestion_notes_evaluations':
            include __DIR__ . '/../../ressources/routes/gestionNotesRoutes.php';

            // Impression relevé de notes PDF
            if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'imprimer_releve' && isset($_GET['id'])) {
                require_once __DIR__ . '/../../vendor/autoload.php';
                require_once __DIR__ . '/../../app/models/Scolarite.php';

                $scolarite = new Scolarite(Database::getConnection());

                ob_start();
                include __DIR__ . '/../../ressources/views/releve_notes.php';
                $html = ob_get_clean();

                if (class_exists('\Dompdf\Options')) {
                    $options = new \Dompdf\Options();
                    $options->set('isRemoteEnabled', true);
                    $dompdf = new Dompdf\Dompdf($options);
                } else {
                    $dompdf = new Dompdf\Dompdf();
                }

                $publicPath = realpath(__DIR__ . '/../../');
                if ($publicPath) {
                    $dompdf->setBasePath($publicPath);
                }

                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $dompdf->stream("releve_notes.pdf", array("Attachment" => false));
                exit;
            }

            $contentFile = $partialsBasePath . 'gestion_notes_evaluations_content.php';
            $currentPageLabel = 'Gestion des Notes et Évaluations';
            break;

        case 'rapport_a_valider':
            include __DIR__ . '/../../ressources/routes/verificationRapportsRoutes.php';
            $contentFile = $partialsBasePath . 'rapport_a_valider_content.php';
            $currentPageLabel = 'Rapports à valider';
            break;

        case 'gestion_candidatures':
            include __DIR__ . '/../../ressources/routes/gestionCandidaturesRoutes.php';
            $contentFile = $partialsBasePath . 'gestion_candidatures_soutenance_content.php';
            $currentPageLabel = 'Gestion des Candidatures';
            break;

        case 'verification_candidatures':
            include __DIR__ . '/../../ressources/routes/gestionCandidaturesRoutes.php';
            $contentFile = $partialsBasePath . 'verification_candidatures_soutenance_content.php';
            $currentPageLabel = 'Vérification des Candidatures';
            break;

        case 'evaluation_dossiers':
        case 'evaluations_dossiers_soutenance':
            include __DIR__ . '/../../ressources/routes/evaluationDossiersRoutes.php';
            $contentFile = $partialsBasePath . 'evaluations_dossiers_soutenance_content.php';
            $currentPageLabel = 'Évaluation des Dossiers';
            break;

        case 'programmation_soutenance':
            include __DIR__ . '/../../ressources/routes/dossierAcademiqueRoutes.php';
            $contentFile = $partialsBasePath . 'Programation_soutenance_content.php';
            $currentPageLabel = 'Programmation des Soutenances';
            break;

        case 'planification_soutenance':
            include __DIR__ . '/../../ressources/routes/dossierAcademiqueRoutes.php';
            $contentFile = $partialsBasePath . 'plannificaiton_soutenance_content.php';
            $currentPageLabel = 'Planification des Soutenances';
            break;

        case 'gestion_dossiers_candidatures':
            include __DIR__ . '/../../ressources/routes/gestionCandidaturesRoutes.php';
            $contentFile = $partialsBasePath . 'gestion_dossiers_candidatures_content.php';
            $currentPageLabel = 'Gestion des Dossiers de Candidatures';
            break;

        case 'evaluation_soutenance':
            include __DIR__ . '/../../ressources/routes/evaluationDossiersRoutes.php';
            $contentFile = $partialsBasePath . 'evaluation_soutenance_content.php';
            $currentPageLabel = 'Évaluation de Soutenance';
            break;

        case 'consultation_cr_etud':
            include __DIR__ . '/../../ressources/routes/archivesCompteRenduRoutes.php';
            $contentFile = $partialsBasePath . 'consultation_cr_etud_content.php';
            $currentPageLabel = 'Consultation des Comptes Rendus';
            break;

        case 'archive_comptes_rendus':
            include __DIR__ . '/../../ressources/routes/archivesCompteRenduRoutes.php';
            $contentFile = $partialsBasePath . 'redaction_compte_rendu/archives_compte_rendu_content.php';
            $currentPageLabel = 'Archives des Comptes Rendus';
            break;

        case 'redaction_compte_rendu':
            include __DIR__ . '/../../ressources/routes/redactionCompteRenduRoutes.php';
            $contentFile = $partialsBasePath . 'redaction_compte_rendu_content.php';
            $currentPageLabel = 'Rédaction de Compte Rendu';
            break;

        case 'admin_historique':
            if (isset($_GET['action'])) {
                $currentAction = $_GET['action'];
                switch ($currentAction) {
                    case 'view_student':
                        $contentFile = $partialsBasePath . 'dossiers_academiques_content.php';
                        $currentPageLabel = 'Dossiers Académiques';
                        break;
                    case 'import_result':
                        $contentFile = $partialsBasePath . 'notes_resultats_content.php';
                        $currentPageLabel = 'Import des Résultats';
                        break;
                    default:
                        $contentFile = $partialsBasePath . 'admin_historique.php';
                        $currentPageLabel = 'Historique Administratif';
                }
            } else {
                $contentFile = $partialsBasePath . 'admin_historique.php';
                $currentPageLabel = 'Historique Administratif';
            }
            break;

        case 'dashboard':
            include __DIR__ . '/../../ressources/routes/gestionDashboardRoutes.php';
            $contentFile = $partialsBasePath . 'dashboard.php';
            $currentPageLabel = 'Tableau de Bord';
            break;

        case 'dashboard_commission':
            include __DIR__ . '/../../ressources/routes/gestionDashboardRoutes.php';
            $contentFile = $partialsBasePath . 'dashboard.php';
            $currentPageLabel = 'Tableau de Bord — Commission';
            break;

        case 'dashboard_scolarite':
            include __DIR__ . '/../../ressources/routes/gestionDashboardRoutes.php';
            $contentFile = $partialsBasePath . 'dashboard.php';
            $currentPageLabel = 'Tableau de Bord — Scolarité';
            break;

        case 'dashboard_secretaire':
            include __DIR__ . '/../../ressources/routes/gestionDashboardRoutes.php';
            $contentFile = $partialsBasePath . 'dashboard.php';
            $currentPageLabel = 'Tableau de Bord';
            break;

        case 'dashboard_enseignant':
            include __DIR__ . '/../../ressources/routes/dashboardEnseignantRoutes.php';
            $contentFile = $partialsBasePath . 'dashboard.php';
            $currentPageLabel = 'Tableau de Bord Enseignant';
            break;

        case 'gestion_utilisateurs':
            include __DIR__ . '/../../ressources/routes/gestionUtilisateurRoutes.php';
            $contentFile = $partialsBasePath . 'gestion_utilisateurs_content.php';
            $currentPageLabel = 'Gestion des Utilisateurs';
            break;

        case 'notes_resultats':
            include __DIR__ . '/../../ressources/routes/notesResultatsRoutes.php';
            $contentFile = $partialsBasePath . 'notes_resultats_content.php';
            $currentPageLabel = 'Notes et Résultats';
            break;

        case 'dossiers_academiques':
            include __DIR__ . '/../../ressources/routes/dossierAcademiqueRoutes.php';
            $contentFile = $partialsBasePath . 'dossiers_academiques_content.php';
            $currentPageLabel = 'Dossiers Académiques';
            break;

        case 'sauvegarde_restauration':
            include __DIR__ . '/../../ressources/routes/sauvegardeRestaurationRoutes.php';
            $contentFile = $partialsBasePath . 'sauvegarde_restauration_content.php';
            $currentPageLabel = 'Sauvegarde et Restauration';
            break;

        case 'audit':
        case 'piste_audit':
            include __DIR__ . '/../../ressources/routes/auditRoutes.php';
            $contentFile = $partialsBasePath . 'piste_audit_content.php';
            $currentPageLabel = 'Piste d\'Audit';
            break;

        case 'archives_dossiers_soutenance':
            include __DIR__ . '/../../ressources/routes/archivesDossiersSoutenanceRoutes.php';
            $contentFile = $partialsBasePath . 'archives_dossiers_soutenance_content.php';
            $currentPageLabel = 'Archives des Dossiers de Soutenance';
            break;

        default:
            // Default case: charger dynamiquement le fichier de contenu
            $possibleFile = $partialsBasePath . $currentMenuSlug . '_content.php';
            if (file_exists($possibleFile)) {
                $contentFile = $possibleFile;
                $currentPageLabel = ucfirst(str_replace('_', ' ', $currentMenuSlug));
            } else {
                $contentFile = '';
                $currentPageLabel = 'Page introuvable';
            }
            break;
    }