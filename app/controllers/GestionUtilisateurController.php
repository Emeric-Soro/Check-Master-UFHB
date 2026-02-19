<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Utilisateur.php";
require_once __DIR__ . "/../models/TypeUtilisateur.php";
require_once __DIR__ . "/../models/GroupeUtilisateur.php";
require_once __DIR__ . "/../models/NiveauAccesDonnees.php";
require_once __DIR__ . "/../models/AuditLog.php";
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

// Composer autoload (optionnel). Si vendor/ n'est pas installé, certaines fonctions (email) seront indisponibles.
$composerAutoload = __DIR__ . '/../../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

class GestionUtilisateurController
{
    private $utilisateur;

    private $typeUtilisateur;

    private $groupeUtilisateur;

    private $niveauAcces;
    private $auditLog;



    public function __construct()
    {

        $this->utilisateur = new Utilisateur(Database::getConnection());
        $this->groupeUtilisateur = new GroupeUtilisateur(Database::getConnection());
        $this->typeUtilisateur = new TypeUtilisateur(Database::getConnection());
        $this->niveauAcces = new NiveauAccesDonnees(Database::getConnection());
        $this->auditLog = new AuditLog(Database::getConnection());

    }

    /**
     * Vérifie la disponibilité d'un login et propose une alternative si nécessaire
     * Endpoint AJAX pour vérification en temps réel
     */
    public function checkLoginAvailability()
    {
        header('Content-Type: application/json');

        if (!isset($_GET['login']) || empty($_GET['login'])) {
            echo json_encode(['success' => false, 'message' => 'Login non fourni']);
            exit;
        }

        $baseLogin = trim($_GET['login']);
        $loginToCheck = $baseLogin;
        $counter = 1;

        // Vérifier si le login existe déjà
        while ($this->utilisateur->isLoginUsed($loginToCheck)) {
            $counter++;
            $loginToCheck = $baseLogin . $counter;

            // Limite de sécurité pour éviter une boucle infinie
            if ($counter > 100) {
                echo json_encode([
                    'success' => false,
                    'available' => false,
                    'message' => 'Trop de doublons trouvés'
                ]);
                exit;
            }
        }

        // Si le login original est disponible
        if ($loginToCheck === $baseLogin) {
            echo json_encode([
                'success' => true,
                'available' => true,
                'login' => $baseLogin,
                'message' => 'Login disponible'
            ]);
        } else {
            // Si on a dû modifier le login
            echo json_encode([
                'success' => true,
                'available' => false,
                'suggestedLogin' => $loginToCheck,
                'originalLogin' => $baseLogin,
                'message' => "Le login '$baseLogin' existe déjà. Suggestion : $loginToCheck"
            ]);
        }
        exit;
    }

    private function createPasswordResetToken(string $email): string
    {
        $db = Database::getConnection();
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $stmt = $db->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)');
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires', $expires);
        $stmt->execute();
        return $token;
    }

    private function buildResetLink(string $token): string
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

    // Afficher la liste des étudiants
    public function index()
    {
        // Gérer les requêtes AJAX
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'checkLogin') {
            $this->checkLoginAvailability();
            return; // Sortir après le traitement AJAX
        }

        $utilisateur_a_modifier = null;
        $messageErreur = '';
        $messageSuccess = '';
        $action = $_GET['action'] ?? '';

        try {
            // Récupérer les personnes non enregistrées comme utilisateurs
            $enseignantsNonUtilisateurs = $this->utilisateur->getEnseignantsNonUtilisateurs();
            $personnelNonUtilisateurs = $this->utilisateur->getPersonnelNonUtilisateurs();
            // Récupérer tous les étudiants non-utilisateurs (avec ou sans inscription)
            $etudiantsNonUtilisateurs = $this->utilisateur->getEtudiantsNonUtilisateurs();

            // Gestion des actions GET pour les modales
            if ($action === 'edit' && isset($_GET['id_utilisateur'])) {
                $utilisateur_a_modifier = $this->utilisateur->getUtilisateurById($_GET['id_utilisateur']);
                if (!$utilisateur_a_modifier) {
                    $messageErreur = "Utilisateur non trouvé.";
                }
            } elseif ($action === 'add' || $action === 'addMasse') {
                // Pour l'ajout, on initialise un objet vide
                $utilisateur_a_modifier = (object) [
                    'id_utilisateur' => '',
                    'nom_utilisateur' => '',
                    'login_utilisateur' => '',
                    'id_type_utilisateur' => '',
                    'statut_utilisateur' => 'Actif',
                    'id_GU' => '',
                    'id_niv_acces_donnee' => ''
                ];
            }

            // Gestion des actions POST
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Ajout d'un nouvel utilisateur
                if (isset($_POST['btn_add_utilisateur'])) {
                    $nom_utilisateur = $_POST['nom_utilisateur'] ?? '';
                    $id_type_utilisateur = $_POST['id_type_utilisateur'] ?? '';
                    $id_GU = $_POST['id_GU'] ?? '';
                    $login_utilisateur = $_POST['login_utilisateur'] ?? '';
                    $statut_utilisateur = $_POST['statut_utilisateur'] ?? '';
                    $id_niveau_acces = $_POST['id_niveau_acces'] ?? '';

                    if (
                        empty($nom_utilisateur) || empty($id_type_utilisateur) || empty($id_GU) ||
                        empty($login_utilisateur) || empty($statut_utilisateur) || empty($id_niveau_acces)
                    ) {
                        $messageErreur = "Tous les champs sont obligatoires.";
                    } else {
                        // Vérifier si le login est déjà utilisé
                        if ($this->utilisateur->isLoginUsed($login_utilisateur)) {
                            $messageErreur = "Ce login (email) est déjà utilisé par un autre utilisateur.";
                        } else {
                            // Récupérer l'email réel de l'utilisateur à partir de son nom et type
                            $email = $this->utilisateur->getEmailByNomAndType($nom_utilisateur, $id_type_utilisateur);

                            if (!$email) {
                                $messageErreur = "Impossible de trouver l'email de cet utilisateur dans la base de données.";
                            } else {
                                // Mot de passe technique aléatoire (l'utilisateur doit définir le sien via lien)
                                $mdp_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

                                if (
                                    $this->utilisateur->ajouterUtilisateur(
                                        $nom_utilisateur,
                                        $id_type_utilisateur,
                                        $id_GU,
                                        $id_niveau_acces,
                                        $statut_utilisateur,
                                        $login_utilisateur,
                                        $mdp_hash
                                    )
                                ) {
                                    $token = $this->createPasswordResetToken($email);
                                    $resetLink = $this->buildResetLink($token);
                                    $emailResult = $this->envoyerEmailInscriptionPHPMailer($email, $nom_utilisateur, $login_utilisateur, null, $resetLink);
                                    if ($emailResult['success']) {
                                        $messageSuccess = "Utilisateur ajouté avec succès. Un lien de définition du mot de passe a été envoyé.";
                                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'utilisateur', 'Succès');
                                    } else {
                                        $messageSuccess = "Utilisateur ajouté avec succès mais erreur lors de l'envoi de l'email: " . $emailResult['message'];
                                        // La DB n'accepte que {'Erreur','Succès'}
                                        $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur');
                                    }
                                } else {
                                    $messageErreur = "Erreur lors de l'ajout de l'utilisateur.";
                                    $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur');
                                }
                            }
                        }
                    }
                }

                // Traitement de l'ajout en masse
                if (isset($_POST['btn_add_multiple']) && !empty($_POST['selected_persons'])) {

                    $utilisateurs = [];
                    $utilisateurModel = new Utilisateur(Database::getConnection());

                    foreach ($_POST['selected_persons'] as $person) {
                        list($type, $id) = explode('_', $person);
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
                                'id_type' => $_POST['id_type_utilisateur'],
                                'id_groupe' => $_POST['id_GU'],
                                'id_niveau' => $_POST['id_niveau_acces'],
                                'statut' => $_POST['statut_utilisateur']
                            ];
                        }
                    }

                    if (!empty($utilisateurs)) {
                        try {
                            $utilisateursAjoutes = $utilisateurModel->ajouterUtilisateursEnMasse($utilisateurs);

                            // Envoyer les emails aux utilisateurs ajoutés
                            $emailErrors = [];
                            foreach ($utilisateursAjoutes as $utilisateur) {
                                $token = $this->createPasswordResetToken($utilisateur['login']);
                                $resetLink = $this->buildResetLink($token);
                                $emailResult = $this->envoyerEmailInscriptionPHPMailer(
                                    $utilisateur['login'],
                                    $utilisateur['nom'],
                                    $utilisateur['login'],
                                    null,
                                    $resetLink
                                );
                                if (!$emailResult['success']) {
                                    $emailErrors[] = $utilisateur['nom'] . ': ' . $emailResult['message'];
                                }
                            }

                            if (empty($emailErrors)) {
                                $messageSuccess = count($utilisateursAjoutes) . " utilisateur(s) ajouté(s) avec succès et emails envoyés.";
                                $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'utilisateur', 'Succès');
                            } else {
                                $messageErreur = "Utilisateurs ajoutés mais erreurs d'envoi: " . implode('; ', $emailErrors);
                                $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur');
                            }
                        } catch (Exception $e) {
                            $messageErreur = "Erreur lors de l'ajout en masse : " . $e->getMessage();
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur');
                        }
                    } else {
                        $messageErreur = "Aucun utilisateur valide à ajouter";
                    }


                }

                // Modification d'un utilisateur
                if (isset($_POST['btn_modifier_utilisateur'])) {
                    $id_utilisateur = $_POST['id_utilisateur'] ?? '';
                    $nom_utilisateur = $_POST['nom_utilisateur'] ?? '';
                    $id_type_utilisateur = $_POST['id_type_utilisateur'] ?? '';
                    $id_GU = $_POST['id_GU'] ?? '';
                    $login_utilisateur = $_POST['login_utilisateur'] ?? '';
                    $statut_utilisateur = $_POST['statut_utilisateur'] ?? '';
                    $id_niveau_acces = $_POST['id_niveau_acces'] ?? '';


                    if (
                        empty($id_utilisateur) || empty($nom_utilisateur) || empty($id_type_utilisateur) ||
                        empty($id_GU) || empty($login_utilisateur) || empty($statut_utilisateur) ||
                        empty($id_niveau_acces)
                    ) {
                        $messageErreur = "Tous les champs sont obligatoires.";
                    } else {
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
                            $messageSuccess = "Utilisateur modifié avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Succès');
                        } else {
                            $messageErreur = "Erreur lors de la modification de l'utilisateur.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur');
                        }
                    }

                }

                // Activation ou désactivation d'utilisateurs
                if (isset($_POST['selected_ids'])) {
                    if (isset($_POST['submit_enable_multiple']) && $_POST['submit_enable_multiple'] == 3) {
                        $success = true;
                        foreach ($_POST['selected_ids'] as $id) {
                            if (!$this->utilisateur->reactiverUtilisateur($id)) {
                                $success = false;
                                break;
                            }
                        }
                        if ($success) {
                            $messageSuccess = "Utilisateurs activés avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Succès');
                        } else {
                            $messageErreur = "Erreur lors de l'activation des utilisateurs.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur');
                        }
                    } elseif (isset($_POST['submit_disable_multiple']) && $_POST['submit_disable_multiple'] == 2) {
                        $success = true;
                        foreach ($_POST['selected_ids'] as $id) {
                            if (!$this->utilisateur->desactiverUtilisateur($id)) {
                                $success = false;
                                break;
                            }
                        }
                        if ($success) {
                            $messageSuccess = "Utilisateurs désactivés avec succès.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Succès');
                        } else {
                            $messageErreur = "Erreur lors de la désactivation des utilisateurs.";
                            $this->auditLog->logModification($_SESSION['id_utilisateur'], 'utilisateur', 'Erreur');
                        }
                    } elseif (isset($_POST['submit_send_access']) && $_POST['submit_send_access'] == 4) {
                        // Envoi des accès par email
                        $successCount = 0;
                        $errorCount = 0;
                        $errors = [];

                        foreach ($_POST['selected_ids'] as $id) {
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

                                    // Envoyer l'email avec le lien de définition du mot de passe
                                    $emailResult = $this->envoyerEmailInscriptionPHPMailer(
                                        $email,
                                        $user->nom_utilisateur,
                                        $user->login_utilisateur,
                                        null,
                                        $resetLink
                                    );

                                    if ($emailResult['success']) {
                                        $successCount++;
                                    } else {
                                        $errorCount++;
                                        $errors[] = $user->nom_utilisateur . ' - ' . $emailResult['message'];
                                    }
                                } catch (Exception $e) {
                                    $errorCount++;
                                    $errors[] = $user->nom_utilisateur . ' (erreur: ' . $e->getMessage() . ')';
                                }
                            }
                        }

                        if ($successCount > 0 && $errorCount === 0) {
                            $messageSuccess = "Accès envoyé avec succès à {$successCount} utilisateur(s).";
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'envoi_acces', 'Succès');
                        } elseif ($successCount > 0 && $errorCount > 0) {
                            $messageSuccess = "Accès envoyé à {$successCount} utilisateur(s). Échec: {$errorCount}. Détails: " . implode('; ', $errors);
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'envoi_acces', 'Succès');
                        } else {
                            $messageErreur = "Erreur lors de l'envoi des accès. Détails: " . implode('; ', $errors);
                            $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'envoi_acces', 'Erreur');
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $messageErreur = "Erreur : " . $e->getMessage();
        }

        // Préparation des données pour la vue
        $GLOBALS['messageErreur'] = $messageErreur;
        $GLOBALS['messageSuccess'] = $messageSuccess;
        $GLOBALS['utilisateurs'] = $this->utilisateur->getAllUtilisateurs();
        $GLOBALS['types_utilisateur'] = $this->typeUtilisateur->getAllTypeUtilisateur();
        $GLOBALS['groupes_utilisateur'] = $this->groupeUtilisateur->getAllGroupeUtilisateur();
        $GLOBALS['niveau_acces'] = $this->niveauAcces->getAllNiveauxAccesDonnees();
        $GLOBALS['utilisateur_a_modifier'] = $utilisateur_a_modifier;
        $GLOBALS['action'] = $action;
        $GLOBALS['enseignantsNonUtilisateurs'] = $enseignantsNonUtilisateurs;
        $GLOBALS['personnelNonUtilisateurs'] = $personnelNonUtilisateurs;
        $GLOBALS['etudiantsNonUtilisateurs'] = $etudiantsNonUtilisateurs;
    }


    // Fonction pour générer un mot de passe aléatoire
    function generateRandomPassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

    function construireMessageHTML($nom, $login, $motDePasse, $resetLink = null)
    {
        // Construction du sujet
        $sujet = "Bienvenue sur Soutenance Manager, " . htmlspecialchars($nom) . " !";

        // Construction du corps du message HTML
        $message = '
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Bienvenue</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #10b981; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9fafb; }
                .footer { margin-top: 20px; padding: 10px; text-align: center; font-size: 12px; color: #6b7280; }
                .button {
                    display: inline-block; padding: 10px 20px; background-color: #10b981; 
                    color: white; text-decoration: none; border-radius: 5px; margin: 15px 0;
                }
                .credentials { background-color: #e5e7eb; padding: 15px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1> ' . htmlspecialchars($sujet) . '</h1>
                </div>
                
                <div class="content">
                    <p>Bonjour ' . htmlspecialchars($nom) . ',</p>
                    <p>Votre compte a été créé avec succès sur notre plateforme.</p>
                    
                    <div class="credentials">
                        <p><strong>Identifiant de connexion:</strong> ' . htmlspecialchars($login) . '</p>';

        // Ajout du mot de passe temporaire si fourni
        if ($resetLink) {
            $message .= '<p style="margin-top:10px"><strong>Définir votre mot de passe:</strong></p>
                         <p><a href="' . htmlspecialchars($resetLink) . '">' . htmlspecialchars($resetLink) . '</a></p>
                         <p style="color:#ef4444; font-size:0.9em;">Ce lien expire dans 1 heure.</p>';
        }

        $message .= '
                    </div>
                    
                    <p>Vous pouvez dès maintenant vous connecter à votre compte :</p>
                     <a href="page_connexion.php" class="button " style="color:#fff">Se connecter</a>
                    <p>Si vous n\'êtes pas à l\'origine de cette création de compte, veuillez ignorer cet email ou contacter notre support.</p>
                </div>
                
                <div class="footer">
                    <p>© ' . date('Y') . ' Soutenance Manager. Tous droits réservés.</p>
                </div>
            </div>
        </body>
        </html>';


        return $message;
    }


    function envoyerEmailInscriptionPHPMailer($email, $nom, $login, $motDePasse = null, $resetLink = null)
    {
        $mail = new PHPMailer(true);

        try {
            // Charger la configuration SMTP depuis le fichier de config
            $config_email = require __DIR__ . '/../config/email.php';

            // Configuration du serveur SMTP
            $mail->SMTPDebug = 0; // Désactiver le debug pour éviter l'affichage
            $mail->Debugoutput = function ($str, $level) {
                error_log("PHPMailer Debug: $str");
            };

            $mail->isSMTP();
            $mail->Host = $config_email['smtp']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config_email['smtp']['username'];
            $mail->Password = $config_email['smtp']['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $config_email['smtp']['port'];
            $mail->CharSet = 'UTF-8';

            // Destinataires
            $mail->setFrom($config_email['smtp']['from_email'], $config_email['smtp']['from_name']);
            $mail->addAddress($email, $nom);
            $mail->addReplyTo($config_email['smtp']['from_email'], 'Support technique');

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = "Bienvenue sur notre plateforme, $nom !";

            // Construction du message HTML
            $message = $this->construireMessageHTML($nom, $login, $motDePasse, $resetLink);
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);

            error_log("Tentative d'envoi d'email à : " . $email);
            $result = $mail->send();
            error_log("Email envoyé avec succès à : " . $email);

            // Écrire aussi dans un fichier de log personnalisé
            file_put_contents(
                __DIR__ . '/../../logs/email.log',
                date('Y-m-d H:i:s') . " - Email envoyé avec succès à : $email\n",
                FILE_APPEND | LOCK_EX
            );

            return ['success' => true, 'message' => 'Email envoyé'];
        } catch (Exception $e) {
            $errorMessage = $e->getMessage() . ' | ErrorInfo: ' . $mail->ErrorInfo;
            error_log("Erreur PHPMailer détaillée: " . $errorMessage);

            // Écrire l'erreur dans un fichier de log personnalisé
            $logMessage = date('Y-m-d H:i:s') . " - ERREUR Email à $email: " . $errorMessage . "\n";
            file_put_contents(__DIR__ . '/../../logs/email.log', $logMessage, FILE_APPEND | LOCK_EX);

            return ['success' => false, 'message' => $errorMessage];
        }
    }



}