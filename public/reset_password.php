<?php

require_once __DIR__ . '/../app/utils/StyleManager.php';
session_start();
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Utilisateur.php';
require_once __DIR__ . '/../app/utils/EmailService.php';

// Créer la table password_resets si elle n'existe pas (à faire une seule fois en SQL, voir README)
// CREATE TABLE `password_resets` (
//   `id` int NOT NULL AUTO_INCREMENT,
//   `email` varchar(255) NOT NULL,
//   `token` varchar(255) NOT NULL,
//   `expires_at` datetime NOT NULL,
//   `used` tinyint(1) NOT NULL DEFAULT 0,
//   `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
//   PRIMARY KEY (`id`),
//   UNIQUE KEY `token` (`token`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

// Gestion des messages
$success = $error = '';

// 1. Demande de reset (formulaire email)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    // Recherche utilisateur par login (email)
    $stmt = $db->prepare('SELECT * FROM utilisateur WHERE login_utilisateur = :email');
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // Toujours répondre pareil pour la sécurité
    $success = "Si un compte existe pour cet email, un lien de réinitialisation a été envoyé.";
    if ($user) {
        // Générer un token unique
        $token = generateToken();
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1h
        // Insérer le token
        $stmt = $db->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)');
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires', $expires);
        $stmt->execute();
        // Envoyer l'email
        $resetLink = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
        $subject = "Réinitialisation de votre mot de passe";
        $message = "<p>Bonjour,<br>Pour réinitialiser votre mot de passe, cliquez sur le lien ci-dessous :<br><a href='$resetLink'>$resetLink</a><br>Ce lien expirera dans 1 heure.</p>";
        $emailService->sendEmail($email, $subject, $message, true);
    }
}

// 2. Soumission du nouveau mot de passe
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
    } elseif (!preg_match('/[A-Z]/', $newPassword)) {
        $error = "Le mot de passe doit contenir au moins une majuscule.";
    } elseif (!preg_match('/[0-9]/', $newPassword)) {
        $error = "Le mot de passe doit contenir au moins un chiffre.";
    } elseif (!preg_match("/[!@#\$%\^&\*()_+\-=\[\]{};':\"\\|,.<>\/?]+/", $newPassword)) {
        $error = "Le mot de passe doit contenir au moins un caractère spécial.";
    } else {
        // Mettre à jour le mot de passe
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

// 3. Affichage du formulaire
$showResetForm = isset($_GET['token']) && getPasswordResetByToken($db, $_GET['token']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/output.css">
    <link rel="shortcut icon" href="./images/logo_cm_sbg.png" type="image/x-icon">
    <title>Mot de passe oublié</title>
</head>

<body>
    <div class="bg-gray-50">
        <div class="min-h-screen flex flex-col items-center justify-center py-6 px-4">
            <div class="max-w-md w-full">


                <div class="p-8 rounded-md bg-white shadow-xl">
                    <a href="reset_password.php"><img src="./images/logo_cm_sbg.png" class=" mx-auto block "
                            style="width:40%" />
                    </a>
                    <h2 class="text-primary-500 text-center text-xl font-semibold mb-6">Réinitialisation de mot de passe
                    </h2>
                    <?php if ($success): ?>
                    <div class="bg-primary-50 border-l-4 border-primary-500 text-primary-700 p-2 mb-4 rounded text-sm"
                        role="alert">
                        <?= htmlspecialchars($success) ?>
                    </div>
                    <?php elseif ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-2 mb-4 rounded text-sm" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($showResetForm): ?>
                    <form class="mt-12 space-y-6" method="POST">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">
                        <div>
                            <label class="text-slate-800 text-sm font-medium mb-2 block">Nouveau mot de passe</label>
                            <div class="relative flex items-center">
                                <input name="newPassword" type="password" required
                                    class="w-full text-slate-800 text-sm border border-slate-300 px-4 py-3 rounded-md outline-blue-600"
                                    placeholder="Entrer votre nouveau mot de passe" />

                            </div>
                        </div>
                        <div>
                            <label class="text-slate-800 text-sm font-medium mb-2 block">Confirmer votre mot de
                                passe</label>
                            <div class="relative flex items-center">
                                <input name="confirmPassword" type="password" required
                                    class="w-full text-slate-800 text-sm border border-slate-300 px-4 py-3 rounded-md outline-blue-600"
                                    placeholder="Confirmer votre mot de passe" />

                            </div>
                        </div>


                        <div class="!mt-12">
                            <button type="submit"
                                class="w-full py-2 px-4 text-[15px] font-medium tracking-wide rounded-md text-white bg-primary-500 hover:bg-primary-700 focus:outline-none cursor-pointer">
                                Réinitialiser mon mot de passe
                            </button>
                        </div>
                    </form>
                    <?php elseif (!$success): ?>
                    <form class="mt-12 space-y-6" method="POST">
                        <div>
                            <label class="text-slate-800 text-sm font-medium mb-2 block">Email</label>
                            <div class="relative flex items-center">
                                <input name="email" type="email" required
                                    class="w-full text-slate-800 text-sm border border-slate-300 px-4 py-3 rounded-md outline-blue-600"
                                    placeholder="login@gmail.com" />

                            </div>
                        </div>

                        <div class="!mt-12">
                            <button type="submit"
                                class="w-full py-2 px-4 text-[15px] font-medium tracking-wide rounded-md text-white bg-primary-500 hover:bg-primary-700 focus:outline-none cursor-pointer">
                                Recevoir le lien de réinitialisation
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                    <div class="text-center mt-4">

                        <a href="page_connexion.php" class="text-primary-500 underline hover:underline font-medium">
                            Retour à la connexion

                            </form>
                    </div>
                </div>
            </div>
        </div>

</body>

</html>