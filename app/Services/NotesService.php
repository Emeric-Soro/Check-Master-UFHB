<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/NiveauEtude.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/AcademicYear.php';

use Note;
use Etudiant;
use NiveauEtude;
use AnneeAcademique;
use AuditLog;

class NotesService
{
    private $db;
    private $noteModel;
    private $etudiantModel;
    private $niveauModel;
    private $anneeAcadModel;
    private $auditLog;

    public function __construct($db)
    {
        $this->db = $db;
        $this->noteModel = new Note($db);
        $this->etudiantModel = new Etudiant($db);
        $this->niveauModel = new NiveauEtude($db);
        $this->anneeAcadModel = new AnneeAcademique($db);
        $this->auditLog = new AuditLog($db);
    }

    /**
     * Déduire le niveau d'un étudiant à partir de sa dernière inscription
     * (fallback sur etudiants.id_niveau si nécessaire).
     */
    private function resolveStudentNiveau(string $studentId): ?int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT i.id_niv_etude
                FROM inscriptions i
                WHERE i.num_carte_etud = ?
                ORDER BY i.date_inscription DESC, i.id_inscription DESC
                LIMIT 1
            ");
            $stmt->execute([$studentId]);
            $niveau = $stmt->fetchColumn();
            if ($niveau !== false && (int) $niveau > 0) {
                return (int) $niveau;
            }
        } catch (\Throwable $e) {
            error_log('resolveStudentNiveau(inscriptions): ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get all data needed for the notes index page.
     */
    public function getIndexData(array $queryParams): array
    {
        $selectedNiveau = isset($queryParams['niveau']) ? (int) $queryParams['niveau'] : null;
        $selectedAnneeAcad = isset($queryParams['annee']) && is_numeric($queryParams['annee'])
            ? (int) $queryParams['annee']
            : \AcademicYear::getSelectedIdFromSession();
        $selectedStudentId = $queryParams['student'] ?? null;

        if (!$selectedNiveau && !empty($selectedStudentId)) {
            $selectedNiveau = $this->resolveStudentNiveau((string) $selectedStudentId);
        }

        $studentObj = $selectedStudentId ? $this->etudiantModel->getEtudiantById($selectedStudentId) : null;

        $data = [
            'niveaux'           => $this->niveauModel->getAllNiveauxEtudes(),
            'anneesAcademiques' => $this->anneeAcadModel->getAllAnneeAcademiques(),
            'etudiants'         => $selectedNiveau
                ? $this->etudiantModel->getEtudiantsByNiveau($selectedNiveau, $selectedAnneeAcad)
                : $this->etudiantModel->getAllEtudiants($selectedAnneeAcad),
            'selectedNiveau'    => $selectedNiveau,
            'selectedAnneeAcad' => $selectedAnneeAcad,
            'niveau'            => $selectedNiveau ? $this->niveauModel->getNiveauEtudeById($selectedNiveau) : null,
            'selectedStudent'   => $studentObj,
            'studentNote'       => null,
            'moyenneGenerale'   => null,
        ];

        // Load student notes
        if ($studentObj && $selectedAnneeAcad) {
            $data['studentNote'] = $this->noteModel->getByStudentAndYear($studentObj->num_carte_etud, $selectedAnneeAcad);
        } elseif ($studentObj) {
            $data['studentNote'] = $this->noteModel->getLatestNote($studentObj->num_carte_etud);
        }

        // Calculate average if notes exist
        if ($data['studentNote']) {
            $note = $data['studentNote'];
            $data['moyenneGenerale'] = ($note->moyenne_M1 + $note->moyenne_M2) / 2;
        }

        return $data;
    }

    /**
     * Save or update notes for a student.
     *
     * @return array{success: bool, message: string}
     */
    public function enregistrerNotes(string $studentId, int $anneeAcadId, float $moyenneM1, float $moyenneM2, int $idUtilisateur): array
    {
        // Validation: grades must be between 0 and 20
        if ($moyenneM1 < 0 || $moyenneM1 > 20 || $moyenneM2 < 0 || $moyenneM2 > 20) {
            return ['success' => false, 'message' => 'Les moyennes doivent être comprises entre 0 et 20.'];
        }

        $writeGuard = \AcademicYear::ensureWritableYear($this->db, $anneeAcadId, 'des notes');
        if (!$writeGuard['success']) {
            return ['success' => false, 'message' => $writeGuard['message']];
        }

        try {
            $result = $this->noteModel->saveNotes($studentId, $moyenneM1, $moyenneM2, $anneeAcadId);

            if ($result) {
                $this->auditLog->logCreation($idUtilisateur, 'notes', 'Succès');
                return ['success' => true, 'message' => 'Les notes ont été enregistrées avec succès.'];
            }

            $this->auditLog->logCreation($idUtilisateur, 'notes', 'Erreur');
            return ['success' => false, 'message' => "Une erreur est survenue lors de l'enregistrement des notes."];
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'enregistrement des notes: ' . $e->getMessage());
            $this->auditLog->logCreation($idUtilisateur, 'notes', 'Erreur');
            return ['success' => false, 'message' => 'Une erreur est survenue : ' . $e->getMessage()];
        }
    }

    /**
     * Get notes for a given student (optionally filtered by academic year).
     *
     * @return array
     */
    public function getNotesByEtudiant(string $studentId, ?int $anneeAcadId = null): array
    {
        return $this->noteModel->getByStudent($studentId, $anneeAcadId);
    }
}
