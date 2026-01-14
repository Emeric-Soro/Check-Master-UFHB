<?php

namespace App\Controllers;

use PDO;
use App\Models\Utilisateur;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\PersAdmin;
use App\Models\AuditLog;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * AuthController - Connexion, déconnexion, réinitialisation mot de passe
 * 
 * Ce contrôleur gère l'authentification des utilisateurs :
 * - Connexion et création de session
 * - Déconnexion et destruction de session
 * - Mise à jour du mot de passe
 * 
 * @package App\Controllers
 */
class AuthController
{
    private PDO $db;
    private Utilisateur $utilisateur;
    private Enseignant $enseignantModel;
    private PersAdmin $persAdminModel;
    private Etudiant $etudiantModel;
    private AuditLog $auditLog;
    private LoggerInterface $logger;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $db,
        Utilisateur $utilisateur,
        Enseignant $enseignantModel,
        PersAdmin $persAdminModel,
        Etudiant $etudiantModel,
        AuditLog $auditLog,
        LoggerInterface $logger
    ) {
        $this->db = $db;
        $this->utilisateur = $utilisateur;
        $this->enseignantModel = $enseignantModel;
        $this->persAdminModel = $persAdminModel;
        $this->etudiantModel = $etudiantModel;
        $this->auditLog = $auditLog;
        $this->logger = $logger;
    }

    /**
     * Action : Connexion utilisateur (PUBLIC - pas de vérification de droits)
     * 
     * @param string $login Identifiant de l'utilisateur
     * @param string $password Mot de passe de l'utilisateur
     * @return bool True si connexion réussie
     */
    public function login(string $login, string $password): bool
    {
        try {
            $infoUtilisateur = $this->utilisateur->verifierConnexion($login, $password);

            if ($infoUtilisateur) {
                // Stocker les infos de session
                $_SESSION['id_utilisateur'] = $infoUtilisateur['id_utilisateur'];
                $_SESSION['nom_utilisateur'] = $infoUtilisateur['nom_utilisateur'];
                $_SESSION['statut_utilisateur'] = $infoUtilisateur['statut_utilisateur'];
                $_SESSION['login_utilisateur'] = $infoUtilisateur['login_utilisateur'];
                
                // Récupérer les autres infos via le modèle
                $_SESSION['type_utilisateur'] = $this->utilisateur->getLibelleTypeUtilisateur($infoUtilisateur['id_utilisateur']);
                $_SESSION['id_GU'] = $infoUtilisateur['id_GU'];
                $_SESSION['lib_GU'] = $this->utilisateur->getLibelleGroupeUtilisateur($infoUtilisateur['id_utilisateur']);
                $_SESSION['niveau_acces'] = $this->utilisateur->getLibelleNivAcces($infoUtilisateur['id_utilisateur']);

                $type_utilisateur = $this->utilisateur->getLibelleTypeUtilisateur($infoUtilisateur['id_utilisateur']);

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
                $this->logger->info("Connexion réussie pour l'utilisateur: " . $login);
                return true;
            }
            
            $this->logger->warning("Tentative de connexion échouée pour: " . $login);
            return false;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la connexion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Action : Déconnexion utilisateur (vérification de session active)
     * 
     * @return bool True si déconnexion réussie
     */
    public function logout(): bool
    {
        try {
            // Vérifier qu'une session est active
            if (!isset($_SESSION['id_utilisateur'])) {
                $this->logger->warning("Tentative de déconnexion sans session active");
                return false;
            }
            
            $this->auditLog->logDeconnexion($_SESSION['id_utilisateur'], 'utilisateur', 'Succès');
            $this->logger->info("Déconnexion réussie pour l'utilisateur ID: " . $_SESSION['id_utilisateur']);

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
            return session_destroy();
            
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la déconnexion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Action : Mise à jour du mot de passe (UPDATE)
     * 
     * @param string $currentPassword Mot de passe actuel
     * @param string $newPassword Nouveau mot de passe
     * @param string $confirmPassword Confirmation du nouveau mot de passe
     * @return bool True si mise à jour réussie
     */
    public function updatePassword(string $currentPassword, string $newPassword, string $confirmPassword): bool
    {
        try {
            // Vérifier qu'une session est active
            if (!isset($_SESSION['id_utilisateur'])) {
                $GLOBALS['messageErreur'] = 'Session expirée. Veuillez vous reconnecter.';
                return false;
            }
            
            $user = $this->utilisateur->getUtilisateurById($_SESSION['id_utilisateur']);
            
            if (!$user) {
                $GLOBALS['messageErreur'] = 'Utilisateur non trouvé.';
                return false;
            }

            // Vérifier si le mot de passe actuel est correct
            if (!password_verify($currentPassword, $user->mdp_utilisateur)) {
                $GLOBALS['messageErreur'] = 'Le mot de passe actuel est incorrect.';
                $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur');
                $this->logger->warning("Tentative de changement de mot de passe avec mot de passe incorrect pour user " . $_SESSION['id_utilisateur']);
                return false;
            }

            // Vérifier si les nouveaux mots de passe correspondent
            if ($newPassword !== $confirmPassword) {
                $GLOBALS['messageErreur'] = 'Les mots de passe ne correspondent pas.';
                $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur');
                return false;
            }

            // Vérifier si le nouveau mot de passe est différent du mot de passe actuel
            if (password_verify($newPassword, $user->mdp_utilisateur)) {
                $GLOBALS['messageErreur'] = 'Le nouveau mot de passe doit être différent de l\'ancien.';
                $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur');
                return false;
            }

            // Validation du mot de passe
            $validationResult = $this->validatePassword($newPassword);
            if ($validationResult !== true) {
                $GLOBALS['messageErreur'] = $validationResult;
                $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur');
                return false;
            }

            // Hasher le nouveau mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Mettre à jour le mot de passe
            if ($this->utilisateur->updatePassword($hashedPassword, $_SESSION['id_utilisateur'])) {
                $GLOBALS['messageSuccess'] = 'Mot de passe mis à jour avec succès.';
                $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Succès');
                $this->logger->info("Mot de passe mis à jour pour l'utilisateur ID: " . $_SESSION['id_utilisateur']);
                return true;
            }

            $GLOBALS['messageErreur'] = 'Erreur lors de la mise à jour du mot de passe.';
            return false;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la mise à jour du mot de passe: " . $e->getMessage());
            $GLOBALS['messageErreur'] = 'Une erreur est survenue lors de la mise à jour du mot de passe.';
            return false;
        }
    }

    /**
     * Valide la complexité du mot de passe
     * 
     * @param string $password Mot de passe à valider
     * @return bool|string True si valide, message d'erreur sinon
     */
    private function validatePassword(string $password)
    {
        // Vérifier la longueur minimale
        if (strlen($password) < 8) {
            return 'Le mot de passe doit contenir au moins 8 caractères.';
        }

        // Vérifier la présence d'au moins une majuscule
        if (!preg_match('/[A-Z]/', $password)) {
            return 'Le mot de passe doit contenir au moins une majuscule.';
        }

        // Vérifier la présence d'au moins un chiffre
        if (!preg_match('/[0-9]/', $password)) {
            return 'Le mot de passe doit contenir au moins un chiffre.';
        }

        // Vérifier la présence d'au moins un caractère spécial
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+/', $password)) {
            return 'Le mot de passe doit contenir au moins un caractère spécial.';
        }

        return true;
    }
}