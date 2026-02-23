<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/AuditLog.php';

use Etudiant;
use AuditLog;

/**
 * Service métier de la gestion des étudiants
 *
 * Contient toute la logique métier pour :
 * - Génération de numéros étudiants
 * - Récupération des niveaux d'étude et années académiques
 * - Ajout, modification et suppression d'étudiants
 * - Filtrage et pagination de la liste des étudiants
 */
class GestionEtudiantService
{
    /** @var Etudiant */
    private $etudiant;

    /** @var AuditLog */
    private $auditLog;

    /** @var \PDO */
    private $db;

    /**
     * @param \PDO $db Connexion à la base de données
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->etudiant = new Etudiant($db);
        $this->auditLog = new AuditLog($db);
    }

    private function niveauExists(int $idNiveau): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM niveau_etude WHERE id_niv_etude = ?");
        $stmt->execute([$idNiveau]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function anneeExists(int $idAnnee): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM annee_academique WHERE id_annee_acad = ?");
        $stmt->execute([$idAnnee]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @return array{valid:bool,value:int|null,message:string}
     */
    private function resolveNiveauId($raw): array
    {
        $raw = is_string($raw) ? trim($raw) : $raw;
        if ($raw === null || $raw === '') {
            return ['valid' => true, 'value' => null, 'message' => ''];
        }

        if (is_numeric($raw)) {
            $id = (int) $raw;
            if ($id > 0 && $this->niveauExists($id)) {
                return ['valid' => true, 'value' => $id, 'message' => ''];
            }
            return ['valid' => false, 'value' => null, 'message' => "Niveau invalide. Veuillez selectionner un niveau existant."];
        }

        $stmt = $this->db->prepare("SELECT id_niv_etude FROM niveau_etude WHERE LOWER(lib_niv_etude) = LOWER(?) LIMIT 1");
        $stmt->execute([(string) $raw]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return ['valid' => true, 'value' => (int) $id, 'message' => ''];
        }

        return ['valid' => false, 'value' => null, 'message' => "Niveau invalide. Veuillez selectionner un niveau existant."];
    }

    /**
     * @return array{valid:bool,value:int|null,message:string}
     */
    private function resolveAnneeId($raw): array
    {
        $raw = is_string($raw) ? trim($raw) : $raw;
        if ($raw === null || $raw === '') {
            return ['valid' => true, 'value' => null, 'message' => ''];
        }

        if (is_numeric($raw)) {
            $id = (int) $raw;
            if ($id > 0 && $this->anneeExists($id)) {
                return ['valid' => true, 'value' => $id, 'message' => ''];
            }
            return ['valid' => false, 'value' => null, 'message' => "Annee academique invalide."];
        }

        if (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', (string) $raw, $m)) {
            $stmt = $this->db->prepare("
                SELECT id_annee_acad
                FROM annee_academique
                WHERE YEAR(date_deb) = ? AND YEAR(date_fin) = ?
                LIMIT 1
            ");
            $stmt->execute([(int) $m[1], (int) $m[2]]);
            $id = $stmt->fetchColumn();
            if ($id !== false) {
                return ['valid' => true, 'value' => (int) $id, 'message' => ''];
            }
        }

        return ['valid' => false, 'value' => null, 'message' => "Annee academique invalide."];
    }

    /**
     * Génère un numéro d'étudiant unique basé sur la promotion
     *
     * @param string $promotion_etu Promotion (ex: "2023-2024")
     * @return string Numéro étudiant généré (ex: "20230001")
     */
    public function genererNumeroEtudiant(string $promotion_etu): string
    {
        $annee = explode('-', $promotion_etu)[0];

        $query = "SELECT MAX(CAST(SUBSTRING(num_carte_etud, 5) AS UNSIGNED)) as max_num FROM etudiants WHERE num_carte_etud LIKE :prefix";
        $stmt = $this->db->prepare($query);
        $prefix = $annee . '%';
        $stmt->bindParam(':prefix', $prefix);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_OBJ);

        $next_num = ($result->max_num ?? 0) + 1;
        return $annee . str_pad($next_num, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Récupère la liste des niveaux d'étude
     *
     * @return array Liste des niveaux d'étude
     */
    public function getNiveauxEtude(): array
    {
        try {
            $query = "SELECT id_niv_etude, lib_niv_etude FROM niveau_etude ORDER BY lib_niv_etude";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des niveaux : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la liste des années académiques
     *
     * @return array Liste des années académiques
     */
    public function getAnneesAcademiques(): array
    {
        try {
            $query = "SELECT id_annee_acad, date_deb, date_fin FROM annee_academique ORDER BY date_deb DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des années académiques : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère un étudiant par son identifiant
     *
     * @param string $numEtu Numéro de carte étudiant
     * @return object|null L'étudiant ou null
     */
    public function getEtudiantById(string $numEtu)
    {
        return $this->etudiant->getEtudiantById($numEtu);
    }

    /**
     * Récupère tous les étudiants
     *
     * @return array Liste de tous les étudiants
     */
    public function getAllEtudiants(): array
    {
        return $this->etudiant->getAllEtudiants();
    }

    /**
     * Ajoute un nouvel étudiant
     *
     * @param array $data Données de l'étudiant
     * @param int $idUtilisateur ID de l'utilisateur effectuant l'action
     * @return array Résultat ['success' => bool, 'message' => string]
     */
    public function ajouterEtudiant(array $data, int $idUtilisateur): array
    {
        // Validation des champs obligatoires
        if (
            empty($data['num_etu']) || empty($data['nom_etu']) ||
            empty($data['prenom_etu']) || empty($data['date_naiss_etu']) ||
            empty($data['genre_etu']) || empty($data['email_etu'])
        ) {
            return [
                'success' => false,
                'message' => "Les champs N° Étudiant, Nom, Prénom, Date de naissance, Genre et Email sont obligatoires."
            ];
        }

        $num_etu = trim($data['num_etu']);

        // Vérifier si le numéro étudiant existe déjà
        $existingStudent = $this->etudiant->getEtudiantById($num_etu);
        if ($existingStudent) {
            return [
                'success' => false,
                'message' => "Ce numéro étudiant existe déjà. Veuillez en choisir un autre."
            ];
        }

        $promotion_etu = !empty($data['promotion_etu']) ? $data['promotion_etu'] : date('Y') . '-' . (date('Y') + 1);
        $nom_etu = trim($data['nom_etu']);
        $prenom_etu = trim($data['prenom_etu']);
        $date_naiss_etu = $data['date_naiss_etu'];
        $genre_etu = $data['genre_etu'];
        $email_etu = trim($data['email_etu']);
        $niveauResolved = $this->resolveNiveauId($data['id_niveau'] ?? null);
        if (!$niveauResolved['valid']) {
            return ['success' => false, 'message' => $niveauResolved['message']];
        }
        $id_niveau = $niveauResolved['value'];

        $anneeResolved = $this->resolveAnneeId($data['id_annee_acad'] ?? null);
        if (!$anneeResolved['valid']) {
            return ['success' => false, 'message' => $anneeResolved['message']];
        }
        $id_annee_acad = $anneeResolved['value'];
        $identifiant_mesrs = !empty($data['identifiant_mesrs']) ? trim($data['identifiant_mesrs']) : null;

        // Validation de l'email
        if (!filter_var($email_etu, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => "L'adresse email n'est pas valide."
            ];
        }

        if ($this->etudiant->ajouterEtudiant($num_etu, $nom_etu, $prenom_etu, $date_naiss_etu, $genre_etu, $email_etu, $promotion_etu, $id_niveau, $id_annee_acad, $identifiant_mesrs)) {
            $this->auditLog->logCreation($idUtilisateur, "etudiants", "Succès");
            return [
                'success' => true,
                'message' => "Étudiant ajouté avec succès. Numéro étudiant : " . $num_etu
            ];
        } else {
            $this->auditLog->logCreation($idUtilisateur, "etudiants", "Erreur");
            return [
                'success' => false,
                'message' => "Erreur lors de l'ajout de l'étudiant."
            ];
        }
    }

    /**
     * Modifie un étudiant existant
     *
     * @param array $data Données de modification
     * @param int $idUtilisateur ID de l'utilisateur effectuant l'action
     * @return array Résultat ['success' => bool, 'message' => string]
     */
    public function modifierEtudiant(array $data, int $idUtilisateur): array
    {
        // Validation des champs obligatoires
        if (
            empty($data['old_num_etu']) || empty($data['num_etu']) || empty($data['nom_etu']) ||
            empty($data['prenom_etu']) || empty($data['date_naiss_etu']) ||
            empty($data['genre_etu']) || empty($data['email_etu'])
        ) {
            return [
                'success' => false,
                'message' => "Les champs Nom, Prénom, Date de naissance, Genre et Email sont obligatoires."
            ];
        }

        $old_num_etu = trim($data['old_num_etu']);
        $num_etu = trim($data['num_etu']);

        // Si le numéro a changé, vérifier qu'il n'existe pas déjà
        if ($old_num_etu !== $num_etu) {
            $existingStudent = $this->etudiant->getEtudiantById($num_etu);
            if ($existingStudent) {
                return [
                    'success' => false,
                    'message' => "Ce numéro étudiant existe déjà. Veuillez en choisir un autre."
                ];
            }
        }

        $nom_etu = trim($data['nom_etu']);
        $prenom_etu = trim($data['prenom_etu']);
        $date_naiss_etu = $data['date_naiss_etu'];
        $genre_etu = $data['genre_etu'];
        $email_etu = trim($data['email_etu']);
        $promotion_etu = !empty($data['promotion_etu']) ? $data['promotion_etu'] : null;
        $niveauResolved = $this->resolveNiveauId($data['id_niveau'] ?? null);
        if (!$niveauResolved['valid']) {
            return ['success' => false, 'message' => $niveauResolved['message']];
        }
        $id_niveau = $niveauResolved['value'];

        $anneeResolved = $this->resolveAnneeId($data['id_annee_acad'] ?? null);
        if (!$anneeResolved['valid']) {
            return ['success' => false, 'message' => $anneeResolved['message']];
        }
        $id_annee_acad = $anneeResolved['value'];
        $identifiant_mesrs = !empty($data['num_ident_etud']) ? trim($data['num_ident_etud']) : null;

        // Validation de l'email
        if (!filter_var($email_etu, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => "L'adresse email n'est pas valide."
            ];
        }

        // Récupérer les anciennes données pour l'audit
        $ancienEtudiant = $this->etudiant->getEtudiantById($old_num_etu);
        $anciennesDonnees = $ancienEtudiant ? [
            'nom_etu' => $ancienEtudiant->nom_etu,
            'prenom_etu' => $ancienEtudiant->prenom_etu,
            'date_naiss_etu' => $ancienEtudiant->date_naiss_etu,
            'genre_etu' => $ancienEtudiant->genre_etu,
            'email_etu' => $ancienEtudiant->email_etu,
            'promotion_etu' => $ancienEtudiant->promotion_etu,
            'id_niveau' => $ancienEtudiant->id_niveau ?? null,
            'id_annee_acad' => $ancienEtudiant->id_annee_acad ?? null,
            'identifiant_mesrs' => $ancienEtudiant->identifiant_mesrs ?? null
        ] : null;

        $nouvellesDonnees = [
            'nom_etu' => $nom_etu,
            'prenom_etu' => $prenom_etu,
            'date_naiss_etu' => $date_naiss_etu,
            'genre_etu' => $genre_etu,
            'email_etu' => $email_etu,
            'promotion_etu' => $promotion_etu,
            'id_niveau' => $id_niveau,
            'id_annee_acad' => $id_annee_acad,
            'identifiant_mesrs' => $identifiant_mesrs
        ];

        if ($this->etudiant->modifierEtudiant($old_num_etu, $num_etu, $nom_etu, $prenom_etu, $date_naiss_etu, $genre_etu, $email_etu, $promotion_etu, $id_niveau, $id_annee_acad, $identifiant_mesrs)) {
            $this->auditLog->logModification($idUtilisateur, 'etudiants', 'Succès');
            return [
                'success' => true,
                'message' => "Étudiant modifié avec succès."
            ];
        } else {
            $this->auditLog->logModification($idUtilisateur, 'etudiants', 'Erreur');
            return [
                'success' => false,
                'message' => "Erreur lors de la modification de l'étudiant."
            ];
        }
    }

    /**
     * Supprime une liste d'étudiants
     *
     * @param array $selectedIds Numéros étudiants à supprimer
     * @param int $idUtilisateur ID de l'utilisateur effectuant l'action
     * @return array Résultat ['success' => bool, 'message' => string]
     */
    public function supprimerEtudiants(array $selectedIds, int $idUtilisateur): array
    {
        $success = true;
        $etudiantsSupprimes = [];

        foreach ($selectedIds as $num_etu) {
            $etudiant = $this->etudiant->getEtudiantById($num_etu);
            if ($etudiant) {
                $etudiantsSupprimes[] = "{$etudiant->nom_etu} {$etudiant->prenom_etu} ($num_etu)";
            }

            if (!$this->etudiant->supprimerEtudiant($num_etu)) {
                $success = false;
                break;
            }
        }

        if ($success) {
            foreach ($selectedIds as $num_etu) {
                $this->auditLog->logSuppression($idUtilisateur, 'etudiants', 'Succès');
            }
            return [
                'success' => true,
                'message' => "Étudiants supprimés avec succès."
            ];
        } else {
            return [
                'success' => false,
                'message' => "Erreur lors de la suppression des étudiants."
            ];
        }
    }

    /**
     * Filtre les étudiants par terme de recherche
     *
     * @param array $listeEtudiants Liste complète des étudiants
     * @param string $searchTerm Terme de recherche
     * @return array Liste filtrée
     */
    public function filtrerEtudiants(array $listeEtudiants, string $searchTerm): array
    {
        if (empty($searchTerm)) {
            return $listeEtudiants;
        }

        $filtered = array_filter($listeEtudiants, function ($etudiant) use ($searchTerm) {
            $term = strtolower($searchTerm);
            return strpos(strtolower($etudiant->nom_etu), $term) !== false ||
                strpos(strtolower($etudiant->prenom_etu), $term) !== false;
        });

        return array_values($filtered);
    }

    /**
     * Pagine une liste d'étudiants
     *
     * @param array $listeEtudiants Liste complète
     * @param int $currentPage Page courante
     * @param int $itemsPerPage Éléments par page
     * @return array Données de pagination
     */
    public function paginer(array $listeEtudiants, int $currentPage, int $itemsPerPage): array
    {
        $totalItems = count($listeEtudiants);
        $totalPages = $totalItems > 0 ? ceil($totalItems / $itemsPerPage) : 0;

        if ($currentPage < 1) {
            $currentPage = 1;
        } elseif ($currentPage > $totalPages && $totalPages > 0) {
            $currentPage = $totalPages;
        }

        $startIndex = ($currentPage - 1) * $itemsPerPage;
        $endIndex = min($startIndex + $itemsPerPage, $totalItems);
        $currentPageItems = array_slice($listeEtudiants, $startIndex, $itemsPerPage);

        return [
            'items' => $currentPageItems,
            'currentPage' => $currentPage,
            'totalPages' => (int) $totalPages,
            'totalItems' => $totalItems,
            'startIndex' => $startIndex,
            'endIndex' => $endIndex,
            'itemsPerPage' => $itemsPerPage
        ];
    }
}
