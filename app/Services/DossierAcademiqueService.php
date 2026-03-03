<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/DossierAcademique.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/AcademicYear.php';

use DossierAcademique;
use AuditLog;

class DossierAcademiqueService
{
    private $db;
    private $model;
    private $auditLog;

    public function __construct($db)
    {
        $this->db = $db;
        $this->model = new DossierAcademique($db);
        $this->auditLog = new AuditLog($db);
    }

    /**
     * Save or update a dossier académique.
     *
     * @param array  $data           POST data for the dossier
     * @param int    $idUtilisateur  ID of the authenticated user
     * @return bool  true on success, false on failure
     */
    public function saveOrUpdate(array $data, int $idUtilisateur): bool
    {
        $numEtu = (string) ($data['num_etu'] ?? '');
        if ($numEtu !== '') {
            try {
                $stmt = $this->db->prepare('SELECT id_annee_acad FROM inscriptions WHERE id_etudiant = ? ORDER BY date_inscription DESC, id_inscription DESC LIMIT 1');
                $stmt->execute([$numEtu]);
                $yearId = $stmt->fetchColumn();
                $writeGuard = \AcademicYear::ensureWritableYear($this->db, $yearId, 'un dossier academique');
                if (!$writeGuard['success']) {
                    return false;
                }
            } catch (\Throwable $e) {
                error_log('Erreur saveOrUpdate dossier guard: ' . $e->getMessage());
                return false;
            }
        }

        $success = $this->model->saveOrUpdate($data);

        if ($success) {
            $this->auditLog->logCreation($idUtilisateur, 'dossiers_academiques', 'Succès');
        } else {
            $this->auditLog->logCreation($idUtilisateur, 'dossiers_academiques', 'Erreur');
        }

        return $success;
    }

    /**
     * Retrieve a dossier académique by student number.
     *
     * @param string $numEtu  Student number
     * @return array|null
     */
    public function getByNumEtu(string $numEtu)
    {
        return $this->model->getByNumEtu($numEtu);
    }
}
