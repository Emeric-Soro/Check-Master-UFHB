<?php

namespace App\Models;

use PDO;

class AuditLog
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Enregistre une action métier dans la base de données
     */
    public function logAction(?int $idUtilisateur, string $action, string $nomTable, string $statut): bool
    {
        $idUtilisateur = $idUtilisateur ?? 0;
        
        $sql = "INSERT INTO pister (id_utilisateur, action, nom_table, statut_action, date_creation) 
                VALUES (?, ?, ?, ?, NOW())";
                
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$idUtilisateur, $action, $nomTable, $statut]);
    }

    // Méthodes de confort
    public function logCreation(int $userId, string $table, string $statut) {
        return $this->logAction($userId, 'Création', $table, $statut);
    }
    
    public function logModification(int $userId, string $table, string $statut) {
        return $this->logAction($userId, 'Modification', $table, $statut);
    }
    
    public function logSuppression(int $userId, string $table, string $statut) {
        return $this->logAction($userId, 'Suppression', $table, $statut);
    }
    
    public function logConnexion(int $userId, string $statut) {
        return $this->logAction($userId, 'Connexion', 'utilisateur', $statut);
    }
}