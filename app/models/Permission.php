<?php

class Permission
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Vérifier si un groupe a une permission spécifique sur une fonctionnalité
     */
    public function checkPermission($id_GU, $id_fonctionnalite, $type_permission = 'peut_voir')
    {
        $sql = "SELECT $type_permission FROM permissions 
                WHERE id_GU = :id_GU AND id_fonctionnalite = :id_fonctionnalite";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_GU', $id_GU, PDO::PARAM_INT);
        $stmt->bindParam(':id_fonctionnalite', $id_fonctionnalite, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ? (bool) $result->$type_permission : false;
    }

    /**
     * Vérifier si un groupe a accès à une fonctionnalité par son code
     */
    public function checkPermissionByCode($id_GU, $code_fonctionnalite, $type_permission = 'peut_voir')
    {
        $sql = "SELECT p.$type_permission 
                FROM permissions p
                INNER JOIN fonctionnalites f ON p.id_fonctionnalite = f.id_fonctionnalite
                WHERE p.id_GU = :id_GU AND f.code_fonctionnalite = :code_fonctionnalite";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_GU', $id_GU, PDO::PARAM_INT);
        $stmt->bindParam(':code_fonctionnalite', $code_fonctionnalite, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ? (bool) $result->$type_permission : false;
    }

    /**
     * Récupérer toutes les permissions d'un groupe pour une fonctionnalité
     */
    public function getPermissions($id_GU, $id_fonctionnalite)
    {
        $sql = "SELECT * FROM permissions 
                WHERE id_GU = :id_GU AND id_fonctionnalite = :id_fonctionnalite";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_GU', $id_GU, PDO::PARAM_INT);
        $stmt->bindParam(':id_fonctionnalite', $id_fonctionnalite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Récupérer toutes les permissions d'un groupe
     */
    public function getAllPermissionsForGroupe($id_GU)
    {
        $sql = "SELECT p.*, f.code_fonctionnalite, f.lib_fonctionnalite, c.lib_categorie
                FROM permissions p
                INNER JOIN fonctionnalites f ON p.id_fonctionnalite = f.id_fonctionnalite
                INNER JOIN categories_fonctionnalites c ON f.id_categorie = c.id_categorie
                WHERE p.id_GU = :id_GU
                ORDER BY c.ordre_categorie ASC, f.ordre_fonctionnalite ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_GU', $id_GU, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Créer une nouvelle permission
     */
    public function createPermission($data)
    {
        $sql = "INSERT INTO permissions 
                (id_GU, id_fonctionnalite, peut_voir, peut_creer, peut_modifier, peut_supprimer) 
                VALUES (:id_GU, :id_fonctionnalite, :peut_voir, :peut_creer, :peut_modifier, :peut_supprimer)
                ON DUPLICATE KEY UPDATE 
                peut_voir = VALUES(peut_voir),
                peut_creer = VALUES(peut_creer),
                peut_modifier = VALUES(peut_modifier),
                peut_supprimer = VALUES(peut_supprimer)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_GU', $data['id_GU'], PDO::PARAM_INT);
        $stmt->bindParam(':id_fonctionnalite', $data['id_fonctionnalite'], PDO::PARAM_INT);
        $stmt->bindParam(':peut_voir', $data['peut_voir'], PDO::PARAM_BOOL);
        $stmt->bindParam(':peut_creer', $data['peut_creer'], PDO::PARAM_BOOL);
        $stmt->bindParam(':peut_modifier', $data['peut_modifier'], PDO::PARAM_BOOL);
        $stmt->bindParam(':peut_supprimer', $data['peut_supprimer'], PDO::PARAM_BOOL);

        return $stmt->execute();
    }

    /**
     * Mettre à jour une permission
     */
    public function updatePermission($id_GU, $id_fonctionnalite, $data)
    {
        $sql = "UPDATE permissions 
                SET peut_voir = :peut_voir, 
                    peut_creer = :peut_creer, 
                    peut_modifier = :peut_modifier, 
                    peut_supprimer = :peut_supprimer
                WHERE id_GU = :id_GU AND id_fonctionnalite = :id_fonctionnalite";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_GU', $id_GU, PDO::PARAM_INT);
        $stmt->bindParam(':id_fonctionnalite', $id_fonctionnalite, PDO::PARAM_INT);
        $stmt->bindParam(':peut_voir', $data['peut_voir'], PDO::PARAM_BOOL);
        $stmt->bindParam(':peut_creer', $data['peut_creer'], PDO::PARAM_BOOL);
        $stmt->bindParam(':peut_modifier', $data['peut_modifier'], PDO::PARAM_BOOL);
        $stmt->bindParam(':peut_supprimer', $data['peut_supprimer'], PDO::PARAM_BOOL);

        return $stmt->execute();
    }

    /**
     * Supprimer une permission
     */
    public function deletePermission($id_GU, $id_fonctionnalite)
    {
        $sql = "DELETE FROM permissions WHERE id_GU = :id_GU AND id_fonctionnalite = :id_fonctionnalite";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_GU', $id_GU, PDO::PARAM_INT);
        $stmt->bindParam(':id_fonctionnalite', $id_fonctionnalite, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Attribuer toutes les permissions d'une catégorie à un groupe
     */
    public function assignCategorieToGroupe($id_GU, $id_categorie, $permissions = [])
    {
        $peut_voir = $permissions['peut_voir'] ?? true;
        $peut_creer = $permissions['peut_creer'] ?? false;
        $peut_modifier = $permissions['peut_modifier'] ?? false;
        $peut_supprimer = $permissions['peut_supprimer'] ?? false;

        $sql = "INSERT INTO permissions (id_GU, id_fonctionnalite, peut_voir, peut_creer, peut_modifier, peut_supprimer)
                SELECT :id_GU, id_fonctionnalite, :peut_voir, :peut_creer, :peut_modifier, :peut_supprimer
                FROM fonctionnalites
                WHERE id_categorie = :id_categorie AND actif = TRUE
                ON DUPLICATE KEY UPDATE 
                peut_voir = VALUES(peut_voir),
                peut_creer = VALUES(peut_creer),
                peut_modifier = VALUES(peut_modifier),
                peut_supprimer = VALUES(peut_supprimer)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_GU', $id_GU, PDO::PARAM_INT);
        $stmt->bindParam(':id_categorie', $id_categorie, PDO::PARAM_INT);
        $stmt->bindParam(':peut_voir', $peut_voir, PDO::PARAM_BOOL);
        $stmt->bindParam(':peut_creer', $peut_creer, PDO::PARAM_BOOL);
        $stmt->bindParam(':peut_modifier', $peut_modifier, PDO::PARAM_BOOL);
        $stmt->bindParam(':peut_supprimer', $peut_supprimer, PDO::PARAM_BOOL);

        return $stmt->execute();
    }

    /**
     * Révoquer toutes les permissions d'une catégorie pour un groupe
     */
    public function revokeCategorieFromGroupe($id_GU, $id_categorie)
    {
        $sql = "DELETE p FROM permissions p
                INNER JOIN fonctionnalites f ON p.id_fonctionnalite = f.id_fonctionnalite
                WHERE p.id_GU = :id_GU AND f.id_categorie = :id_categorie";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_GU', $id_GU, PDO::PARAM_INT);
        $stmt->bindParam(':id_categorie', $id_categorie, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
