<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../models/TypeUtilisateur.php';
require_once __DIR__ . '/../models/GroupeUtilisateur.php';
require_once __DIR__ . '/../models/NiveauAccesDonnees.php';
require_once __DIR__ . '/../models/AuditLog.php';

use Utilisateur;
use TypeUtilisateur;
use GroupeUtilisateur;
use NiveauAccesDonnees;
use AuditLog;
use PHPMailer\PHPMailer\PHPMailer;
require_once __DIR__ . '/../utils/EmailService.php';

// Composer autoload (optionnel). Si vendor/ n'est pas installé, certaines fonctions (email) seront indisponibles.
$composerAutoload = __DIR__ . '/../../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

/**
 * Service métier de la gestion des utilisateurs
 *
 * Contient toute la logique métier pour :
 * - Vérification de disponibilité de login
 * - Ajout d'un utilisateur (unitaire et en masse)
 * - Modification d'un utilisateur
 * - Activation / désactivation d'utilisateurs
 * - Envoi d'accès par email
 * - Génération de mots de passe et tokens de réinitialisation
 * - Envoi d'emails d'inscription (PHPMailer)
 * - Récupération des listes de référence pour les vues
 */
class GestionUtilisateurService
{
    /** @var Utilisateur */
    private $utilisateur;

    /** @var TypeUtilisateur */
    private $typeUtilisateur;

    /** @var GroupeUtilisateur */
    private $groupeUtilisateur;

    /** @var NiveauAccesDonnees */
    private $niveauAcces;

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
        $this->utilisateur = new Utilisateur($db);
        $this->groupeUtilisateur = new GroupeUtilisateur($db);
        $this->typeUtilisateur = new TypeUtilisateur($db);
        $this->niveauAcces = new NiveauAccesDonnees($db);
        $this->auditLog = new AuditLog($db);
    }

    /**
     * Vérifie la disponibilité d'un login et propose une alternative si nécessaire
     *
     * @param string $baseLogin Le login à vérifier
     * @return array Résultat de la vérification
     */
    public function checkLoginAvailability(string $baseLogin): array
    {
        $loginToCheck = $baseLogin;
        $counter = 1;

        // Vérifier si le login existe déjà
        while ($this->utilisateur->isLoginUsed($loginToCheck)) {
            $counter++;
            $loginToCheck = $baseLogin . $counter;

            // Limite de sécurité pour éviter une boucle infinie
            if ($counter > 100) {
                return [
                    'success' => false,
                    'available' => false,
                    'message' => 'Trop de doublons trouvés'
                ];
            }
        }

        // Si le login original est disponible
        if ($loginToCheck === $baseLogin) {
            return [
                'success' => true,
                'available' => true,
                'login' => $baseLogin,
                'message' => 'Login disponible'
            ];
        }

        // Si on a dû modifier le login
        return [
            'success' => true,
            'available' => false,
            'suggestedLogin' => $loginToCheck,
            'originalLogin' => $baseLogin,
            'message' => "Le login '$baseLogin' existe déjà. Suggestion : $loginToCheck"
        ];
    }

    /**
     * Crée un token de réinitialisation de mot de passe
     *
     * @param string $email
     * @return string Le token généré
     */
    public function createPasswordResetToken(string $email): string
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $stmt = $this->db->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)');
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires', $expires);
        $stmt->execute();
        return $token;
    }

    /**
     * Construit le lien de réinitialisation de mot de passe
     *
     * @param string $token
     * @return string L'URL complète
     */
    public function buildResetLink(string $token): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // reset_password.php est dans /public/
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/public/'), '/\\');
        if ($base === '' || $base === '.') {
            $base = '/public';
        }
        return $scheme . $host . $base . '/reset_password.php?token=' . urlencode($token);
    }

    private function buildLoginLink(): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/public/layout.php');
        $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        if (preg_match('#/public/app$#', $base)) {
            return $scheme . $host . $base . '/index.php?_path=/login';
        }

        if ($base === '' || $base === '.') {
            $base = '/public';
        }

        return $scheme . $host . $base . '/app/index.php?_path=/login';
    }

    private function normalizeEmailValue($email): ?string
    {
        $email = trim((string) $email);
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $email;
    }

    private function recordExists(string $table, string $column, int $id): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM {$table} WHERE {$column} = ? LIMIT 1");
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    private function groupBelongsToType(int $groupId, int $typeId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM groupe_utilisateur
            WHERE id_GU = ? AND id_type_utilisateur = ?
            LIMIT 1
        ");
        $stmt->execute([$groupId, $typeId]);
        return (bool) $stmt->fetchColumn();
    }

    private function resolveInvitationEmail(array $data, string $nomUtilisateur, int $idTypeUtilisateur, string $loginUtilisateur): ?string
    {
        $sourceEmail = $this->normalizeEmailValue($data['source_reference_email'] ?? null);
        if ($sourceEmail !== null) {
            return $sourceEmail;
        }

        $resolvedEmail = $this->normalizeEmailValue($this->utilisateur->getEmailByNomAndType($nomUtilisateur, $idTypeUtilisateur));
        if ($resolvedEmail !== null) {
            return $resolvedEmail;
        }

        return $this->normalizeEmailValue($loginUtilisateur);
    }

    private function safeAudit(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            error_log('Audit utilisateur: ' . $e->getMessage());
        }
    }

    private function buildCreateUserFailureMessage(\Throwable $e): string
    {
        $message = $e->getMessage();

        if (stripos($message, 'Integrity constraint violation') !== false) {
            return "Les informations de type, groupe ou niveau d'accès sont incohérentes.";
        }

        if (stripos($message, 'Duplicate entry') !== false) {
            return 'Ce login est déjà utilisé.';
        }

        return "Erreur lors de l'ajout de l'utilisateur.";
    }

    /**
     * Ajoute un nouvel utilisateur
     *
     * @param array $data Données du formulaire
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string]
     */
    public function addUtilisateur(array $data, int $userId): array
    {
        $nom_utilisateur = trim((string) ($data['nom_utilisateur'] ?? ''));
        $id_type_utilisateur = (int) ($data['id_type_utilisateur'] ?? 0);
        $id_GU = (int) ($data['id_GU'] ?? 0);
        $login_utilisateur = trim((string) ($data['login_utilisateur'] ?? ''));
        $statut_utilisateur = trim((string) ($data['statut_utilisateur'] ?? ''));
        $id_niveau_acces = (int) ($data['id_niveau_acces'] ?? 0);

        if (
            empty($nom_utilisateur) || empty($id_type_utilisateur) || empty($id_GU) ||
            empty($login_utilisateur) || empty($statut_utilisateur) || empty($id_niveau_acces)
        ) {
            return ['success' => false, 'message' => 'Tous les champs sont obligatoires.'];
        }

        if (!$this->recordExists('type_utilisateur', 'id_type_utilisateur', $id_type_utilisateur)) {
            return ['success' => false, 'message' => "Type d'utilisateur invalide."];
        }

        if (!$this->groupBelongsToType($id_GU, $id_type_utilisateur)) {
            return ['success' => false, 'message' => "Le groupe utilisateur sélectionné ne correspond pas au type choisi."];
        }

        if (!$this->recordExists('niveau_acces_donnees', 'id_niveau_acces_donnees', $id_niveau_acces)) {
            return ['success' => false, 'message' => "Niveau d'accès invalide."];
        }

        if (strlen($login_utilisateur) > 60) {
            return ['success' => false, 'message' => 'Le login ne doit pas dépasser 60 caractères.'];
        }

        if ($this->utilisateur->isLoginUsed($login_utilisateur)) {
            return ['success' => false, 'message' => 'Ce login est déjà utilisé par un autre utilisateur.'];
        }

        $invitationEmail = $this->resolveInvitationEmail($data, $nom_utilisateur, $id_type_utilisateur, $login_utilisateur);
        $temporaryPassword = $this->generateRandomPassword(12);
        $mdp_hash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
        $resetLink = null;
        $manageTransaction = !$this->db->inTransaction();

        try {
            if ($manageTransaction) {
                $this->db->beginTransaction();
            }

            $created = $this->utilisateur->ajouterUtilisateur(
                $nom_utilisateur,
                $id_type_utilisateur,
                $id_GU,
                $id_niveau_acces,
                $statut_utilisateur,
                $login_utilisateur,
                $mdp_hash
            );

            if (!$created) {
                throw new \RuntimeException("Insertion utilisateur impossible.");
            }

            if ($invitationEmail !== null) {
                $token = $this->createPasswordResetToken($invitationEmail);
                $resetLink = $this->buildResetLink($token);
            }

            if ($manageTransaction && $this->db->inTransaction()) {
                $this->db->commit();
            }
        } catch (\Throwable $e) {
            if ($manageTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->safeAudit(function () use ($userId) {
                $this->auditLog->logCreation($userId, 'utilisateur', 'Erreur');
            });
            error_log('GestionUtilisateurService::addUtilisateur - ' . $e->getMessage());
            return ['success' => false, 'message' => $this->buildCreateUserFailureMessage($e)];
        }

        if ($invitationEmail === null) {
            $this->safeAudit(function () use ($userId) {
                $this->auditLog->logCreation($userId, 'utilisateur', 'Succès');
            });
            return [
                'success' => true,
                'feedback_type' => 'warning',
                'message' => "Utilisateur ajouté avec succès. Aucun email n'est renseigné pour ce profil, les accès n'ont pas été envoyés.",
            ];
        }

        $emailResult = $this->envoyerEmailInscriptionPHPMailer($invitationEmail, $nom_utilisateur, $login_utilisateur, $temporaryPassword, $resetLink);
        if ($emailResult['success']) {
            $this->safeAudit(function () use ($userId) {
                $this->auditLog->logCreation($userId, 'utilisateur', 'Succès');
            });
            return [
                'success' => true,
                'feedback_type' => 'success',
                'message' => "Utilisateur ajouté avec succès. Un lien de définition du mot de passe a été envoyé.",
            ];
        }

        $this->safeAudit(function () use ($userId) {
            $this->auditLog->logCreation($userId, 'utilisateur', 'Erreur');
        });
        return [
            'success' => true,
            'feedback_type' => 'warning',
            'message' => "Utilisateur ajouté avec succès mais erreur lors de l'envoi de l'email: " . $emailResult['message'],
        ];
    }

    /**
     * Ajoute des utilisateurs en masse
     *
     * @param array $selectedPersons Personnes sélectionnées (format type_id)
     * @param array $commonData Données communes (id_type_utilisateur, id_GU, id_niveau_acces, statut_utilisateur)
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string]
     */
    public function addUtilisateursEnMasse(array $selectedPersons, array $commonData, int $userId): array
    {
        $utilisateurs = [];
        $utilisateurModel = new Utilisateur($this->db);
        $selectedTypeId = (int) ($commonData['id_type_utilisateur'] ?? 0);
        $allowedPersonTypes = [];
        if ($selectedTypeId === 4) {
            $allowedPersonTypes = ['pers'];
        } elseif ($selectedTypeId === 5 || $selectedTypeId === 6) {
            $allowedPersonTypes = ['ens'];
        } elseif ($selectedTypeId === 7) {
            $allowedPersonTypes = ['etu'];
        }

        foreach ($selectedPersons as $person) {
            $parts = explode('_', (string) $person, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$type, $id] = $parts;
            if (!empty($allowedPersonTypes) && !in_array($type, $allowedPersonTypes, true)) {
                continue;
            }
            $login = '';
            $nom = '';

            switch ($type) {
                case 'ens':
                    $enseignant = $utilisateurModel->getEnseignantById($id);
                    if ($enseignant && !$utilisateurModel->isLoginUsed($enseignant->mail_enseignant)) {
                        $login = $enseignant->mail_enseignant;
                        $nom = $enseignant->nom_enseignant . ' ' . $enseignant->prenom_enseignant;
                    }
                    break;
                case 'pers':
                    $personnel = $utilisateurModel->getPersonnelById($id);
                    if ($personnel && !$utilisateurModel->isLoginUsed($personnel->email_pers_admin)) {
                        $login = $personnel->email_pers_admin;
                        $nom = $personnel->nom_pers_admin . ' ' . $personnel->prenom_pers_admin;
                    }
                    break;
                case 'etu':
                    $etudiant = $utilisateurModel->getEtudiantById($id);
                    if ($etudiant && !$utilisateurModel->isLoginUsed($etudiant->email_etu)) {
                        $login = $etudiant->email_etu;
                        $nom = $etudiant->nom_etu . ' ' . $etudiant->prenom_etu;
                    }
                    break;
            }

            if ($login && $nom) {
                $utilisateurs[] = [
                    'nom' => $nom,
                    'login' => $login,
                    'id_type' => $commonData['id_type_utilisateur'],
                    'id_groupe' => $commonData['id_GU'],
                    'id_niveau' => $commonData['id_niveau_acces'],
                    'statut' => $commonData['statut_utilisateur']
                ];
            }
        }

        if (empty($utilisateurs)) {
            return ['success' => false, 'message' => 'Aucun utilisateur valide à ajouter'];
        }

        try {
            $utilisateursAjoutes = $utilisateurModel->ajouterUtilisateursEnMasse($utilisateurs);

            // Envoyer les emails aux utilisateurs ajoutés
            $emailErrors = [];
            foreach ($utilisateursAjoutes as $utilisateur) {
                $token = $this->createPasswordResetToken($utilisateur['login']);
                $resetLink = $this->buildResetLink($token);
                $temporaryPassword = $this->generateRandomPassword(12);
                $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
                if (!$utilisateurModel->updatePasswordByLogin($utilisateur['login'], $passwordHash)) {
                    $emailErrors[] = $utilisateur['nom'] . ': mise à jour du mot de passe impossible';
                    continue;
                }
                $emailResult = $this->envoyerEmailInscriptionPHPMailer(
                    $utilisateur['login'],
                    $utilisateur['nom'],
                    $utilisateur['login'],
                    $temporaryPassword,
                    $resetLink
                );
                if (!$emailResult['success']) {
                    $emailErrors[] = $utilisateur['nom'] . ': ' . $emailResult['message'];
                }
            }

            if (empty($emailErrors)) {
                $this->auditLog->logCreation($userId, 'utilisateur', 'Succès');
                return ['success' => true, 'message' => count($utilisateursAjoutes) . " utilisateur(s) ajouté(s) avec succès et emails envoyés."];
            } else {
                $this->auditLog->logCreation($userId, 'utilisateur', 'Erreur');
                return ['success' => false, 'message' => "Utilisateurs ajoutés mais erreurs d'envoi: " . implode('; ', $emailErrors)];
            }
        } catch (\Throwable $e) {
            $this->auditLog->logCreation($userId, 'utilisateur', 'Erreur');
            return ['success' => false, 'message' => "Erreur lors de l'ajout en masse : " . $e->getMessage()];
        }
    }

    /**
     * Modifie un utilisateur existant
     *
     * @param array $data Données du formulaire
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateUtilisateur(array $data, int $userId): array
    {
        $id_utilisateur = $data['id_utilisateur'] ?? '';
        $nom_utilisateur = $data['nom_utilisateur'] ?? '';
        $id_type_utilisateur = $data['id_type_utilisateur'] ?? '';
        $id_GU = $data['id_GU'] ?? '';
        $login_utilisateur = $data['login_utilisateur'] ?? '';
        $statut_utilisateur = $data['statut_utilisateur'] ?? '';
        $id_niveau_acces = $data['id_niveau_acces'] ?? '';

        if (
            empty($id_utilisateur) || empty($nom_utilisateur) || empty($id_type_utilisateur) ||
            empty($id_GU) || empty($login_utilisateur) || empty($statut_utilisateur) ||
            empty($id_niveau_acces)
        ) {
            return ['success' => false, 'message' => 'Tous les champs sont obligatoires.'];
        }

        if (
            $this->utilisateur->updateUtilisateur(
                $nom_utilisateur,
                $id_type_utilisateur,
                $id_GU,
                $id_niveau_acces,
                $statut_utilisateur,
                $login_utilisateur,
                $id_utilisateur
            )
        ) {
            $this->auditLog->logModification($userId, 'utilisateur', 'Succès');
            return ['success' => true, 'message' => 'Utilisateur modifié avec succès.'];
        }

        $this->auditLog->logModification($userId, 'utilisateur', 'Erreur');
        return ['success' => false, 'message' => "Erreur lors de la modification de l'utilisateur."];
    }

    /**
     * Active plusieurs utilisateurs
     *
     * @param array $ids Liste d'identifiants
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string]
     */
    public function enableMultipleUtilisateurs(array $ids, int $userId): array
    {
        foreach ($ids as $id) {
            if (!$this->utilisateur->reactiverUtilisateur($id)) {
                $this->auditLog->logModification($userId, 'utilisateur', 'Erreur');
                return ['success' => false, 'message' => "Erreur lors de l'activation des utilisateurs."];
            }
        }
        $this->auditLog->logModification($userId, 'utilisateur', 'Succès');
        return ['success' => true, 'message' => 'Utilisateurs activés avec succès.'];
    }

    /**
     * Désactive plusieurs utilisateurs
     *
     * @param array $ids Liste d'identifiants
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string]
     */
    public function disableMultipleUtilisateurs(array $ids, int $userId): array
    {
        foreach ($ids as $id) {
            if (!$this->utilisateur->desactiverUtilisateur($id)) {
                $this->auditLog->logModification($userId, 'utilisateur', 'Erreur');
                return ['success' => false, 'message' => "Erreur lors de la désactivation des utilisateurs."];
            }
        }
        $this->auditLog->logModification($userId, 'utilisateur', 'Succès');
        return ['success' => true, 'message' => 'Utilisateurs désactivés avec succès.'];
    }

    /**
     * Supprime plusieurs utilisateurs
     *
     * @param array $ids Liste d'identifiants
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteMultipleUtilisateurs(array $ids, int $userId): array
    {
        foreach ($ids as $id) {
            if (!$this->utilisateur->supprimerUtilisateur($id)) {
                $this->auditLog->logSuppression($userId, 'utilisateur', 'Erreur');
                return ['success' => false, 'message' => "Erreur lors de la suppression des utilisateurs."];
            }
        }
        $this->auditLog->logSuppression($userId, 'utilisateur', 'Succès');
        return ['success' => true, 'message' => 'Utilisateurs supprimés avec succès.'];
    }

    /**
     * Envoie les accès par email à plusieurs utilisateurs
     *
     * @param array $ids Liste d'identifiants
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string]
     */
    public function sendAccessToMultipleUtilisateurs(array $ids, int $userId): array
    {
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            $user = $this->utilisateur->getUtilisateurById($id);
            if ($user) {
                // Récupérer l'email réel de l'utilisateur à partir de son nom et type
                $email = $this->utilisateur->getEmailByNomAndType(
                    $user->nom_utilisateur,
                    $user->id_type_utilisateur
                );

                if (!$email) {
                    $errorCount++;
                    $errors[] = $user->nom_utilisateur . ' (email introuvable - type: ' . $user->id_type_utilisateur . ')';
                    continue;
                }

                // Créer un token de réinitialisation de mot de passe avec l'email réel
                try {
                    $token = $this->createPasswordResetToken($email);
                    $resetLink = $this->buildResetLink($token);

                    $temporaryPassword = $this->generateRandomPassword(12);
                    $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
                    if (!$this->utilisateur->updatePasswordByLogin($user->login_utilisateur, $passwordHash)) {
                        $errorCount++;
                        $errors[] = $user->nom_utilisateur . ' (mise à jour du mot de passe impossible)';
                        continue;
                    }

                    // Envoyer l'email avec le mot de passe temporaire et le lien de définition du mot de passe
                    $emailResult = $this->envoyerEmailInscriptionPHPMailer(
                        $email,
                        $user->nom_utilisateur,
                        $user->login_utilisateur,
                        $temporaryPassword,
                        $resetLink
                    );

                    if ($emailResult['success']) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = $user->nom_utilisateur . ' - ' . $emailResult['message'];
                    }
                } catch (\Throwable $e) {
                    $errorCount++;
                    $errors[] = $user->nom_utilisateur . ' (erreur: ' . $e->getMessage() . ')';
                }
            }
        }

        if ($successCount > 0 && $errorCount === 0) {
            $this->auditLog->logCreation($userId, 'envoi_acces', 'Succès');
            return ['success' => true, 'message' => "Accès envoyé avec succès à {$successCount} utilisateur(s)."];
        } elseif ($successCount > 0 && $errorCount > 0) {
            $this->auditLog->logCreation($userId, 'envoi_acces', 'Succès');
            return ['success' => true, 'message' => "Accès envoyé à {$successCount} utilisateur(s). Échec: {$errorCount}. Détails: " . implode('; ', $errors)];
        } else {
            $this->auditLog->logCreation($userId, 'envoi_acces', 'Erreur');
            return ['success' => false, 'message' => "Erreur lors de l'envoi des accès. Détails: " . implode('; ', $errors)];
        }
    }

    /**
     * Récupère un utilisateur par son identifiant
     *
     * @param int $id
     * @return mixed
     */
    public function getUtilisateurById($id)
    {
        return $this->utilisateur->getUtilisateurById($id);
    }

    /**
     * Récupère les personnes non encore enregistrées comme utilisateurs
     *
     * @return array Tableau associatif [enseignants, personnel, etudiants]
     */
    public function getNonUtilisateurs(): array
    {
        return [
            'enseignantsNonUtilisateurs' => $this->utilisateur->getEnseignantsNonUtilisateurs(),
            'personnelNonUtilisateurs' => $this->utilisateur->getPersonnelNonUtilisateurs(),
            'etudiantsNonUtilisateurs' => $this->utilisateur->getEtudiantsNonUtilisateurs(),
        ];
    }

    /**
     * Récupère toutes les listes de référence nécessaires aux vues
     *
     * @return array Tableau associatif des listes
     */
    public function getReferenceLists(): array
    {
        return [
            'utilisateurs' => $this->utilisateur->getAllUtilisateurs(),
            'types_utilisateur' => $this->typeUtilisateur->getAllTypeUtilisateur(),
            'groupes_utilisateur' => $this->groupeUtilisateur->getAllGroupeUtilisateur(),
            'niveau_acces' => $this->niveauAcces->getAllNiveauxAccesDonnees(),
        ];
    }

    /**
     * Génère un mot de passe aléatoire
     *
     * @param int $length Longueur du mot de passe
     * @return string
     */
    public function generateRandomPassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $password;
    }


    /**
     * Envoie un email d'inscription via PHPMailer
     *
     * @param string      $email
     * @param string      $nom
     * @param string      $login
     * @param string|null $motDePasse
     * @param string|null $resetLink
     * @return array ['success' => bool, 'message' => string]
     */
    public function envoyerEmailInscriptionPHPMailer($email, $nom, $login, $motDePasse = null, $resetLink = null): array
    {
        try {
            $emailService = new \EmailService();
            
            // Build the dynamic variables
            $login_url = $this->buildLoginLink();
            
            $password_row = '';
            if ($motDePasse) {
                $password_row = '
                <tr>
                    <td style="padding-bottom: 12px;" class="info-label">Mot de passe :</td>
                    <td style="padding-bottom: 12px;">
                        <div class="copyable-container">
                            <code class="copy-value">' . htmlspecialchars($motDePasse) . '</code>
                            <span class="copy-btn" data-copy="' . htmlspecialchars($motDePasse) . '">Copier</span>
                        </div>
                    </td>
                </tr>';
            }

            $reset_password_section = '';
            if ($resetLink) {
                $reset_password_section = '
                <p style="margin-top: 15px; border-top: 1px solid #cbd5e1; padding-top: 15px;"><strong>Définir ou réinitialiser votre mot de passe :</strong></p>
                <p><a href="' . htmlspecialchars($resetLink) . '" style="color: #2980b9; word-break: break-all;">' . htmlspecialchars($resetLink) . '</a></p>
                <p style="color: #ef4444; font-size: 0.85em; margin-top: 5px;">Ce lien expire dans 1 heure.</p>';
            }
            
            $data = [
                'nom' => htmlspecialchars($nom),
                'login' => htmlspecialchars($login),
                'password_row' => $password_row,
                'reset_password_section' => $reset_password_section,
                'login_url' => $login_url
            ];

            error_log("Tentative d'envoi d'email à : " . $email);
            $result = $emailService->sendTemplate('USER_WELCOME', $email, $data);
            
            if ($result) {
                error_log("Email envoyé avec succès à : " . $email);
                file_put_contents(
                    __DIR__ . '/../../logs/email.log',
                    date('Y-m-d H:i:s') . " - Email envoyé avec succès à : $email\n",
                    FILE_APPEND | LOCK_EX
                );
                return ['success' => true, 'message' => 'Email envoyé'];
            } else {
                throw new \Exception("L'envoi a retourné false");
            }

        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            error_log("Erreur d'envoi d'email: " . $errorMessage);

            $logMessage = date('Y-m-d H:i:s') . " - ERREUR Email à $email: " . $errorMessage . "\n";
            file_put_contents(__DIR__ . '/../../logs/email.log', $logMessage, FILE_APPEND | LOCK_EX);

            return ['success' => false, 'message' => $errorMessage];
        }
    }
}
