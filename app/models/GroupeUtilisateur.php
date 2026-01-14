<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class GroupeUtilisateur
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    // Récupérer tous les groupe utilisateurs
    public function getAllGroupeUtilisateur()
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM groupe_utilisateur ORDER BY lib_GU");
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de tous les groupes utilisateurs : " . $e->getMessage());
            return [];
        }
    }

    // Ajouter un nouveau groupe utilisateur
    public function ajouterGroupeUtilisateur($lib_GU)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO groupe_utilisateur (lib_GU) VALUES (?)");
            return $stmt->execute([$lib_GU]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout du groupe utilisateur : " . $e->getMessage());
            return false;
        }
    }

    //Modifier un groupe utilisateur
    public function updateGroupeUtilisateur($id_GU, $lib_GU)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE groupe_utilisateur SET lib_GU = ? WHERE id_GU = ?");
            return $stmt->execute([$lib_GU, $id_GU]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur pendant la maj du groupe utilisateur : " . $e->getMessage());
            return false;
        }
    }

    // Supprimer un groupe utilisateur
    public function deleteGroupeUtilisateur($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM groupe_utilisateur WHERE id_GU = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression du groupe utilisateur : " . $e->getMessage());
            return false;
        }
    }

    public function getGroupeUtilisateurById($id_GU)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM groupe_utilisateur WHERE id_GU = ?");
            $stmt->execute([$id_GU]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du groupe utilisateur par ID : " . $e->getMessage());
            return null;
        }
    }
}