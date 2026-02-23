<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reclamation.php';
require_once __DIR__ . '/../models/AuditLog.php';

use Reclamation;
use AuditLog;
use Database;

/**
 * Service métier pour la gestion des réclamations côté scolarité.
 *
 * Contient la logique métier extraite du contrôleur :
 * - Récupération et partition des réclamations (en cours / traitées)
 * - Changement de statut avec journalisation audit
 */
class GestionReclamationsScolariteService
{
    /** @var Reclamation */
    private $reclamationModel;

    /** @var AuditLog */
    private $auditLog;

    /**
     * @param Reclamation $reclamationModel
     * @param AuditLog    $auditLog
     */
    public function __construct(Reclamation $reclamationModel, AuditLog $auditLog)
    {
        $this->reclamationModel = $reclamationModel;
        $this->auditLog = $auditLog;
    }

    /**
     * Récupère toutes les réclamations et les sépare en deux listes :
     * - en cours / en attente
     * - traitées / clôturées
     *
     * @return array{reclamationsEnCours: array, reclamationsTraitees: array}
     */
    public function getReclamationsPartitionnees(): array
    {
        $allReclamations = $this->reclamationModel->getAllReclamationsWithEtudiant();

        $reclamationsEnCours = [];
        $reclamationsTraitees = [];

        foreach ($allReclamations as $rec) {
            $statut = strtolower(trim((string) ($rec->statut_reclamation ?? '')));
            if ($statut === 'en attente' || $statut === 'en cours') {
                $reclamationsEnCours[] = $rec;
            } else {
                $reclamationsTraitees[] = $rec;
            }
        }

        return [
            'reclamationsEnCours'  => $reclamationsEnCours,
            'reclamationsTraitees' => $reclamationsTraitees,
        ];
    }

    /**
     * Change le statut d'une réclamation et journalise l'opération.
     *
     * @param int    $reclamationId  ID de la réclamation
     * @param string $nouveauStatut  Nouveau statut à appliquer
     * @param int    $idUtilisateur  ID de l'utilisateur connecté
     * @return bool true si la mise à jour a réussi
     */
    public function changerStatut(int $reclamationId, string $nouveauStatut, int $idUtilisateur): bool
    {
        if ($this->reclamationModel->updateStatut($reclamationId, $nouveauStatut)) {
            $this->auditLog->logModification($idUtilisateur, 'reclamations', 'Succès');
            return true;
        }

        $this->auditLog->logModification($idUtilisateur, 'reclamations', 'Erreur');
        return false;
    }
}