<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Enseignant.php';
require_once __DIR__ . '/../models/Grade.php';
require_once __DIR__ . '/../models/Fonction.php';
require_once __DIR__ . '/../models/Specialite.php';
require_once __DIR__ . '/../models/Jury.php';

use Enseignant;
use Grade;
use Fonction;
use Specialite;
use Jury;

/**
 * Service d'agrégation des données pour la Fiche Enseignante Complète.
 *
 * Rassemble identité, grades (actuel + historique), fonctions,
 * spécialité, jurys, encadrements, statistiques et compte utilisateur.
 */
class FicheEnseignantService
{
    /** @var \PDO */
    private \PDO $pdo;

    /** @var Enseignant */
    private Enseignant $enseignantModel;

    /** @var Grade */
    private Grade $gradeModel;

    /** @var Fonction */
    private Fonction $fonctionModel;

    /** @var Specialite */
    private Specialite $specialiteModel;

    /** @var Jury */
    private Jury $juryModel;

    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo ?: \Database::getConnection();
        $this->enseignantModel = new Enseignant($this->pdo);
        $this->gradeModel = new Grade($this->pdo);
        $this->fonctionModel = new Fonction($this->pdo);
        $this->specialiteModel = new Specialite($this->pdo);
        $this->juryModel = new Jury($this->pdo);
    }

    /**
     * Retourne la liste de tous les enseignants avec grade et spécialité.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getListeEnseignants(): array
    {
        $enseignants = $this->enseignantModel->getAllEnseignants();
        $result = [];

        foreach ($enseignants as $ens) {
            $result[] = [
                'id_enseignant' => (string) ($ens->id_enseignant ?? ''),
                'matricule_enseignant' => (string) ($ens->matricule_enseignant ?? ''),
                'nom_enseignant' => (string) ($ens->nom_enseignant ?? ''),
                'prenom_enseignant' => (string) ($ens->prenom_enseignant ?? ''),
                'mail_enseignant' => (string) ($ens->mail_enseignant ?? ''),
                'tel_enseignant' => (string) ($ens->tel_enseignant ?? ''),
                'lib_grade' => (string) ($ens->lib_grade ?? ''),
                'lib_specialite' => (string) ($ens->lib_specialite ?? ''),
                'lib_fonction' => (string) ($ens->lib_fonction ?? ''),
                'type_enseignant' => (string) ($ens->type_enseignant ?? ''),
            ];
        }

        return $result;
    }

    /**
     * Agrège toutes les données pour la fiche complète d'un enseignant.
     *
     * @param string $id matricule de l'enseignant
     * @return array<string, mixed>
     */
    public function getFicheComplete(string $id): array
    {
        // 1. Identité de base
        $enseignant = $this->enseignantModel->getEnseignantById($id);

        if (!$enseignant) {
            return [];
        }

        $identite = [
            'id_enseignant' => (string) ($enseignant->id_enseignant ?? ''),
            'matricule_enseignant' => (string) ($enseignant->matricule_enseignant ?? ''),
            'nom_enseignant' => (string) ($enseignant->nom_enseignant ?? ''),
            'prenom_enseignant' => (string) ($enseignant->prenom_enseignant ?? ''),
            'mail_enseignant' => (string) ($enseignant->mail_enseignant ?? ''),
            'tel_enseignant' => (string) ($enseignant->tel_enseignant ?? ''),
            'lib_specialite' => (string) ($enseignant->lib_specialite ?? ''),
            'id_specialite' => (string) ($enseignant->id_specialite ?? ''),
            'type_enseignant' => (string) ($enseignant->type_enseignant ?? ''),
        ];

        // 2. Grade actuel
        $gradeActuel = [];
        if (!empty($enseignant->id_grade)) {
            $gradeActuel = [
                'id_grade' => (string) ($enseignant->id_grade ?? ''),
                'lib_grade' => (string) ($enseignant->lib_grade ?? ''),
                'date_grade' => (string) ($enseignant->date_grade ?? ''),
            ];
        }

        // 3. Historique des grades (toutes les affectations)
        $historiqueGrades = $this->getHistoriqueGrades($id);

        // 4. Fonctions occupées
        $fonctions = $this->getFonctionsOccupees($id);

        // 5. Type enseignant
        $typeEnseignantLib = '';
        if (!empty($enseignant->type_enseignant)) {
            $typeEnseignantLib = $this->getTypeEnseignantLibelle((int) $enseignant->type_enseignant);
        }

        // 6. Historique jurys
        $jurys = $this->getJuryHistory($id);

        // 7. Encadrements (rapports)
        $encadrements = $this->getEncadrements($id);

        // 8. Statistiques
        $stats = $this->getStatsEnseignant($id);

        // 9. Compte utilisateur associé
        $compteUtilisateur = $this->getCompteUtilisateur($id);

        return [
            'identite' => $identite,
            'grade_actuel' => $gradeActuel,
            'historique_grades' => $historiqueGrades,
            'fonctions' => $fonctions,
            'type_enseignant_lib' => $typeEnseignantLib,
            'jurys' => $jurys,
            'encadrements' => $encadrements,
            'stats' => $stats,
            'compte_utilisateur' => $compteUtilisateur,
        ];
    }

    /**
     * Historique complet des grades (table avoir).
     *
     * @param string $idEnseignant
     * @return array<int, array<string, mixed>>
     */
    private function getHistoriqueGrades(string $idEnseignant): array
    {
        try {
            $sql = "SELECT a.id_grade, g.lib_grade, a.date_grade
                    FROM avoir a
                    JOIN grade g ON g.id_grade = a.id_grade
                    WHERE a.id_enseignant = :id
                    ORDER BY a.date_grade DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $idEnseignant]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log('FicheEnseignantService::getHistoriqueGrades: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fonctions occupées avec dates (table occuper).
     *
     * @param string $idEnseignant
     * @return array<int, array<string, mixed>>
     */
    private function getFonctionsOccupees(string $idEnseignant): array
    {
        try {
            $sql = "SELECT o.id_fonction, f.lib_fonction, o.date_occupation
                    FROM occuper o
                    JOIN fonction f ON f.id_fonction = o.id_fonction
                    WHERE o.id_enseignant = :id
                    ORDER BY o.date_occupation DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $idEnseignant]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log('FicheEnseignantService::getFonctionsOccupees: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Libellé du type d'enseignant.
     *
     * @param int $idType
     * @return string
     */
    private function getTypeEnseignantLibelle(int $idType): string
    {
        try {
            $sql = "SELECT libelle FROM type_enseignant WHERE id_type_enseignant = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $idType]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ? (string) ($row['libelle'] ?? '') : '';
        } catch (\PDOException $e) {
            error_log('FicheEnseignantService::getTypeEnseignantLibelle: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Historique des jurys : soutenances où l'enseignant a participé.
     *
     * @param string $idEnseignant
     * @return array<int, array<string, mixed>>
     */
    private function getJuryHistory(string $idEnseignant): array
    {
        try {
            $sql = "SELECT
                        ej.num_soutenance,
                        ej.id_qualite_jury,
                        qj.lib_role,
                        ps.date_soutenance,
                        ps.num_etud,
                        ps.theme_soutenance,
                        ev.note AS note_attribuee
                    FROM enseignant_jury ej
                    JOIN qualite_jury qj ON qj.id_role_jury = ej.id_qualite_jury
                    JOIN programmer_soutenance ps ON ps.num_soutenance = ej.num_soutenance
                    LEFT JOIN evaluer ev ON ev.num_etudiant = ps.num_etud AND CAST(ev.num_jury AS CHAR) = ps.num_soutenance
                    WHERE ej.id_enseignant = :id
                    ORDER BY ps.date_soutenance DESC, ps.num_soutenance ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $idEnseignant]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log('FicheEnseignantService::getJuryHistory: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Encadrements : rapports où l'enseignant est encadrant/directeur.
     *
     * @param string $idEnseignant
     * @return array<int, array<string, mixed>>
     */
    private function getEncadrements(string $idEnseignant): array
    {
        try {
            $sql = "SELECT
                        r.id_rapport,
                        r.theme_rapport,
                        r.date_redaction_rapport,
                        r.statut_rapport,
                        a.role,
                        r.num_etu,
                        CONCAT(e.nom_etu, ' ', e.prenom_etu) AS nom_etudiant
                    FROM affecter a
                    JOIN rapport_etudiants r ON r.id_rapport = a.id_rapport
                    JOIN etudiants e ON (e.num_carte_etud = r.num_etu OR e.num_ident_etud = r.num_etu)
                    WHERE a.id_enseignant = :id
                    ORDER BY r.date_redaction_rapport DESC, a.role ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $idEnseignant]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log('FicheEnseignantService::getEncadrements: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Statistiques globales pour un enseignant.
     *
     * @param string $idEnseignant
     * @return array<string, mixed>
     */
    private function getStatsEnseignant(string $idEnseignant): array
    {
        $stats = [
            'nb_soutenances' => 0,
            'nb_presidences' => 0,
            'nb_examinateurs' => 0,
            'nb_directions' => 0,
            'nb_encadrements' => 0,
            'nb_maitres_stage' => 0,
            'note_moyenne' => 0,
        ];

        try {
            // Compter les soutenances par rôle
            $sql = "SELECT
                        ej.id_qualite_jury,
                        COUNT(DISTINCT ej.num_soutenance) AS total
                    FROM enseignant_jury ej
                    WHERE ej.id_enseignant = :id
                    GROUP BY ej.id_qualite_jury";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $idEnseignant]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $totalSoutenances = 0;
            foreach ($rows as $row) {
                $role = (string) ($row['id_qualite_jury'] ?? '');
                $total = (int) ($row['total'] ?? 0);
                $totalSoutenances += $total;

                if ($role === 'PJ') {
                    $stats['nb_presidences'] = $total;
                } elseif ($role === 'EX') {
                    $stats['nb_examinateurs'] = $total;
                } elseif ($role === 'DM') {
                    $stats['nb_directions'] = $total;
                } elseif ($role === 'EN') {
                    $stats['nb_encadrements'] = $total;
                } elseif ($role === 'MS') {
                    $stats['nb_maitres_stage'] = $total;
                }
            }
            $stats['nb_soutenances'] = $totalSoutenances;

            // Note moyenne attribuée
            $sqlNote = "SELECT ROUND(AVG(ev.note), 2) AS moyenne
                        FROM enseignant_jury ej
                        JOIN programmer_soutenance ps ON ps.num_soutenance = ej.num_soutenance
                        LEFT JOIN evaluer ev ON ev.num_etudiant = ps.num_etud AND CAST(ev.num_jury AS CHAR) = ps.num_soutenance
                        WHERE ej.id_enseignant = :id";
            $stmtNote = $this->pdo->prepare($sqlNote);
            $stmtNote->execute([':id' => $idEnseignant]);
            $noteRow = $stmtNote->fetch(\PDO::FETCH_ASSOC);
            $stats['note_moyenne'] = $noteRow ? (float) ($noteRow['moyenne'] ?? 0) : 0;

            // Compter les encadrements (affecter)
            $sqlEnc = "SELECT COUNT(DISTINCT a.id_rapport) AS total
                       FROM affecter a
                       WHERE a.id_enseignant = :id";
            $stmtEnc = $this->pdo->prepare($sqlEnc);
            $stmtEnc->execute([':id' => $idEnseignant]);
            $stats['nb_encadrements_rapports'] = (int) $stmtEnc->fetchColumn();

        } catch (\PDOException $e) {
            error_log('FicheEnseignantService::getStatsEnseignant: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Compte utilisateur associé à l'enseignant.
     *
     * @param string $idEnseignant
     * @return array<string, mixed>|null
     */
    private function getCompteUtilisateur(string $idEnseignant): ?array
    {
        try {
            // On cherche l'utilisateur qui a le même login que le mail de l'enseignant
            $sql = "SELECT id_utilisateur, nom_utilisateur, login_utilisateur, statut_utilisateur,
                           id_type_utilisateur, id_GU
                    FROM utilisateur
                    WHERE login_utilisateur = :login
                    LIMIT 1";
            $stmt = $this->pdo->prepare($sql);

            // Récupérer d'abord l'email de l'enseignant
            $ensSql = "SELECT mail_enseignant, nom_enseignant, prenom_enseignant FROM enseignants WHERE id_enseignant = :id";
            $ensStmt = $this->pdo->prepare($ensSql);
            $ensStmt->execute([':id' => $idEnseignant]);
            $ens = $ensStmt->fetch(\PDO::FETCH_ASSOC);

            if (!$ens || empty($ens['mail_enseignant'])) {
                return null;
            }

            $stmt->execute([':login' => $ens['mail_enseignant']]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                // Essayer aussi avec une recherche par nom/prénom
                $sql2 = "SELECT id_utilisateur, nom_utilisateur, login_utilisateur, statut_utilisateur,
                                id_type_utilisateur, id_GU
                         FROM utilisateur
                         WHERE nom_utilisateur LIKE :nom
                         LIMIT 1";
                $stmt2 = $this->pdo->prepare($sql2);
                $nomPattern = '%' . ($ens['nom_enseignant'] ?? '') . '%';
                $stmt2->execute([':nom' => $nomPattern]);
                $user = $stmt2->fetch(\PDO::FETCH_ASSOC);
            }

            return $user ?: null;
        } catch (\PDOException $e) {
            error_log('FicheEnseignantService::getCompteUtilisateur: ' . $e->getMessage());
            return null;
        }
    }
}
