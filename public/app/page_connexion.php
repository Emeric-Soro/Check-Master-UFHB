<?php
// Les imports et l'initialisation sont déjà faits par le routeur (app/index.php)
// On récupère juste le token CSRF et le message d'erreur
use CheckMaster\Core\Csrf;

$csrfToken = Csrf::token();
$errorMessage = isset($_SESSION['error']) ? htmlspecialchars((string) $_SESSION['error']) : '';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | CheckMaster</title>
    <link rel="stylesheet" href="../css/output.css">
    <link rel="shortcut icon" href="../image/logo_cm_sbg.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap"
        rel="stylesheet">
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
                        accent: '#4caf50',
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

<body class="min-h-screen font-poppins text-slate-900" style="background-color: #DFF2FF;">
    <div class="relative min-h-screen overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-br from-primary/10 via-white to-primary-light/10"></div>
            <div class="absolute -top-24 -left-16 h-72 w-72 rounded-full bg-primary/10 blur-3xl"></div>
            <div class="absolute -bottom-20 -right-10 h-80 w-80 rounded-full bg-primary-light/10 blur-3xl"></div>
        </div>
        <div class="relative flex min-h-screen items-center justify-center px-6 py-16">
            <div class="mx-auto grid w-full max-w-6xl items-center gap-12 lg:grid-cols-[1.1fr_0.9fr]">
                <div class="space-y-10">
                    <a href="../site/index.php"
                        class="inline-flex items-center space-x-3 rounded-full border border-primary/20 bg-white/60 px-5 py-2 text-sm font-semibold text-primary shadow-sm backdrop-blur">
                        <span
                            class="inline-flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-white shadow-sm ring-2 ring-primary/20">
                            <img src="../image/logo_cm_sbg.png" alt="UFHB" class="h-full w-full object-contain">
                        </span>
                        <span>Retourner sur l'accueil UFHB</span>
                    </a>
                    <div class="space-y-6">
                        <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                            Accédez à votre espace CheckMaster
                        </h1>
                        <p class="max-w-xl text-lg text-slate-600">
                            Retrouver toutes les fonctionnalités de gestion des soutenances MIAGE dans une interface
                            unifiée, élégante et performante.
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm backdrop-blur">
                            <div
                                class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <i class="fas fa-shield-alt text-xl"></i>
                            </div>
                            <h2 class="text-lg font-semibold text-slate-900">Authentification sécurisée</h2>
                            <p class="mt-2 text-sm text-slate-600">Connexion protégée et conforme aux standards de la
                                plateforme.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white/80 p-6 shadow-sm backdrop-blur">
                            <div
                                class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-secondary/10 text-secondary">
                                <i class="fas fa-chart-line text-xl"></i>
                            </div>
                            <h2 class="text-lg font-semibold text-slate-900">Suivi centralisé</h2>
                            <p class="mt-2 text-sm text-slate-600">Tableaux de bord dynamiques pour piloter chaque étape
                                facilement.</p>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute -top-10 -right-8 h-32 w-32 rounded-full bg-primary/10 blur-2xl"></div>
                    <div
                        class="relative rounded-3xl border border-white/40 bg-white/90 p-10 shadow-elevate backdrop-blur-lg">
                        <div class="mb-8 text-center">
                            <div class="mb-5 flex items-center justify-center">
                                <div
                                    class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-lg ring-2 ring-primary/10">
                                    <img src="../image/logo_cm_sbg.png" alt="Logo CheckMaster"
                                        class="h-full w-full object-contain p-2">
                                </div>
                            </div>
                            <h2 class="text-2xl font-semibold text-slate-900">Connexion</h2>
                            <p class="mt-2 text-sm text-slate-600">Identifiez-vous pour poursuivre la gestion de vos
                                soutenances.</p>
                        </div>
                        <?php if ($errorMessage): ?>
                            <div id="errorMessage"
                                class="mb-6 rounded-2xl border border-danger/20 bg-danger/10 px-4 py-3 text-sm font-medium text-danger"
                                role="alert">
                                <?= $errorMessage ?>
                            </div>
                        <?php endif; ?>
                        <form action="index.php?_path=/login" method="POST" class="space-y-5" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <div class="space-y-2">
                                <label for="login" class="text-sm font-semibold text-slate-800">Adresse e-mail</label>
                                <div class="relative">
                                    <input id="login" name="login" type="email" required
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-900 shadow-sm transition focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10"
                                        placeholder="login@exemple.com">
                                    <div
                                        class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-primary">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <label for="password" class="text-sm font-semibold text-slate-800">Mot de
                                        passe</label>
                                    <a href="reset_password.php"
                                        class="text-sm font-semibold text-primary hover:text-primary-light">Mot de passe
                                        oublié ?</a>
                                </div>
                                <div class="relative">
                                    <input id="password" name="password" type="password" required
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-900 shadow-sm transition focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10"
                                        placeholder="Votre mot de passe">
                                    <div
                                        class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-primary">
                                        <i class="fas fa-key"></i>
                                    </div>
                                </div>
                            </div>
                            <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-primary px-6 py-3 text-sm font-semibold tracking-wide text-white transition hover:bg-primary-light focus:outline-none focus:ring-4 focus:ring-primary/20">
                                Se connecter
                            </button>
                        </form>
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