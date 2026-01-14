<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class TypeUtilisateur
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    // Récupérer tous les type utilisateur
    public function getAllTypeUtilisateur()
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM type_utilisateur ORDER BY lib_type_utilisateur");
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de tous les types d'utilisateurs : " . $e->getMessage());
            return [];
        }
    }

    // Ajouter un nouveau type utilisateur
    public function ajouterTypeUtilisateur($lib_type_utilisateur)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO type_utilisateur (lib_type_utilisateur) VALUES (?)");
            return $stmt->execute([$lib_type_utilisateur]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout du type d'utilisateur : " . $e->getMessage());
            return false;
        }
    }

    //Modifier un type utilisateur
    public function updateTypeUtilisateur($id_type_utilisateur, $lib_type_utilisateur)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE type_utilisateur SET lib_type_utilisateur = ? WHERE id_type_utilisateur = ?");
            return $stmt->execute([$lib_type_utilisateur, $id_type_utilisateur]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur pendant la maj du type d'utilisateur : " . $e->getMessage());
            return false;
        }
    }

    // Supprimer un type utilisateur
    public function deleteTypeUtilisateur($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM type_utilisateur WHERE id_type_utilisateur = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression du type d'utilisateur : " . $e->getMessage());
            return false;
        }
    }

    public function getTypeUtilisateurById($id_type_utilisateur)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM type_utilisateur WHERE id_type_utilisateur = ?");
            $stmt->execute([$id_type_utilisateur]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du type d'utilisateur par ID : " . $e->getMessage());
            return null;
        }
    }

    public function getTypeUtilisateurByLibelle($lib_type_utilisateur)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM type_utilisateur WHERE lib_type_utilisateur = ?");
            $stmt->execute([$lib_type_utilisateur]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du type d'utilisateur par libellé : " . $e->getMessage());
            return null;
        }
    }
}