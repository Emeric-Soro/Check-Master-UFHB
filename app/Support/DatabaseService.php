<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

/**
 * Accès BDD centralisé (Phase 2).
 *
 * Factorise le boilerplate PDO répété dans les services :
 *  - requêtes préparées typées (select / selectOne / execute / scalar) ;
 *  - transactions ;
 *  - pagination uniforme (count + limit/offset) ;
 *  - validation des noms de colonnes (anti-injection).
 *
 * Les services existants peuvent migrer progressivement vers cette classe
 * sans changer leur comportement métier.
 */
final class DatabaseService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Exécute une requête SELECT et retourne toutes les lignes.
     *
     * @param array<string,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->prepare($sql, $params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Exécute une requête SELECT et retourne la première ligne (ou null).
     *
     * @param array<string,mixed> $params
     * @return array<string,mixed>|null
     */
    public function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->prepare($sql, $params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * Exécute une requête et retourne la première colonne de la première ligne.
     *
     * @param array<string,mixed> $params
     */
    public function scalar(string $sql, array $params = [], mixed $default = null): mixed
    {
        $stmt = $this->prepare($sql, $params);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : $value;
    }

    /**
     * Exécute une requête de modification (INSERT/UPDATE/DELETE).
     *
     * @param array<string,mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->prepare($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Insère une ligne et retourne son ID.
     *
     * @param array<string,mixed> $data Colonnes => valeurs.
     */
    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $safe = array_map([$this, 'column'], $columns);
        $placeholders = array_map(static fn(string $c): string => ':' . $c, $columns);

        $sql = 'INSERT INTO ' . $this->table($table)
            . ' (' . implode(', ', $safe) . ')'
            . ' VALUES (' . implode(', ', $placeholders) . ')';

        $this->execute($sql, $data);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Met à jour une ligne (ou plusieurs) et retourne le nombre de lignes affectées.
     *
     * @param array<string,mixed> $data Colonnes => nouvelles valeurs.
     * @param array<string,mixed> $where Conditions (colonne => valeur).
     */
    public function update(string $table, array $data, array $where): int
    {
        if ($data === [] || $where === []) {
            return 0;
        }

        $sets = [];
        $params = [];
        foreach ($data as $column => $value) {
            $safe = $this->column($column);
            $sets[] = "$safe = :set_$column";
            $params[":set_$column"] = $value;
        }

        $conditions = [];
        foreach ($where as $column => $value) {
            $safe = $this->column($column);
            $conditions[] = "$safe = :where_$column";
            $params[":where_$column"] = $value;
        }

        $sql = 'UPDATE ' . $this->table($table)
            . ' SET ' . implode(', ', $sets)
            . ' WHERE ' . implode(' AND ', $conditions);

        return $this->execute($sql, $params);
    }

    /**
     * Supprime des lignes et retourne le nombre affecté.
     *
     * @param array<string,mixed> $where Conditions (colonne => valeur).
     */
    public function delete(string $table, array $where): int
    {
        if ($where === []) {
            return 0;
        }

        $conditions = [];
        $params = [];
        foreach ($where as $column => $value) {
            $safe = $this->column($column);
            $conditions[] = "$safe = :$column";
            $params[":$column"] = $value;
        }

        return $this->execute('DELETE FROM ' . $this->table($table) . ' WHERE ' . implode(' AND ', $conditions), $params);
    }

    /**
     * Pagination uniforme : retourne data + métadonnées.
     *
     * @param string $countSql  Requête COUNT (sans LIMIT).
     * @param string $selectSql Requête SELECT (sans LIMIT/OFFSET).
     * @param array<string,mixed> $params
     * @return array{data:array<int,array<string,mixed>>,total:int,page:int,per_page:int,last_page:int}
     */
    public function paginate(string $countSql, string $selectSql, array $params, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $total = (int) $this->scalar($countSql, $params, 0);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $data = [];
        if ($total > 0) {
            $data = $this->select($selectSql . ' LIMIT ' . $perPage . ' OFFSET ' . $offset, $params);
        }

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
    }

    // ─── Transactions ─────────────────────────────────────

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Exécute un callback dans une transaction (auto-commit/rollback).
     *
     * @template T
     * @param callable(self):T $callback
     * @return T
     * @throws \Throwable
     */
    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // ─── Helpers ───────────────────────────────────────────

    /**
     * Prépare et exécute une requête avec typage automatique des paramètres.
     *
     * @param array<string,mixed> $params
     */
    private function prepare(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();
        return $stmt;
    }

    /**
     * Valide un nom de colonne (anti-injection SQL).
     */
    public function column(string $name): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("Nom de colonne invalide : $name");
        }
        return $name;
    }

    /**
     * Valide un nom de table.
     */
    public function table(string $name): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("Nom de table invalide : $name");
        }
        return $name;
    }
}
