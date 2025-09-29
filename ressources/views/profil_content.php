<?php

require_once __DIR__ . '/../../app/utils/StyleManager.php';
$nom_user = $_SESSION['nom_utilisateur'] ?? '';
$login_user = $_SESSION['login_utilisateur'] ?? '';
$statut_user = $_SESSION['statut_utilisateur'] ?? '';
$niveau_acces = $_SESSION['niveau_acces'] ?? '';
$lib_GU = $_SESSION['lib_GU'] ?? '';
$libelle_type_utilisateur = $_SESSION['type_utilisateur'] ?? '';
$libelle_niveau_acces = $_SESSION['niveau_acces'] ?? '';
$libelle_GU = $_SESSION['lib_GU'] ?? '';

// Récupération des informations spécifiques selon le type d'utilisateur
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
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }

        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }

    .fade-in {
        animation: fadeIn 0.3s ease-out forwards;
    }

    .fade-out {
        animation: fadeOut 0.3s ease-out forwards;
    }

    .alert-message {
        opacity: 0;
        animation: fadeIn 0.3s ease-out forwards;
        animation-delay: 0.5s;
    }

    .alert-message.error {
        background-color: #FEE2E2;
        border-color: #EF4444;
        color: #B91C1C;
    }

    .alert-message.success {
        background-color: #D1FAE5;
        border-color: #10B981;
        color: #065F46;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert-message');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.classList.add('fade-out');
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 3000);
        });

        // Vérifier si on doit afficher l'onglet mot de passe
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('tab') === 'password') {
            document.querySelector('[data-tab="password"]').click();
        }
    });
    </script>
</head>

<body class="bg-gray-50 min-h-screen font-sans text-gray-800"
    x-data="{ currentTab: '<?php echo isset($_GET['tab']) && $_GET['tab'] === 'password' ? 'password' : 'profile'; ?>' }">
    <div class="container max-w-4xl mx-auto px-4 py-8">
        <!-- Header -->
        <header class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-user-circle mr-2 text-primary-600"></i> Mon Profil
            </h1>
            <p class="text-gray-600 mt-1">Gérez vos informations personnelles</p>
        </header>

        <!-- Tab Navigation -->
        <div class="flex border-b border-gray-200 mb-6">
            <button @click="currentTab = 'profile'"
                :class="{'text-primary-600 border-primary-600': currentTab === 'profile', 'text-gray-500 hover:text-gray-700': currentTab !== 'profile'}"
                class="py-2 px-4 font-medium text-sm border-b-2 -mb-px transition duration-150 ease-in-out"
                data-tab="profile">
                <i class="fas fa-user mr-2"></i> Informations
            </button>
            <button @click="currentTab = 'password'"
                :class="{'text-primary-600 border-primary-600': currentTab === 'password', 'text-gray-500 hover:text-gray-700': currentTab !== 'password'}"
                class="py-2 px-4 font-medium text-sm border-b-2 -mb-px transition duration-150 ease-in-out"
                data-tab="password">
                <i class="fas fa-lock mr-2"></i> Mot de passe
            </button>
        </div>

        <!-- Profile Tab Content -->
        <div x-show="currentTab === 'profile'" class="fade-in">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Informations personnelles
                        </h3>

                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- First Column -->
                        <div>
                            <div class="mb-5">
                                <label class="<?= StyleManager::getLabelClass() ?>" for="nom">Nom
                                    complet</label>
                                <div class="relative">
                                    <div
                                        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <input id="nom" type="text" value="<?= $nom_user ?>" disabled
                                        class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition duration-200">
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="<?= StyleManager::getLabelClass() ?>" for="email">Email</label>
                                <div class="relative">
                                    <div
                                        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <input id="email" type="email" value="<?= $login_user ?>" disabled
                                        class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition duration-200">
                                </div>
                            </div>
                            <div class="mb-5">
                                <label class="<?= StyleManager::getLabelClass() ?>" for="gu">Groupe
                                    utilisateur</label>
                                <div class="relative">
                                    <div
                                        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <input id="gu" type="text" value="<?= $libelle_GU ?>" disabled
                                        class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition duration-200">
                                </div>
                            </div>

                        </div>

                        <!-- Second Column -->
                        <div>
                            <div class="mb-5">
                                <label class="<?= StyleManager::getLabelClass() ?>"
                                    for="login">Identifiant</label>
                                <div class="relative">
                                    <div
                                        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                        <i class="fas fa-at"></i>
                                    </div>
                                    <input id="login" type="text" value="<?= $login_user ?>" disabled
                                        class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition duration-200">
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="<?= StyleManager::getLabelClass() ?>">Type d'utilisateur</label>
                                <div class="relative  rounded-lg pl-3 pr-4 py-2  border border-gray-200">
                                    <div class="flex items-center">
                                        <i class="fas fa-user-tag text-gray-400 mr-2"></i>
                                        <span><?= $libelle_type_utilisateur ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-5">
                                <label class="<?= StyleManager::getLabelClass() ?>">Niveau d'accès</label>
                                <div class="relative rounded-lg pl-3 pr-4 py-2  border border-gray-200">
                                    <div class="flex items-center">
                                        <i class="fas fa-shield-alt text-gray-400 mr-2"></i>
                                        <span><?= $libelle_niveau_acces ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations spécifiques selon le type d'utilisateur -->
                    <?php if ($libelle_type_utilisateur === 'Enseignant simple' || $libelle_type_utilisateur === 'Enseignant administratif'): ?>
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6">
                            Informations professionnelles
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="mb-5">
                                    <label class="<?= StyleManager::getLabelClass() ?>">Spécialité</label>
                                    <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                        <div class="flex items-center">
                                            <i class="fas fa-graduation-cap text-gray-400 mr-2"></i>
                                            <span><?= htmlspecialchars($specialite) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="<?= StyleManager::getLabelClass() ?>">Grade</label>
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
                                    <label class="<?= StyleManager::getLabelClass() ?>">Fonction</label>
                                    <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                        <div class="flex items-center">
                                            <i class="fas fa-briefcase text-gray-400 mr-2"></i>
                                            <span><?= htmlspecialchars($fonction) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="<?= StyleManager::getLabelClass() ?>">Date d'obtention du
                                        grade</label>
                                    <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                        <div class="flex items-center">
                                            <i class="fas fa-calendar-check text-gray-400 mr-2"></i>
                                            <span><?= htmlspecialchars($date_grade) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="<?= StyleManager::getLabelClass() ?>">Date d'occupation de la
                                        fonction</label>
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
                        <h3 class="text-lg font-semibold text-gray-800 mb-6">
                            Informations professionnelles
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="mb-5">
                                    <label class="<?= StyleManager::getLabelClass() ?>">Téléphone</label>
                                    <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                        <div class="flex items-center">
                                            <i class="fas fa-phone text-gray-400 mr-2"></i>
                                            <span><?= htmlspecialchars($telephone) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label class="<?= StyleManager::getLabelClass() ?>">Poste</label>
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
                                    <label class="<?= StyleManager::getLabelClass() ?>">Date d'embauche</label>
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

        <!-- Password Tab Content -->
        <div x-show="currentTab === 'password'" class="fade-in" x-transition>
            <form action="?page=profil&tab=password" method="POST">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6">
                            Changer mon mot de passe
                        </h3>

                        <?php if (isset($_SESSION['password_error'])): ?>
                        <div class="alert-message error mb-4 border-l-4 p-4 rounded-md" role="alert">
                            <p><?= htmlspecialchars($_SESSION['password_error']) ?></p>
                        </div>
                        <?php unset($_SESSION['password_error']); endif; ?>

                        <?php if (isset($_SESSION['password_success'])): ?>
                        <div class="alert-message success mb-4 border-l-4 p-4 rounded-md" role="alert">
                            <p><?= htmlspecialchars($_SESSION['password_success']) ?></p>
                        </div>
                        <?php unset($_SESSION['password_success']); endif; ?>

                        <input type="hidden" name="id_utilisateur" value="<?php echo $_SESSION['id_utilisateur']; ?>">
                        <div class="space-y-4">
                            <div>
                                <label class="<?= StyleManager::getLabelClass() ?>" for="currentPassword">Mot de
                                    passe actuel</label>
                                <div class="relative">
                                    <div
                                        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                    <input id="currentPassword" type="password" name="currentPassword" required
                                        style="outline: none;"
                                        class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition duration-200">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="<?= StyleManager::getLabelClass() ?>"
                                        for="newPassword">Nouveau
                                        mot de passe</label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                            <i class="fas fa-lock"></i>
                                        </div>
                                        <input id="newPassword" type="password" name="newPassword" required
                                            style="outline: none;"
                                            class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition duration-200">
                                    </div>
                                </div>
                                <div>
                                    <label class="<?= StyleManager::getLabelClass() ?>"
                                        for="confirmPassword">Confirmer le mot de passe</label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                            <i class="fas fa-lock"></i>
                                        </div>
                                        <input id="confirmPassword" type="password" name="confirmPassword" required
                                            style="outline: none;"
                                            class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition duration-200">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <button type="submit" name="update_password"
                                    class="w-full bg-primary-600 text-white py-2 px-4 rounded-lg hover:bg-primary-700 transition duration-200 flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i>
                                    Mettre à jour le mot de passe
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <!-- Password Requirements -->
            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-sm font-medium text-blue-800 mb-2 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i> Exigences pour le mot de passe
                </h4>
                <ul class="text-xs text-blue-700 space-y-1">
                    <li class="flex items-center"><i class="fas fa-check-circle mr-2 text-primary-500"></i>
                        Minimum 8 caractères</li>
                    <li class="flex items-center"><i class="fas fa-check-circle mr-2 text-primary-500"></i> Au
                        moins une majuscule</li>
                    <li class="flex items-center"><i class="fas fa-check-circle mr-2 text-primary-500"></i> Au
                        moins un chiffre</li>
                    <li class="flex items-center"><i class="fas fa-check-circle mr-2 text-primary-500"></i> Au
                        moins un caractère spécial</li>
                </ul>
            </div>
        </div>
    </div>
</body>

</html>