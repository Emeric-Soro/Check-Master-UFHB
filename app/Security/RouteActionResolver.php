<?php

declare(strict_types=1);

namespace CheckMaster\Security;

use PDO;

final class RouteActionResolver
{
    /**
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @return array<int,string>
     */
    public static function buildLegacyRoutePatterns(array $get, array $post, string $method): array
    {
        $page = isset($get['page']) && is_string($get['page']) ? trim((string) $get['page']) : '';
        if ($page === '') {
            return [''];
        }

        $components = ['page=' . $page];

        $token = self::resolveActionToken($get, $post, $method);
        $tab = isset($get['tab']) && is_string($get['tab']) && trim((string) $get['tab']) !== ''
            ? 'tab=' . trim((string) $get['tab'])
            : null;
        $extra = self::resolveExtraToken($get);

        $patterns = [];
        if ($token !== null && $tab !== null && $extra !== null) {
            $patterns[] = implode('&', array_merge($components, [$token, $tab, $extra]));
        }
        if ($token !== null && $tab !== null) {
            $patterns[] = implode('&', array_merge($components, [$token, $tab]));
        }
        if ($token !== null && $extra !== null) {
            $patterns[] = implode('&', array_merge($components, [$token, $extra]));
        }
        if ($token !== null) {
            $patterns[] = implode('&', array_merge($components, [$token]));
        }
        if ($tab !== null && $extra !== null) {
            $patterns[] = implode('&', array_merge($components, [$tab, $extra]));
        }
        if ($tab !== null) {
            $patterns[] = implode('&', array_merge($components, [$tab]));
        }
        if ($extra !== null) {
            $patterns[] = implode('&', array_merge($components, [$extra]));
        }

        $patterns[] = implode('&', $components);
        return array_values(array_unique($patterns));
    }

    /**
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @return array{
     *   action:'voir'|'creer'|'modifier'|'supprimer',
     *   reason:string,
     *   pattern:string,
     *   id_fonctionnalite:int|null,
     *   slug_permission:string,
     *   is_public:bool
     * }
     */
    public static function resolve(PDO $pdo, array $get, array $post, string $method): array
    {
        $method = strtoupper($method ?: 'GET');
        $patterns = self::buildLegacyRoutePatterns($get, $post, $method);

        foreach ($patterns as $pattern) {
            if ($pattern === '') {
                continue;
            }

            if (PermissionRegistry::isPublicRoute($pattern, $method)) {
                return [
                    'action' => 'voir',
                    'reason' => 'registry_public',
                    'pattern' => $pattern,
                    'id_fonctionnalite' => null,
                    'slug_permission' => '',
                    'is_public' => true,
                ];
            }

            $row = self::queryRouteAction($pdo, $pattern, $method);
            if ($row !== null) {
                $action = isset($row['action_crud']) && is_string($row['action_crud']) ? $row['action_crud'] : 'voir';
                /** @var 'voir'|'creer'|'modifier'|'supprimer' $action */
                return [
                    'action' => $action,
                    'reason' => 'db_mapping',
                    'pattern' => $pattern,
                    'id_fonctionnalite' => isset($row['id_fonctionnalite']) && $row['id_fonctionnalite'] !== null ? (int) $row['id_fonctionnalite'] : null,
                    'slug_permission' => isset($row['slug_permission']) && is_string($row['slug_permission']) ? $row['slug_permission'] : '',
                    'is_public' => (bool) ($row['is_public'] ?? false),
                ];
            }
        }

        $page = isset($get['page']) && is_string($get['page']) ? (string) $get['page'] : '';
        $fallback = LegacyActionResolver::resolve($page, $get, $post, $method);
        $feature = PermissionRegistry::findFeatureByIdentifier($page);
        $slug = isset($feature['slug']) ? (string) $feature['slug'] : '';

        return [
            'action' => $fallback['action'],
            'reason' => 'legacy_fallback',
            'pattern' => $patterns[0] ?? ('page=' . $page),
            'id_fonctionnalite' => null,
            'slug_permission' => $slug,
            'is_public' => false,
        ];
    }

    private static function queryRouteAction(PDO $pdo, string $pattern, string $method): ?array
    {
        try {
            $sql = "SELECT ra.action_crud,
                           ra.id_fonctionnalite,
                           ra.is_public,
                           f.slug_permission
                    FROM route_actions ra
                    LEFT JOIN fonctionnalites f ON f.id_fonctionnalite = ra.id_fonctionnalite
                    WHERE ra.actif = 1
                      AND ra.route_pattern = :pattern
                      AND (ra.http_method = :method OR ra.http_method = '*')
                    ORDER BY (ra.http_method = :method) DESC
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':pattern' => $pattern,
                ':method' => $method,
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (\Throwable $firstError) {
            try {
                $sql = "SELECT action_crud
                        FROM route_actions
                        WHERE actif = 1
                          AND route_pattern = :pattern
                          AND (http_method = :method OR http_method = '*')
                        ORDER BY (http_method = :method) DESC
                        LIMIT 1";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':pattern' => $pattern,
                    ':method' => $method,
                ]);

                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return is_array($row) ? $row : null;
            } catch (\Throwable $ignored) {
                return null;
            }
        }
    }

    /**
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     */
    private static function resolveActionToken(array $get, array $post, string $method): ?string
    {
        if (strtoupper($method) === 'POST') {
            if (isset($post['action']) && is_string($post['action']) && trim((string) $post['action']) !== '') {
                return 'action=' . trim((string) $post['action']);
            }

            if (isset($post['modalAction']) && is_string($post['modalAction']) && trim((string) $post['modalAction']) !== '') {
                return 'modalAction=' . trim((string) $post['modalAction']);
            }

            $markers = [
                'btn_add_utilisateur' => 'action=btn_add_utilisateur',
                'btn_add_multiple' => 'action=btn_add_multiple',
                'btn_modifier_utilisateur' => 'action=btn_modifier_utilisateur',
                'submit_enable_multiple' => 'action=submit_enable_multiple',
                'submit_disable_multiple' => 'action=submit_disable_multiple',
                'submit_send_access' => 'action=submit_send_access',
                'submit_add_etudiant' => 'action=submit_add_etudiant',
                'submit_modifier_etudiant' => 'action=submit_modifier_etudiant',
                'btn_add_enseignant' => 'action=btn_add_enseignant',
                'btn_modifier_enseignant' => 'action=btn_modifier_enseignant',
                'btn_add_pers_admin' => 'action=btn_add_pers_admin',
                'btn_modifier_pers_admin' => 'action=btn_modifier_pers_admin',
                'submit_delete_multiple' => 'action=submit_delete_multiple',
                'submit_import_upload' => 'action=submit_import_upload',
                'submit_import_commit' => 'action=submit_import_commit',
                'btn_enregistrer_notes' => 'action=btn_enregistrer_notes',
                'valider' => 'action=valider',
                'rejeter' => 'action=rejeter',
                'update_email' => 'action=update_email',
                'update_password' => 'action=update_password',
            ];

            foreach ($markers as $key => $token) {
                if (array_key_exists($key, $post)) {
                    return $token;
                }
            }

            if (isset($post['selected_ids'])) {
                return 'action=selected_ids';
            }

            if (isset($post['nouveau_statut'])) {
                return 'action=repondre_reclamation';
            }
        }

        if (isset($get['action']) && is_string($get['action']) && trim((string) $get['action']) !== '') {
            return 'action=' . trim((string) $get['action']);
        }

        if (isset($get['modalAction']) && is_string($get['modalAction']) && trim((string) $get['modalAction']) !== '') {
            return 'modalAction=' . trim((string) $get['modalAction']);
        }

        return null;
    }

    /**
     * @param array<string,mixed> $get
     */
    private static function resolveExtraToken(array $get): ?string
    {
        foreach (['detail', 'fichier', 'export', 'download', 'preview', 'examiner'] as $key) {
            if (array_key_exists($key, $get)) {
                return $key . '=1';
            }
        }

        return null;
    }
}
