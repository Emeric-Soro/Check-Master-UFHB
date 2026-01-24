<?php

class GroupeUtilisateur
{
    private $pdo;
    private ?bool $hasTypeColumn = null;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    private function supportsTypeLink(): bool
    {
        if ($this->hasTypeColumn !== null) {
            return $this->hasTypeColumn;
        }
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM groupe_utilisateur LIKE 'id_type_utilisateur'");
            $this->hasTypeColumn = (bool) ($stmt && $stmt->fetch(PDO::FETCH_ASSOC));
        } catch (Throwable $e) {
            $this->hasTypeColumn = false;
        }
        return $this->hasTypeColumn;
    }

    // Récupérer tous les groupe utilisateurs
    public function getAllGroupeUtilisateur()
    {
        $stmt = $this->pdo->query("SELECT * FROM groupe_utilisateur ORDER BY lib_GU");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Récupérer les groupes liés à un type utilisateur.
     * - Si `groupe_utilisateur.id_type_utilisateur` existe: filtre direct.
     * - Sinon: fallback (groupes utilisés par au moins un utilisateur de ce type).
     */
    public function getGroupesByTypeUtilisateur(int $idTypeUtilisateur): array
    {
        if ($idTypeUtilisateur <= 0) {
            return [];
        }

        try {
            if ($this->supportsTypeLink()) {
                $stmt = $this->pdo->prepare("SELECT * FROM groupe_utilisateur WHERE id_type_utilisateur = ? ORDER BY lib_GU");
                $stmt->execute([$idTypeUtilisateur]);
                return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
            }
        } catch (Throwable $e) {
            // fallback ci-dessous
        }

        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT gu.*
             FROM groupe_utilisateur gu
             INNER JOIN utilisateur u ON u.id_GU = gu.id_GU
             WHERE u.id_type_utilisateur = ?
             ORDER BY gu.lib_GU"
        );
        $stmt->execute([$idTypeUtilisateur]);
        return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
    }

    // Ajouter un nouveau groupe utilisateur
    public function ajouterGroupeUtilisateur($lib_GU, $id_type_utilisateur = null)
    {
        try {
            if ($this->supportsTypeLink()) {
                $stmt = $this->pdo->prepare("INSERT INTO groupe_utilisateur (lib_GU, id_type_utilisateur) VALUES (?, ?)");
                return $stmt->execute([$lib_GU, $id_type_utilisateur ?: null]);
            }
        } catch (Throwable $e) {
            // fallback
        }

        $stmt = $this->pdo->prepare("INSERT INTO groupe_utilisateur (lib_GU) VALUES (?)");
        return $stmt->execute([$lib_GU]);
    }

    //Modifier un groupe utilisateur
    public function updateGroupeUtilisateur($id_GU, $lib_GU, $id_type_utilisateur = null)
    {
        try {
            if ($this->supportsTypeLink()) {
                $stmt = $this->pdo->prepare("UPDATE groupe_utilisateur SET lib_GU = ?, id_type_utilisateur = ? WHERE id_GU = ?");
                return $stmt->execute([$lib_GU, $id_type_utilisateur ?: null, $id_GU]);
            }
            $stmt = $this->pdo->prepare("UPDATE groupe_utilisateur SET lib_GU = ? WHERE id_GU = ?");
            return $stmt->execute([$lib_GU, $id_GU]);
        } catch (PDOException $e) {
            error_log("Erreur pendant la maj du groupe utilisateur");
            return false;
        }
    }

    // Supprimer un groupe utilisateur
    public function deleteGroupeUtilisateur($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM groupe_utilisateur WHERE id_GU = ?");
        return $stmt->execute([$id]);
    }

    public function getGroupeUtilisateurById($id_GU)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM groupe_utilisateur WHERE id_GU = ?");
        $stmt->execute([$id_GU]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}