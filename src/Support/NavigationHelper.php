<?php
/**
 * NavigationHelper - Menu building with RBAC filtering
 * Builds the full menu structure matching the screen descriptions.
 */
class NavigationHelper
{
    private $fullMenu = [
        ['label' => 'Tableau de bord', 'icon' => 'fa-tachometer-alt', 'url' => '?page=dashboard'],
        [
            'label' => 'Scolarité',
            'icon' => 'fa-graduation-cap',
            'url' => '#',
            'children' => [
                ['label' => 'Étudiants', 'url' => '?page=etudiants', 'permission' => 'etudiants.view', 'icon' => 'fa-users'],
                ['label' => 'Inscriptions', 'url' => '?page=inscriptions', 'permission' => 'inscriptions.view', 'icon' => 'fa-file-signature'],
                ['label' => 'Notes & Résultats', 'url' => '?page=notes', 'permission' => 'notes.view', 'icon' => 'fa-chart-line'],
                ['label' => 'Candidatures', 'url' => '?page=candidatures', 'permission' => 'candidatures.view', 'icon' => 'fa-file-alt'],
                ['label' => 'Réclamations', 'url' => '?page=reclamations', 'permission' => 'reclamations.view', 'icon' => 'fa-exclamation-circle'],
            ]
        ],
        [
            'label' => 'Commission & Soutenance',
            'icon' => 'fa-chalkboard-teacher',
            'url' => '#',
            'children' => [
                ['label' => 'Tableau de bord', 'url' => '?page=commission_dashboard', 'permission' => 'commission.view', 'icon' => 'fa-chart-pie'],
                ['label' => 'Rapports / Mémoires', 'url' => '?page=rapports', 'permission' => 'rapports.view', 'icon' => 'fa-file-pdf'],
                ['label' => 'Évaluation', 'url' => '?page=evaluation', 'permission' => 'soutenances.evaluer', 'icon' => 'fa-clipboard-check'],
                ['label' => 'Suivi de validation', 'url' => '?page=suivi_validation', 'permission' => 'commission.suivi', 'icon' => 'fa-tasks'],
                ['label' => 'Comptes rendus', 'url' => '?page=comptes_rendus', 'permission' => 'comptes-rendus.view', 'icon' => 'fa-pen-nib'],
            ]
        ],
        [
            'label' => 'Soutenances',
            'icon' => 'fa-award',
            'url' => '#',
            'children' => [
                ['label' => 'Planification', 'url' => '?page=soutenances_planification', 'permission' => 'soutenances.planifier', 'icon' => 'fa-calendar-alt'],
                ['label' => 'Programmation', 'url' => '?page=soutenances_programmation', 'permission' => 'soutenances.programmer', 'icon' => 'fa-calendar-check'],
                ['label' => 'Grille d\'évaluation', 'url' => '?page=grille_evaluation', 'permission' => 'soutenances.evaluer', 'icon' => 'fa-th'],
            ]
        ],
        [
            'label' => 'Espace Étudiant',
            'icon' => 'fa-user-graduate',
            'url' => '#',
            'children' => [
                ['label' => 'Mon dossier', 'url' => '?page=mon_dossier', 'permission' => 'etudiant.dossier', 'icon' => 'fa-folder-open'],
                ['label' => 'Mon rapport', 'url' => '?page=mon_rapport', 'permission' => 'etudiant.rapport', 'icon' => 'fa-file-upload'],
                ['label' => 'Mes réclamations', 'url' => '?page=mes_reclamations', 'permission' => 'etudiant.reclamations', 'icon' => 'fa-comment-dots'],
            ]
        ],
        [
            'label' => 'Espace Enseignant',
            'icon' => 'fa-chalkboard',
            'url' => '#',
            'children' => [
                ['label' => 'Tableau de bord', 'url' => '?page=enseignant_dashboard', 'permission' => 'enseignant.dashboard', 'icon' => 'fa-chart-bar'],
                ['label' => 'Mes étudiants', 'url' => '?page=mes_etudiants', 'permission' => 'enseignant.etudiants', 'icon' => 'fa-user-friends'],
                ['label' => 'Suivi de rapports', 'url' => '?page=suivi_rapports', 'permission' => 'enseignant.rapports', 'icon' => 'fa-book-reader'],
            ]
        ],
        [
            'label' => 'Administration',
            'icon' => 'fa-shield-alt',
            'url' => '#',
            'children' => [
                ['label' => 'Tableau de bord Admin', 'url' => '?page=admin_dashboard', 'permission' => 'admin.dashboard', 'icon' => 'fa-cogs'],
                ['label' => 'Année académique', 'url' => '?page=annee_academique', 'permission' => 'annee_acad.view', 'icon' => 'fa-calendar'],
                ['label' => 'Paramètres généraux', 'url' => '?page=parametres_generaux', 'permission' => 'parametres.view', 'icon' => 'fa-sliders-h'],
                ['label' => 'Paramètres spécifiques', 'url' => '?page=parametres_specifiques', 'permission' => 'parametres.specifiques', 'icon' => 'fa-cog'],
                ['label' => 'Utilisateurs', 'url' => '?page=utilisateurs', 'permission' => 'utilisateurs.view', 'icon' => 'fa-users-cog'],
                ['label' => 'Historique / Audit', 'url' => '?page=historique', 'permission' => 'historique.view', 'icon' => 'fa-history'],
                ['label' => 'Supervision système', 'url' => '?page=supervision', 'permission' => 'supervision.view', 'icon' => 'fa-server'],
                ['label' => 'Notifications', 'url' => '?page=notifications', 'permission' => 'notifications.view', 'icon' => 'fa-bell'],
                ['label' => 'Personnel admin', 'url' => '?page=personnel_admin', 'permission' => 'rh.view', 'icon' => 'fa-id-card-alt'],
            ]
        ],
        [
            'label' => 'Archives',
            'icon' => 'fa-archive',
            'url' => '#',
            'children' => [
                ['label' => 'Dossiers', 'url' => '?page=archives_dossiers', 'permission' => 'archives.view', 'icon' => 'fa-folder'],
                ['label' => 'Comptes rendus', 'url' => '?page=archives_comptes_rendus', 'permission' => 'archives.view', 'icon' => 'fa-file-archive'],
            ]
        ],
    ];

    /**
     * Returns filtered menu based on user permissions
     */
    public function buildMenu(array $user = null): array
    {
        if (!$user || !isset($user['permissions'])) {
            return $this->filterByPermission($this->fullMenu, []);
        }

        return $this->filterByPermission($this->fullMenu, $user['permissions']);
    }

    /**
     * Recursive filter by permission
     */
    public function filterByPermission(array $menu, array $userPermissions): array
    {
        $filtered = [];
        foreach ($menu as $item) {
            if (isset($item['permission']) && !in_array($item['permission'], $userPermissions)) {
                continue;
            }

            if (isset($item['children'])) {
                $item['children'] = $this->filterByPermission($item['children'], $userPermissions);
                if (empty($item['children']) && (!isset($item['url']) || $item['url'] === '#')) {
                    continue;
                }
            }

            $filtered[] = $item;
        }
        return $filtered;
    }

    /**
     * Returns menu with active states set
     */
    public function buildMenuWithActive(array $user = null, string $currentPage = ''): array
    {
        $menu = $this->buildMenu($user);
        return $this->setActiveStates($menu, $currentPage);
    }

    /**
     * Sets is_active flags on menu items matching the current page
     */
    private function setActiveStates(array $menu, string $currentPage): array
    {
        foreach ($menu as &$item) {
            $item['is_active'] = false;

            if (isset($item['children'])) {
                $item['children'] = $this->setActiveStates($item['children'], $currentPage);
                // Parent is active if any child is active
                foreach ($item['children'] as $child) {
                    if ($child['is_active'] ?? false) {
                        $item['is_active'] = true;
                        break;
                    }
                }
            }

            if (isset($item['url']) && $item['url'] !== '#') {
                // Match by ?page= parameter
                $itemPage = '';
                if (preg_match('/[?&]page=([^&]+)/', $item['url'], $m)) {
                    $itemPage = $m[1];
                }
                if ($itemPage && $itemPage === $currentPage) {
                    $item['is_active'] = true;
                }
            }
        }
        return $menu;
    }

    /**
     * Returns breadcrumb items
     */
    public function buildBreadcrumb(string $currentPage, array $menus = null): array
    {
        $menus = $menus ?? $this->fullMenu;
        $breadcrumbs = [['label' => 'Accueil', 'url' => '?page=dashboard']];

        foreach ($menus as $item) {
            if (isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    $childPage = '';
                    if (preg_match('/[?&]page=([^&]+)/', $child['url'] ?? '', $m)) {
                        $childPage = $m[1];
                    }
                    if ($childPage === $currentPage) {
                        $breadcrumbs[] = ['label' => $item['label'], 'url' => $item['url'] ?? '#'];
                        $breadcrumbs[] = ['label' => $child['label'], 'url' => $child['url']];
                        return $breadcrumbs;
                    }
                }
            }
            else {
                $itemPage = '';
                if (preg_match('/[?&]page=([^&]+)/', $item['url'] ?? '', $m)) {
                    $itemPage = $m[1];
                }
                if ($itemPage === $currentPage) {
                    $breadcrumbs[] = ['label' => $item['label'], 'url' => $item['url']];
                    return $breadcrumbs;
                }
            }
        }

        return $breadcrumbs;
    }

    /**
     * Returns tab items for a section
     */
    public function buildTabs(string $section, string $activeTab = null): array
    {
        $tabs = [];
        foreach ($this->fullMenu as $item) {
            $itemSection = strtolower(str_replace(' ', '_', $item['label'] ?? ''));
            if ($itemSection === $section && isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    $childPage = '';
                    if (preg_match('/[?&]page=([^&]+)/', $child['url'] ?? '', $m)) {
                        $childPage = $m[1];
                    }
                    $tabs[] = [
                        'label' => $child['label'],
                        'url' => $child['url'],
                        'icon' => $child['icon'] ?? '',
                        'active' => ($childPage === $activeTab)
                    ];
                }
            }
        }
        return $tabs;
    }

    /**
     * Get the full menu (unfiltered)
     */
    public function getFullMenu(): array
    {
        return $this->fullMenu;
    }
}
