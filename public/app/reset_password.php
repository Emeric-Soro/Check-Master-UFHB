<?php
require_once __DIR__ . '/../../app/Core/Autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use CheckMaster\Core\Session;
use CheckMaster\Core\Bootstrap;

Bootstrap::init();
Session::start();

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Utilisateur.php';
// EmailService : autoloadé par Composer classmap

use CheckMaster\Core\Csrf;
use CheckMaster\Security\DbRateLimiter;

$db = Database::getConnection();
$utilisateurModel = new Utilisateur($db);
$emailService = new EmailService();

function generateToken($length = 64)
{
    return bin2hex(random_bytes($length / 2));
}
function getPasswordResetByToken($db, $token)
{
    $stmt = $db->prepare('SELECT * FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW()');
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function markTokenUsed($db, $token)
{
    $stmt = $db->prepare('UPDATE password_resets SET used = 1 WHERE token = :token');
    $stmt->bindParam(':token', $token);
    $stmt->execute();
}

$success = '';
$error = '';
$csrf = Csrf::token();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $error = "Session expirée. Veuillez réessayer.";
    } else {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $identifier = strtolower(trim((string) ($_POST['email'] ?? '')));
        if ($identifier === '') {
            $identifier = '-';
        }
        $limiter = new DbRateLimiter($db);
        if (!$limiter->isAllowed('reset', $ip, $identifier)) {
            $error = "Trop de demandes de réinitialisation. Veuillez patienter avant de réessayer.";
        } else {
            $limiter->hit('reset', $ip, $identifier, 5, 15 * 60, 15 * 60);

            $email = trim((string) $_POST['email']);
            $stmt = $db->prepare('SELECT * FROM utilisateur WHERE login_utilisateur = :email');
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $success = "Si un compte existe pour cet email, un lien de réinitialisation a été envoyé.";
            if ($user) {
                $token = generateToken();
                $expires = date('Y-m-d H:i:s', time() + 3600);
                $stmt = $db->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)');
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':token', $token);
                $stmt->bindParam(':expires', $expires);
                $stmt->execute();

                $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://';
                $resetLink = $scheme . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
                $emailService->sendTemplate('PASSWORD_RESET', $email, ['reset_link' => $resetLink]);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'], $_POST['newPassword'], $_POST['confirmPassword'])) {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $error = "Session expirée. Veuillez réessayer.";
    } else {
        $token = $_POST['token'];
        $newPassword = $_POST['newPassword'];
        $confirmPassword = $_POST['confirmPassword'];
        $reset = getPasswordResetByToken($db, $token);
        if (!$reset) {
            $error = "Lien invalide ou expiré.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "Les mots de passe ne correspondent pas.";
        } elseif (strlen($newPassword) < 8) {
            $error = "Le mot de passe doit contenir au moins 8 caractères.";
        } elseif (!preg_match('/[A-Z]/', $newPassword)) {
            $error = "Le mot de passe doit contenir au moins une majuscule.";
        } elseif (!preg_match('/[0-9]/', $newPassword)) {
            $error = "Le mot de passe doit contenir au moins un chiffre.";
        } elseif (!preg_match('/[!@#$%^&*()_+\-=[\]{};\':"\\|,.<>\/?]+/', $newPassword)) {
            $error = "Le mot de passe doit contenir au moins un caractère spécial.";
        } else {
            $user = null;
            $email = $reset['email'];

            $stmt = $db->prepare('SELECT nom_enseignant, prenom_enseignant FROM enseignants WHERE mail_enseignant = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $enseignant = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($enseignant) {
                $nomComplet = $enseignant['nom_enseignant'] . ' ' . $enseignant['prenom_enseignant'];
                $stmt = $db->prepare('SELECT * FROM utilisateur WHERE nom_utilisateur = :nom');
                $stmt->execute(['nom' => $nomComplet]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$user) {
                $stmt = $db->prepare('SELECT nom_pers_admin, prenom_pers_admin FROM personnel_admin WHERE email_pers_admin = :email LIMIT 1');
                $stmt->execute(['email' => $email]);
                $personnel = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($personnel) {
                    $nomComplet = $personnel['nom_pers_admin'] . ' ' . $personnel['prenom_pers_admin'];
                    $stmt = $db->prepare('SELECT * FROM utilisateur WHERE nom_utilisateur = :nom');
                    $stmt->execute(['nom' => $nomComplet]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }

            if (!$user) {
                $stmt = $db->prepare('SELECT nom_etu, prenom_etu FROM etudiants WHERE email_etu = :email LIMIT 1');
                $stmt->execute(['email' => $email]);
                $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($etudiant) {
                    $nomComplet = $etudiant['nom_etu'] . ' ' . $etudiant['prenom_etu'];
                    $stmt = $db->prepare('SELECT * FROM utilisateur WHERE nom_utilisateur = :nom');
                    $stmt->execute(['nom' => $nomComplet]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }

            if ($user) {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $utilisateurModel->updatePassword($user['id_utilisateur'], $hashed);
                markTokenUsed($db, $token);
                $success = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
            } else {
                $error = "Utilisateur introuvable.";
            }
        }
    }
}

$showResetForm = isset($_GET['token']) && getPasswordResetByToken($db, $_GET['token']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation | CheckMaster</title>
    <link rel="stylesheet" href="../assets/css/checkmaster-theme.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="shortcut icon" href="../image/logo_cm_sbg.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="cm-login-page">
    <main class="cm-login-shell">
        <section class="cm-login-brand">
            <a href="../site/index.php" class="cm-login-back-link">
                <img src="../image/logo_cm_sbg.png" alt="UFHB">
                <span>Retourner sur l'accueil UFHB</span>
            </a>

            <h1>Réinitialisez votre mot de passe</h1>
            <p>Entrez votre adresse e-mail pour recevoir un lien de réinitialisation sécurisé.</p>
        </section>

        <section class="cm-login-card">
            <div class="cm-login-card__header">
                <img src="../image/logo_cm_sbg.png" alt="Logo CheckMaster">
                <h2>Réinitialisation</h2>
                <p><?php echo $showResetForm ? 'Définissez votre nouveau mot de passe' : 'Entrez votre email pour recevoir le lien'; ?>
                </p>
            </div>

            <?php if ($success !== ''): ?>
                <div class="cm-alert is-success" id="feedback" role="alert">
                    <span class="cm-alert__icon"><i class="fas fa-circle-check" aria-hidden="true"></i></span>
                    <div class="cm-alert__content">
                        <span class="cm-alert__message"><?php echo htmlspecialchars($success); ?></span>
                    </div>
                </div>
            <?php elseif ($error !== ''): ?>
                <div class="cm-alert is-danger" id="feedback" role="alert">
                    <span class="cm-alert__icon"><i class="fas fa-circle-exclamation" aria-hidden="true"></i></span>
                    <div class="cm-alert__content">
                        <span class="cm-alert__message"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($showResetForm): ?>
                <form method="POST" class="cm-login-form" autocomplete="off">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="token"
                        value="<?php echo htmlspecialchars($_GET['token'], ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="cm-form-group is-required">
                        <label for="newPassword" class="cm-form-label">Nouveau mot de passe <span
                                class="cm-required-star">*</span></label>
                        <div class="cm-login-input-icon">
                            <input id="newPassword" name="newPassword" type="password" required class="cm-form-control"
                                placeholder="Votre nouveau mot de passe">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                        </div>
                    </div>

                    <div class="cm-form-group is-required">
                        <label for="confirmPassword" class="cm-form-label">Confirmer le mot de passe <span
                                class="cm-required-star">*</span></label>
                        <div class="cm-login-input-icon">
                            <input id="confirmPassword" name="confirmPassword" type="password" required
                                class="cm-form-control" placeholder="Confirmez le mot de passe">
                            <i class="fas fa-check" aria-hidden="true"></i>
                        </div>
                    </div>

                    <button type="submit" class="cm-btn is-primary is-lg cm-login-submit">Réinitialiser</button>
                </form>
            <?php elseif ($success === ''): ?>
                <form method="POST" class="cm-login-form" autocomplete="off">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="cm-form-group is-required">
                        <label for="email" class="cm-form-label">Adresse e-mail <span
                                class="cm-required-star">*</span></label>
                        <div class="cm-login-input-icon">
                            <input id="email" name="email" type="email" required class="cm-form-control"
                                placeholder="login@exemple.com">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                        </div>
                    </div>

                    <button type="submit" class="cm-btn is-primary is-lg cm-login-submit">Envoyer le lien</button>
                </form>
            <?php endif; ?>

            <p class="cm-login-card__footer">
                <a href="index.php?_path=/login" class="cm-link">Retour à la connexion</a>
            </p>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var feedback = document.getElementById('feedback');
            if (feedback) {
                setTimeout(function () {
                    feedback.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>

</html>