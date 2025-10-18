<?php
$nom_user = $_SESSION['nom_utilisateur'] ?? '';
$login_user = $_SESSION['login_utilisateur'] ?? '';
$statut_user = $_SESSION['statut_utilisateur'] ?? '';
$niveau_acces = $_SESSION['niveau_acces'] ?? '';
$lib_GU = $_SESSION['lib_GU'] ?? '';
$libelle_type_utilisateur = $_SESSION['type_utilisateur'] ?? '';
$libelle_niveau_acces = $_SESSION['niveau_acces'] ?? '';
$libelle_GU = $_SESSION['lib_GU'] ?? '';
$specialite = $_SESSION['specialite'] ?? '';
$grade = $_SESSION['grade'] ?? '';
$fonction = $_SESSION['fonction'] ?? '';
$date_grade = $_SESSION['date_grade'] ?? '';
$date_fonction = $_SESSION['date_fonction'] ?? '';
$telephone = $_SESSION['telephone'] ?? '';
$poste = $_SESSION['poste'] ?? '';
$date_embauche = $_SESSION['date_embauche'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil utilisateur</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        :root {
            --ufhb-blue: #1a5276;
            --ufhb-blue-light: #2980b9;
            --ufhb-green: #10b981;
            --muted: #64748B;
            --bg: #F8FAFC;
        }
        body { background: var(--bg); color: #1f2937; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
        .icon-green { color: var(--ufhb-green); }
        .bg-white { background-color: #ffffff; }
        .border-gray-200, .border-gray-300 { border-color: rgba(15,76,117,0.12); }
        .text-gray-700 { color: #374151; }
        .text-gray-600 { color: #4b5563; }
        .text-gray-800 { color: #1f2937; }
        .rounded-lg { border-radius: 0.5rem; }
        .shadow-md { box-shadow: 0 6px 18px rgba(15,76,117,0.06); }
        .container { max-width: 64rem; margin-left: auto; margin-right: auto; padding-left: 1rem; padding-right: 1rem; }
        .fade-in { animation: fadeIn .3s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        button:focus, input:focus, textarea:focus, select:focus { outline: none; box-shadow: 0 0 0 4px rgba(15,76,117,0.12); border-color: var(--ufhb-blue); }
        .input-focus-green:focus { box-shadow: 0 0 0 4px rgba(16,185,129,0.12); border-color: var(--ufhb-green); }
        .alert-success { background-color: rgba(16,185,129,0.08); border-left: 4px solid var(--ufhb-green); color: #065F46; }
        .alert-error { background-color: rgba(239,68,68,0.08); border-left: 4px solid #ef4444; color: #7f1d1d; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-message');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.classList.add('fade-out');
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 3000);
            });
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab') === 'password') {
                const el = document.querySelector('[data-tab="password"]');
                if (el) el.click();
            }
        });
    </script>
</head>
<body class="min-h-screen" x-data="{ currentTab: '<?php echo isset($_GET['tab']) && $_GET['tab'] === 'password' ? 'password' : 'profile'; ?>' }">
<div class="container px-4 py-8">
    <header class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-user-circle mr-2 icon-green"></i> Mon Profil
        </h1>
        <p class="text-gray-600 mt-1">Gérez vos informations personnelles</p>
    </header>
    <div class="flex border-b border-gray-200 mb-6">
        <button @click="currentTab = 'profile'" :class="currentTab === 'profile' ? 'tab-active' : 'text-gray-500 hover:text-gray-700'" class="py-2 px-4 font-medium text-sm border-b-2 -mb-px transition" data-tab="profile">
            <i class="fas fa-user mr-2"></i> Informations
        </button>
        <button @click="currentTab = 'password'" :class="currentTab === 'password' ? 'tab-active' : 'text-gray-500 hover:text-gray-700'" class="py-2 px-4 font-medium text-sm border-b-2 -mb-px transition" data-tab="password">
            <i class="fas fa-lock mr-2"></i> Mot de passe
        </button>
    </div>
    <div x-show="currentTab === 'profile'" class="fade-in">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">Informations personnelles</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2" for="nom">Nom complet</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <i class="fas fa-user"></i>
                                </div>
                                <input id="nom" type="text" value="<?= $nom_user ?>" disabled class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg input-focus-green">
                            </div>
                        </div>
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2" for="email">Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <input id="email" type="email" value="<?= $login_user ?>" disabled class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg input-focus-green">
                            </div>
                        </div>
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2" for="gu">Groupe utilisateur</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <i class="fas fa-users"></i>
                                </div>
                                <input id="gu" type="text" value="<?= $libelle_GU ?>" disabled class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg input-focus-green">
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2" for="login">Identifiant</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <i class="fas fa-at"></i>
                                </div>
                                <input id="login" type="text" value="<?= $login_user ?>" disabled class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg input-focus-green">
                            </div>
                        </div>
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type d'utilisateur</label>
                            <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                <div class="flex items-center">
                                    <i class="fas fa-user-tag text-gray-400 mr-2"></i>
                                    <span><?= $libelle_type_utilisateur ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Niveau d'accès</label>
                            <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                <div class="flex items-center">
                                    <i class="fas fa-shield-alt text-gray-400 mr-2"></i>
                                    <span><?= $libelle_niveau_acces ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($libelle_type_utilisateur === 'Enseignant simple' || $libelle_type_utilisateur === 'Enseignant administratif'): ?>
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6">Informations professionnelles</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="mb-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Spécialité</label>
                                    <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                        <div class="flex items-center">
                                            <i class="fas fa-graduation-cap text-gray-400 mr-2"></i>
                                            <span><?= htmlspecialchars($specialite) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Grade</label>
                                    <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                        <div class="flex items-center">
                                            <i class="fas fa-award text-gray-400 mr-2"></i>
                                            <span><?= htmlspecialchars($grade) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="mb-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fonction</label>
                                    <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                        <div class="flex items-center">
                                            <i class="fas fa-briefcase text-gray-400 mr-2"></i>
                                            <span><?= htmlspecialchars($fonction) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date d'obtention du grade</label>
                                    <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                        <div class="flex items-center">
                                            <i class="fas fa-calendar-check text-gray-400 mr-2"></i>
                                            <span><?= htmlspecialchars($date_grade) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date d'occupation de la fonction</label>
                                    <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                        <div class="flex items-center">
                                            <i class="fas fa-calendar-alt text-gray-400 mr-2"></i>
                                            <span><?= htmlspecialchars($date_fonction) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($libelle_type_utilisateur === 'Personnel administratif'): ?>
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6">Informations professionnelles</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="mb-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                                    <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                        <div class="flex items-center">
                                            <i class="fas fa-phone text-gray-400 mr-2"></i>
                                            <span><?= htmlspecialchars($telephone) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Poste</label>
                                    <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                        <div class="flex items-center">
                                            <i class="fas fa-briefcase text-gray-400 mr-2"></i>
                                            <span><?= htmlspecialchars($poste) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="mb-5">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date d'embauche</label>
                                    <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                        <div class="flex items-center">
                                            <i class="fas fa-calendar-alt text-gray-400 mr-2"></i>
                                            <span><?= htmlspecialchars($date_embauche) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div x-show="currentTab === 'password'" class="fade-in" x-transition>
        <form action="?page=profil&tab=password" method="POST">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Changer mon mot de passe</h3>
                    <?php if (isset($_SESSION['password_error'])): ?>
                        <div class="alert-message alert-error mb-4" role="alert">
                            <p><?= htmlspecialchars($_SESSION['password_error']) ?></p>
                        </div>
                        <?php unset($_SESSION['password_error']); endif; ?>
                    <?php if (isset($_SESSION['password_success'])): ?>
                        <div class="alert-message alert-success mb-4" role="alert">
                            <p><?= htmlspecialchars($_SESSION['password_success']) ?></p>
                        </div>
                        <?php unset($_SESSION['password_success']); endif; ?>
                    <input type="hidden" name="id_utilisateur" value="<?php echo $_SESSION['id_utilisateur']; ?>">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2" for="currentPassword">Mot de passe actuel</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <input id="currentPassword" type="password" name="currentPassword" required class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg input-focus-green">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2" for="newPassword">Nouveau mot de passe</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                    <input id="newPassword" type="password" name="newPassword" required class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg input-focus-green">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2" for="confirmPassword">Confirmer le mot de passe</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                    <input id="confirmPassword" type="password" name="confirmPassword" required class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg input-focus-green">
                                </div>
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="submit" name="update_password" class="w-full bg-green-600 text-white py-2 px-4 rounded-lg input-focus-green flex items-center justify-center">
                                <i class="fas fa-save mr-2"></i> Mettre à jour le mot de passe
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-blue-800 mb-2 flex items-center">
                <i class="fas fa-info-circle mr-2"></i> Exigences pour le mot de passe
            </h4>
            <ul class="text-xs text-blue-700 space-y-1">
                <li class="flex items-center"><i class="fas fa-check-circle mr-2 icon-green"></i> Minimum 8 caractères</li>
                <li class="flex items-center"><i class="fas fa-check-circle mr-2 icon-green"></i> Au moins une majuscule</li>
                <li class="flex items-center"><i class="fas fa-check-circle mr-2 icon-green"></i> Au moins un chiffre</li>
                <li class="flex items-center"><i class="fas fa-check-circle mr-2 icon-green"></i> Au moins un caractère spécial</li>
            </ul>
        </div>
    </div>
</div>
</body>
</html>