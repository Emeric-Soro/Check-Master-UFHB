<?php

namespace App\Controllers;

use PDO;
use App\Models\Etudiant;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Valitron\Validator;

/**
 * GestionEtudiantController - CRUD des étudiants
 * 
 * Ce contrôleur gère la liste, l'ajout, la modification et la suppression
 * des étudiants dans le système.
 * 
 * @package App\Controllers
 */
class GestionEtudiantController
{
    private PDO $pdo;
    private Etudiant $etudiant;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;
    private string $baseViewPath;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        Etudiant $etudiant,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->etudiant = $etudiant;
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->logger = $logger;
        $this->baseViewPath = __DIR__ . '/../../ressources/views/';

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Vérification centralisée des permissions
     * 
     * @param string $action L'action à vérifier (read, create, update, delete)
     * @return bool True si l'utilisateur a la permission
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'gestion_etudiants', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur gestion_etudiants"
            );
            
            $GLOBALS['messageErreur'] = "Vous n'avez pas les droits nécessaires pour effectuer cette action.";
            
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                http_response_code(403);
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            
            return false;
        }
        
        return true;
    }

    /**
     * Génère automatiquement un numéro étudiant basé sur la promotion
     * 
     * @param string $promotionEtu Année de promotion (ex: "2023-2024")
     * @return string Numéro étudiant généré
     */
    private function genererNumeroEtudiant(string $promotionEtu): string
    {
        // Extraire l'année de début de la promotion (ex: "2023-2024" -> "2023")
        $annee = explode('-', $promotionEtu)[0];
        
        // Rechercher le dernier numéro pour cette promotion
        $query = "SELECT MAX(CAST(SUBSTRING(num_etu, 5) AS UNSIGNED)) as max_num 
                  FROM etudiants WHERE num_etu LIKE :prefix";
        $stmt = $this->pdo->prepare($query);
        $prefix = $annee . '%';
        $stmt->bindParam(':prefix', $prefix);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        
        $nextNum = ($result->max_num ?? 0) + 1;
        return $annee . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Action principale : Affichage de la liste des étudiants (READ)
     */
    public function index(): void
    {
        // Vérification des permissions de lecture
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            $currentPage = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
            $itemsPerPage = 10;
            $etudiantAModifier = null;
            $modalAction = '';
            $searchTerm = $this->security->sanitizeInput($_GET['search'] ?? '');

            // Gestion des actions GET pour les modales (édition)
            if (isset($_GET['modalAction']) && $_GET['modalAction'] === 'edit' && isset($_GET['num_etu'])) {
                $this->handleEditModal($_GET['num_etu'], $etudiantAModifier, $modalAction);
            }

            // Gestion des actions POST (CRUD)
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->handlePostActions();
            }

            // Récupération et filtrage des étudiants
            $listeEtudiants = $this->etudiant->getAllEtudiants();
            
            if (!empty($searchTerm)) {
                $listeEtudiants = $this->filterEtudiants($listeEtudiants, $searchTerm);
            }

            // Pagination
            $listeEtudiants = array_values($listeEtudiants);
            $totalItems = count($listeEtudiants);
            $totalPages = max(1, ceil($totalItems / $itemsPerPage));
            
            if ($currentPage > $totalPages) {
                $currentPage = $totalPages;
            }
            
            $startIndex = ($currentPage - 1) * $itemsPerPage;
            $endIndex = min($startIndex + $itemsPerPage, $totalItems);
            $currentPageItems = array_slice($listeEtudiants, $startIndex, $itemsPerPage);

            // Encodage des IDs pour la vue (sécurité)
            foreach ($currentPageItems as $etu) {
                if (property_exists($etu, 'num_etu')) {
                    $etu->hashed_id = $this->security->encodeId((int)$etu->num_etu);
                }
            }

            // Passage des données à la vue
            $GLOBALS['listeEtudiants'] = $currentPageItems;
            $GLOBALS['allEtudiants'] = $listeEtudiants;
            $GLOBALS['etudiant_a_modifier'] = $etudiantAModifier;
            $GLOBALS['modalAction'] = $modalAction;
            $GLOBALS['currentPage'] = $currentPage;
            $GLOBALS['totalPages'] = $totalPages;
            $GLOBALS['totalItems'] = $totalItems;
            $GLOBALS['startIndex'] = $startIndex;
            $GLOBALS['endIndex'] = $endIndex;
            $GLOBALS['itemsPerPage'] = $itemsPerPage;
            $GLOBALS['searchTerm'] = $searchTerm;

        } catch (\Exception $e) {
            $this->logger->error("Erreur dans GestionEtudiantController::index : " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue. Veuillez réessayer.";
        }
    }

    /**
     * Gère l'ouverture de la modale d'édition
     */
    private function handleEditModal(string $numEtu, ?object &$etudiantAModifier, string &$modalAction): void
    {
        // Note: num_etu peut être encodé ou non selon le contexte
        $etudiantAModifier = $this->etudiant->getEtudiantById($numEtu);
        
        if (!$etudiantAModifier) {
            $GLOBALS['messageErreur'] = "Étudiant non trouvé.";
            return;
        }
        
        $modalAction = 'edit';
        
        // Si requête AJAX, renvoyer JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            
            header('Content-Type: application/json');
            echo json_encode([
                'num_etu' => $etudiantAModifier->num_etu,
                'nom_etu' => $etudiantAModifier->nom_etu,
                'prenom_etu' => $etudiantAModifier->prenom_etu,
                'date_naiss_etu' => $etudiantAModifier->date_naiss_etu,
                'genre_etu' => $etudiantAModifier->genre_etu,
                'email_etu' => $etudiantAModifier->email_etu,
                'promotion_etu' => $etudiantAModifier->promotion_etu
            ]);
            exit;
        }
    }

    /**
     * Gère toutes les actions POST (CREATE, UPDATE, DELETE)
     */
    private function handlePostActions(): void
    {
        if (isset($_POST['submit_add_etudiant'])) {
            $this->createEtudiant();
        } elseif (isset($_POST['submit_modifier_etudiant'])) {
            $this->updateEtudiant();
        } elseif (isset($_POST['selected_ids']) && !empty($_POST['selected_ids'])) {
            $this->deleteEtudiants($_POST['selected_ids']);
        }
    }

    /**
     * Action : Créer un nouvel étudiant (CREATE)
     */
    private function createEtudiant(): void
    {
        // Vérification des permissions de création
        if (!$this->checkPermission('create')) {
            return;
        }

        // Validation avec Valitron
        $v = new Validator($_POST);
        $v->rule('required', ['nom_etu', 'prenom_etu', 'date_naiss_etu', 'genre_etu', 'email_etu', 'promotion_etu']);
        $v->rule('email', 'email_etu');
        $v->rule('in', 'genre_etu', ['M', 'F']);
        $v->rule('date', 'date_naiss_etu');
        
        if (!$v->validate()) {
            $errors = $v->errors();
            $GLOBALS['messageErreur'] = "Erreurs de validation : " . implode(', ', array_map(function($field) use ($errors) {
                return implode(', ', $errors[$field]);
            }, array_keys($errors)));
            return;
        }

        try {
            $numEtu = $this->genererNumeroEtudiant($_POST['promotion_etu']);
            $nomEtu = $this->security->sanitizeInput($_POST['nom_etu']);
            $prenomEtu = $this->security->sanitizeInput($_POST['prenom_etu']);
            $dateNaissEtu = $_POST['date_naiss_etu'];
            $genreEtu = $_POST['genre_etu'];
            $emailEtu = $this->security->sanitizeInput($_POST['email_etu']);
            $promotionEtu = $_POST['promotion_etu'];

            if ($this->etudiant->ajouterEtudiant($numEtu, $nomEtu, $prenomEtu, $dateNaissEtu, $genreEtu, $emailEtu, $promotionEtu)) {
                $GLOBALS['messageSuccess'] = "Étudiant ajouté avec succès. Numéro étudiant : " . $numEtu;
                
                $this->auditLog->logCreation(
                    $_SESSION['id_utilisateur'] ?? 0,
                    'etudiants',
                    'Succès'
                );
                
                $this->logger->info("Étudiant créé: {$nomEtu} {$prenomEtu} ({$numEtu})");
            } else {
                $GLOBALS['messageErreur'] = "Erreur lors de l'ajout de l'étudiant.";
                $this->auditLog->logCreation($_SESSION['id_utilisateur'] ?? 0, 'etudiants', 'Erreur');
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur création étudiant: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors de l'ajout.";
        }
    }

    /**
     * Action : Modifier un étudiant existant (UPDATE)
     */
    private function updateEtudiant(): void
    {
        // Vérification des permissions de modification
        if (!$this->checkPermission('update')) {
            return;
        }

        // Validation avec Valitron
        $v = new Validator($_POST);
        $v->rule('required', ['num_etu', 'nom_etu', 'prenom_etu', 'date_naiss_etu', 'genre_etu', 'email_etu', 'promotion_etu']);
        $v->rule('email', 'email_etu');
        $v->rule('in', 'genre_etu', ['M', 'F']);
        
        if (!$v->validate()) {
            $errors = $v->errors();
            $GLOBALS['messageErreur'] = "Erreurs de validation : " . implode(', ', array_map(function($field) use ($errors) {
                return implode(', ', $errors[$field]);
            }, array_keys($errors)));
            return;
        }

        try {
            $numEtu = $_POST['num_etu'];
            $nomEtu = $this->security->sanitizeInput($_POST['nom_etu']);
            $prenomEtu = $this->security->sanitizeInput($_POST['prenom_etu']);
            $dateNaissEtu = $_POST['date_naiss_etu'];
            $genreEtu = $_POST['genre_etu'];
            $emailEtu = $this->security->sanitizeInput($_POST['email_etu']);
            $promotionEtu = $_POST['promotion_etu'];

            // Récupérer les anciennes données pour l'audit
            $ancienEtudiant = $this->etudiant->getEtudiantById($numEtu);

            if ($this->etudiant->modifierEtudiant($numEtu, $nomEtu, $prenomEtu, $dateNaissEtu, $genreEtu, $emailEtu, $promotionEtu)) {
                $GLOBALS['messageSuccess'] = "Étudiant modifié avec succès.";
                
                $this->auditLog->logModification(
                    $_SESSION['id_utilisateur'] ?? 0,
                    'etudiants',
                    'Succès'
                );
                
                $this->logger->info("Étudiant modifié: {$numEtu}");
            } else {
                $GLOBALS['messageErreur'] = "Erreur lors de la modification de l'étudiant.";
                $this->auditLog->logModification($_SESSION['id_utilisateur'] ?? 0, 'etudiants', 'Erreur');
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur modification étudiant: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors de la modification.";
        }
    }

    /**
     * Action : Supprimer un ou plusieurs étudiants (DELETE)
     * 
     * @param array $selectedIds Liste des IDs à supprimer
     */
    private function deleteEtudiants(array $selectedIds): void
    {
        // Vérification des permissions de suppression
        if (!$this->checkPermission('delete')) {
            return;
        }

        try {
            $success = true;
            $etudiantsSupprimes = [];
            
            foreach ($selectedIds as $numEtu) {
                // Sanitize l'ID
                $numEtu = $this->security->sanitizeInput($numEtu);
                
                // Récupérer les infos avant suppression pour l'audit
                $etudiant = $this->etudiant->getEtudiantById($numEtu);
                if ($etudiant) {
                    $etudiantsSupprimes[] = "{$etudiant->nom_etu} {$etudiant->prenom_etu} ($numEtu)";
                }
                
                if (!$this->etudiant->supprimerEtudiant($numEtu)) {
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                $GLOBALS['messageSuccess'] = "Étudiants supprimés avec succès.";
                
                foreach ($selectedIds as $numEtu) {
                    $this->auditLog->logSuppression(
                        $_SESSION['id_utilisateur'] ?? 0,
                        'etudiants',
                        'Succès'
                    );
                }
                
                $this->logger->info("Étudiants supprimés: " . implode(', ', $etudiantsSupprimes));
            } else {
                $GLOBALS['messageErreur'] = "Erreur lors de la suppression des étudiants.";
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur suppression étudiants: " . $e->getMessage());
            $GLOBALS['messageErreur'] = "Une erreur est survenue lors de la suppression.";
        }
    }

    /**
     * Filtre les étudiants par terme de recherche
     * 
     * @param array $etudiants Liste des étudiants
     * @param string $searchTerm Terme de recherche
     * @return array Liste filtrée
     */
    private function filterEtudiants(array $etudiants, string $searchTerm): array
    {
        $searchTermLower = strtolower($searchTerm);
        
        return array_filter($etudiants, function($etudiant) use ($searchTermLower) {
            return strpos(strtolower($etudiant->nom_etu ?? ''), $searchTermLower) !== false ||
                   strpos(strtolower($etudiant->prenom_etu ?? ''), $searchTermLower) !== false ||
                   strpos(strtolower($etudiant->num_etu ?? ''), $searchTermLower) !== false ||
                   strpos(strtolower($etudiant->email_etu ?? ''), $searchTermLower) !== false;
        });
    }

    /**
     * Action : Récupérer un étudiant par son ID (pour API/AJAX)
     */
    public function getEtudiantById(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        header('Content-Type: application/json');
        
        $hashedId = $_GET['id'] ?? '';
        $numEtu = $this->security->decodeId($hashedId);
        
        if (!$numEtu) {
            echo json_encode(['error' => 'ID invalide']);
            return;
        }

        $etudiant = $this->etudiant->getEtudiantById($numEtu);
        
        if ($etudiant) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'num_etu' => $etudiant->num_etu,
                    'nom_etu' => $etudiant->nom_etu,
                    'prenom_etu' => $etudiant->prenom_etu,
                    'date_naiss_etu' => $etudiant->date_naiss_etu,
                    'genre_etu' => $etudiant->genre_etu,
                    'email_etu' => $etudiant->email_etu,
                    'promotion_etu' => $etudiant->promotion_etu
                ]
            ]);
        } else {
            echo json_encode(['error' => 'Étudiant non trouvé']);
        }
    }
}