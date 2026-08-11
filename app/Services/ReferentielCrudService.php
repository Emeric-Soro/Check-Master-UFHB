<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\DatabaseService;
use PDO;

/**
 * CRUD générique des référentiels simples (Phase 2).
 *
 * Factorise les ~20 méthodes gestion* quasi identiques de ParametreService :
 * chaque référentiel est décrit par une configuration déclarative (table,
 * colonne id, champs, champs requis/bool/int/nullable, ordre) et le CRUD
 * (liste, ajout, modification, suppression multiple) est entièrement générique.
 *
 * La structure de sortie est identique à l'historique :
 *   ['item_a_modifier', 'listeReferentiel', 'messageErreur', 'messageSuccess']
 */
final class ReferentielCrudService
{
    public function __construct(
        private readonly DatabaseService $db,
        private readonly PDO $pdo,
        private readonly ?object $auditLog = null
    ) {
    }

    /**
     * Traite une requête CRUD sur un référentiel.
     *
     * @param array<string,mixed> $config Configuration déclarative.
     * @param array<string,mixed> $post
     * @param array<string,mixed> $get
     * @param string|int $userId
     * @return array{item_a_modifier:?array,listeReferentiel:array<int,array<string,mixed>>,messageErreur:string,messageSuccess:string}
     */
    public function handle(array $config, array $post, array $get, string|int $userId): array
    {
        $table = (string) $config['table'];
        $idColumn = (string) $config['id_column'];
        $idParam = (string) ($config['id_param'] ?? $idColumn);
        $idPostKey = (string) ($config['id_post_key'] ?? $idColumn);
        $fields = is_array($config['fields'] ?? null) ? $config['fields'] : [];
        $requiredFields = is_array($config['required_fields'] ?? null) ? $config['required_fields'] : [];
        $boolFields = is_array($config['bool_fields'] ?? null) ? $config['bool_fields'] : [];
        $intFields = is_array($config['int_fields'] ?? null) ? $config['int_fields'] : [];
        $nullableFields = is_array($config['nullable_fields'] ?? null) ? $config['nullable_fields'] : [];
        $allowManualId = !empty($config['allow_manual_id']);
        $allowIdUpdate = !empty($config['allow_id_update']);
        $orderBy = (string) ($config['order_by'] ?? $idColumn . ' DESC');
        $entity = (string) ($config['audit_entity'] ?? $table);
        $action = (string) ($get['action'] ?? '');
        $addButton = (string) ($config['add_button'] ?? ('btn_add_' . $action));
        $editButton = (string) ($config['edit_button'] ?? ('btn_modifier_' . $action));

        $itemAModifier = null;
        $messageErreur = '';
        $messageSuccess = '';

        if (!$this->tableExists($table)) {
            return [
                'item_a_modifier' => null,
                'listeReferentiel' => [],
                'messageErreur' => "La table '{$table}' n'existe pas dans la base de données.",
                'messageSuccess' => '',
            ];
        }

        // ── Suppression multiple ──
        if (isset($post['submit_delete_multiple']) && isset($post['selected_ids']) && is_array($post['selected_ids'])) {
            $success = true;
            foreach ($post['selected_ids'] as $id) {
                try {
                    $this->db->execute(
                        'DELETE FROM ' . $this->db->table($table) . ' WHERE ' . $this->db->column($idColumn) . ' = :id',
                        [':id' => $id]
                    );
                } catch (\Throwable) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $messageSuccess = 'Éléments supprimés avec succès.';
                $this->audit('logSuppression', $userId, $entity, 'Succès');
            } else {
                $messageErreur = 'Erreur lors de la suppression.';
                $this->audit('logSuppression', $userId, $entity, 'Erreur');
            }
        } elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (isset($post[$addButton]) || isset($post[$editButton]))) {
            $isUpdate = isset($post[$editButton]);
            $data = $this->normalizeFields($fields, $boolFields, $nullableFields, $intFields, $post);

            foreach ($requiredFields as $required) {
                if (!array_key_exists($required, $data) || $data[$required] === null || $data[$required] === '') {
                    $messageErreur = 'Veuillez renseigner tous les champs obligatoires.';
                    break;
                }
            }

            if ($messageErreur === '') {
                try {
                    if ($isUpdate) {
                        $this->handleUpdate($table, $idColumn, $idPostKey, $idColumn, $data, $allowIdUpdate, $post);
                        $messageSuccess = 'Élément modifié avec succès.';
                        $this->audit('logModification', $userId, $entity, 'Succès');
                    } else {
                        $this->handleInsert($table, $idColumn, $data, $allowManualId);
                        $messageSuccess = 'Élément ajouté avec succès.';
                        $this->audit('logCreation', $userId, $entity, 'Succès');
                    }
                } catch (\Throwable $e) {
                    $messageErreur = 'Erreur base de données: ' . $e->getMessage();
                    $this->audit('logModification', $userId, $entity, 'Erreur');
                }
            }
        }

        if (isset($get[$idParam]) && trim((string) $get[$idParam]) !== '') {
            $itemAModifier = $this->fetchRow($table, $idColumn, (string) $get[$idParam]);
        }

        return [
            'item_a_modifier' => $itemAModifier,
            'listeReferentiel' => $this->fetchList($table, $orderBy),
            'messageErreur' => $messageErreur,
            'messageSuccess' => $messageSuccess,
        ];
    }

    /**
     * @param array<string,mixed> $config
     */
    public function listOnly(array $config): array
    {
        $table = (string) $config['table'];
        $orderBy = (string) ($config['order_by'] ?? ((string) $config['id_column']) . ' DESC');
        if (!$this->tableExists($table)) {
            return [];
        }
        return $this->fetchList($table, $orderBy);
    }

    // ─── Helpers ───────────────────────────────────────────

    /**
     * @param list<string> $fields
     * @param list<string> $boolFields
     * @param list<string> $nullableFields
     * @param list<string> $intFields
     * @param array<string,mixed> $post
     * @return array<string,mixed>
     */
    private function normalizeFields(array $fields, array $boolFields, array $nullableFields, array $intFields, array $post): array
    {
        $data = [];
        foreach ($fields as $field) {
            if (in_array($field, $boolFields, true)) {
                $rawBool = $post[$field] ?? null;
                if ($rawBool === null) {
                    $data[$field] = 0;
                } else {
                    $boolValue = is_string($rawBool) ? strtolower(trim($rawBool)) : (string) $rawBool;
                    $data[$field] = in_array($boolValue, ['1', 'true', 'on', 'yes', 'oui'], true) ? 1 : 0;
                }
                continue;
            }

            $value = $post[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            if (in_array($field, $nullableFields, true) && $value === '') {
                $value = null;
            }
            if (in_array($field, $intFields, true) && $value !== null && $value !== '') {
                $value = (int) $value;
            }
            $data[$field] = $value;
        }
        return $data;
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $post
     */
    private function handleUpdate(string $table, string $idColumn, string $idPostKey, string $idColumnForWhere, array $data, bool $allowIdUpdate, array $post): void
    {
        $currentId = trim((string) ($post[$idPostKey] ?? ''));
        if ($currentId === '') {
            throw new \RuntimeException('Identifiant de modification invalide.');
        }

        $updateData = $data;
        if (!$allowIdUpdate) {
            unset($updateData[$idColumn]);
        }
        if (empty($updateData)) {
            throw new \RuntimeException('Aucune donnée à mettre à jour.');
        }

        $this->db->update($table, $updateData, [$idColumnForWhere => $currentId]);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function handleInsert(string $table, string $idColumn, array $data, bool $allowManualId): void
    {
        $insertData = $data;
        if (!$allowManualId) {
            unset($insertData[$idColumn]);
        }
        $insertData = array_filter($insertData, static fn($value) => $value !== null);

        if (empty($insertData)) {
            throw new \RuntimeException('Aucune donnée à enregistrer.');
        }

        $this->db->insert($table, $insertData);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function fetchRow(string $table, string $idColumn, string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM ' . $this->db->table($table) . ' WHERE ' . $this->db->column($idColumn) . ' = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchList(string $table, string $orderBy): array
    {
        $sql = 'SELECT * FROM ' . $this->db->table($table);
        if ($orderBy !== '') {
            // L'ordre peut contenir plusieurs colonnes (ex: 'libelle ASC, id DESC')
            $parts = array_map('trim', explode(',', $orderBy));
            $safeParts = [];
            foreach ($parts as $part) {
                if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)(\s+(ASC|DESC))?$/i', $part, $m)) {
                    $safeParts[] = $m[1] . (isset($m[3]) ? ' ' . strtoupper($m[3]) : '');
                }
            }
            if ($safeParts !== []) {
                $sql .= ' ORDER BY ' . implode(', ', $safeParts);
            }
        }
        return $this->db->select($sql);
    }

    private function tableExists(string $table): bool
    {
        try {
            return (int) $this->db->scalar(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t',
                [':t' => $table],
                0
            ) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Journalise via l'AuditLog (si fourni) sans casser si la méthode n'existe pas.
     */
    private function audit(string $method, string|int $userId, string $entity, string $status): void
    {
        if ($this->auditLog === null) {
            return;
        }
        if (!method_exists($this->auditLog, $method)) {
            return;
        }
        try {
            $this->auditLog->{$method}($userId, $entity, $status);
        } catch (\Throwable) {
            // L'audit ne doit jamais bloquer le CRUD
        }
    }
}
