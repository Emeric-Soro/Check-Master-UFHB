<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class Action
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function ajouterAction($lib_action)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO action (lib_action) VALUES (?)");
            return $stmt->execute([$lib_action]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout de l'action : " . $e->getMessage());
            return false;
        }
    }

    public function updateAction($id_action, $lib_action)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE action SET lib_action = ? WHERE id_action = ?");
            return $stmt->execute([$lib_action, $id_action]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la mise à jour de l'action : " . $e->getMessage());
            return false;
        }
    }

    public function deleteAction($id_action)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM action WHERE id_action = ?");
            return $stmt->execute([$id_action]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression de l'action : " . $e->getMessage());
            return false;
        }
    }

    public function getActionById($id_action)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM action WHERE id_action = ?");
            $stmt->execute([$id_action]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de l'action : " . $e->getMessage());
            return null;
        }
    }

    public function getAllAction()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM action ORDER BY lib_action");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de toutes les actions : " . $e->getMessage());
            return [];
        }
    }
}