<?php
require_once __DIR__ . '/../../app/Core/Autoload.php';

\CheckMaster\Core\Bootstrap::init();
\CheckMaster\Core\Session::start();

$csrfToken = \CheckMaster\Core\Csrf::token();
$errorMessage = isset($_SESSION['error']) ? htmlspecialchars((string) $_SESSION['error'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | CheckMaster</title>
    <link rel="stylesheet" href="../assets/css/checkmaster-theme.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="shortcut icon" href="../image/logo_cm_sbg.png" type="image/x-icon">
    <link rel="stylesheet" href="../assets/vendor/font-awesome/css/all.min.css">
</head>

<body class="cm-login-page">
    <main class="cm-login-shell">
        <section class="cm-login-brand">
            <a href="../site/index.php" class="cm-login-back-link">
                <img src="../image/logo_cm_sbg.png" alt="UFHB">
                <span>Retourner sur l'accueil UFHB</span>
            </a>
            <h1>Accédez à votre espace CheckMaster</h1>
            <div class="cm-login-features">
                <article class="cm-login-feature-card">
                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                    <h2>Authentification sécurisée</h2>
                    <p>Connexion protégée et conforme aux standards de la plateforme.</p>
                </article>
                <article class="cm-login-feature-card">
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                    <h2>Suivi centralisé</h2>
                    <p>Tableaux de bord dynamiques pour piloter chaque étape facilement.</p>
                </article>
            </div>
        </section>
        <section class="cm-login-card">
            <div class="cm-login-card__header">
                <img src="../image/logo_cm_sbg.png" alt="Logo CheckMaster">
                <h2>Connexion</h2>
                <p>Identifiez-vous pour poursuivre la gestion de vos soutenances.</p>
            </div>

            <?php if ($errorMessage !== ''): ?>
                <div class="cm-alert is-danger" id="errorMessage" role="alert">
                    <span class="cm-alert__icon"><i class="fas fa-circle-exclamation" aria-hidden="true"></i></span>
                    <div class="cm-alert__content">
                        <span class="cm-alert__message"><?php echo $errorMessage; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form action="index.php?_path=/login" method="POST" class="cm-login-form" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="cm-form-group is-required">
                    <label for="login" class="cm-form-label">Nom d'utilisateur (login) <span class="cm-required-star">*</span></label>
                    <div class="cm-login-input-icon">
                        <input id="login" name="login" type="text" required class="cm-form-control" placeholder="Votre login (ex: rlatyfa)">
                        <i class="fas fa-user" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="cm-form-group is-required">
                    <div class="cm-login-password-label">
                        <label for="password" class="cm-form-label">Mot de passe <span class="cm-required-star">*</span></label>
                        <a href="reset_password.php">Mot de passe oublié ?</a>
                    </div>
                    <div class="cm-login-input-icon">
                        <input id="password" name="password" type="password" required class="cm-form-control" placeholder="Votre mot de passe">
                        <button type="button"
                                class="cm-login-password-toggle"
                                id="togglePassword"
                                aria-label="Afficher le mot de passe"
                                aria-controls="password"
                                aria-pressed="false">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="cm-btn is-primary is-lg cm-login-submit">Se connecter</button>
            </form>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var errorMessage = document.getElementById('errorMessage');
            var togglePassword = document.getElementById('togglePassword');
            var passwordInput = document.getElementById('password');

            if (!errorMessage) {
                // nothing
            } else {
                setTimeout(function () {
                    errorMessage.style.display = 'none';
                }, 2400);
            }

            if (!togglePassword || !passwordInput) {
                return;
            }

            togglePassword.addEventListener('click', function () {
                var isHidden = passwordInput.getAttribute('type') === 'password';
                var icon = togglePassword.querySelector('i');

                passwordInput.setAttribute('type', isHidden ? 'text' : 'password');
                togglePassword.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                togglePassword.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');

                if (icon) {
                    icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
                }
            });
        });
    </script>
    <?php unset($_SESSION['error']); ?>
</body>

</html>
