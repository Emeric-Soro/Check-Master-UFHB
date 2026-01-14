<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class Message
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function ajouterMessage($contenu_message, $lib_message, $type_message)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO messages (contenu_message, lib_message, type_message) VALUES (?, ?, ?)");
            return $stmt->execute([$contenu_message, $lib_message, $type_message]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'ajout du message : " . $e->getMessage());
            return false;
        }
    }


    public function updateMessage($id_message, $contenu_message, $lib_message, $type_message)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE messages SET contenu_message = ?, lib_message = ?, type_message= ? WHERE id_message = ?");
            return $stmt->execute([$contenu_message, $lib_message, $type_message, $id_message]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la mise à jour du message : " . $e->getMessage());
            return false;
        }
    }

    public function deleteMessage($id_message)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM messages WHERE id_message = ?");
            return $stmt->execute([$id_message]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression du message : " . $e->getMessage());
            return false;
        }
    }

    public function getMessageById($id_message)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM messages WHERE id_message = ?");
            $stmt->execute([$id_message]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du message par ID : " . $e->getMessage());
            return null;
        }
    }

    public function getAllMessages()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM messages ORDER BY contenu_message");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de tous les messages : " . $e->getMessage());
            return [];
        }
    }
}