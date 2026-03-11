<?php

class AnneeAcademique
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllAnneeAcademiques()
    {
        $stmt = $this->pdo->query("SELECT * FROM annee_academique ORDER BY date_deb DESC");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Récupérer l'année académique active (celle qui contient la date actuelle)
     * Si aucune année n'est active, renvoie la plus récente
     */
    public function getAnneeAcademiqueActive()
    {
        // D'abord essayer de trouver l'année en cours
        $stmt = $this->pdo->query("SELECT * FROM annee_academique WHERE CURDATE() BETWEEN date_deb AND date_fin LIMIT 1");
        $annee = $stmt->fetch(PDO::FETCH_OBJ);

        // Si aucune année n'est active, prendre la plus récente
        if (!$annee) {
            $stmt = $this->pdo->query("SELECT * FROM annee_academique ORDER BY date_deb DESC LIMIT 1");
            $annee = $stmt->fetch(PDO::FETCH_OBJ);
        }

        return $annee;
    }

    public function getAnneeAcademiqueById($id_annee_acad)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM annee_academique WHERE id_annee_acad = ?");
        $stmt->execute([$id_annee_acad]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function ajouterAnneeAcademique($date_deb, $date_fin)
    {
        $annee1 = date("Y", strtotime($date_deb));
        $annee2 = date("Y", strtotime($date_fin));
        $id_annee_acad = substr($annee2, 0, 1) . substr($annee2, 2, 2) . substr($annee1, 2, 2);
        try {
            $stmt = $this->pdo->prepare("INSERT INTO annee_academique (id_annee_acad, date_deb, date_fin) VALUES (?, ?, ?)");
            return $stmt->execute([$id_annee_acad, $date_deb, $date_fin]);
        } catch (PDOException $e) {
            error_log("Erreur d'ajout d'année académique: " . $e->getMessage());
            return false;
        }
    }

    public function updateAnneeAcademique($id_annee_acad, $date_deb, $date_fin)
    {
        // Calculer le nouvel ID basé sur les nouvelles dates
        $annee1 = date("Y", strtotime($date_deb));
        $annee2 = date("Y", strtotime($date_fin));
        $nouvel_id = substr($annee2, 0, 1) . substr($annee2, 2, 2) . substr($annee1, 2, 2);
        try {

            $stmt = $this->pdo->prepare("UPDATE annee_academique SET id_annee_acad = ?, date_deb = ?, date_fin = ? WHERE id_annee_acad = ?");
            return $stmt->execute([$nouvel_id, $date_deb, $date_fin, $id_annee_acad]);
        } catch (PDOException $e) {
            error_log("Erreur de mise à jour d'année académique: " . $e->getMessage());
            return false;
        }
    }

    public function deleteAnneeAcademique($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM annee_academique WHERE id_annee_acad = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erreur de suppression d'année académique: " . $e->getMessage());
            return false;
        }
    }

    public function isAnneeAcademiqueInUse($id)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM inscriptions WHERE id_annee_acad = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Vérifie si une année académique existe déjà en fonction de l'ID ou des dates
     * 
     * @param int|null $id_annee L'ID de l'année (null pour nouvelle année)
     * @param string $date_debut Date de début au format Y-m-d
     * @param string $date_fin Date de fin au format Y-m-d
     * @return bool True si une année existe déjà avec ces dates, false sinon
     */
    public function isAnneeAcademiqueExist($id_annee, $date_debut, $date_fin)
    {
        try {
            // Requête de base
            $sql = "SELECT COUNT(*) FROM annee_academique 
                WHERE (date_deb = :date_debut AND date_fin = :date_fin)";

            // Si on vérifie pour une modification (ID existe)
            if ($id_annee !== null) {
                $sql .= " AND id_annee_acad != :id_annee";
            }

            $stmt = $this->pdo->prepare($sql);

            // Liaison des paramètres
            $stmt->bindParam(':date_debut', $date_debut);
            $stmt->bindParam(':date_fin', $date_fin);

            if ($id_annee !== null) {
                $stmt->bindParam(':id_annee', $id_annee, PDO::PARAM_INT);
            }

            $stmt->execute();
            $count = $stmt->fetchColumn();

            return ($count > 0);

        } catch (PDOException $e) {
            error_log("Erreur vérification année académique: " . $e->getMessage());
            return true; // En cas d'erreur, on considère que l'année existe pour éviter les doublons
        }
    }
}