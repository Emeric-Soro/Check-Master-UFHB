<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/NiveauEtude.php';
require_once __DIR__ . '/../models/Semestre.php';
require_once __DIR__ . '/../models/Ue.php';
require_once __DIR__ . '/../models/Ecue.php';

use Note;
use Etudiant;
use NiveauEtude;
use Semestre;
use Ue;
use Ecue;
use PDO;

/**
 * Service métier pour les notes et résultats étudiants
 *
 * Contient toute la logique métier pour :
 * - Récupération des notes, moyennes, classement
 * - Récupération des informations étudiant
 * - Génération du relevé de notes PDF
 */
class NotesResultatsService
{
    /** @var PDO */
    private $db;

    /** @var Note */
    private $noteModel;

    /** @var Etudiant */
    private $etudiantModel;

    /** @var NiveauEtude */
    private $niveauModel;

    /** @var Semestre */
    private $semestreModel;

    /** @var Ue */
    private $ueModel;

    /** @var Ecue */
    private $ecueModel;

    /**
     * Constructeur du service
     *
     * @param PDO $db Connexion à la base de données
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->noteModel = new \Note($db);
        $this->etudiantModel = new \Etudiant($db);
        $this->niveauModel = new \NiveauEtude($db);
        $this->semestreModel = new \Semestre($db);
        $this->ueModel = new \Ue($db);
        $this->ecueModel = new \Ecue($db);
    }

    /**
     * Récupère les informations d'un étudiant
     *
     * @param string $studentId Numéro de l'étudiant
     * @return object|null
     */
    public function getEtudiant($studentId)
    {
        return $this->etudiantModel->getEtudiantById($studentId);
    }

    /**
     * Récupère les notes détaillées d'un étudiant
     *
     * @param string $studentId Numéro de l'étudiant
     * @return array
     */
    public function getNotes($studentId)
    {
        return $this->noteModel->getByStudent($studentId);
    }

    /**
     * Récupère la moyenne générale d'un étudiant
     *
     * @param string $studentId Numéro de l'étudiant
     * @return float|null
     */
    public function getMoyenneGenerale($studentId)
    {
        return $this->noteModel->getMoyenneGenerale($studentId)->moyenne_generale ?? null;
    }

    /**
     * Récupère le nombre d'UE validées par un étudiant
     *
     * @param string $studentId Numéro de l'étudiant
     * @return int
     */
    public function getNbUeValide($studentId)
    {
        return $this->noteModel->getValidUe($studentId)[0]->nb_ue_valide ?? 0;
    }

    /**
     * Récupère le classement et le total d'étudiants du niveau
     *
     * @param string $studentId Numéro de l'étudiant
     * @return array ['classement' => int|null, 'total' => int]
     */
    public function getClassement($studentId)
    {
        $classementObj = $this->noteModel->getClassementStudent($studentId);
        return [
            'classement' => $classementObj->classement ?? null,
            'total' => $classementObj->total ?? 0,
        ];
    }

    /**
     * Récupère les semestres d'un étudiant
     *
     * @param string $studentId Numéro de l'étudiant
     * @return array
     */
    public function getSemestres($studentId)
    {
        return $this->noteModel->getSemestreByEtudiant($studentId);
    }

    /**
     * Récupère le niveau d'études d'un étudiant
     *
     * @param string $studentId Numéro de l'étudiant
     * @return object|null
     */
    public function getNiveau($studentId)
    {
        return $this->etudiantModel->getNiveauByEtudiant($studentId);
    }

    /**
     * Récupère toutes les données de la page notes/résultats et les assigne aux $GLOBALS
     *
     * Méthode de convenance qui orchestre l'appel de toutes les données
     * et les injecte dans $GLOBALS pour la compatibilité avec les vues existantes.
     *
     * @param string $studentId Numéro de l'étudiant
     */
    public function populateIndexGlobals($studentId)
    {
        $GLOBALS['etudiant'] = $this->getEtudiant($studentId);
        $GLOBALS['notes'] = $this->getNotes($studentId);
        $GLOBALS['moyenneGenerale'] = $this->getMoyenneGenerale($studentId);
        $GLOBALS['nbUeValide'] = $this->getNbUeValide($studentId);

        $classement = $this->getClassement($studentId);
        $GLOBALS['classement'] = $classement['classement'];
        $GLOBALS['totalEtudiants'] = $classement['total'];

        $GLOBALS['semestres'] = $this->getSemestres($studentId);
    }

    /**
     * Prépare les données et génère le relevé de notes PDF
     *
     * @param string $studentId Numéro de l'étudiant
     */
    public function generatePdf($studentId)
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $etudiant = $this->getEtudiant($studentId);
        $niveau = $this->getNiveau($studentId);
        $notes = $this->getNotes($studentId);

        // Préparer les variables globales pour la vue
        $GLOBALS['selectedStudent'] = $etudiant;
        $GLOBALS['niveau'] = $niveau;
        $GLOBALS['studentGrades'] = $notes;

        // Générer le HTML de la vue
        ob_start();
        include __DIR__ . '/../../ressources/views/releve_notes.php';
        $html = ob_get_clean();

        // Générer le PDF avec Dompdf
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('releve-notes.pdf', ['Attachment' => true]);
        exit;
    }
}