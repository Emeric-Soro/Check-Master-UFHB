<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Etudiant.php";
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/permissions.php';


class GestionEtudiantController
{
    private $etudiant;
    private $baseViewPath;
    private $db;
    private $auditLog;

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->baseViewPath = __DIR__ . '/../../ressources/views/';
        $this->db = Database::getConnection();
        $this->etudiant = new Etudiant($this->db);
        $this->auditLog = new AuditLog($this->db);
      
    }

    private function genererNumeroEtudiant($promotion_etu) {
        // Extraire l'année de début de la promotion (ex: "2023-2024" -> "2023")
        $annee = explode('-', $promotion_etu)[0];
        
        // Rechercher le dernier numéro pour cette promotion
        $query = "SELECT MAX(CAST(SUBSTRING(num_etu, 5) AS UNSIGNED)) as max_num FROM etudiants WHERE num_etu LIKE :prefix";
        $stmt = $this->db->prepare($query);
        $prefix = $annee . '%';
        $stmt->bindParam(':prefix', $prefix);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        
        $next_num = ($result->max_num ?? 0) + 1;
        return $annee . str_pad($next_num, 4, '0', STR_PAD_LEFT);
    }

    public function index()
    {
        // Préparer les données pour la vue
        $data = [
            'messageErreur' => '',
            'messageSuccess' => '',
            'etudiant_a_modifier' => null,
            'modalAction' => '',
            'searchTerm' => ''
        ];
        
        try {
            $currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
            $itemsPerPage = 10;
            $data['searchTerm'] = isset($_GET['search']) ? trim($_GET['search']) : '';

            // Enregistrer la consultation de la liste des étudiants
           

            // Gestion des actions GET pour les modales
            if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'edit' && isset($_GET['num_etu'])) {
                // Vérifier la permission UPDATE pour afficher le formulaire de modification
                if (!hasPermission('gestion_etudiants', 'UPDATE')) {
                    $data['messageErreur'] = "Vous n'avez pas la permission de modifier les étudiants.";
                } else {
                    $data['etudiant_a_modifier'] = $this->etudiant->getEtudiantById($_GET['num_etu']);
                    if (!$data['etudiant_a_modifier']) {
                        $data['messageErreur'] = "Étudiant non trouvé.";
                       
                    } else {
                        $data['modalAction'] = 'edit';
                    // Enregistrer la consultation d'un étudiant spécifique
                   
                    
                    // Si c'est une requête AJAX, renvoyer les données en JSON
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'num_etu' => $data['etudiant_a_modifier']->num_etu,
                            'nom_etu' => $data['etudiant_a_modifier']->nom_etu,
                            'prenom_etu' => $data['etudiant_a_modifier']->prenom_etu,
                            'date_naiss_etu' => $data['etudiant_a_modifier']->date_naiss_etu,
                            'genre_etu' => $data['etudiant_a_modifier']->genre_etu,
                            'email_etu' => $data['etudiant_a_modifier']->email_etu,
                            'promotion_etu' => $data['etudiant_a_modifier']->promotion_etu
                        ]);
                        exit;
                    }
                }
            }
            }

            // Gestion des actions POST
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Ajout d'un nouvel étudiant
                if (isset($_POST['submit_add_etudiant'])) {
                    // Vérifier la permission CREATE
                    if (!hasPermission('gestion_etudiants', 'CREATE')) {
                        $data['messageErreur'] = "Vous n'avez pas la permission d'ajouter des étudiants.";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'etudiants', 'Erreur - Permission refusée');
                        return $data;
                    }
                    
                    // Validation des champs
                    if (empty($_POST['nom_etu']) || empty($_POST['prenom_etu']) || 
                        empty($_POST['date_naiss_etu']) || empty($_POST['genre_etu']) || 
                        empty($_POST['email_etu']) || empty($_POST['promotion_etu'])) {
                        $data['messageErreur'] = "Tous les champs sont obligatoires.";
                       
                        return $data;
                    }

                    $nom_etu = trim($_POST['nom_etu']);
                    $prenom_etu = trim($_POST['prenom_etu']);
                    $date_naiss_etu = $_POST['date_naiss_etu'];
                    $genre_etu = $_POST['genre_etu'];
                    $email_etu = trim($_POST['email_etu']);
                    $promotion_etu = $_POST['promotion_etu'];
                    
                    // Strict validation
                    if (!preg_match('/^[a-zA-ZÀ-ÿ\s\'-]{2,50}$/u', $nom_etu)) {
                        $data['messageErreur'] = "Le nom n'est pas valide (2-50 caractères alphabétiques).";
                        return $data;
                    }
                    
                    if (!preg_match('/^[a-zA-ZÀ-ÿ\s\'-]{2,50}$/u', $prenom_etu)) {
                        $data['messageErreur'] = "Le prénom n'est pas valide (2-50 caractères alphabétiques).";
                        return $data;
                    }
                    
                    if (!in_array($genre_etu, ['M', 'F', 'Autre'], true)) {
                        $data['messageErreur'] = "Le genre n'est pas valide.";
                        return $data;
                    }
                    
                    if (!preg_match('/^\d{4}-\d{4}$/', $promotion_etu)) {
                        $data['messageErreur'] = "Le format de promotion n'est pas valide (AAAA-AAAA).";
                        return $data;
                    }

                    $num_etu = $this->genererNumeroEtudiant($promotion_etu);

                    // Validation de l'email
                    if (!filter_var($email_etu, FILTER_VALIDATE_EMAIL)) {
                        $data['messageErreur'] = "L'adresse email n'est pas valide.";
                        
                        return $data;
                    }

                    if ($this->etudiant->ajouterEtudiant($num_etu, $nom_etu, $prenom_etu, $date_naiss_etu, $genre_etu, $email_etu, $promotion_etu)) {
                        $data['messageSuccess'] = "Étudiant ajouté avec succès. Numéro étudiant : " . $num_etu;
                        
                        // Enregistrer la création de l'étudiant
                        $details = "Création de l'étudiant: $nom_etu $prenom_etu (Numéro: $num_etu, Email: $email_etu, Promotion: $promotion_etu)";
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'],"etudiants","Succès");
                    } else {
                        $data['messageErreur'] = "Erreur lors de l'ajout de l'étudiant.";
                        
                        $this->auditLog->logCreation($_SESSION['id_utilisateur'],"etudiants","Erreur");

                    }
                }

                // Modification d'un étudiant
                if (isset($_POST['submit_modifier_etudiant'])) {
                    // Vérifier la permission UPDATE
                    if (!hasPermission('gestion_etudiants', 'UPDATE')) {
                        $data['messageErreur'] = "Vous n'avez pas la permission de modifier les étudiants.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'etudiants', 'Erreur - Permission refusée');
                        return $data;
                    }
                    
                    if (empty($_POST['num_etu']) || empty($_POST['nom_etu']) || 
                        empty($_POST['prenom_etu']) || empty($_POST['date_naiss_etu']) || 
                        empty($_POST['genre_etu']) || empty($_POST['email_etu']) || 
                        empty($_POST['promotion_etu'])) {
                        $data['messageErreur'] = "Tous les champs sont obligatoires.";
                        return $data;
                    }

                    $num_etu = $_POST['num_etu'];
                    $nom_etu = trim($_POST['nom_etu']);
                    $prenom_etu = trim($_POST['prenom_etu']);
                    $date_naiss_etu = $_POST['date_naiss_etu'];
                    $genre_etu = $_POST['genre_etu'];
                    $email_etu = trim($_POST['email_etu']);
                    $promotion_etu = $_POST['promotion_etu'];

                    // Validation de l'email
                    if (!filter_var($email_etu, FILTER_VALIDATE_EMAIL)) {
                        $data['messageErreur'] = "L'adresse email n'est pas valide.";
                      
                        return $data;
                    }

                    // Récupérer les anciennes données pour l'audit
                    $ancienEtudiant = $this->etudiant->getEtudiantById($num_etu);
                    $anciennesDonnees = $ancienEtudiant ? [
                        'nom_etu' => $ancienEtudiant->nom_etu,
                        'prenom_etu' => $ancienEtudiant->prenom_etu,
                        'date_naiss_etu' => $ancienEtudiant->date_naiss_etu,
                        'genre_etu' => $ancienEtudiant->genre_etu,
                        'email_etu' => $ancienEtudiant->email_etu,
                        'promotion_etu' => $ancienEtudiant->promotion_etu
                    ] : null;

                    $nouvellesDonnees = [
                        'nom_etu' => $nom_etu,
                        'prenom_etu' => $prenom_etu,
                        'date_naiss_etu' => $date_naiss_etu,
                        'genre_etu' => $genre_etu,
                        'email_etu' => $email_etu,
                        'promotion_etu' => $promotion_etu
                    ];

                    if ($this->etudiant->modifierEtudiant($num_etu, $nom_etu, $prenom_etu, $date_naiss_etu, $genre_etu, $email_etu, $promotion_etu)) {
                        $data['messageSuccess'] = "Étudiant modifié avec succès.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'etudiants', 'Succès');
                    } else {
                        $data['messageErreur'] = "Erreur lors de la modification de l'étudiant.";
                        $this->auditLog->logModification($_SESSION['id_utilisateur'], 'etudiants', 'Erreur');

                       
                    }
                }

                // Suppression d'étudiants
                if (isset($_POST['selected_ids']) && !empty($_POST['selected_ids'])) {
                    // Vérifier la permission DELETE
                    if (!hasPermission('gestion_etudiants', 'DELETE')) {
                        $data['messageErreur'] = "Vous n'avez pas la permission de supprimer des étudiants.";
                        $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'etudiants', 'Erreur - Permission refusée');
                        return $data;
                    }
                    
                    // Validate selected IDs format
                    $validIds = array_filter($_POST['selected_ids'], function($id) {
                        return preg_match('/^\d+$/', $id);
                    });
                    
                    if (empty($validIds)) {
                        $data['messageErreur'] = "Aucun étudiant valide sélectionné.";
                        return $data;
                    }
                    
                    $success = true;
                    $etudiantsSupprimes = [];
                    
                    // Retrieve all students info in one query (optimize N+1)
                    $placeholders = str_repeat('?,', count($validIds) - 1) . '?';
                    $stmt = $this->db->prepare("SELECT num_etu, nom_etu, prenom_etu FROM etudiants WHERE num_etu IN ($placeholders)");
                    $stmt->execute($validIds);
                    $etudiants = $stmt->fetchAll(PDO::FETCH_OBJ);
                    
                    foreach ($etudiants as $etudiant) {
                        $etudiantsSupprimes[] = "{$etudiant->nom_etu} {$etudiant->prenom_etu} ({$etudiant->num_etu})";
                        
                        if (!$this->etudiant->supprimerEtudiant($etudiant->num_etu)) {
                            $success = false;
                            break;
                        }
                    }
                    
                    if ($success) {
                        $data['messageSuccess'] = "Étudiants supprimés avec succès.";
                        $this->auditLog->logSuppression($_SESSION['id_utilisateur'], 'etudiants', 'Succès - ' . count($validIds) . ' étudiants');
                    } else {
                        $data['messageErreur'] = "Erreur lors de la suppression des étudiants.";
                       
                    }
                }
            }

            // Récupération des données pour l'affichage
            $listeEtudiants = $this->etudiant->getAllEtudiants();
            
            // Filtrer les étudiants si un terme de recherche est présent
            if (!empty($data['searchTerm'])) {
                $listeEtudiants = array_filter($listeEtudiants, function($etudiant) use ($data) {
                    $searchTerm = strtolower($data['searchTerm']);
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

            // Ajouter les données de pagination
            $data['listeEtudiants'] = $currentPageItems;
            $data['allEtudiants'] = $listeEtudiants;
            $data['currentPage'] = $currentPage;
            $data['totalPages'] = $totalPages;
            $data['totalItems'] = $totalItems;
            $data['startIndex'] = $startIndex;
            $data['endIndex'] = $endIndex;
            $data['itemsPerPage'] = $itemsPerPage;

        } catch (Exception $e) {
            error_log("Erreur dans GestionEtudiantController::index : " . $e->getMessage());
            $data['messageErreur'] = "Une erreur est survenue. Veuillez réessayer.";
        }
        
        return $data;
    }
}