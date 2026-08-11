<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Chargement d'entités avec contrôle d'existence et validation d'identifiants.
 *
 * Factorise les patterns répétés dans les services :
 *  - validation d'ID numérique / chaîne ;
 *  - chargement d'une ligne par clé avec retour null ;
 *  - contrôle d'existence.
 */
final class EntityLoader
{
    public function __construct(private readonly DatabaseService $db)
    {
    }

    /**
     * Valide un identifiant numérique (int ou chaîne numérique).
     */
    public function positiveInt(mixed $value, string $label = 'identifiant'): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new \InvalidArgumentException("$label invalide.");
        }
        return $id;
    }

    /**
     * Nettoie un identifiant de type chaîne (ex: matricule) et vérifie qu'il n'est pas vide.
     */
    public function nonEmptyString(mixed $value, string $label = 'identifiant'): string
    {
        $clean = trim((string) ($value ?? ''));
        if ($clean === '') {
            throw new \InvalidArgumentException("$label requis.");
        }
        return $clean;
    }

    /**
     * Charge une ligne par clé primaire avec contrôle d'existence.
     *
     * @return array<string,mixed>|null
     */
    public function findById(string $table, string $primaryKey, int|string $id): ?array
    {
        $id = is_int($id) ? $id : $this->nonEmptyString($id);
        return $this->db->selectOne(
            'SELECT * FROM ' . $this->db->table($table)
            . ' WHERE ' . $this->db->column($primaryKey) . ' = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * Charge une ligne et lève une exception si absente.
     *
     * @return array<string,mixed>
     * @throws \RuntimeException
     */
    public function findOrFail(string $table, string $primaryKey, int|string $id, string $label = 'Élément'): array
    {
        $row = $this->findById($table, $primaryKey, $id);
        if ($row === null) {
            throw new \RuntimeException("$label introuvable (id=$id).");
        }
        return $row;
    }

    /**
     * Vérifie qu'une ligne existe.
     */
    public function exists(string $table, string $primaryKey, int|string $id): bool
    {
        return $this->findById($table, $primaryKey, $id) !== null;
    }
}
