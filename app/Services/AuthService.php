<?php

namespace CheckMaster\Services;

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

use CheckMaster\Security\DbRateLimiter;
use Utilisateur;
use Enseignant;
use PersAdmin;
use Etudiant;
use AuditLog;

class AuthService
{
    private $db;
    private $enseignantModel;
    private $persAdminModel;
    private $etudiantModel;
    private $auditLog;

    public function __construct($db)
    {
        $this->db = $db;
        $this->enseignantModel = new Enseignant($db);
        $this->persAdminModel = new PersAdmin($db);
        $this->etudiantModel = new Etudiant($db);
        $this->auditLog = new AuditLog($db);
    }

    public function login($login, $password, $ip)
    {
        $identifier = strtolower(trim((string) $login));
        if ($identifier === '') {
            $identifier = '-';
        }
        $limiter = new DbRateLimiter($this->db);

        if (!$limiter->isAllowed('login', $ip, $identifier)) {
            return ['success' => false, 'message' => 'Trop de tentatives. Veuillez patienter avant de réessayer.'];
        }

        $utilisateur = new Utilisateur($this->db);
        $infoUtilisateur = $utilisateur->verifierConnexion($login, $password);

        if ($infoUtilisateur) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }

            $_SESSION['id_utilisateur'] = $infoUtilisateur['id_utilisateur'];
            $_SESSION['nom_utilisateur'] = $infoUtilisateur['nom_utilisateur'];
            $_SESSION['statut_utilisateur'] = $infoUtilisateur['statut_utilisateur'];
            $_SESSION['login_utilisateur'] = $infoUtilisateur['login_utilisateur'];
            $_SESSION['type_utilisateur'] = $utilisateur->getLibelleTypeUtilisateur($infoUtilisateur['id_utilisateur']);
            $_SESSION['id_GU'] = $infoUtilisateur['id_GU'];
            $_SESSION['lib_GU'] = $utilisateur->getLibelleGroupeUtilisateur($infoUtilisateur['id_utilisateur']);
            $_SESSION['niveau_acces'] = $utilisateur->getLibelleNivAcces($infoUtilisateur['id_utilisateur']);

            $type_utilisateur = $_SESSION['type_utilisateur'];

            if ($type_utilisateur !== 'Etudiant') {
                if ($type_utilisateur === 'Enseignant simple' || $type_utilisateur === 'Enseignant administratif') {
                    $enseignant = $this->enseignantModel->getEnseignantByLogin($infoUtilisateur['login_utilisateur']);
                    if ($enseignant) {
                        $_SESSION['specialite'] = $enseignant->lib_specialite;
                        $_SESSION['grade'] = $enseignant->lib_grade;
                        $_SESSION['fonction'] = $enseignant->lib_fonction;
                        $_SESSION['date_grade'] = $enseignant->date_grade;
                        $_SESSION['date_fonction'] = $enseignant->date_occupation;
                    }
                } else if ($type_utilisateur === 'Personnel administratif') {
                    $persAdmin = $this->persAdminModel->getPersAdminByLogin($infoUtilisateur['login_utilisateur']);
                    if ($persAdmin) {
                        $_SESSION['telephone'] = $persAdmin->tel_pers_admin;
                        $_SESSION['poste'] = $persAdmin->poste;
                        $_SESSION['date_embauche'] = $persAdmin->date_embauche;
                    }
                }
            } else {
                $etudiant = $this->etudiantModel->getEtudiantByLogin($infoUtilisateur['nom_utilisateur']);
                if (!$etudiant && !empty($infoUtilisateur['login_utilisateur'])) {
                    $etudiant = $this->etudiantModel->getEtudiantByEmail($infoUtilisateur['login_utilisateur']);
                }
                if ($etudiant) {
                    $_SESSION['num_etu'] = $etudiant->num_carte_etud;
                    $_SESSION['nom_etu'] = $etudiant->nom_etu;
                    $_SESSION['prenom_etu'] = $etudiant->prenom_etu;
                }
            }

            $this->auditLog->logConnexion($infoUtilisateur['id_utilisateur'], 'utilisateur', 'Succès');
            $limiter->reset('login', $ip, $identifier);

            return ['success' => true];
        }

        $limiter->hit('login', $ip, $identifier, 5, 15 * 60, 10 * 60);
        return ['success' => false, 'message' => 'Identifiants incorrects.'];
    }

    public function logout($idUtilisateur)
    {
        $this->auditLog->logDeconnexion($idUtilisateur, 'utilisateur', 'Succès');

        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        return session_destroy();
    }

    public function updateEmail($idUtilisateur, $newEmail, $confirmEmail)
    {
        $idUtilisateur = (int) $idUtilisateur;
        $newEmail = strtolower(trim((string) $newEmail));
        $confirmEmail = strtolower(trim((string) $confirmEmail));

        if ($idUtilisateur <= 0) {
            return ['success' => false, 'message' => 'Session utilisateur invalide. Veuillez vous reconnecter.'];
        }

        if ($newEmail === '' || $confirmEmail === '') {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Tous les champs adresse mail sont obligatoires.'];
        }

        if ($newEmail !== $confirmEmail) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Les adresses mail ne correspondent pas.'];
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Adresse mail invalide.'];
        }

        if (strlen($newEmail) > 100) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Adresse mail trop longue (100 caractères maximum).'];
        }

        $utilisateur = new Utilisateur($this->db);
        $user = $utilisateur->getUtilisateurById($idUtilisateur);
        if (!$user) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Utilisateur introuvable.'];
        }

        $nomUtilisateur = trim((string) ($user->nom_utilisateur ?? ''));
        $idTypeUtilisateur = (int) ($user->id_type_utilisateur ?? 0);
        if ($nomUtilisateur === '' || $idTypeUtilisateur <= 0) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Profil utilisateur incomplet.'];
        }

        $currentEmail = trim((string) $utilisateur->getEmailByNomAndType($nomUtilisateur, $idTypeUtilisateur));
        if ($currentEmail !== '' && strcasecmp($currentEmail, $newEmail) === 0) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'La nouvelle adresse mail doit être différente de l\'adresse actuelle.'];
        }

        if (!$utilisateur->updateEmailByNomAndType($nomUtilisateur, $idTypeUtilisateur, $newEmail)) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'adresse mail.'];
        }

        $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Succès');
        return ['success' => true, 'message' => 'Adresse mail mise à jour avec succès.'];
    }

    public function getContactEmail($idUtilisateur): ?string
    {
        $idUtilisateur = (int) $idUtilisateur;
        if ($idUtilisateur <= 0) {
            return null;
        }

        $utilisateur = new Utilisateur($this->db);
        $user = $utilisateur->getUtilisateurById($idUtilisateur);
        if (!$user) {
            return null;
        }

        $nomUtilisateur = trim((string) ($user->nom_utilisateur ?? ''));
        $idTypeUtilisateur = (int) ($user->id_type_utilisateur ?? 0);
        if ($nomUtilisateur === '' || $idTypeUtilisateur <= 0) {
            return null;
        }

        $email = trim((string) $utilisateur->getEmailByNomAndType($nomUtilisateur, $idTypeUtilisateur));
        return $email !== '' ? $email : null;
    }

    public function updatePassword($idUtilisateur, $currentPassword, $newPassword, $confirmPassword)
    {
        $idUtilisateur = (int) $idUtilisateur;
        $currentPassword = (string) $currentPassword;
        $newPassword = (string) $newPassword;
        $confirmPassword = (string) $confirmPassword;

        if ($idUtilisateur <= 0) {
            return ['success' => false, 'message' => 'Session utilisateur invalide. Veuillez vous reconnecter.'];
        }

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Tous les champs mot de passe sont obligatoires.'];
        }

        $utilisateur = new Utilisateur($this->db);
        $user = $utilisateur->getUtilisateurById($idUtilisateur);
        if (!$user || !isset($user->mdp_utilisateur)) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Utilisateur introuvable.'];
        }

        $storedPassword = (string) $user->mdp_utilisateur;
        $isCurrentPasswordValid = password_verify($currentPassword, $storedPassword)
            || hash_equals($storedPassword, $currentPassword);

        if (!$isCurrentPasswordValid) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Le mot de passe actuel est incorrect.'];
        }

        if ($newPassword !== $confirmPassword) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Les mots de passe ne correspondent pas.'];
        }

        $isSameAsCurrent = password_verify($newPassword, $storedPassword)
            || hash_equals($storedPassword, $newPassword);
        if ($isSameAsCurrent) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Le nouveau mot de passe doit être différent de l\'ancien.'];
        }

        if (strlen($newPassword) < 8) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères.'];
        }

        if (!preg_match('/[A-Z]/', $newPassword)) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins une majuscule.'];
        }

        if (!preg_match('/[0-9]/', $newPassword)) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins un chiffre.'];
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+/', $newPassword)) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins un caractère spécial.'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        if ($utilisateur->updatePassword($idUtilisateur, $hashedPassword)) {
            $this->auditLog->logModification($idUtilisateur, 'utilisateur', 'Succès');
            return ['success' => true, 'message' => 'Mot de passe mis à jour avec succès.'];
        }

        return ['success' => false, 'message' => 'Erreur lors de la mise à jour du mot de passe.'];
    }
}
