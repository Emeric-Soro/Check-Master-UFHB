<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

/**
 * Wrapper autour de la classe statique Database (app/config/database.php).
 * Fournit une instance injectable avec une méthode pdo() pour l'accès PDO.
 */
final class Database
{
    private ?PDO $pdo = null;

    /**
     * Retourne l'instance PDO (singleton par instance de Database).
     */
    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = \Database::getConnection();
        }

        return $this->pdo;
    }
}
