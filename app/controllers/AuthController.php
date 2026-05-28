<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Core/Autoload.php';
require_once __DIR__ . '/../Services/AuthService.php';

class AuthController
{
    private $authService;

    public function __construct($db)
    {
        $this->authService = new \CheckMaster\Services\AuthService($db);
    }

    public function login($login, $password)
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        $result = $this->authService->login($login, $password, $ip);

        if (!$result['success']) {
            $_SESSION['error'] = $result['message'] ?? 'Identifiants incorrects.';
            return false;
        }

        return true;
    }

    public function logout()
    {
        $idUtilisateur = $_SESSION['id_utilisateur'] ?? null;

        return $this->authService->logout($idUtilisateur);
    }

    public function updatePassword($currentPassword, $newPassword, $confirmPassword)
    {
        $idUtilisateur = $_SESSION['id_utilisateur'] ?? null;

        $result = $this->authService->updatePassword($idUtilisateur, $currentPassword, $newPassword, $confirmPassword);

        if ($result['success']) {
            $GLOBALS['messageSuccess'] = $result['message'];
            try {
                $this->authService->notifierMdpChange($idUtilisateur);
            } catch (\Throwable $e) {
                error_log('Erreur notif mdp change: ' . $e->getMessage());
            }
            return true;
        }

        $GLOBALS['messageErreur'] = $result['message'];
        $GLOBALS['messageSuccess'] = '';
        return false;
    }

    public function updateEmail($newEmail, $confirmEmail)
    {
        $idUtilisateur = $_SESSION['id_utilisateur'] ?? null;
        
        $ancienEmail = $this->getContactEmail(); // Get before update

        $result = $this->authService->updateEmail($idUtilisateur, $newEmail, $confirmEmail);

        if ($result['success']) {
            $GLOBALS['messageSuccess'] = $result['message'];
            try {
                $this->authService->notifierEmailModifie($idUtilisateur, $ancienEmail ?? '', $newEmail);
            } catch (\Throwable $e) {
                error_log('Erreur notif email modifie: ' . $e->getMessage());
            }
            return true;
        }

        $GLOBALS['messageErreur'] = $result['message'];
        $GLOBALS['messageSuccess'] = '';
        return false;
    }

    public function getContactEmail()
    {
        $idUtilisateur = $_SESSION['id_utilisateur'] ?? null;
        return $this->authService->getContactEmail($idUtilisateur);
    }
}
