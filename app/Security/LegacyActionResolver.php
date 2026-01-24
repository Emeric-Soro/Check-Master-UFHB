<?php

namespace CheckMaster\Security;

/**
 * Résout l'action CRUD requise pour l'ancien routing (?page=...&action=...).
 * Objectif: éviter la déduction “magique” fragile et permettre une table de règles.
 */
final class LegacyActionResolver
{
    /**
     * @return array{action:string, reason:string}
     */
    public static function resolve(string $page, array $get, array $post, string $method): array
    {
        $method = strtoupper($method);

        // Mapping déclaratif par page (prioritaire)
        $map = self::map();
        if (isset($map[$page])) {
            foreach ($map[$page] as $rule) {
                if (($rule['when'] ?? null) && is_callable($rule['when'])) {
                    if (($rule['when'])($get, $post, $method) === true) {
                        return ['action' => $rule['action'], 'reason' => $rule['reason'] ?? 'mapped'];
                    }
                }
            }
        }

        // Fallback (amélioré) pour ne pas laisser “voir” sur des POST évidents.
        if ($method === 'POST') {
            $keys = array_keys($post);
            $keyStr = implode(' ', $keys);

            // Création
            $createHints = ['btn_add', 'btn_create', 'btn_add_utilisateur', 'btn_add_multiple', 'submit_add', 'submit_create', 'submit_ajouter'];
            foreach ($createHints as $h) {
                if (strpos($keyStr, $h) !== false) {
                    return ['action' => 'creer', 'reason' => 'post_hint_create'];
                }
            }

            // Modification
            $editHints = ['btn_modifier', 'btn_update', 'btn_modifier_utilisateur', 'submit_edit', 'submit_update', 'submit_modifier'];
            foreach ($editHints as $h) {
                if (strpos($keyStr, $h) !== false) {
                    return ['action' => 'modifier', 'reason' => 'post_hint_edit'];
                }
            }

            // Suppression / bulk delete
            $deleteHints = ['btn_delete', 'submit_delete', 'submit_supprimer', 'submit_delete_multiple', 'delete'];
            foreach ($deleteHints as $h) {
                if (strpos($keyStr, $h) !== false) {
                    return ['action' => 'supprimer', 'reason' => 'post_hint_delete'];
                }
            }
        }

        // Paramètre action dans l'URL (optionnel)
        if (isset($get['action']) && is_string($get['action'])) {
            $a = strtolower($get['action']);
            if (strpos($a, 'ajouter') !== false || strpos($a, 'create') !== false || strpos($a, 'new') !== false) {
                return ['action' => 'creer', 'reason' => 'get_action_create'];
            }
            if (strpos($a, 'modifier') !== false || strpos($a, 'edit') !== false || strpos($a, 'update') !== false) {
                return ['action' => 'modifier', 'reason' => 'get_action_edit'];
            }
            if (strpos($a, 'supprimer') !== false || strpos($a, 'delete') !== false || strpos($a, 'remove') !== false) {
                return ['action' => 'supprimer', 'reason' => 'get_action_delete'];
            }
        }

        return ['action' => 'voir', 'reason' => 'default'];
    }

    /**
     * Mapping déclaratif minimal pour pages critiques.
     * @return array<string, array<int, array{action:string, when:callable, reason?:string}>>
     */
    private static function map(): array
    {
        return [
            // Gestion utilisateurs: POST doit être contrôlé finement
            'gestion_utilisateurs' => [
                [
                    'action' => 'creer',
                    'reason' => 'gestion_utilisateurs:create',
                    'when' => static function (array $get, array $post, string $method): bool {
                        return $method === 'POST' && (isset($post['btn_add_utilisateur']) || isset($post['btn_add_multiple']));
                    },
                ],
                [
                    'action' => 'modifier',
                    'reason' => 'gestion_utilisateurs:update',
                    'when' => static function (array $get, array $post, string $method): bool {
                        if ($method !== 'POST') return false;
                        return isset($post['btn_modifier_utilisateur'])
                            || isset($post['submit_enable_multiple'])
                            || isset($post['submit_disable_multiple']);
                    },
                ],
            ],
            // Sauvegarde/restauration: POST = modification (actions sensibles)
            'sauvegarde_restauration' => [
                [
                    'action' => 'modifier',
                    'reason' => 'sauvegarde_restauration:post',
                    'when' => static function (array $get, array $post, string $method): bool {
                        return $method === 'POST';
                    },
                ],
            ],
            // Rapports: création et modifications via actions/POST
            'gestion_rapports' => [
                [
                    'action' => 'creer',
                    'reason' => 'gestion_rapports:create_action',
                    'when' => static function (array $get, array $post, string $method): bool {
                        return isset($get['action']) && $get['action'] === 'creer_rapport';
                    },
                ],
                [
                    'action' => 'modifier',
                    'reason' => 'gestion_rapports:post',
                    'when' => static function (array $get, array $post, string $method): bool {
                        return $method === 'POST';
                    },
                ],
            ],
            // Candidature soutenance: soumission/MAJ via POST
            'candidature_soutenance' => [
                [
                    'action' => 'creer',
                    'reason' => 'candidature_soutenance:post',
                    'when' => static function (array $get, array $post, string $method): bool {
                        return $method === 'POST';
                    },
                ],
            ],
            // Réclamations: soumission via POST
            'gestion_reclamations' => [
                [
                    'action' => 'creer',
                    'reason' => 'gestion_reclamations:post',
                    'when' => static function (array $get, array $post, string $method): bool {
                        return $method === 'POST';
                    },
                ],
            ],
            // Notes & scolarité: modifications via POST
            'gestion_scolarite' => [
                [
                    'action' => 'modifier',
                    'reason' => 'gestion_scolarite:post',
                    'when' => static function (array $get, array $post, string $method): bool {
                        return $method === 'POST';
                    },
                ],
            ],
            'gestion_notes_evaluations' => [
                [
                    'action' => 'modifier',
                    'reason' => 'gestion_notes_evaluations:post',
                    'when' => static function (array $get, array $post, string $method): bool {
                        return $method === 'POST';
                    },
                ],
            ],
        ];
    }
}

