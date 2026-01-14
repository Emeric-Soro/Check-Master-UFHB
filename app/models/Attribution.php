<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class Attribution
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * Récupère tous les droits configurés pour un groupe
     */
    public function getDroitsByGroupe(int $idGroupe): array
    {
        $sql = "SELECT d.*, t.lib_traitement, t.label_traitement 
                FROM droits d
                JOIN traitement t ON d.id_traitement = t.id_traitement
                WHERE d.id_GU = :id_gu";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id_gu' => $idGroupe]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur récupération droits : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Définit ou met à jour une permission spécifique
     * @param int $idGroupe
     * @param int $idTraitement
     * @param array $permissions ['read' => 1, 'create' => 0, etc.]
     */
    public function setPermissions(int $idGroupe, int $idTraitement, array $permissions): bool
    {
        // On utilise ON DUPLICATE KEY UPDATE pour gérer l'insertion ou la mise à jour en une seule requête
        $sql = "INSERT INTO droits (id_GU, id_traitement, can_read, can_create, can_update, can_delete)
                VALUES (:id_gu, :id_tr, :read, :create, :update, :delete)
                ON DUPLICATE KEY UPDATE 
                can_read = VALUES(can_read),
                can_create = VALUES(can_create),
                can_update = VALUES(can_update),
                can_delete = VALUES(can_delete)";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'id_gu'   => $idGroupe,
                'id_tr'   => $idTraitement,
                'read'    => $permissions['read'] ?? 0,
                'create'  => $permissions['create'] ?? 0,
                'update'  => $permissions['update'] ?? 0,
                'delete'  => $permissions['delete'] ?? 0
            ]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur assignation droits : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime tous les droits d'un groupe (Reset)
     */
    public function removeAllDroits(int $idGroupe): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM droits WHERE id_GU = ?");
            return $stmt->execute([$idGroupe]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur suppression droits : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Alias pour removeAllDroits (legacy compatibility)
     */
    public function deleteAttribution(int $idGroupe): bool
    {
        return $this->removeAllDroits($idGroupe);
    }

    /**
     * Ajoute une attribution simple (legacy compatibility)
     * Définit par défaut can_read = 1
     */
    public function ajouterAttribution(int $idGroupe, int $idTraitement): bool
    {
        return $this->setPermissions($idGroupe, $idTraitement, ['read' => 1]);
    }

    /**
     * Récupère les traitements attribués (legacy compatibility)
     */
    public function getTraitementsByGroupe(int $idGroupe): array
    {
        // On retourne sous forme d'objets pour la compatibilité avec le code existant
        $droits = $this->getDroitsByGroupe($idGroupe);
        return array_map(function($item) {
            return (object)$item;
        }, $droits);
    }
}