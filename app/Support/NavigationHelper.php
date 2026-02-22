<?php

namespace CheckMaster\Support;

/**
 * NavigationHelper - Menu dynamique RBAC, breadcrumb et onglets.
 */
class NavigationHelper
{
    /**
     * Construit le menu filtré par permissions.
     *
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function buildMenu(array $user): array
    {
        // Logique simplifiée : en production, cela viendrait de la base de données via PermissionService
        $idGU = (int) ($user['id_GU'] ?? 0);

        $menu = [
            [
                'label'     => 'Tableau de bord',
                'icon'      => 'fas fa-tachometer-alt',
                'url'       => '/dashboard',
                'is_active' => true,
            ]
        ];

        // Exemple RBAC
        if (in_array($idGU, [1, 16])) { // Admin
            $menu[] = [
                'label'    => 'Administration',
                'icon'     => 'fas fa-tools',
                'url'      => '#',
                'children' => [
                    ['label' => 'Utilisateurs', 'url' => '/admin/users'],
                    ['label' => 'Permissions',  'url' => '/admin/permissions'],
                    ['label' => 'Sauvegardes',  'url' => '/admin/backups'],
                ]
            ];
        }

        return $menu;
    }

    /**
     * Construit le fil d'Ariane.
     *
     * @param array<int, array<string, mixed>> $menus
     * @return array<int, array<string, mixed>>
     */
    public function buildBreadcrumb(string $currentPath, array $menus): array
    {
        // Logique de matching récursif pour trouver le chemin
        $breadcrumb = [['label' => 'Accueil', 'url' => '/']];

        foreach ($menus as $item) {
            if ($item['url'] === $currentPath) {
                $breadcrumb[] = ['label' => $item['label'], 'url' => $item['url']];
                break;
            }
            if (!empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    if ($child['url'] === $currentPath) {
                        $breadcrumb[] = ['label' => $item['label'],  'url' => $item['url']];
                        $breadcrumb[] = ['label' => $child['label'], 'url' => $child['url']];
                        break 2;
                    }
                }
            }
        }

        return $breadcrumb;
    }

    /**
     * Construit les onglets pour un groupe donné.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildTabs(string $group, string $activeTab): array
    {
        $configs = [
            'parametres_generaux' => [
                ['key' => 'annees',    'label' => 'Années Académiques', 'url' => '?action=annees_academiques'],
                ['key' => 'grades',    'label' => 'Grades',             'url' => '?action=grades'],
                ['key' => 'fonctions', 'label' => 'Fonctions',          'url' => '?action=fonctions'],
            ]
        ];

        $tabs = $configs[$group] ?? [];
        foreach ($tabs as &$tab) {
            $tab['is_active'] = ($tab['key'] === $activeTab);
        }
        return $tabs;
    }

    /**
     * Construit les étapes d'un wizard.
     *
     * @param array<int, array<string, mixed>> $steps
     * @return array<int, array<string, mixed>>
     */
    public function buildSteps(array $steps, int $currentStep = 0): array
    {
        foreach ($steps as $index => &$step) {
            if ($index < $currentStep) {
                $step['status'] = 'completed';
            } elseif ($index === $currentStep) {
                $step['status'] = 'current';
            } else {
                $step['status'] = 'pending';
            }
        }
        return $steps;
    }
}
