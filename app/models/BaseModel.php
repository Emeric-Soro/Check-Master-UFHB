<?php

declare(strict_types=1);

namespace CheckMaster\Models;

use PDO;
use PDOException;

/**
 * Classe abstraite pour tous les models.
 * Fournit : PDO typé, CRUD générique, gestion d'erreurs, pagination.
 * Chaque model enfant DOIT définir TABLE et PRIMARY_KEY.
 */
abstract class BaseModel
{
    /** Nom de la table SQL (à définir dans chaque enfant) */
    protected const TABLE = '';

    /** Clé primaire (à définir dans chaque enfant) */
    protected const PRIMARY_KEY = 'id';

    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ─── CRUD générique ───────────────────────────────────

    /**
     * Trouver par ID.
     */
    public function findById(int|string $id): ?array
    {
        $sql = "SELECT * FROM " . static::TABLE . " WHERE " . static::PRIMARY_KEY . " = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }

    /**
     * Trouver tous les enregistrements.
     */
    public function findAll(string $orderBy = '', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM " . static::TABLE;
        if ($orderBy !== '') {
            $sql .= " ORDER BY " . $this->validateColumnName($orderBy);
        }
        if ($limit > 0) {
            $sql .= " LIMIT " . $limit;
            if ($offset > 0) {
                $sql .= " OFFSET " . $offset;
            }
        }
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trouver par critères.
     */
    public function findBy(array $criteria, string $orderBy = '', int $limit = 0): array
    {
        if (empty($criteria)) {
            return $this->findAll($orderBy, $limit);
        }

        $conditions = [];
        $params = [];
        foreach ($criteria as $column => $value) {
            $safeColumn = $this->validateColumnName($column);
            $conditions[] = "$safeColumn = :$column";
            $params[":$column"] = $value;
        }

        $sql = "SELECT * FROM " . static::TABLE . " WHERE " . implode(' AND ', $conditions);
        if ($orderBy !== '') {
            $sql .= " ORDER BY " . $this->validateColumnName($orderBy);
        }
        if ($limit > 0) {
            $sql .= " LIMIT " . $limit;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trouver un seul enregistrement par critères.
     */
    public function findOneBy(array $criteria): ?array
    {
        $results = $this->findBy($criteria, '', 1);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Compter les enregistrements.
     */
    public function count(array $criteria = []): int
    {
        if (empty($criteria)) {
            $sql = "SELECT COUNT(*) as cnt FROM " . static::TABLE;
            $stmt = $this->pdo->query($sql);
        } else {
            $conditions = [];
            $params = [];
            foreach ($criteria as $column => $value) {
                $safeColumn = $this->validateColumnName($column);
                $conditions[] = "$safeColumn = :$column";
                $params[":$column"] = $value;
            }
            $sql = "SELECT COUNT(*) as cnt FROM " . static::TABLE . " WHERE " . implode(' AND ', $conditions);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Insérer un enregistrement.
     * @return int L'ID inséré (lastInsertId)
     */
    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $safeColumns = array_map([$this, 'validateColumnName'], $columns);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);

        $sql = "INSERT INTO " . static::TABLE
            . " (" . implode(', ', $safeColumns) . ")"
            . " VALUES (" . implode(', ', $placeholders) . ")";

        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour un enregistrement par ID.
     * @return int Nombre de lignes affectées
     */
    public function update(int|string $id, array $data): int
    {
        $sets = [];
        $params = [':id' => $id];

        foreach ($data as $column => $value) {
            $safeColumn = $this->validateColumnName($column);
            $sets[] = "$safeColumn = :$column";
            $params[":$column"] = $value;
        }

        $sql = "UPDATE " . static::TABLE
            . " SET " . implode(', ', $sets)
            . " WHERE " . static::PRIMARY_KEY . " = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Supprimer un enregistrement par ID.
     * @return int Nombre de lignes affectées
     */
    public function delete(int|string $id): int
    {
        $sql = "DELETE FROM " . static::TABLE . " WHERE " . static::PRIMARY_KEY . " = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    /**
     * Upsert (INSERT ... ON DUPLICATE KEY UPDATE).
     * @return int L'ID inséré ou mis à jour
     */
    public function upsert(array $data, array $updateColumns): int
    {
        $columns = array_keys($data);
        $safeColumns = array_map([$this, 'validateColumnName'], $columns);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);

        $updateSets = [];
        foreach ($updateColumns as $col) {
            $safeCol = $this->validateColumnName($col);
            $updateSets[] = "$safeCol = VALUES($safeCol)";
        }

        $sql = "INSERT INTO " . static::TABLE
            . " (" . implode(', ', $safeColumns) . ")"
            . " VALUES (" . implode(', ', $placeholders) . ")"
            . " ON DUPLICATE KEY UPDATE " . implode(', ', $updateSets);

        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $this->pdo->lastInsertId();
    }

    // ─── Pagination ───────────────────────────────────────

    /**
     * Paginer les résultats.
     * @return array{data: array, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginate(int $page = 1, int $perPage = 20, array $criteria = [], string $orderBy = ''): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $total = $this->count($criteria);
        $lastPage = max(1, (int) ceil($total / $perPage));

        $data = [];
        if ($total > 0) {
            if (empty($criteria)) {
                $data = $this->findAll($orderBy, $perPage, $offset);
            } else {
                $data = $this->findByPaginated($criteria, $orderBy, $perPage, $offset);
            }
        }

        return [
            'data'     => $data,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
    }

    private function findByPaginated(array $criteria, string $orderBy, int $limit, int $offset): array
    {
        $conditions = [];
        $params = [];
        foreach ($criteria as $column => $value) {
            $safeColumn = $this->validateColumnName($column);
            $conditions[] = "$safeColumn = :$column";
            $params[":$column"] = $value;
        }

        $sql = "SELECT * FROM " . static::TABLE . " WHERE " . implode(' AND ', $conditions);
        if ($orderBy !== '') {
            $sql .= " ORDER BY " . $this->validateColumnName($orderBy);
        }
        $sql .= " LIMIT " . $limit . " OFFSET " . $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─── Requête brute ────────────────────────────────────

    /**
     * Exécuter une requête SELECT brute avec paramètres typés.
     */
    protected function query(string $sql, array $params = [], string $fetchMode = 'assoc'): array
    {
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();

        $mode = match ($fetchMode) {
            'assoc' => PDO::FETCH_ASSOC,
            'obj'   => PDO::FETCH_OBJ,
            'both'  => PDO::FETCH_BOTH,
            default => PDO::FETCH_ASSOC,
        };

        return $stmt->fetchAll($mode);
    }

    /**
     * Exécuter une requête qui retourne une seule ligne.
     */
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $results = $this->query($sql, $params);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Exécuter une requête de modification (INSERT/UPDATE/DELETE).
     * @return int Nombre de lignes affectées
     */
    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();
        return $stmt->rowCount();
    }

    // ─── Transactions ─────────────────────────────────────

    protected function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    protected function commit(): bool
    {
        return $this->pdo->commit();
    }

    protected function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Exécuter un callback dans une transaction.
     * Auto-commit ou auto-rollback.
     */
    protected function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    // ─── Validation ───────────────────────────────────────

    /**
     * Valider qu'un nom de colonne ne contient que des caractères sûrs.
     * Empêche l'injection SQL dans les noms de colonnes.
     */
    protected function validateColumnName(string $column): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new \InvalidArgumentException("Nom de colonne invalide : $column");
        }
        return $column;
    }

    // ─── Informations sur la table ────────────────────────

    /**
     * Obtenir le nom de la table.
     */
    public static function tableName(): string
    {
        return static::TABLE;
    }

    /**
     * Obtenir le nom de la clé primaire.
     */
    public static function primaryKey(): string
    {
        return static::PRIMARY_KEY;
    }

    /**
     * Vérifier si un enregistrement existe.
     */
    public function exists(int|string $id): bool
    {
        $sql = "SELECT 1 FROM " . static::TABLE . " WHERE " . static::PRIMARY_KEY . " = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() !== false;
    }
}
