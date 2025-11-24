<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../models/Traitement.php';
require_once __DIR__ . '/../models/Enseignant.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/PersAdmin.php';
require_once __DIR__ . '/../models/Grade.php';
require_once __DIR__ . '/../models/Fonction.php';
require_once __DIR__ . '/../models/Specialite.php';
require_once __DIR__ . '/../models/AuditLog.php';

// Si nécessaire pour d'autres opérations

class AuthController {
    private $db;
    private $enseignantModel;
    private $persAdminModel;
    private $etudiantModel;
    private $auditLog;

    public function __construct($db) {
        $this->db = $db;
        $this->enseignantModel = new Enseignant($db);
        $this->persAdminModel = new PersAdmin($db);
        $this->etudiantModel = new Etudiant($db);
        $this->auditLog = new AuditLog($db);
        
       
    }

    public function login($login, $password)
    {
        $utilisateur = new Utilisateur($this->db);
        $infoUtilisateur = $utilisateur->verifierConnexion($login, $password);

        if ($infoUtilisateur) {
            
           
            // Stocker les infos de session
            $_SESSION['id_utilisateur'] = $infoUtilisateur['id_utilisateur'];
            $_SESSION['nom_utilisateur'] = $infoUtilisateur['nom_utilisateur'];
            $_SESSION['statut_utilisateur'] = $infoUtilisateur['statut_utilisateur'];
            $_SESSION['login_utilisateur'] = $infoUtilisateur['login_utilisateur'];
            // Récupérer les autres infos via le modèle
            $_SESSION['type_utilisateur'] = $utilisateur->getLibelleTypeUtilisateur($infoUtilisateur['id_utilisateur']);
            $_SESSION['id_GU'] = $infoUtilisateur['id_GU'];
            $_SESSION['lib_GU'] = $utilisateur->getLibelleGroupeUtilisateur($infoUtilisateur['id_utilisateur']);
            $_SESSION['niveau_acces'] = $utilisateur->getLibelleNivAcces($infoUtilisateur['id_utilisateur']);

            $type_utilisateur = $utilisateur->getLibelleTypeUtilisateur($infoUtilisateur['id_utilisateur']);

            if ($type_utilisateur !== 'Etudiant') {
                if ($type_utilisateur === 'Enseignant simple' || $type_utilisateur === 'Enseignant administratif') {
                    // Récupérer les informations de l'enseignant
                    $enseignant = $this->enseignantModel->getEnseignantByLogin($infoUtilisateur['login_utilisateur']);
                    if ($enseignant) {
                        $_SESSION['specialite'] = $enseignant->lib_specialite;
                        $_SESSION['grade'] = $enseignant->lib_grade;
                        $_SESSION['fonction'] = $enseignant->lib_fonction;
                        $_SESSION['date_grade'] = $enseignant->date_grade;
                        $_SESSION['date_fonction'] = $enseignant->date_occupation;
                    }
                } else if ($type_utilisateur === 'Personnel administratif') {
                    // Récupérer les informations du personnel administratif
                    $persAdmin = $this->persAdminModel->getPersAdminByLogin($infoUtilisateur['login_utilisateur']);
                    if ($persAdmin) {
                        $_SESSION['telephone'] = $persAdmin->tel_pers_admin;
                        $_SESSION['poste'] = $persAdmin->poste;
                        $_SESSION['date_embauche'] = $persAdmin->date_embauche;
                    }
                }
            }
            if ($type_utilisateur == 'Etudiant') {
                $etudiant = $this->etudiantModel->getEtudiantByLogin($infoUtilisateur['login_utilisateur']);
                if ($etudiant) {
                    $_SESSION['num_etu'] = $etudiant->num_etu;
                }
            }
            $this->auditLog->logConnexion($infoUtilisateur['id_utilisateur'], 'utilisateur', 'Succès');
            return true;
        } 
        // Ne pas enregistrer les tentatives de connexion échouées dans l'audit
        // Les logs du serveur web capturent déjà ces informations
        return false;
    }

    /**
     * Handle login form submission from the router
     */
    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $login = $_POST['login'] ?? '';
            $password = $_POST['password'] ?? '';

            if ($this->login($login, $password)) {
                // Récupérer les traitements autorisés pour l'utilisateur
                require_once __DIR__ . '/MenuController.php';
                $menuController = new MenuController();
                $traitements = $menuController->genererMenu($_SESSION['id_GU']);

                // Utiliser le premier traitement comme page par défaut ou dashboard
                $defaultPage = !empty($traitements) ? $traitements[0]['lib_traitement'] : 'dashboard';

                // For now, redirect to layout.php for backward compatibility
                header('Location: /layout?page=' . urlencode($defaultPage));
                exit;
            } else {
                $_SESSION['error'] = 'Login ou mot de passe incorrect';
                header('Location: /login');
                exit;
            }
        } else {
            header('Location: /login');
            exit;
        }
    }

    public function logout()
    {
        if (isset($_SESSION['id_utilisateur'])) {
            $this->auditLog->logDeconnexion($_SESSION['id_utilisateur'] , 'utilisateur', 'Succès');
        }

        // Détruire toutes les données de session
        $_SESSION = array();

        // Si vous voulez détruire complètement la session, effacez aussi le cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Finalement, détruire la session
        session_destroy();
        
        // Redirect to login page
        header('Location: /login');
        exit;
    }

    public function updatePassword($currentPassword, $newPassword, $confirmPassword) {
        $utilisateur = new Utilisateur($this->db);
        $user = $utilisateur->getUtilisateurById($_SESSION['id_utilisateur']);
        $messageErreur = '';
        $messageSuccess = '';

        // Vérifier si le mot de passe actuel est correct
        if (!password_verify($currentPassword, $user->mdp_utilisateur)) {
            $messageErreur = 'Le mot de passe actuel est incorrect.';
            $GLOBALS['messageErreur'] = $messageErreur;
            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur'); 
        
            return false;
        }

        // Vérifier si les nouveaux mots de passe correspondent
        if ($newPassword !== $confirmPassword) {
            $messageErreur = 'Les mots de passe ne correspondent pas.';
            $GLOBALS['messageErreur'] = $messageErreur;
            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur'); 
            
            return false;
        }

        // Vérifier si le nouveau mot de passe est différent du mot de passe actuel
        if (password_verify($newPassword, $user->mdp_utilisateur)) {
            $messageErreur = 'Le nouveau mot de passe doit être différent de l\'ancien.';
            $GLOBALS['messageErreur'] = $messageErreur;
            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur'); 
            
            return false;
        }

        // Vérifier la longueur minimale
        if (strlen($newPassword) < 8) {
            $messageErreur = 'Le mot de passe doit contenir au moins 8 caractères.';
            $GLOBALS['messageErreur'] = $messageErreur;
            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur'); 
            
            return false;
        }

        // Vérifier la présence d'au moins une majuscule
        if (!preg_match('/[A-Z]/', $newPassword)) {
            $messageErreur = 'Le mot de passe doit contenir au moins une majuscule.';
            $GLOBALS['messageErreur'] = $messageErreur;
            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur'); 
            
            return false;
        }

        // Vérifier la présence d'au moins un chiffre
        if (!preg_match('/[0-9]/', $newPassword)) {
            $messageErreur = 'Le mot de passe doit contenir au moins un chiffre.';
            $GLOBALS['messageErreur'] = $messageErreur;
            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur'); 
         
            return false;
        }

        // Vérifier la présence d'au moins un caractère spécial
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+/', $newPassword)) {
            $messageErreur = 'Le mot de passe doit contenir au moins un caractère spécial.';
            $GLOBALS['messageErreur'] = $messageErreur;
            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur'); 
          
            return false;
        }

        // Hasher le nouveau mot de passe
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Mettre à jour le mot de passe
        if ($utilisateur->updatePassword($hashedPassword, $_SESSION['id_utilisateur'])) {
            $messageSuccess = 'Mot de passe mis à jour avec succès.';
            $GLOBALS['messageSuccess'] = $messageSuccess;
            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Succès'); 
            return true;
        }

        $messageErreur = 'Erreur lors de la mise à jour du mot de passe.';
        $GLOBALS['messageErreur'] = $messageErreur;
        $GLOBALS['messageSuccess'] = $messageSuccess;

        return false;
    }
}