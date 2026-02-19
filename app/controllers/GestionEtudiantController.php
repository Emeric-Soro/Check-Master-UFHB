<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Etudiant.php";
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../Core/Autoload.php';

use CheckMaster\Core\Session;


class GestionEtudiantController
{
    private $etudiant;
    private $baseViewPath;
    private $db;
    private $auditLog;

    public function __construct()
    {
        Session::start();

        $this->baseViewPath = __DIR__ . '/../../ressources/views/';
        $this->db = Database::getConnection();
        $this->etudiant = new Etudiant($this->db);
        $this->auditLog = new AuditLog($this->db);

    }

    private function genererNumeroEtudiant($promotion_etu)
    {
        // Extraire l'année de début de la promotion (ex: "2023-2024" -> "2023")
        $annee = explode('-', $promotion_etu)[0];

        // Rechercher le dernier numéro pour cette promotion
        $query = "SELECT MAX(CAST(SUBSTRING(num_carte_etud, 5) AS UNSIGNED)) as max_num FROM etudiants WHERE num_carte_etud LIKE :prefix";
        $stmt = $this->db->prepare($query);
        $prefix = $annee . '%';
        $stmt->bindParam(':prefix', $prefix);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        $next_num = ($result->max_num ?? 0) + 1;
        return $annee . str_pad($next_num, 4, '0', STR_PAD_LEFT);
    }

    private function getNiveauxEtude()
    {
        try {
            $query = "SELECT id_niv_etude, lib_niv_etude FROM niveau_etude ORDER BY lib_niv_etude";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des niveaux : " . $e->getMessage());
            return [];
        }
    }

    private function getAnneesAcademiques()
    {
        try {
            $query = "SELECT id_annee_acad, date_deb, date_fin FROM annee_academique ORDER BY date_deb DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des années académiques : " . $e->getMessage());
            return [];
        }
    }

    public function index()
    {
        try {
            $currentPage = isset($_GET['p']) ? (int) $_GET['p'] : 1;
            $itemsPerPage = 10;
            $etudiant_a_modifier = null;
            $modalAction = '';
            $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

            // Charger la liste des niveaux d'étude
            $listeNiveaux = $this->getNiveauxEtude();

            // Charger la liste des années académiques
            $listeAnneesAcad = $this->getAnneesAcademiques();

            // Charger les données de l'étudiant à modifier si num_etu est présent
            if (isset($_GET['num_etu']) && !empty($_GET['num_etu'])) {
                $etudiant_a_modifier = $this->etudiant->getEtudiantById($_GET['num_etu']);
                if (!$etudiant_a_modifier) {
                    $GLOBALS['messageErreur'] = "Étudiant non trouvé.";
                }
            }

            // Enregistrer la consultation de la liste des étudiants
            // Gestion des actions GET pour les modales
            if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'edit' && isset($_GET['num_etu'])) {
                // Cette partie n'est plus nécessaire car on charge directement avec num_etu
                // On la garde pour compatibilité rétroactive
                $etudiant_a_modifier = $this->etudiant->getEtudiantById($_GET['num_etu']);
                if (!$etudiant_a_modifier) {
                    $GLOBALS['messageErreur'] = "Étudiant non trouvé.";
                } else {
                    $modalAction = 'edit';
                    // Enregistrer la consultation d'un étudiant spécifique

                    // Si c'est une requête AJAX, renvoyer les données en JSON
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'num_etu' => $etudiant_a_modifier->num_carte_etud,
                            'nom_etu' => $etudiant_a_modifier->nom_etu,
                            'prenom_etu' => $etudiant_a_modifier->prenom_etu,
                            'date_naiss_etu' => $etudiant_a_modifier->date_naiss_etu,
                            'genre_etu' => $etudiant_a_modifier->genre_etu,
                            'email_etu' => $etudiant_a_modifier->email_etu,
                            'promotion_etu' => $etudiant_a_modifier->promotion_etu
                        ]);
                        exit;
                    }
                }
            }

            // Gestion des actions POST
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Ajout d'un nouvel étudiant
                if (isset($_POST['submit_add_etudiant'])) {
                    // Validation des champs
                    if (
                        empty($_POST['num_etu']) || empty($_POST['nom_etu']) ||
                        empty($_POST['prenom_etu']) || empty($_POST['date_naiss_etu']) ||
                        empty($_POST['genre_etu']) || empty($_POST['email_etu'])
                    ) {
                        $GLOBALS['messageErreur'] = "Les champs N° Étudiant, Nom, Prénom, Date de naissance, Genre et Email sont obligatoires.";
                        return;
                    }

                    // Récupérer le numéro étudiant saisi
                    $num_etu = trim($_POST['num_etu']);

                    // Vérifier si le numéro étudiant existe déjà
                    $existingStudent = $this->etudiant->getEtudiantById($num_etu);
                    if ($existingStudent) {
                        $GLOBALS['messageErreur'] = "Ce numéro étudiant existe déjà. Veuillez en choisir un autre.";
                        return;
                    }

                    $promotion_etu = !empty($_POST['promotion_etu']) ? $_POST['promotion_etu'] : date('Y') . '-' . (date('Y') + 1);
                    $nom_etu = trim($_POST['nom_etu']);
                    $prenom_etu = trim($_POST['prenom_etu']);
                    $date_naiss_etu = $_POST['date_naiss_etu'];
                    $genre_etu = $_POST['genre_etu'];
                    $email_etu = trim($_POST['email_etu']);
                    $id_niveau = !empty($_POST['id_niveau']) ? (int) $_POST['id_niveau'] : null;
                    $id_annee_acad = !empty($_POST['id_annee_acad']) ? (int) $_POST['id_annee_acad'] : null;
                    $identifiant_mesrs = !empty($_POST['identifiant_mesrs']) ? trim($_POST['identifiant_mesrs']) : null;

                    // Validation de l'email
                    if (!filter_var($email_etu, FILTER_VALIDATE_EMAIL)) {
                        $GLOBALS['messageErreur'] = "L'adresse email n'est pas valide.";
                        return;
                    }

                    if ($this->etudiant->ajouterEtudiant($num_etu, $nom_etu, $prenom_etu, $date_naiss_etu, $genre_etu, $email_etu, $promotion_etu, $id_niveau, $id_annee_acad, $identifiant_mesrs)) {
                        $GLOBALS['messageSuccess'] = "Étudiant ajouté avec succès. Numéro étudiant : " . $num_etu;

                        // Enregistrer la création de l'étudiant
                        $details = "Création de l'étudiant: $nom_etu $prenom_etu (Numéro: $num_etu, Email: $email_etu, Promotion: $promotion_etu)";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], "etudiants", "Succès");
                    } else {
                        $GLOBALS['messageErreur'] = "Erreur lors de l'ajout de l'étudiant.";

                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], "etudiants", "Erreur");

                    }
                }

                // Modification d'un étudiant
                if (isset($_POST['submit_modifier_etudiant'])) {
                    if (
                        empty($_POST['old_num_etu']) || empty($_POST['num_etu']) || empty($_POST['nom_etu']) ||
                        empty($_POST['prenom_etu']) || empty($_POST['date_naiss_etu']) ||
                        empty($_POST['genre_etu']) || empty($_POST['email_etu'])
                    ) {
                        $GLOBALS['messageErreur'] = "Les champs Nom, Prénom, Date de naissance, Genre et Email sont obligatoires.";
                        return;
                    }

                    $old_num_etu = trim($_POST['old_num_etu']);
                    $num_etu = trim($_POST['num_etu']);

                    // Si le numéro a changé, vérifier qu'il n'existe pas déjà
                    if ($old_num_etu !== $num_etu) {
                        $existingStudent = $this->etudiant->getEtudiantById($num_etu);
                        if ($existingStudent) {
                            $GLOBALS['messageErreur'] = "Ce numéro étudiant existe déjà. Veuillez en choisir un autre.";
                            return;
                        }
                    }

                    $nom_etu = trim($_POST['nom_etu']);
                    $prenom_etu = trim($_POST['prenom_etu']);
                    $date_naiss_etu = $_POST['date_naiss_etu'];
                    $genre_etu = $_POST['genre_etu'];
                    $email_etu = trim($_POST['email_etu']);
                    $promotion_etu = !empty($_POST['promotion_etu']) ? $_POST['promotion_etu'] : null;
                    $id_niveau = !empty($_POST['id_niveau']) ? (int) $_POST['id_niveau'] : null;
                    $id_annee_acad = !empty($_POST['id_annee_acad']) ? (int) $_POST['id_annee_acad'] : null;
                    $identifiant_mesrs = !empty($_POST['identifiant_mesrs']) ? trim($_POST['identifiant_mesrs']) : null;

                    // Validation de l'email
                    if (!filter_var($email_etu, FILTER_VALIDATE_EMAIL)) {
                        $GLOBALS['messageErreur'] = "L'adresse email n'est pas valide.";

                        return;
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
                        $GLOBALS['messageSuccess'] = "Étudiant modifié avec succès.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'etudiants', 'Succès');
                    } else {
                        $GLOBALS['messageErreur'] = "Erreur lors de la modification de l'étudiant.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'etudiants', 'Erreur');


                    }
                }

                // Suppression d'étudiants
                if (isset($_POST['selected_ids']) && !empty($_POST['selected_ids'])) {
                    $success = true;
                    $etudiantsSupprimes = [];

                    foreach ($_POST['selected_ids'] as $num_etu) {
                        // Récupérer les informations de l'étudiant avant suppression
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
                        $GLOBALS['messageSuccess'] = "Étudiants supprimés avec succès.";

                        foreach ($_POST['selected_ids'] as $num_etu) {
                            $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'etudiants', 'Succès');
                        }
                    } else {
                        $GLOBALS['messageErreur'] = "Erreur lors de la suppression des étudiants.";

                    }
                }
            }

            // Récupération des données pour l'affichage
            $listeEtudiants = $this->etudiant->getAllEtudiants();

            // Filtrer les étudiants si un terme de recherche est présent
            if (!empty($searchTerm)) {
                $listeEtudiants = array_filter($listeEtudiants, function ($etudiant) use ($searchTerm) {
                    $searchTerm = strtolower($searchTerm);
                    return strpos(strtolower($etudiant->nom_etu), $searchTerm) !== false ||
                        strpos(strtolower($etudiant->prenom_etu), $searchTerm) !== false;
                });


            }

            // Convertir le résultat en tableau indexé
            $listeEtudiants = array_values($listeEtudiants);

            $totalItems = count($listeEtudiants);
            $totalPages = ceil($totalItems / $itemsPerPage);

            // Validation de la page courante
            if ($currentPage < 1) {
                $currentPage = 1;
            } elseif ($currentPage > $totalPages && $totalPages > 0) {
                $currentPage = $totalPages;
            }

            $startIndex = ($currentPage - 1) * $itemsPerPage;
            $endIndex = min($startIndex + $itemsPerPage, $totalItems);

            // Récupérer les étudiants pour la page courante
            $currentPageItems = array_slice($listeEtudiants, $startIndex, $itemsPerPage);

            // Préparation des données pour la vue
            $GLOBALS['listeEtudiants'] = $currentPageItems;
            $GLOBALS['allEtudiants'] = $listeEtudiants;
            $GLOBALS['etudiant_a_modifier'] = $etudiant_a_modifier;
            $GLOBALS['modalAction'] = $modalAction;
            $GLOBALS['currentPage'] = $currentPage;
            $GLOBALS['totalPages'] = $totalPages;
            $GLOBALS['totalItems'] = $totalItems;
            $GLOBALS['startIndex'] = $startIndex;
            $GLOBALS['endIndex'] = $endIndex;
            $GLOBALS['itemsPerPage'] = $itemsPerPage;
            $GLOBALS['searchTerm'] = $searchTerm;
            $GLOBALS['listeNiveaux'] = $listeNiveaux;
            $GLOBALS['listeAnneesAcad'] = $listeAnneesAcad;

        } catch (Exception $e) {
            error_log("Erreur dans GestionEtudiantController::index : " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue. Veuillez réessayer.";
        }
    }
}