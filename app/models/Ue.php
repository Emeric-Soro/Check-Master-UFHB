<?php

class Ue
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllUes()
    {
        // La table ue n'existe plus
        error_log("INFO - La table 'ue' n'existe plus. Retour d'un tableau vide.");
        return [];
    }

    public function ajouterUe($lib_ue, $id_niveau_etude, $id_semestre, $id_annee_academique, $credit, $id_enseignant = null)
    {
        error_log("INFO - La table 'ue' n'existe plus. Opération désactivée.");
        return false;
    }

    public function updateUe($id_ue, $lib_ue, $id_niveau_etude, $id_semestre, $id_annee_academique, $credit, $id_enseignant = null)
    {
        error_log("INFO - La table 'ue' n'existe plus. Opération désactivée.");
        return false;
    }

    public function deleteUe($id)
    {
        error_log("INFO - La table 'ue' n'existe plus. Opération désactivée.");
        return false;
    }

    public function getUeById($id)
    {
        error_log("INFO - La table 'ue' n'existe plus. Retour de null.");
        return null;
    }

    public function getUesByNiveau(int $niveauId): array
    {
        error_log("INFO - La table 'ue' n'existe plus. Retour d'un tableau vide.");
        return [];
    }

    public function getUesByEnseignant($enseignantId)
    {
        error_log("INFO - La table 'ue' n'existe plus. Retour d'un tableau vide.");
        return [];
    }
}
