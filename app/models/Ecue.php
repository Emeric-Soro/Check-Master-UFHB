<?php

class Ecue
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Obtenir tous les ECUEs avec les détails de leur UE associée
    // NOTE: Les tables 'ecue' et 'ue' n'existent plus
    public function getAllEcues()
    {
        error_log("INFO - Les tables 'ecue' et 'ue' n'existent plus. Retour d'un tableau vide.");
        return [];
    }

    // Ajouter un ECUE avec validation du crédit
    public function ajouterEcue($id_ue, $lib_ecue, $credit, $id_enseignant = null)
    {
        error_log("INFO - La table 'ecue' n'existe plus. Opération désactivée.");
        return false;
    }

    // Modifier un ECUE
    public function updateEcue($id_ecue, $id_ue, $lib_ecue, $credit, $id_enseignant = null)
    {
        error_log("INFO - La table 'ecue' n'existe plus. Opération désactivée.");
        return false;
    }

    // Supprimer un ECUE
    public function deleteEcue($id)
    {
        error_log("INFO - La table 'ecue' n'existe plus. Opération désactivée.");
        return false;
    }

    // Récupérer un ECUE par son ID
    public function getEcueById($id)
    {
        error_log("INFO - La table 'ecue' n'existe plus. Retour de null.");
        return null;
    }

    // Crédit restant pour une UE
    private function creditRestantPourUe($id_ue, $excludeEcueId = null)
    {
        error_log("INFO - La table 'ue' n'existe plus. Retour de 0.");
        return 0;
    }

    // Vérifier si on peut ajouter ce crédit à l'UE
    public function verifierCreditDisponible($id_ue, $nouveauCredit)
    {
        error_log("INFO - La table 'ue' n'existe plus. Retour de false.");
        return false;
    }

    public function getEcuesByNiveau(int $niveauId): array
    {
        error_log("INFO - La table 'ecue' n'existe plus. Retour d'un tableau vide.");
        return [];
    }

    /**
     * Récupérer tous les ECUE d'un enseignant
     */
    public function getEcuesByEnseignant($enseignantId): array
    {
        error_log("INFO - La table 'ecue' n'existe plus. Retour d'un tableau vide.");
        return [];
    }
}
