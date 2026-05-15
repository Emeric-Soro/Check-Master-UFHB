<?php

if (!function_exists('cm_audit_label_from_slug')) {
    function cm_audit_label_from_slug(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '') {
            return '-';
        }

        $labels = [
            'profil' => 'Mon profil',
            'profile' => 'Informations du profil',
            'password' => 'Mot de passe',
            'history' => 'Historique',
            'dashboard' => 'Tableau de bord',
            'dashboard_commission' => 'Tableau de bord commission',
            'dashboard_enseignant' => 'Tableau de bord enseignant',
            'dashboard_scolarite' => 'Tableau de bord scolarité',
            'gestion_utilisateurs' => 'Gestion des utilisateurs',
            'gestion_scolarite' => 'Gestion de la scolarité',
            'gestion_notes_evaluations' => 'Gestion des notes',
            'gestion_reclamations' => 'Gestion des réclamations',
            'parametres_generaux' => 'Paramètres généraux',
            'parametres_specifiques' => 'Paramètres spécifiques',
            'piste_audit' => "Piste d'audit",
            'sauvegarde_restauration' => 'Sauvegarde et restauration',
        ];

        if (isset($labels[$slug])) {
            return $labels[$slug];
        }

        $slug = str_replace(['_', '-'], ' ', $slug);
        return ucfirst($slug);
    }
}

if (!function_exists('cm_audit_label_from_action_token')) {
    function cm_audit_label_from_action_token(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return 'action utilisateur';
        }

        $map = [
            'update_email' => "mise à jour de l'adresse mail",
            'update_password' => 'mise à jour du mot de passe',
            'btn_add_utilisateur' => "ajout d'un utilisateur",
            'btn_add_multiple' => "ajout en masse d'utilisateurs",
            'btn_modifier_utilisateur' => "modification d'un utilisateur",
            'submit_enable_multiple' => "activation d'utilisateurs",
            'submit_disable_multiple' => "désactivation d'utilisateurs",
            'submit_send_access' => 'envoi des accès utilisateurs',
            'submit_add_etudiant' => "ajout d'étudiant",
            'submit_modifier_etudiant' => "modification d'étudiant",
            'btn_add_enseignant' => "ajout d'enseignant",
            'btn_modifier_enseignant' => "modification d'enseignant",
            'btn_add_pers_admin' => 'ajout du personnel administratif',
            'btn_modifier_pers_admin' => 'modification du personnel administratif',
            'submit_delete_multiple' => 'suppression en masse',
            'btn_enregistrer_notes' => 'enregistrement des notes',
            'selected_ids' => 'opération en masse',
            'valider' => 'validation',
            'rejeter' => 'rejet',
            'repondre_reclamation' => 'réponse à une réclamation',
        ];

        if (isset($map[$token])) {
            return $map[$token];
        }

        return str_replace('_', ' ', $token);
    }
}

if (!function_exists('cm_audit_parse_compact_request_action')) {
    /**
     * @return array{method:string,page:string,action:string,tab:string}|null
     */
    function cm_audit_parse_compact_request_action(string $rawAction): ?array
    {
        $parts = array_values(array_filter(array_map('trim', explode('|', $rawAction)), static function ($part) {
            return $part !== '';
        }));
        if (count($parts) < 2) {
            return null;
        }

        $method = strtoupper((string) ($parts[0] ?? ''));
        if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }

        $page = (string) ($parts[1] ?? '');
        $action = '';
        $tab = '';
        foreach (array_slice($parts, 2) as $part) {
            if (str_starts_with($part, 'act:')) {
                $action = trim(substr($part, 4));
                continue;
            }
            if (str_starts_with($part, 'tab:')) {
                $tab = trim(substr($part, 4));
            }
        }

        return [
            'method' => $method,
            'page' => $page,
            'action' => $action,
            'tab' => $tab,
        ];
    }
}

if (!function_exists('cm_audit_humanize_action')) {
    function cm_audit_humanize_action(array $log): string
    {
        $raw = trim((string) ($log['action'] ?? ''));
        if ($raw === '') {
            return '-';
        }

        $parsed = cm_audit_parse_compact_request_action($raw);
        if (is_array($parsed)) {
            $pageLabel = cm_audit_label_from_slug((string) ($parsed['page'] ?? ''));
            $actionToken = (string) ($parsed['action'] ?? '');
            $tabToken = (string) ($parsed['tab'] ?? '');

            if ((string) $parsed['method'] === 'GET') {
                $message = 'Consultation de "' . $pageLabel . '"';
                if ($actionToken !== '') {
                    $message .= ' (' . cm_audit_label_from_action_token($actionToken) . ')';
                }
            } else {
                if ($actionToken !== '') {
                    $message = 'Execution de ' . cm_audit_label_from_action_token($actionToken);
                } else {
                    $message = 'Action effectuée sur "' . $pageLabel . '"';
                }
            }

            if ($tabToken !== '') {
                $message .= ' - section "' . cm_audit_label_from_slug($tabToken) . '"';
            }

            return $message;
        }

        $legacyMap = [
            'Connexion' => 'Connexion au compte',
            'Déconnexion' => 'Déconnexion du compte',
            'Deconnexion' => 'Déconnexion du compte',
            'Création' => "Création d'un élément",
            'Creation' => "Création d'un élément",
            'Modification' => "Mise à jour d'un élément",
            'Suppression' => "Suppression d'un élément",
            'Dépôt' => "Dépôt d'un document",
            'Depot' => "Dépôt d'un document",
            'Validation' => "Validation d'un élément",
            'Rejet' => "Rejet d'un élément",
            'Evaluation' => 'Évaluation enregistrée',
            'Exportation' => 'Export de données',
            'Impression' => "Impression d'un document",
            'Archivage' => 'Archivage de données',
            'Consultation archive' => 'Consultation des archives',
            'Clôture année' => "Clôture de l'année académique",
            'Cloture année' => "Clôture de l'année académique",
            'Cloture annee' => "Clôture de l'année académique",
            'Téléchargement' => 'Téléchargement de données',
            'Telechargement' => 'Téléchargement de données',
            'Nettoyage' => "Nettoyage du journal d'audit",
        ];

        return $legacyMap[$raw] ?? $raw;
    }
}

if (!function_exists('cm_audit_humanize_context')) {
    function cm_audit_humanize_context(array $log): string
    {
        $raw = trim((string) ($log['nom_table'] ?? ''));
        if ($raw === '' || $raw === '-') {
            return '-';
        }

        $parts = array_values(array_filter(array_map('trim', explode('|', $raw)), static function ($part) {
            return $part !== '';
        }));
        if (count($parts) > 0) {
            $first = strtolower((string) $parts[0]);
            if ($first === 'ui' || $first === 'xhr') {
                $source = $first === 'xhr' ? 'Action en ligne' : 'Navigation classique';
                $page = '';
                $action = '';
                $tab = '';

                foreach (array_slice($parts, 1) as $part) {
                    if (str_starts_with($part, 'page=')) {
                        $page = trim(substr($part, 5));
                        continue;
                    }
                    if (str_starts_with($part, 'action=')) {
                        $action = trim(substr($part, 7));
                        continue;
                    }
                    if (str_starts_with($part, 'tab=')) {
                        $tab = trim(substr($part, 4));
                    }
                }

                $chunks = [$source];
                if ($page !== '') {
                    $chunks[] = 'Ecran: ' . cm_audit_label_from_slug($page);
                }
                if ($action !== '') {
                    $chunks[] = 'Opération: ' . cm_audit_label_from_action_token($action);
                }
                if ($tab !== '') {
                    $chunks[] = 'Section: ' . cm_audit_label_from_slug($tab);
                }

                return implode(' - ', $chunks);
            }
        }

        $contextMap = [
            'utilisateur' => 'Gestion des utilisateurs',
            'permission' => 'Controle des permissions',
            'pister' => "Journal d'audit",
            'base_de_donnees' => 'Base de données',
            'sauvegarde' => 'Sauvegardes',
            'exports_conformite' => "Centre des exports conformité",
            'archives_documents' => "Centre des archives documentaires",
            'annee_academique' => 'Gestion des années académiques',
            'deposer' => 'Dépôt des rapports',
            'valider' => 'Validation des rapports',
            'evaluer' => 'Evaluation des soutenances',
            'notes' => 'Gestion des notes',
        ];

        if (isset($contextMap[$raw])) {
            return $contextMap[$raw];
        }

        return cm_audit_label_from_slug($raw);
    }
}
