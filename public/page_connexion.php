<?php
session_start();
require_once __DIR__.'/../app/utils/CSRFProtection.php';

// Générer un jeton CSRF pour le formulaire de connexion
CSRFProtection::generateToken();
$errorMessage = isset($_SESSION['error']) ? htmlspecialchars($_SESSION['error']) : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | CheckMaster</title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="shortcut icon" href="image/logo_cm_sbg.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="js/alpine.min.js" defer></script>
</head>
<body class="min-h-screen bg-white font-poppins text-slate-900">
<div class="relative min-h-screen overflow-hidden">
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/10 via-white to-primary-light/10"></div>
        <div class="absolute -top-24 -left-16 h-72 w-72 rounded-full bg-primary/10 blur-3xl"></div>
        <div class="absolute -bottom-20 -right-10 h-80 w-80 rounded-full bg-primary-light/10 blur-3xl"></div>
    </div>
    <div class="relative flex min-h-screen items-center justify-center px-6 py-16">
        <div class="mx-auto grid w-full max-w-6xl items-center gap-12 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="space-y-10">
                <a href="index.php" class="inline-flex items-center space-x-3 rounded-full border border-primary/20 bg-white/60 px-5 py-2 text-sm font-semibold text-primary shadow-sm backdrop-blur">
                    <span class="inline-flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-white shadow-sm ring-2 ring-primary/20">
                        <img src="image/logo_cm_sbg.png" alt="UFHB" class="h-full w-full object-contain">
                    </span>
                    <span>Retourner sur l'accueil UFHB</span>
                </a>
                <div class="space-y-6">
                    <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                        Accédez à votre espace CheckMaster
                    </h1>
                    <p class="max-w-xl text-lg text-slate-600">
                        Retrouver toutes les fonctionnalités de gestion des soutenances MIAGE dans une interface unifiée, élégante et performante.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="card bg-white/80 border border-slate-200 shadow-sm backdrop-blur">
                        <div class="card-body p-6">
                            <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <i class="fas fa-shield-alt text-xl"></i>
                            </div>
                            <h2 class="card-title text-lg">Authentification sécurisée</h2>
                            <p class="mt-2 text-sm text-base-content/70">Connexion protégée et conforme aux standards de la plateforme.</p>
                        </div>
                    </div>
                    <div class="card bg-white/80 border border-slate-200 shadow-sm backdrop-blur">
                        <div class="card-body p-6">
                            <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-warning/10 text-warning">
                                <i class="fas fa-chart-line text-xl"></i>
                            </div>
                            <h2 class="card-title text-lg">Suivi centralisé</h2>
                            <p class="mt-2 text-sm text-base-content/70">Tableaux de bord dynamiques pour piloter chaque étape facilement.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative">
                <div class="absolute -top-10 -right-8 h-32 w-32 rounded-full bg-primary/10 blur-2xl"></div>
                <div class="card relative bg-white/90 shadow-elevate backdrop-blur-lg">
                    <div class="card-body">
                        <div class="mb-8 text-center">
                            <div class="mb-5 flex items-center justify-center">
                                <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-lg ring-2 ring-primary/10">
                                    <img src="image/logo_cm_sbg.png" alt="Logo CheckMaster" class="h-full w-full object-contain p-2">
                                </div>
                            </div>
                            <h2 class="text-2xl font-semibold text-slate-900">Connexion</h2>
                            <p class="mt-2 text-sm text-slate-600">Identifiez-vous pour poursuivre la gestion de vos soutenances.</p>
                        </div>
                        <?php if ($errorMessage): ?>
                            <div id="errorMessage" class="alert alert-error mb-6" role="alert">
                                <i class="fas fa-exclamation-circle"></i>
                                <span><?= $errorMessage ?></span>
                            </div>
                        <?php endif; ?>
                        <form action="login.php" method="POST" class="space-y-5" autocomplete="off">
                            <?= CSRFProtection::getTokenField() ?>
                            <div class="form-control">
                                <label class="label" for="login">
                                    <span class="label-text font-semibold">Adresse e-mail</span>
                                </label>
                                <div class="relative">
                                    <input id="login" name="login" type="email" required 
                                           class="input input-bordered w-full" 
                                           placeholder="login@exemple.com">
                                    <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-primary">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="form-control">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="label" for="password">
                                        <span class="label-text font-semibold">Mot de passe</span>
                                    </label>
                                    <a href="reset_password.php" class="link link-primary text-sm">Mot de passe oublié ?</a>
                                </div>
                                <div class="relative">
                                    <input id="password" name="password" type="password" required 
                                           class="input input-bordered w-full" 
                                           placeholder="Votre mot de passe">
                                    <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-primary">
                                        <i class="fas fa-key"></i>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-full">
                                Se connecter
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var errorMessage = document.getElementById('errorMessage');
        if (errorMessage) {
            setTimeout(function () {
                errorMessage.style.display = 'none';
            }, 2400);
        }
    });
</script>
<?php
unset($_SESSION['error']);
?>
</body>
</html>