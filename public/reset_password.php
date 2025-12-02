<?php
session_start();
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Utilisateur.php';
require_once __DIR__ . '/../app/utils/EmailService.php';
$db = Database::getConnection();
$utilisateurModel = new Utilisateur($db);
$emailService = new EmailService();
function generateToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}
function getPasswordResetByToken($db, $token) {
    $stmt = $db->prepare('SELECT * FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW()');
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function markTokenUsed($db, $token) {
    $stmt = $db->prepare('UPDATE password_resets SET used = 1 WHERE token = :token');
    $stmt->bindParam(':token', $token);
    $stmt->execute();
}
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
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
        $subject = "Réinitialisation de votre mot de passe";
        $message = "<p>Bonjour,<br>Pour réinitialiser votre mot de passe, cliquez sur le lien ci-dessous :<br><a href='$resetLink'>$resetLink</a><br>Ce lien expirera dans 1 heure.</p>";
        $emailService->sendEmail($email, $subject, $message, true);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'], $_POST['newPassword'], $_POST['confirmPassword'])) {
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
    } else {
        $stmt = $db->prepare('SELECT * FROM utilisateur WHERE login_utilisateur = :email');
        $stmt->bindParam(':email', $reset['email']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
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
$showResetForm = isset($_GET['token']) && getPasswordResetByToken($db, $_GET['token']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation | CheckMaster</title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="shortcut icon" href="image/logo_cm_sbg.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a5276',
                        'primary-light': '#2980b9',
                        'primary-lighter': '#3498db',
                        secondary: '#ff8c00',
                        success: '#4caf50',
                        danger: '#e74c3c'
                    },
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                        montserrat: ['Montserrat', 'sans-serif']
                    },
                    boxShadow: {
                        elevate: '0 25px 60px -15px rgba(26,82,118,0.25)'
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-white font-poppins text-slate-900">
<div class="relative min-h-screen overflow-hidden">
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/10 via-white to-primary-light/10"></div>
        <div class="absolute -top-24 -left-16 h-72 w-72 rounded-full bg-primary/10 blur-3xl"></div>
        <div class="absolute -bottom-20 -right-10 h-80 w-80 rounded-full bg-primary-light/10 blur-3xl"></div>
    </div>
    <div class="relative flex min-h-screen items-center justify-center px-6 py-16">
        <div class="w-full max-w-5xl">
            <div class="grid gap-12 lg:grid-cols-2">
                <div class="space-y-8">
                    <a href="https://checkmaster.ufrmi-ufhb-ci.com/" class="inline-flex items-center space-x-3 rounded-full border border-primary/20 bg-white/60 px-5 py-2 text-sm font-semibold text-primary shadow-sm backdrop-blur">
                        <span class="inline-flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-white shadow-sm ring-2 ring-primary/20">
                            <img src="image/logo_cm_sbg.png" alt="CheckMaster" class="h-full w-full object-contain">
                        </span>
                        <span>Retourner sur CheckMaster</span>
                    </a>
                    <div class="space-y-5">
                        <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">Réinitialisez votre mot de passe</h1>
                        <p class="max-w-lg text-lg text-slate-600">Restaurez l'accès à votre espace en suivant les étapes de vérification sécurisée. Le lien reçu est valable une heure.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm backdrop-blur">
                            <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <i class="fas fa-envelope-open-text text-xl"></i>
                            </div>
                            <h2 class="text-lg font-semibold text-slate-900">Instructions instantanées</h2>
                            <p class="mt-2 text-sm text-slate-600">Recevez un lien crypté dans votre boîte mail pour sécuriser l'opération.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm backdrop-blur">
                            <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-success/10 text-success">
                                <i class="fas fa-user-shield text-xl"></i>
                            </div>
                            <h2 class="text-lg font-semibold text-slate-900">Validation renforcée</h2>
                            <p class="mt-2 text-sm text-slate-600">Chaque requête est vérifiée pour protéger vos informations personnelles.</p>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute -top-10 -right-8 h-32 w-32 rounded-full bg-primary/10 blur-2xl"></div>
                    <div class="relative rounded-3xl border border-white/40 bg-white/90 p-10 shadow-elevate backdrop-blur-lg">
                        <div class="mb-8 text-center">
                            <div class="mb-5 flex items-center justify-center">
                                <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-lg ring-2 ring-primary/10">
                                    <img src="image/logo_cm_sbg.png" alt="Logo CheckMaster" class="h-full w-full object-contain p-2">
                                </div>
                            </div>
                            <h2 class="text-2xl font-semibold text-slate-900">Réinitialisation</h2>
                            <p class="mt-2 text-sm text-slate-600">Suivez les instructions pour définir un mot de passe robuste.</p>
                        </div>
                        <?php if ($success): ?>
                            <div id="feedback" class="mb-6 rounded-2xl border border-success/20 bg-success/10 px-4 py-3 text-sm font-medium text-success" role="alert">
                                <?= htmlspecialchars($success) ?>
                            </div>
                        <?php elseif ($error): ?>
                            <div id="feedback" class="mb-6 rounded-2xl border border-danger/20 bg-danger/10 px-4 py-3 text-sm font-medium text-danger" role="alert">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($showResetForm): ?>
                            <form method="POST" class="space-y-5">
                                <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">
                                <div class="space-y-2">
                                    <label for="newPassword" class="text-sm font-semibold text-slate-800">Nouveau mot de passe</label>
                                    <div class="relative">
                                        <input id="newPassword" name="newPassword" type="password" required class="w-full rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-900 shadow-sm transition focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10" placeholder="Votre nouveau mot de passe">
                                        <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-primary">
                                            <i class="fas fa-lock"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label for="confirmPassword" class="text-sm font-semibold text-slate-800">Confirmez le mot de passe</label>
                                    <div class="relative">
                                        <input id="confirmPassword" name="confirmPassword" type="password" required class="w-full rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-900 shadow-sm transition focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10" placeholder="Confirmez le mot de passe">
                                        <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-primary">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-primary px-6 py-3 text-sm font-semibold tracking-wide text-white transition hover:bg-primary-light focus:outline-none focus:ring-4 focus:ring-primary/20">
                                    Réinitialiser
                                </button>
                            </form>
                        <?php elseif (!$success): ?>
                            <form method="POST" class="space-y-5">
                                <div class="space-y-2">
                                    <label for="email" class="text-sm font-semibold text-slate-800">Adresse e-mail</label>
                                    <div class="relative">
                                        <input id="email" name="email" type="email" required class="w-full rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-900 shadow-sm transition focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10" placeholder="login@exemple.com">
                                        <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-primary">
                                            <i class="fas fa-paper-plane"></i>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-primary px-6 py-3 text-sm font-semibold tracking-wide text-white transition hover:bg-primary-light focus:outline-none focus:ring-4 focus:ring-primary/20">
                                    Envoyer le lien
                                </button>
                            </form>
                        <?php endif; ?>
                        <p class="mt-8 text-center text-sm text-slate-500"><a href="page_connexion.php" class="font-semibold text-primary hover:text-primary-light">Retour à la connexion</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var feedback = document.getElementById('feedback');
        if (feedback) {
            setTimeout(function () {
                feedback.style.display = 'none';
            }, 2600);
        }
    });
</script>
</body>
</html>