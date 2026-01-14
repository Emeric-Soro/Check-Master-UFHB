<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;

class DossierAcademique
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function getByNumEtu($num_etu)
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM dossier_academique WHERE num_etu = ? LIMIT 1');
            $stmt->execute([$num_etu]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du dossier académique : " . $e->getMessage());
            return null;
        }
    }

    public function saveOrUpdate($data)
    {
        try {
            $num_etu = $data['num_etu'] ?? '';

            // Correction : annee_obtention_diplome doit être NULL si vide ou invalide
            if (isset($data['annee_obtention_diplome'])) {
                if ($data['annee_obtention_diplome'] === '' || $data['annee_obtention_diplome'] === null) {
                    $data['annee_obtention_diplome'] = null;
                } else {
                    // S'assurer que c'est un entier valide
                    $annee = intval($data['annee_obtention_diplome']);
                    if ($annee > 0) {
                        $data['annee_obtention_diplome'] = $annee;
                    } else {
                        $data['annee_obtention_diplome'] = null;
                    }
                }
            }

            // Nettoyer les autres champs pour éviter les erreurs de troncature
            $data = array_map(function ($value) {
                if ($value === '') {
                    return null;
                }
                return $value;
            }, $data);

            // Vérifie si le dossier existe
            $stmt = $this->pdo->prepare('SELECT id_dossier FROM dossier_academique WHERE num_etu = ?');
            $stmt->execute([$num_etu]);
            if ($stmt->fetch()) {
                // Update
                $sql = 'UPDATE dossier_academique SET adresse=:adresse, telephone=:telephone, nationalite=:nationalite, situation_familiale=:situation_familiale, dernier_diplome=:dernier_diplome, etablissement_origine=:etablissement_origine, annee_obtention_diplome=:annee_obtention_diplome, mention_diplome=:mention_diplome';
                $sql .= ' WHERE num_etu=:num_etu';
                $data['num_etu'] = $num_etu;
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($data);
            } else {
                // Insert
                $fields = array_unique(array_merge(array_keys($data), ['num_etu']));
                $placeholders = array_map(fn ($k) => ":$k", $fields);
                $sql = 'INSERT INTO dossier_academique (' . implode(',', $fields) . ') VALUES (' . implode(',', $placeholders) . ')';
                $data['num_etu'] = $num_etu;
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($data);
            }
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de l'enregistrement du dossier académique : " . $e->getMessage());
            return false;
        }
    }

    public function deleteByNumEtu($num_etu)
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM dossier_academique WHERE num_etu = ?');
            return $stmt->execute([$num_etu]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression du dossier académique : " . $e->getMessage());
            return false;
        }
    }
}