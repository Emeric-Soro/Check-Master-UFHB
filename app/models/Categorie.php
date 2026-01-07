<?php

class Categorie
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupérer toutes les catégories actives triées par ordre
     */
    public function getAllCategories()
    {
        $sql = "SELECT * FROM categories_fonctionnalites WHERE actif = TRUE ORDER BY ordre_categorie ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Récupérer une catégorie par son ID
     */
    public function getCategorieById($id_categorie)
    {
        $sql = "SELECT * FROM categories_fonctionnalites WHERE id_categorie = :id_categorie";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_categorie', $id_categorie, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Récupérer une catégorie par son code
     */
    public function getCategorieByCode($code_categorie)
    {
        $sql = "SELECT * FROM categories_fonctionnalites WHERE code_categorie = :code_categorie";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':code_categorie', $code_categorie, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Créer une nouvelle catégorie
     */
    public function createCategorie($data)
    {
        $sql = "INSERT INTO categories_fonctionnalites 
                (code_categorie, lib_categorie, description_categorie, icone_categorie, ordre_categorie, actif) 
                VALUES (:code, :lib, :description, :icone, :ordre, :actif)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':code', $data['code_categorie']);
        $stmt->bindParam(':lib', $data['lib_categorie']);
        $stmt->bindParam(':description', $data['description_categorie']);
        $stmt->bindParam(':icone', $data['icone_categorie']);
        $stmt->bindParam(':ordre', $data['ordre_categorie'], PDO::PARAM_INT);
        $stmt->bindParam(':actif', $data['actif'], PDO::PARAM_BOOL);

        return $stmt->execute();
    }

    /**
     * Mettre à jour une catégorie
     */
    public function updateCategorie($id_categorie, $data)
    {
        $sql = "UPDATE categories_fonctionnalites 
                SET lib_categorie = :lib, 
                    description_categorie = :description, 
                    icone_categorie = :icone, 
                    ordre_categorie = :ordre, 
                    actif = :actif
                WHERE id_categorie = :id_categorie";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_categorie', $id_categorie, PDO::PARAM_INT);
        $stmt->bindParam(':lib', $data['lib_categorie']);
        $stmt->bindParam(':description', $data['description_categorie']);
        $stmt->bindParam(':icone', $data['icone_categorie']);
        $stmt->bindParam(':ordre', $data['ordre_categorie'], PDO::PARAM_INT);
        $stmt->bindParam(':actif', $data['actif'], PDO::PARAM_BOOL);

        return $stmt->execute();
    }

    /**
     * Supprimer une catégorie (supprime aussi les fonctionnalités associées via CASCADE)
     */
    public function deleteCategorie($id_categorie)
    {
        $sql = "DELETE FROM categories_fonctionnalites WHERE id_categorie = :id_categorie";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_categorie', $id_categorie, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Récupérer les catégories accessibles par un groupe utilisateur
     */
    public function getCategoriesForGroupe($id_GU)
    {
        $sql = "SELECT DISTINCT c.* 
                FROM categories_fonctionnalites c
                INNER JOIN fonctionnalites f ON c.id_categorie = f.id_categorie
                INNER JOIN permissions p ON f.id_fonctionnalite = p.id_fonctionnalite
                WHERE p.id_GU = :id_GU AND p.peut_voir = TRUE AND c.actif = TRUE
                ORDER BY c.ordre_categorie ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_GU', $id_GU, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
