<?php

namespace CheckMaster\Security;

use PDO;

final class RouteActionResolver
{
    /**
     * Construit une clé stable pour la route legacy.
     * Ex: page=parametres_generaux&action=annees_academiques
     */
    public static function buildLegacyRoutePattern(string $page, array $get): string
    {
        $pattern = 'page=' . $page;

        // Sous-écrans classiques
        if (isset($get['action']) && is_string($get['action']) && $get['action'] !== '') {
            $pattern .= '&action=' . $get['action'];
        }

        // Certains écrans segmentent par onglets (on l'inclut si présent)
        if (isset($get['tab']) && is_string($get['tab']) && $get['tab'] !== '') {
            $pattern .= '&tab=' . $get['tab'];
        }

        return $pattern;
    }

    /**
     * Résout l'action CRUD requise.
     * Priorité: DB (route_actions) -> fallback LegacyActionResolver.
     *
     * @return array{action:'voir'|'creer'|'modifier'|'supprimer',reason:string,pattern:string}
     */
    public static function resolve(PDO $pdo, string $page, array $get, array $post, string $method): array
    {
        $method = strtoupper($method ?: 'GET');
        $pattern = self::buildLegacyRoutePattern($page, $get);

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
            if (is_array($row) && isset($row['action_crud']) && is_string($row['action_crud'])) {
                /** @var 'voir'|'creer'|'modifier'|'supprimer' $action */
                $action = $row['action_crud'];
                return ['action' => $action, 'reason' => 'db_mapping', 'pattern' => $pattern];
            }
        } catch (\Throwable $e) {
            // Ne jamais casser l'app si la table n'existe pas encore.
        }

        $fallback = LegacyActionResolver::resolve($page, $get, $post, $method);
        return [
            'action' => $fallback['action'],
            'reason' => 'legacy_fallback',
            'pattern' => $pattern,
        ];
    }
}

