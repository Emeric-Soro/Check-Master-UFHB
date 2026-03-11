<?php

/**
 * Modèle NiveauEtude
 * 
 * NOTE IMPORTANTE: Les montants (montant_scolarite, montant_inscription) ne sont
 * plus stockés dans cette table. Ils sont maintenant dans la table frais_inscription
 * avec une liaison par id_annee_acad et id_niv_etude pour permettre des tarifs variables
 * par année académique.
 * 
 * Structure actuelle de niveau_etude:
 * - id_niv_etude (PK)
 * - lib_niv_etude
 * 
 * Pour les montants, voir table frais_inscription:
 * - id_frais_inscription (PK)
 * - id_annee_acad
 * - id_niv_etude
 * - montant
 */

class NiveauEtude
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllNiveauxEtudes()
    {
        $stmt = $this->pdo->query("SELECT n.* 
        FROM niveau_etude n
        ORDER BY n.lib_niv_etude");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getAll()
    {
        return $this->getAllNiveauxEtudes();
    }

    /**
     * Ajouter un niveau d'étude
     * @deprecated Les paramètres montant_scolarite et montant_inscription sont ignorés
     * @param string $lib Libellé du niveau
     * @param float $montant_scolarite OBSOLÈTE - Ignoré
     * @param float $montant_inscription OBSOLÈTE - Ignoré
     * @param int|null $id_enseignant OBSOLÈTE - Ignoré
     * @return bool
     */
    public function ajouterNiveauEtude($lib, $montant_scolarite = null, $montant_inscription = null, $id_enseignant = null)
    {
        // NOTE: montant_scolarite, montant_inscription et id_enseignant ne sont plus utilisés
        // Les montants doivent être gérés via frais_inscription
        $stmt = $this->pdo->prepare("INSERT INTO niveau_etude (lib_niv_etude) VALUES (?)");
        return $stmt->execute([$lib]);
    }

    /**
     * Mettre à jour un niveau d'étude
     * @deprecated Les paramètres montant_scolarite et montant_inscription sont ignorés
     * @param int $id ID du niveau
     * @param string $lib Libellé du niveau
     * @param float $montant_scolarite OBSOLÈTE - Ignoré
     * @param float $montant_inscription OBSOLÈTE - Ignoré
     * @param int|null $id_enseignant OBSOLÈTE - Ignoré
     * @return bool
     */
    public function updateNiveauEtude($id, $lib, $montant_scolarite = null, $montant_inscription = null, $id_enseignant = null)
    {
        // NOTE: montant_scolarite, montant_inscription et id_enseignant ne sont plus utilisés
        // Les montants doivent être gérés via frais_inscription
        $stmt = $this->pdo->prepare("UPDATE niveau_etude SET lib_niv_etude = ? WHERE id_niv_etude = ?");
        return $stmt->execute([$lib, $id]);
    }

    public function deleteNiveauEtude($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM niveau_etude WHERE id_niv_etude = ?");
        return $stmt->execute([$id]);
    }

    public function getNiveauEtudeById($id)
    {
        $stmt = $this->pdo->prepare("SELECT n.* FROM niveau_etude n WHERE n.id_niv_etude = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}