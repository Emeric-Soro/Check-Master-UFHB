<?php

$utilisateur_a_modifier = $GLOBALS['utilisateur_a_modifier'];
$showModal = isset($_GET['action']) && ($_GET['action'] === 'edit' || $_GET['action'] === 'add' || $_GET['action'] === 'addMasse');

$utilisateurs = $GLOBALS['utilisateurs'] ?? [];
$niveau_acces = $GLOBALS['niveau_acces'];
$types_utilisateur = $GLOBALS['types_utilisateur'];
$groupes_utilisateur = $GLOBALS['groupes_utilisateur'];
$enseignantsNonUtilisateurs = $GLOBALS['enseignantsNonUtilisateurs'] ?? [];
$personnelNonUtilisateurs = $GLOBALS['personnelNonUtilisateurs'] ?? [];
$etudiantsNonUtilisateurs = $GLOBALS['etudiantsNonUtilisateurs'] ?? [];


// Calculer les statistiques sur l'ensemble des utilisateurs
$allUtilisateurs = $GLOBALS['utilisateurs'] ?? [];
$totalUtilisateurs = count($allUtilisateurs);
$utilisateursActifs = count(array_filter($allUtilisateurs, function ($u) {
    return $u->statut_utilisateur === 'Actif';
}));
$utilisateursInactifs = $totalUtilisateurs - $utilisateursActifs;

// Pagination
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
// Valider la limite (entre 5 et 100)
if ($limit < 5)
    $limit = 5;
if ($limit > 100)
    $limit = 100;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';

// Filter the list based on search
if (!empty($search)) {
    $allUtilisateurs = array_filter($allUtilisateurs, function ($utilisateur) use ($search) {
        return stripos($utilisateur->nom_utilisateur, $search) !== false ||
            stripos($utilisateur->prenom_utilisateur, $search) !== false ||
            stripos($utilisateur->email_utilisateur, $search) !== false;
    });
}

// Trier les utilisateurs par id décroissant pour afficher les plus récents en premier
usort($allUtilisateurs, function ($a, $b) {
    return ($b->id_utilisateur ?? 0) <=> ($a->id_utilisateur ?? 0);
});

// Total pages calculation
$total_items = count($allUtilisateurs);
$total_pages = ceil($total_items / $limit);

// Validation de la page courante
if ($page < 1) {
    $page = 1;
} elseif ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

// Slice the array for pagination
$utilisateurs = array_slice($allUtilisateurs, $offset, $limit);






?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
    <style>
        /* Styles pour les notifications */
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            color: white;
            max-width: 24rem;
            z-index: 50;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            animation: slideIn 0.5s ease-out;
        }

        .notification.success {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }

        .notification.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        /* Animations pour la modale de chargement */
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Transition pour la modale */
        .transform {
            transition-property: transform, opacity;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 300ms;
        }

        .scale-95 {
            transform: scale(0.95);
        }

        .scale-100 {
            transform: scale(1);
        }

        .opacity-0 {
            opacity: 0;
        }

        .opacity-100 {
            opacity: 1;
        }

        @keyframes progress {
            0% {
                width: 0%;
            }

            50% {
                width: 70%;
            }

            100% {
                width: 100%;
            }
        }

        .progress-bar {
            animation: progress 2s ease-in-out infinite;
            background: linear-gradient(90deg, #22c55e, #16a34a);
        }

        /* Styles pour le tableau avec rayures alternées */
        .table-row-hover:hover {
            background-color: #f0fdf4 !important;
            transition: background-color 0.2s ease;
        }

        /* Style pour le sélecteur de limite */
        #limitSelect {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        #limitSelect:hover {
            border-color: #10b981;
        }

        /* Autocomplete custom styles */
        .autocomplete-wrapper {
            position: relative;
        }

        .autocomplete-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 4px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            max-height: 240px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .autocomplete-suggestions.active {
            display: block;
            animation: slideDown 0.2s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .autocomplete-suggestion {
            padding: 12px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.15s ease;
            border-bottom: 1px solid #f3f4f6;
        }

        .autocomplete-suggestion:last-child {
            border-bottom: none;
        }

        .autocomplete-suggestion:hover,
        .autocomplete-suggestion.selected {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(5, 150, 105, 0.08));
        }

        .autocomplete-suggestion .icon {
            width: 36px;
            height: 36px;
            border-radius: 0.375rem;
            background: linear-gradient(135deg, #10b981, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .autocomplete-suggestion .icon svg {
            width: 20px;
            height: 20px;
            color: white;
        }

        .autocomplete-suggestion .text {
            flex: 1;
            font-size: 14px;
            color: #1f2937;
            font-weight: 500;
        }

        .autocomplete-no-results {
            padding: 16px;
            text-align: center;
            color: #9ca3af;
            font-size: 14px;
        }

        /* Scrollbar personnalisé */
        .autocomplete-suggestions::-webkit-scrollbar {
            width: 6px;
        }

        .autocomplete-suggestions::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 0.5rem;
        }

        .autocomplete-suggestions::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 0.5rem;
        }

        .autocomplete-suggestions::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
    </style>

</head>

<body style="background-color: #DFF2FF;">

    <!-- Container pour les notifications -->
    <?php if (!empty($GLOBALS['messageSuccess']) || !empty($GLOBALS['messageErreur'])): ?>
        <div class="fixed top-4 right-4 z-50 space-y-4">
            <?php if (!empty($GLOBALS['messageSuccess'])): ?>
                <div class="notification success animate__animated animate__fadeIn">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <p><?= htmlspecialchars($GLOBALS['messageSuccess']) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($GLOBALS['messageErreur'])): ?>
                <div class="notification error animate__animated animate__fadeIn">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <p><?= htmlspecialchars($GLOBALS['messageErreur']) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="relative container mx-auto px-3 py-3">

        <!-- Formulaire d'ajout/modification d'utilisateur -->
        <div class="bg-white rounded-lg shadow-card p-4 mb-4 border border-gray-200">
            <div class="flex items-center mb-3 pb-2 border-b border-gray-200">
                <div class="bg-green-100 p-2 rounded-full mr-3">
                    <i class="fas fa-user-plus text-green-500 text-base"></i>
                </div>
                <h2 class="text-lg font-semibold text-gray-700">
                    <?php echo (isset($_GET['action']) && $_GET['action'] == 'edit') ? 'Modifier un utilisateur' : 'Ajouter un Utilisateur' ?>
                </h2>
            </div>
            <form id="userForm" class="space-y-3" method="POST" action="?page=gestion_utilisateurs">
                <input type="hidden" id="userId" name="id_utilisateur"
                    value="<?php echo $utilisateur_a_modifier ? $utilisateur_a_modifier->id_utilisateur : ''; ?>">

                <!-- Ligne 1: Type et Personne -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Étape 1: Type d'utilisateur -->
                    <div class="space-y-1">
                        <label for="id_type_utilisateur" class="block text-xs font-semibold text-gray-700">
                            <span
                                class="bg-green-500 text-white rounded-full w-5 h-5 inline-flex items-center justify-center text-xs mr-1">1</span>
                            <i class="fas fa-id-badge text-green-500 mr-1"></i>Type d'utilisateur
                        </label>
                        <select name="id_type_utilisateur" id="id_type_utilisateur" required
                            class="focus:outline-none w-full px-3 py-2 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                            <option value="">-- Sélectionner d'abord le type d'utilisateur --</option>
                            <?php foreach ($types_utilisateur as $type): ?>
                                <option value="<?php echo htmlspecialchars($type->id_type_utilisateur); ?>"
                                    data-type-label="<?php echo htmlspecialchars($type->lib_type_utilisateur); ?>" <?php echo ($utilisateur_a_modifier && $type->id_type_utilisateur == $utilisateur_a_modifier->id_type_utilisateur) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type->lib_type_utilisateur); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Étape 2: Sélection de la personne -->
                    <div class="space-y-1">
                        <label for="nom_utilisateur" class="block text-xs font-semibold text-gray-700">
                            <span
                                class="bg-green-500 text-white rounded-full w-5 h-5 inline-flex items-center justify-center text-xs mr-1">2</span>
                            <i class="fas fa-user text-green-500 mr-1"></i>Sélectionner la personne
                        </label>
                        <?php if (!isset($_GET['action']) || $_GET['action'] === 'add'): ?>
                            <div class="autocomplete-wrapper">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-user text-gray-400"></i>
                                    </div>
                                    <input type="text" id="personne_search" required disabled autocomplete="off"
                                        class="pl-9 focus:outline-none w-full px-3 py-2 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm"
                                        placeholder="Choisissez d'abord un type">
                                    <input type="hidden" name="nom_utilisateur" id="nom_utilisateur" required>
                                </div>
                                <div class="autocomplete-suggestions" id="suggestions-list-personne"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Tapez pour rechercher une personne dans la liste
                            </p>
                        <?php else: ?>
                            <input type="text" name="nom_utilisateur" id="nom_utilisateur" required
                                value="<?php echo $utilisateur_a_modifier ? htmlspecialchars($utilisateur_a_modifier->nom_utilisateur) : ''; ?>"
                                class="focus:outline-none w-full px-3 py-2 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ligne 2: Login et Groupe -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Étape 3: Login -->
                    <div class="space-y-1">
                        <label for="login_utilisateur" class="block text-xs font-semibold text-gray-700">
                            <span
                                class="bg-green-500 text-white rounded-full w-5 h-5 inline-flex items-center justify-center text-xs mr-1">3</span>
                            <i class="fas fa-key text-green-500 mr-1"></i>Login
                        </label>
                        <div class="relative">
                            <input type="text" name="login_utilisateur" id="login_utilisateur" required
                                value="<?php echo $utilisateur_a_modifier ? htmlspecialchars($utilisateur_a_modifier->login_utilisateur) : ''; ?>"
                                placeholder="Généré automatiquement"
                                class="focus:outline-none w-full px-3 py-2 pr-8 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm">
                            <div id="login-status-icon"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none hidden">
                                <!-- Icône de chargement -->
                                <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <p id="login-status-message" class="text-xs mt-1 hidden">
                            <i class="fas fa-info-circle mr-1"></i>
                            <span></span>
                        </p>
                    </div>

                    <!-- Étape 4: Groupe utilisateur -->
                    <div class="space-y-1">
                        <label for="id_GU" class="block text-xs font-semibold text-gray-700">
                            <span
                                class="bg-green-500 text-white rounded-full w-5 h-5 inline-flex items-center justify-center text-xs mr-1">4</span>
                            <i class="fas fa-users text-green-500 mr-1"></i>Groupe utilisateur
                        </label>
                        <select name="id_GU" id="id_GU" required
                            class="focus:outline-none w-full px-3 py-2 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                            <option value="">Sélectionner un groupe utilisateur</option>
                            <?php foreach ($groupes_utilisateur as $groupe): ?>
                                <option value="<?php echo htmlspecialchars($groupe->id_GU); ?>" <?php echo ($utilisateur_a_modifier && $groupe->id_GU == $utilisateur_a_modifier->id_GU) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($groupe->lib_GU); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Champs cachés avec valeurs par défaut -->
                <input type="hidden" name="statut_utilisateur" value="Actif">
                <input type="hidden" name="id_niveau_acces"
                    value="<?php echo !empty($niveau_acces) ? $niveau_acces[0]->id_niveau_acces_donnees : '1'; ?>">



                <div class="flex justify-end gap-2 pt-3 border-t border-gray-200 mt-3">
                    <?php if (isset($_GET['action']) && $_GET['action'] == 'edit'): ?>
                        <a href="?page=gestion_utilisateurs"
                            class="px-4 py-1.5 border border-gray-300 text-xs font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200">
                            <i class="fas fa-times mr-1"></i>Annuler
                        </a>
                        <button type="button" onclick="submitModifyForm()"
                            class="px-4 py-1.5 border border-transparent text-xs font-medium rounded-lg shadow-sm text-white bg-gradient hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-save mr-1"></i>Modifier
                        </button>
                    <?php else: ?>
                        <button type="reset"
                            class="px-4 py-1.5 border border-gray-300 text-xs font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200">
                            <i class="fas fa-redo mr-1"></i>Réinitialiser
                        </button>
                        <button type="submit" name="btn_add_utilisateur"
                            class="px-4 py-1.5 border border-transparent text-xs font-medium rounded-lg shadow-sm text-white bg-gradient hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-save mr-1"></i>Enregistrer
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if (isset($_GET['action']) && $_GET['action'] === 'addMasse'): ?>
            <!-- Formulaire d'ajout en masse -->
            <div class="bg-white rounded-lg shadow-card p-8 mb-8 border border-gray-200">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-full mr-4">
                            <i class="fas fa-users text-blue-500 text-xl"></i>
                        </div>
                        <h2 class="text-2xl font-semibold text-gray-700">Ajout en masse d'utilisateurs</h2>
                    </div>
                    <a href="?page=gestion_utilisateurs" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times fa-lg"></i>
                    </a>
                </div>
                <form method="POST" action="?page=gestion_utilisateurs" class="space-y-4" id="userMasse">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-users text-green-500 mr-2"></i>Sélectionner les personnes
                            </label>
                            <select name="selected_persons[]" multiple size="10" required
                                class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200"
                                style="height: auto; min-height: 200px;">
                                <optgroup label="Enseignants">
                                    <?php foreach ($enseignantsNonUtilisateurs as $enseignant): ?>
                                        <option value="ens_<?php echo $enseignant->id_enseignant; ?>"
                                            class="py-1 px-2 hover:bg-green-50 cursor-pointer">
                                            <?php echo htmlspecialchars($enseignant->nom_enseignant . ' ' . $enseignant->prenom_enseignant); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Personnel Administratif">
                                    <?php foreach ($personnelNonUtilisateurs as $personnel): ?>
                                        <option value="pers_<?php echo $personnel->id_pers_admin; ?>"
                                            class="py-1 px-2 hover:bg-green-50 cursor-pointer">
                                            <?php echo htmlspecialchars($personnel->nom_pers_admin . ' ' . $personnel->prenom_pers_admin); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Étudiants">
                                    <?php foreach ($etudiantsNonUtilisateurs as $etudiant): ?>
                                        <option value="etu_<?php echo $etudiant->num_carte_etud; ?>"
                                            class="py-1 px-2 hover:bg-green-50 cursor-pointer">
                                            <?php echo htmlspecialchars($etudiant->nom_etu . ' ' . $etudiant->prenom_etu); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                            <p class="text-sm text-gray-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Maintenez Shift ou Ctrl (ou Cmd sur Mac) pour sélectionner plusieurs personnes
                            </p>
                        </div>
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <label for="mass_type_utilisateur" class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-id-badge text-green-500 mr-2"></i>Type utilisateur
                                </label>
                                <select name="id_type_utilisateur" id="mass_type_utilisateur" required
                                    class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                                    <option value="">Sélectionner un type utilisateur</option>
                                    <?php foreach ($types_utilisateur as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type->id_type_utilisateur); ?>">
                                            <?php echo htmlspecialchars($type->lib_type_utilisateur); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label for="mass_groupe_utilisateur" class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-users text-green-500 mr-2"></i>Groupe utilisateur
                                </label>
                                <select name="id_GU" id="mass_groupe_utilisateur" required
                                    class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                                    <option value="">Sélectionner un groupe utilisateur</option>
                                    <?php foreach ($groupes_utilisateur as $groupe): ?>
                                        <option value="<?php echo htmlspecialchars($groupe->id_GU); ?>">
                                            <?php echo htmlspecialchars($groupe->lib_GU); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label for="mass_niveau_acces" class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-lock text-green-500 mr-2"></i>Niveau d'accès
                                </label>
                                <select name="id_niveau_acces" id="mass_niveau_acces" required
                                    class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                                    <option value="">Sélectionner un niveau</option>
                                    <?php foreach ($niveau_acces as $niveau): ?>
                                        <option value="<?php echo htmlspecialchars($niveau->id_niveau_acces_donnees); ?>">
                                            <?php echo htmlspecialchars($niveau->lib_niveau_acces_donnees); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label for="mass_statut" class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-toggle-on text-green-500 mr-2"></i>Statut
                                </label>
                                <select name="statut_utilisateur" id="mass_statut" required
                                    class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                                    <option value="">Sélectionner un statut</option>
                                    <option value="Actif">Actif</option>
                                    <option value="Inactif">Inactif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between gap-4">
                        <a href="?page=gestion_utilisateurs"
                            class="px-6 py-2.5 border border-gray-300 text-sm font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>Retour
                        </a>
                        <button type="submit" name="btn_add_multiple"
                            class="px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-gradient hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-users mr-2"></i>Ajouter en masse
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- User Stats Cards -->
        <?php if (!isset($_GET['action']) || $_GET['action'] !== 'addMasse'): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                <div class="bg-white rounded-lg shadow-card p-3 border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-green-100 mr-3">
                            <i class="fas fa-users text-green-600 text-base"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Total Utilisateurs</p>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo $totalUtilisateurs; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-card p-3 border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-blue-100 mr-3">
                            <i class="fas fa-user-check text-blue-600 text-base"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Utilisateurs Actifs</p>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo $utilisateursActifs; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-card p-3 border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-red-100 mr-3">
                            <i class="fas fa-user-times text-red-600 text-base"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Utilisateurs Inactifs</p>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo $utilisateursInactifs; ?></h3>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Main Content -->
            <div class="bg-white shadow-card rounded-lg overflow-hidden border border-gray-200 mb-4">

                <!-- Action Bar for Table -->
                <div
                    class="px-4 py-2 flex flex-col sm:flex-row justify-between items-center border-b border-gray-200 gap-2">
                    <div class="flex gap-2 w-full sm:w-auto">
                        <div class="flex items-center gap-1">
                            <label for="limitSelect" class="text-xs text-gray-600 whitespace-nowrap">
                                <i class="fas fa-list-ol mr-1"></i>Afficher:
                            </label>
                            <select id="limitSelect" onchange="changeLimit(this.value)"
                                class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white">
                                <option value="5" <?php echo $limit == 5 ? 'selected' : ''; ?>>5</option>
                                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                            </select>
                        </div>
                        <div class="relative flex-1 sm:flex-initial sm:w-64">
                            <input type="text" id="searchInput" placeholder="Rechercher un utilisateur..."
                                class="w-full px-3 py-1.5 pl-8 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                                <i class="fas fa-search text-gray-400 text-xs"></i>
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5 justify-center sm:justify-end">
                        <button onclick="printTable()"
                            class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-1 px-2.5 text-xs rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                            <i class="fas fa-print mr-1"></i>Imprimer
                        </button>
                        <button onclick="exportToExcel()"
                            class="bg-orange-500 hover:bg-orange-600 text-white font-medium py-1 px-2.5 text-xs rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-opacity-50">
                            <i class="fas fa-file-export mr-1"></i>Exporter
                        </button>
                        <button id="desactiverButton" type="button"
                            class="bg-red-500 hover:bg-red-600 text-white font-medium py-1 px-2.5 text-xs rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">
                            <i class="fa-solid fa-eye-slash mr-1"></i>Désactiver
                        </button>
                        <button id="activerButton" type="button"
                            class="bg-green-500 hover:bg-green-600 text-white font-medium py-1 px-2.5 text-xs rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                            <i class="fa-solid fa-eye-slash mr-1"></i>Activer
                        </button>
                        <button id="envoyerAccesButton" type="button"
                            class="bg-purple-500 hover:bg-purple-600 text-white font-medium py-1 px-2.5 text-xs rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-opacity-50">
                            <i class="fas fa-envelope mr-1"></i>Envoyer accès
                        </button>
                    </div>
                </div>

                <!-- Users Table with Scroll -->
                <div class="overflow-y-auto" style="max-height: 400px;">
                    <form class="overflow-x-auto" method="POST" action="?page=gestion_utilisateurs"
                        id="formListeUtilisateurs">
                        <input type="hidden" name="submit_disable_multiple" id="submitDisableHidden" value="0">
                        <input type="hidden" name="submit_enable_multiple" id="submitEnableHidden" value="0">
                        <input type="hidden" name="submit_send_access" id="submitSendAccessHidden" value="0">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-2 py-1.5 text-center">
                                        <input type="checkbox" id="selectAllCheckbox"
                                            class="form-checkbox h-3.5 w-3.5 text-green-600 border-gray-300 rounded focus:ring-green-500 cursor-pointer">
                                    </th>
                                    <th
                                        class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <span>Nom d'utilisateur</span>
                                            <i class="fas fa-sort ml-1 text-gray-400 text-xs"></i>
                                        </div>
                                    </th>
                                    <th
                                        class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <span>Groupe utilisateur</span>
                                            <i class="fas fa-sort ml-1 text-gray-400 text-xs"></i>
                                        </div>
                                    </th>
                                    <th
                                        class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <span>Statut</span>
                                            <i class="fas fa-sort ml-1 text-gray-400 text-xs"></i>
                                        </div>
                                    </th>
                                    <th
                                        class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <span>Login</span>
                                            <i class="fas fa-sort ml-1 text-gray-400 text-xs"></i>
                                        </div>
                                    </th>
                                    <?php if (canEdit()): ?>
                                        <th
                                            class="px-3 py-1.5 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="usersTableBody">
                                <?php if (empty($utilisateurs)): ?>
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-users text-gray-300 text-3xl mb-3"></i>
                                                <p>Aucun utilisateur trouvé.</p>
                                                <p class="text-xs mt-1">Ajoutez de nouveaux utilisateurs en cliquant sur le
                                                    bouton
                                                    "Ajouter un Utilisateur"</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    // Grouper les utilisateurs affichés par groupe utilisateur (lib_GU)
                                    $grouped = [];
                                    foreach ($utilisateurs as $user) {
                                        $groupName = $user->lib_GU ?? 'Sans groupe';
                                        if (!isset($grouped[$groupName])) {
                                            $grouped[$groupName] = [];
                                        }
                                        $grouped[$groupName][] = $user;
                                    }

                                    // Afficher chaque groupe avec un en-tête
                                    foreach ($grouped as $groupName => $usersGroup):
                                        ?>
                                        <tr class="bg-gray-50 group-header"
                                            data-group="<?php echo htmlspecialchars(md5($groupName)); ?>">
                                            <td colspan="7"
                                                class="px-3 py-1.5 text-xs font-semibold text-gray-700 cursor-pointer select-none">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        Groupe: <?php echo htmlspecialchars($groupName); ?>
                                                        <span
                                                            class="text-xs text-gray-500">(<?php echo count($usersGroup); ?>)</span>
                                                    </div>
                                                    <div class="group-toggle">
                                                        <i class="fas fa-chevron-down text-gray-500 text-xs"></i>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                        $rowIndex = 0;
                                        foreach ($usersGroup as $user):
                                            $rowIndex++;
                                            ?>
                                            <tr class="table-row-hover group-row <?php echo $rowIndex % 2 == 0 ? 'bg-gray-50' : 'bg-white'; ?>"
                                                data-group="<?php echo htmlspecialchars(md5($groupName)); ?>">

                                                <td class="px-2 py-2 text-center">
                                                    <input type="checkbox" name="selected_ids[]"
                                                        value="<?php echo htmlspecialchars($user->id_utilisateur); ?>"
                                                        class="user-checkbox form-checkbox h-3.5 w-3.5 text-green-600 border-gray-300 rounded focus:ring-green-500 cursor-pointer">
                                                </td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-700">
                                                    <div class="flex items-center">
                                                        <span><?php echo htmlspecialchars($user->nom_utilisateur); ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-700">
                                                    <div class="flex items-center">
                                                        <span><?php echo htmlspecialchars($user->lib_GU); ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-700">
                                                    <div class="flex items-center">
                                                        <span><?php echo htmlspecialchars($user->statut_utilisateur); ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-700">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-envelope text-gray-400 mr-1 text-xs"></i>
                                                        <?php echo htmlspecialchars($user->login_utilisateur); ?>
                                                    </div>
                                                </td>

                                                <?php if (canEdit()): ?>
                                                    <td class="px-3 py-2 whitespace-nowrap text-center">
                                                        <div class="flex justify-center space-x-2">
                                                            <a href="?page=gestion_utilisateurs&action=edit&id_utilisateur=<?php echo $user->id_utilisateur; ?>"
                                                                class="text-blue-500 hover:text-blue-700 transition-colors btn-icon text-sm"
                                                                title="Modifier">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </form>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <?php
                    // Construire les paramètres communs pour la pagination
                    $paginationParams = '';
                    if (!empty($search)) {
                        $paginationParams .= '&search=' . urlencode($search);
                    }
                    $paginationParams .= '&limit=' . $limit;
                    ?>
                    <div class="bg-white rounded-lg shadow-sm p-2 mt-3">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-2">
                            <div class="text-xs text-gray-500">
                                Affichage de <?= $offset + 1 ?> à <?= min($offset + $limit, $total_items) ?> sur
                                <?= $total_items ?> entrées
                            </div>
                            <div class="flex flex-wrap justify-center gap-1">
                                <?php if ($page > 1): ?>
                                    <a href="?page=gestion_utilisateurs&p=<?= $page - 1 ?><?= $paginationParams ?>"
                                        class="btn-hover px-2 py-1 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-50">
                                        <i class="fas fa-chevron-left mr-1"></i>Précédent
                                    </a>
                                <?php endif; ?>

                                <?php
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);

                                if ($start > 1) {
                                    echo '<a href="?page=gestion_utilisateurs&p=1' . $paginationParams . '" class="btn-hover px-2 py-1 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                    if ($start > 2) {
                                        echo '<span class="px-2 py-1 text-gray-500 text-xs">...</span>';
                                    }
                                }

                                for ($i = $start; $i <= $end; $i++):
                                    ?>
                                    <a href="?page=gestion_utilisateurs&p=<?= $i ?><?= $paginationParams ?>"
                                        class="btn-hover px-2 py-1 <?= $i === $page ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-xs font-medium">
                                        <?= $i ?>
                                    </a>
                                <?php endfor;

                                if ($end < $total_pages) {
                                    if ($end < $total_pages - 1) {
                                        echo '<span class="px-2 py-1 text-gray-500 text-xs">...</span>';
                                    }
                                    echo '<a href="?page=gestion_utilisateurs&p=' . $total_pages . $paginationParams . '" class="btn-hover px-2 py-1 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                                }
                                ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=gestion_utilisateurs&p=<?= $page + 1 ?><?= $paginationParams ?>"
                                        class="btn-hover px-2 py-1 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-50">
                                        Suivant<i class="fas fa-chevron-right ml-1"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <!-- Footer -->
        <div class="mt-3 text-center text-gray-500 text-xs">
            <p>© 2025 Système de Gestion des Utilisateurs. Tous droits réservés.</p>
        </div>
    </div>

    <!-- Modale de confirmation de désactivation -->
    <div id="disableModal"
        class="fixed inset-0 flex items-center justify-center z-50 hidden animate__animated animate__fadeIn">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 animate__animated animate__zoomIn shadow-2xl">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmation de désactivation</h3>
                <p class="text-sm text-gray-500 mb-6">
                    <i class="fas fa-info-circle mr-2"></i>
                    Êtes-vous sûr de vouloir désactiver les utilisateurs sélectionnées ?
                </p>
                <div class="flex justify-center gap-4">
                    <button type="button" id="confirmDelete"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200">
                        <i class="fas fa-check mr-2"></i>Confirmer
                    </button>
                    <button type="button" id="cancelDelete"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale de confirmation de réactivation -->
    <div id="enableModal"
        class="fixed inset-0 flex items-center justify-center z-50 hidden animate__animated animate__fadeIn">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 animate__animated animate__zoomIn shadow-2xl">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-green-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmation de réactivation</h3>
                <p class="text-sm text-gray-500 mb-6">
                    <i class="fas fa-info-circle mr-2"></i>
                    Êtes-vous sûr de vouloir réactiver les utilisateurs sélectionnées ?
                </p>
                <div class="flex justify-center gap-4">
                    <button type="button" id="confirmEnable"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200">
                        <i class="fas fa-check mr-2"></i>Confirmer
                    </button>
                    <button type="button" id="cancelEnable"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale de confirmation de modification -->
    <div id="modifyModal"
        class="fixed inset-0 flex items-center justify-center z-50 hidden animate__animated animate__fadeIn">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 animate__animated animate__zoomIn shadow-2xl">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                    <i class="fas fa-edit text-blue-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmation de modification</h3>
                <p class="text-sm text-gray-500 mb-6">
                    <i class="fas fa-info-circle mr-2"></i>
                    Êtes-vous sûr de vouloir modifier cet utilisateur ?
                </p>
                <div class="flex justify-center gap-4">
                    <button type="button" id="confirmModify"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200">
                        <i class="fas fa-check mr-2"></i>Confirmer
                    </button>
                    <button type="button" id="cancelModify"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale de confirmation d'envoi d'accès -->
    <div id="sendAccessModal"
        class="fixed inset-0 flex items-center justify-center z-50 hidden animate__animated animate__fadeIn">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 animate__animated animate__zoomIn shadow-2xl">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 mb-4">
                    <i class="fas fa-envelope text-purple-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmation d'envoi d'accès</h3>
                <p class="text-sm text-gray-500 mb-6">
                    <i class="fas fa-info-circle mr-2"></i>
                    Êtes-vous sûr de vouloir envoyer les informations d'accès par email aux utilisateurs sélectionnés ?
                </p>
                <div class="flex justify-center gap-4">
                    <button type="button" id="confirmSendAccess"
                        class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200">
                        <i class="fas fa-check mr-2"></i>Confirmer
                    </button>
                    <button type="button" id="cancelSendAccess"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fonction pour changer le nombre d'enregistrements affichés
        function changeLimit(limit) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('limit', limit);
            urlParams.set('p', '1'); // Réinitialiser à la première page
            window.location.href = '?' + urlParams.toString();
        }

        // Variables pour les modales de confirmation
        const searchInput = document.getElementById('searchInput');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');

        const disableButton = document.getElementById('desactiverButton');
        const enableButton = document.getElementById('activerButton');
        const sendAccessButton = document.getElementById('envoyerAccesButton');
        const submitDisableHidden = document.getElementById('submitDisableHidden');

        const btnModifier = document.getElementById('btnModifier');
        const modifyModal = document.getElementById('modifyModal');
        const confirmModify = document.getElementById('confirmModify');
        const cancelModify = document.getElementById('cancelModify');

        const disableModal = document.getElementById('disableModal');
        const confirmDelete = document.getElementById('confirmDelete');
        const cancelDelete = document.getElementById('cancelDelete');

        const cancelEnableModal = document.getElementById('enableModal');
        const confirmEnable = document.getElementById('confirmEnable');
        const cancelEnable = document.getElementById('cancelEnable');

        const sendAccessModal = document.getElementById('sendAccessModal');
        const confirmSendAccess = document.getElementById('confirmSendAccess');
        const cancelSendAccess = document.getElementById('cancelSendAccess');

        const submitEnableHidden = document.getElementById('submitEnableHidden');
        const submitSendAccessHidden = document.getElementById('submitSendAccessHidden');
        const submitModifierHidden = document.getElementById('btn_modifier_utilisateur_hidden');

        const formListeUser = document.getElementById('formListeUtilisateurs');

        // Search functionality
        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            const tableBody = document.getElementById('usersTableBody');
            let hasVisibleResults = false;

            // Récupérer tous les utilisateurs depuis PHP
            const allUsers = <?php echo json_encode(array_map(function ($user) {
                return [
                    'id' => $user->id_utilisateur,
                    'username' => $user->nom_utilisateur,
                    'groupe' => $user->lib_GU,
                    'statut' => $user->statut_utilisateur,
                    'login' => $user->login_utilisateur
                ];
            }, $allUtilisateurs)); ?>;

            // Si le champ de recherche est vide, recharger la page pour réinitialiser la pagination
            if (searchTerm === '') {
                window.location.href = '?page=gestion_utilisateurs';
                return;
            }

            // Filtrer les utilisateurs
            const filteredUsers = allUsers.filter(user =>
                user.username.toLowerCase().includes(searchTerm) ||
                user.groupe.toLowerCase().includes(searchTerm) ||
                user.statut.toLowerCase().includes(searchTerm) ||
                user.login.toLowerCase().includes(searchTerm)
            );

            // Vider le tableau
            tableBody.innerHTML = '';

            if (filteredUsers.length > 0) {
                hasVisibleResults = true;
                // Ajouter les utilisateurs filtrés au tableau
                filteredUsers.forEach(user => {
                    const row = document.createElement('tr');
                    row.className = 'table-row-hover';
                    row.innerHTML = `
                    <td class="px-4 py-4 text-center">
                        <input type="checkbox" name="selected_ids[]" value="${user.id}"
                            class="user-checkbox form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 cursor-pointer">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <div class="flex items-center">
                            <span>${user.username}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <div class="flex items-center">
                            <span>${user.groupe}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <div class="flex items-center">
                            <span>${user.statut}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-gray-400 mr-2"></i>
                            ${user.login}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex justify-center space-x-3">
                            <a href="?page=gestion_utilisateurs&action=edit&id_utilisateur=${user.id}"
                                class="text-blue-500 hover:text-blue-700 transition-colors btn-icon"
                                title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                `;
                    tableBody.appendChild(row);
                });
            }

            // Gérer l'affichage du message "Aucun résultat"
            if (!hasVisibleResults) {
                const noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results';
                noResultsRow.innerHTML = `
                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                    <div class="flex flex-col items-center">
                        <i class="fas fa-search text-gray-300 text-4xl mb-4"></i>
                        <p>Aucun résultat trouvé pour "${searchTerm}"</p>
                    </div>
                </td>
            `;
                tableBody.appendChild(noResultsRow);
            }

            // Mettre à jour la pagination
            updatePagination();
        });

        // Fonction pour mettre à jour la pagination
        function updatePagination() {
            const visibleRows = document.querySelectorAll('#usersTableBody tr:not(.no-results)');
            const paginationContainer = document.querySelector('.pagination');

            if (paginationContainer) {
                if (visibleRows.length === 0) {
                    paginationContainer.style.display = 'none';
                } else {
                    paginationContainer.style.display = 'flex';
                }
            }
        }

        updatedisableButtonState();
        updateenableButtonState();
        updateSendAccessButtonState();


        // Select all checkboxes
        selectAllCheckbox.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updatedisableButtonState();
            updateenableButtonState();
            updateSendAccessButtonState();
        });

        // Update disable button state
        function updatedisableButtonState() {
            const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
            let hasActiveUsers = false;

            checkedBoxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const statusCell = row.querySelector('td:nth-child(4) span');
                if (statusCell && statusCell.textContent.trim() === 'Actif') {
                    hasActiveUsers = true;
                }
            });

            disableButton.disabled = !hasActiveUsers;
            disableButton.classList.toggle('opacity-50', !hasActiveUsers);
            disableButton.classList.toggle('cursor-not-allowed', !hasActiveUsers);
        }

        // Update enable button state
        function updateenableButtonState() {
            const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
            let hasInactiveUsers = false;

            checkedBoxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const statusCell = row.querySelector('td:nth-child(4) span');
                if (statusCell && statusCell.textContent.trim() === 'Inactif') {
                    hasInactiveUsers = true;
                }
            });

            enableButton.disabled = !hasInactiveUsers;
            enableButton.classList.toggle('opacity-50', !hasInactiveUsers);
            enableButton.classList.toggle('cursor-not-allowed', !hasInactiveUsers);
        }

        // Update send access button state
        function updateSendAccessButtonState() {
            const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
            sendAccessButton.disabled = checkedBoxes.length === 0;
            sendAccessButton.classList.toggle('opacity-50', checkedBoxes.length === 0);
            sendAccessButton.classList.toggle('cursor-not-allowed', checkedBoxes.length === 0);
        }

        // Event listener for checkbox changes
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('user-checkbox')) {
                updatedisableButtonState();
                updateenableButtonState();
                updateSendAccessButtonState();
                // Also update the "select all" checkbox
                const allCheckboxes = document.querySelectorAll('.user-checkbox');
                const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
                selectAllCheckbox.checked = checkedBoxes.length === allCheckboxes.length && allCheckboxes.length >
                    0;
            }
        });


        // Fonction pour exporter en Excel
        function exportToExcel() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();

            // Récupérer tous les utilisateurs depuis PHP
            const allUsers = <?php echo json_encode(array_map(function ($user) {
                return [
                    'username' => $user->nom_utilisateur,
                    'groupe' => $user->lib_GU,
                    'statut' => $user->statut_utilisateur,
                    'login' => $user->login_utilisateur
                ];
            }, $allUtilisateurs)); ?>;

            // Filtrer les utilisateurs si une recherche est active
            const filteredUsers = searchTerm ? allUsers.filter(user =>
                user.username.toLowerCase().includes(searchTerm) ||
                user.groupe.toLowerCase().includes(searchTerm) ||
                user.statut.toLowerCase().includes(searchTerm) ||
                user.login.toLowerCase().includes(searchTerm)
            ) : allUsers;

            // Créer le contenu CSV
            let csvContent = "data:text/csv;charset=utf-8,";

            // Ajouter les en-têtes
            csvContent += "Nom d'utilisateur,Groupe utilisateur,Statut,Login\n";

            // Ajouter les données
            filteredUsers.forEach(user => {
                csvContent += `"${user.username}","${user.groupe}","${user.statut}","${user.login}"\n`;
            });

            // Créer le lien de téléchargement
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', 'utilisateurs.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }


        // Fonction pour imprimer
        function printTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();

            // Récupérer tous les utilisateurs depuis PHP
            const allUsers = <?php echo json_encode(array_map(function ($user) {
                return [
                    'username' => $user->nom_utilisateur,
                    'groupe' => $user->lib_GU,
                    'statut' => $user->statut_utilisateur,
                    'login' => $user->login_utilisateur
                ];
            }, $allUtilisateurs)); ?>;

            // Filtrer les utilisateurs si une recherche est active
            const filteredUsers = searchTerm ? allUsers.filter(user =>
                user.username.toLowerCase().includes(searchTerm) ||
                user.groupe.toLowerCase().includes(searchTerm) ||
                user.statut.toLowerCase().includes(searchTerm) ||
                user.login.toLowerCase().includes(searchTerm)
            ) : allUsers;

            const printWindow = window.open('', '_blank');

            // Créer le HTML pour l'impression
            const tableHTML = `
            <html>
                <head>
                    <title>Liste des utilisateurs</title>
                    <style>
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f5f5f5; }
                        @media print {
                            body { margin: 0; padding: 15px; }
                        }
                    </style>
                </head>
                <body>
                    <h2>Liste des utilisateurs</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom d'utilisateur</th>
                                <th>Groupe utilisateur</th>
                                <th>Statut</th>
                                <th>Login</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${filteredUsers.map(user => `
                                <tr>
                                    <td>${user.username}</td>
                                    <td>${user.groupe}</td>
                                    <td>${user.statut}</td>
                                    <td>${user.login}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </body>
            </html>
        `;

            printWindow.document.write(tableHTML);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }


        // Gestion des notifications
        document.addEventListener('DOMContentLoaded', function () {
            const successNotification = document.getElementById('successNotification');
            const errorNotification = document.getElementById('errorNotification');
            const loader = document.getElementById('loaderOverlay');

            // Masquer le loader si présent
            if (loader) {
                loader.classList.add('hidden');
            }

            // Afficher les notifications existantes
            if (successNotification) {
                setTimeout(() => {
                    successNotification.classList.remove('animate__fadeIn');
                    successNotification.classList.add('animate__fadeOut');
                    setTimeout(() => {
                        successNotification.remove();
                    }, 500);
                }, 5000);
            }

            if (errorNotification) {
                setTimeout(() => {
                    errorNotification.classList.remove('animate__fadeIn');
                    errorNotification.classList.add('animate__fadeOut');
                    setTimeout(() => {
                        errorNotification.remove();
                    }, 500);
                }, 5000);
            }
        });

        // Gestion de la modale de désactivation
        disableButton.addEventListener('click', function () {
            const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
            if (checkedBoxes.length > 0) {
                disableModal.classList.remove('hidden');
            }
        });

        enableButton.addEventListener('click', function () {
            const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
            if (checkedBoxes.length > 0) {
                enableModal.classList.remove('hidden');
            }
        });

        sendAccessButton.addEventListener('click', function () {
            const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
            if (checkedBoxes.length > 0) {
                sendAccessModal.classList.remove('hidden');
            }
        });

        // Confirmation de désactivation
        confirmDelete.addEventListener('click', function () {
            // Réinitialiser d'abord les trois inputs
            document.getElementById('submitDisableHidden').value = '0';
            document.getElementById('submitEnableHidden').value = '0';
            document.getElementById('submitSendAccessHidden').value = '0';
            // Puis définir la valeur pour la désactivation
            document.getElementById('submitDisableHidden').value = '2';
            formListeUser.submit();
        });

        // Confirmation de l'activation
        confirmEnable.addEventListener('click', function () {
            // Réinitialiser d'abord les trois inputs
            document.getElementById('submitDisableHidden').value = '0';
            document.getElementById('submitEnableHidden').value = '0';
            document.getElementById('submitSendAccessHidden').value = '0';
            // Puis définir la valeur pour l'activation
            document.getElementById('submitEnableHidden').value = '3';
            formListeUser.submit();
        });

        // Confirmation de l'envoi d'accès
        confirmSendAccess.addEventListener('click', function () {
            // Réinitialiser d'abord les trois inputs
            document.getElementById('submitDisableHidden').value = '0';
            document.getElementById('submitEnableHidden').value = '0';
            document.getElementById('submitSendAccessHidden').value = '0';
            // Puis définir la valeur pour l'envoi d'accès
            document.getElementById('submitSendAccessHidden').value = '4';
            formListeUser.submit();
        });

        // Annulation de la désactivation
        cancelDelete.addEventListener('click', function () {
            // Réinitialiser les inputs cachés
            document.getElementById('submitDisableHidden').value = '0';
            document.getElementById('submitEnableHidden').value = '0';
            // Décocher toutes les cases à cocher
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            // Décocher aussi la case "Tout sélectionner"
            selectAllCheckbox.checked = false;
            // Mettre à jour l'état du bouton désactiver
            updatedisableButtonState();
            // Fermer la modale
            disableModal.classList.add('hidden');
        });

        // Annulation de l'activation
        cancelEnable.addEventListener('click', function () {
            // Réinitialiser les inputs cachés
            document.getElementById('submitDisableHidden').value = '0';
            document.getElementById('submitEnableHidden').value = '0';
            // Décocher toutes les cases à cocher
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            // Décocher aussi la case "Tout sélectionner"
            selectAllCheckbox.checked = false;
            // Mettre à jour l'état du bouton activer
            updateenableButtonState();
            // Fermer la modale
            enableModal.classList.add('hidden');
        });

        // Fermer la modale si on clique en dehors
        disableModal.addEventListener('click', function (e) {
            if (e.target === disableModal) {
                // Décocher toutes les cases à cocher
                const checkboxes = document.querySelectorAll('.user-checkbox:checked');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                // Décocher aussi la case "Tout sélectionner"
                selectAllCheckbox.checked = false;
                // Mettre à jour l'état du bouton désactiver
                updatedisableButtonState();
                // Fermer la modale
                disableModal.classList.add('hidden');
            }
        });

        // Fermer la modale si on clique en dehors
        enableModal.addEventListener('click', function (e) {
            if (e.target === enableModal) {
                // Décocher toutes les cases à cocher
                const checkboxes = document.querySelectorAll('.user-checkbox:checked');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                // Décocher aussi la case "Tout sélectionner"
                selectAllCheckbox.checked = false;
                // Mettre à jour l'état du bouton activer
                updateenableButtonState();
                // Fermer la modale
                enableModal.classList.add('hidden');
            }
        });



        function submitModifyForm() {
            document.getElementById('modifyModal').classList.remove('hidden');
        }

        // Gestion de la modale de modification
        confirmModify.addEventListener('click', function () {
            // Ajouter un champ caché pour indiquer que c'est une modification
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'btn_modifier_utilisateur';
            hiddenInput.value = '1';
            userForm.appendChild(hiddenInput);

            // Soumettre le formulaire
            userForm.submit();
        });

        cancelModify.addEventListener('click', function () {
            modifyModal.classList.add('hidden');
        });

        // Fermer la modale si on clique en dehors
        modifyModal.addEventListener('click', function (e) {
            if (e.target === modifyModal) {
                modifyModal.classList.add('hidden');
            }
        });

        // Stocker les données des personnes en JavaScript
        const personnesData = {
            enseignants: <?php echo json_encode(array_map(function ($e) {
                return [
                    'nom' => $e->nom_enseignant . ' ' . $e->prenom_enseignant,
                    'login' => $e->mail_enseignant
                ];
            }, $enseignantsNonUtilisateurs)); ?>,
            personnel: <?php echo json_encode(array_map(function ($p) {
                return [
                    'nom' => $p->nom_pers_admin . ' ' . $p->prenom_pers_admin,
                    'login' => $p->email_pers_admin
                ];
            }, $personnelNonUtilisateurs)); ?>,
            etudiants: <?php echo json_encode(array_map(function ($e) {
                return [
                    'nom' => $e->nom_etu . ' ' . $e->prenom_etu,
                    'login' => $e->email_etu
                ];
            }, $etudiantsNonUtilisateurs)); ?>
        };

        // Autocomplétion pour la sélection de personne
        const personneSearchInput = document.getElementById('personne_search');
        const nomUtilisateurHidden = document.getElementById('nom_utilisateur');
        const loginUtilisateurInput = document.getElementById('login_utilisateur');
        const suggestionsListPersonne = document.getElementById('suggestions-list-personne');
        const typeSelect = document.getElementById('id_type_utilisateur');

        let currentPersonnes = [];
        let selectedIndex = -1;

        // Fonction pour obtenir les personnes selon le type sélectionné
        function getPersonnesByType(typeLabel) {
            const lowerType = typeLabel.toLowerCase();

            if (lowerType.includes('enseign') || lowerType.includes('prof')) {
                return personnesData.enseignants;
            } else if (lowerType.includes('personnel') || lowerType.includes('admin') || lowerType.includes('secrétaire') || lowerType.includes('secretaire')) {
                return personnesData.personnel;
            } else if (lowerType.includes('étudiant') || lowerType.includes('etudiant')) {
                return personnesData.etudiants;
            } else {
                // Si aucune correspondance, afficher toutes les personnes
                return [
                    ...personnesData.enseignants,
                    ...personnesData.personnel,
                    ...personnesData.etudiants
                ];
            }
        }

        // Fonction pour afficher les suggestions
        function showSuggestionsPersonne(value) {
            if (!currentPersonnes || currentPersonnes.length === 0) {
                suggestionsListPersonne.innerHTML = '<div class="autocomplete-no-results">Aucune personne disponible pour ce type</div>';
                suggestionsListPersonne.classList.add('active');
                return;
            }

            const filteredPersonnes = currentPersonnes.filter(p =>
                p.nom.toLowerCase().includes(value.toLowerCase())
            );

            suggestionsListPersonne.innerHTML = '';

            if (value.trim() === '') {
                suggestionsListPersonne.classList.remove('active');
                return;
            }

            if (filteredPersonnes.length === 0) {
                suggestionsListPersonne.innerHTML = '<div class="autocomplete-no-results">Aucun résultat trouvé</div>';
            } else {
                filteredPersonnes.forEach((personne, index) => {
                    const div = document.createElement('div');
                    div.className = 'autocomplete-suggestion';
                    div.setAttribute('data-nom', personne.nom);
                    div.setAttribute('data-login', personne.login);
                    div.innerHTML = `
                        <div class="icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="text">
                            ${personne.nom}
                            <div style="font-size: 11px; color: #6b7280; font-weight: normal; margin-top: 2px;">${personne.login}</div>
                        </div>
                    `;
                    suggestionsListPersonne.appendChild(div);
                });
            }

            suggestionsListPersonne.classList.add('active');
            selectedIndex = -1;

            // Ajouter les écouteurs de clic
            suggestionsListPersonne.querySelectorAll('.autocomplete-suggestion').forEach(item => {
                item.addEventListener('click', function () {
                    const nom = this.getAttribute('data-nom');
                    const login = this.getAttribute('data-login');
                    personneSearchInput.value = nom;
                    nomUtilisateurHidden.value = nom;

                    // Générer le login à partir du nom complet
                    generateAndCheckLogin(nom);

                    suggestionsListPersonne.classList.remove('active');
                });
            });
        }

        /**
         * Génère un login au format "premierelettre+nom" et vérifie sa disponibilité
         * @param {string} fullName - Le nom complet (prénom nom)
         */
        function generateAndCheckLogin(fullName) {
            if (!fullName || fullName.trim() === '') {
                return;
            }

            // Parser le nom complet (format: "Prénom Nom" ou "Prénom Nom1 Nom2")
            const parts = fullName.trim().split(' ').filter(p => p.length > 0);

            if (parts.length < 2) {
                loginUtilisateurInput.value = fullName.toLowerCase().replace(/\s+/g, '');
                checkLoginAvailability(loginUtilisateurInput.value);
                return;
            }

            // Récupérer la première lettre du prénom et le nom de famille
            const prenom = parts[0];
            const nom = parts.slice(1).join(''); // Si plusieurs noms de famille, les concaténer

            // Générer le login : premièreLettrePrenom + nom
            const baseLogin = (prenom.charAt(0) + nom).toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Retirer les accents
                .replace(/[^a-z0-9]/g, ''); // Retirer les caractères spéciaux

            // Afficher l'icône de chargement
            showLoginStatus('loading');

            // Vérifier la disponibilité
            checkLoginAvailability(baseLogin);
        }

        /**
         * Vérifie la disponibilité d'un login via AJAX
         * @param {string} login - Le login à vérifier
         */
        function checkLoginAvailability(login) {
            const url = `?page=gestion_utilisateurs&ajax=checkLogin&login=${encodeURIComponent(login)}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.available) {
                            // Login disponible
                            loginUtilisateurInput.value = data.login;
                            showLoginStatus('success', `Login disponible : ${data.login}`);
                        } else {
                            // Login non disponible, utiliser la suggestion
                            loginUtilisateurInput.value = data.suggestedLogin;
                            showLoginStatus('warning', `"${data.originalLogin}" existe déjà. Suggestion : ${data.suggestedLogin}`);
                        }
                    } else {
                        showLoginStatus('error', data.message || 'Erreur lors de la vérification');
                    }
                })
                .catch(error => {
                    console.error('Erreur AJAX:', error);
                    showLoginStatus('error', 'Erreur de connexion au serveur');
                });
        }

        /**
         * Affiche le statut de vérification du login
         * @param {string} status - 'loading', 'success', 'warning', 'error'
         * @param {string} message - Message à afficher
         */
        function showLoginStatus(status, message = '') {
            const icon = document.getElementById('login-status-icon');
            const messageDiv = document.getElementById('login-status-message');
            const messageSpan = messageDiv.querySelector('span');
            const input = loginUtilisateurInput;

            // Réinitialiser les classes
            icon.classList.add('hidden');
            messageDiv.classList.add('hidden');
            input.classList.remove('border-green-500', 'border-yellow-500', 'border-red-500');

            if (status === 'loading') {
                icon.classList.remove('hidden');
                icon.innerHTML = `
                    <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                `;
            } else if (status === 'success') {
                icon.classList.remove('hidden');
                icon.innerHTML = `
                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                `;
                input.classList.add('border-green-500');
                messageDiv.classList.remove('hidden');
                messageDiv.classList.remove('text-red-600', 'text-yellow-600');
                messageDiv.classList.add('text-green-600');
                messageSpan.textContent = message;
            } else if (status === 'warning') {
                icon.classList.remove('hidden');
                icon.innerHTML = `
                    <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                `;
                input.classList.add('border-yellow-500');
                messageDiv.classList.remove('hidden');
                messageDiv.classList.remove('text-red-600', 'text-green-600');
                messageDiv.classList.add('text-yellow-600');
                messageSpan.textContent = message;
            } else if (status === 'error') {
                icon.classList.remove('hidden');
                icon.innerHTML = `
                    <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                `;
                input.classList.add('border-red-500');
                messageDiv.classList.remove('hidden');
                messageDiv.classList.remove('text-green-600', 'text-yellow-600');
                messageDiv.classList.add('text-red-600');
                messageSpan.textContent = message;
            }
        }

        // Événement changement de type d'utilisateur
        if (typeSelect && personneSearchInput) {
            typeSelect.addEventListener('change', function () {
                const selectedType = this.options[this.selectedIndex];
                const typeLabel = selectedType ? selectedType.dataset.typeLabel : '';

                if (typeLabel) {
                    // Activer l'input de recherche
                    personneSearchInput.disabled = false;
                    personneSearchInput.placeholder = 'Tapez pour rechercher une personne...';
                    personneSearchInput.value = '';
                    nomUtilisateurHidden.value = '';
                    loginUtilisateurInput.value = '';

                    // Charger les personnes correspondant au type
                    currentPersonnes = getPersonnesByType(typeLabel);
                } else {
                    // Désactiver l'input
                    personneSearchInput.disabled = true;
                    personneSearchInput.placeholder = 'Choisissez d\'abord un type';
                    personneSearchInput.value = '';
                    nomUtilisateurHidden.value = '';
                    loginUtilisateurInput.value = '';
                    currentPersonnes = [];
                }

                suggestionsListPersonne.classList.remove('active');
            });
        }

        // Événement input sur le champ de recherche
        if (personneSearchInput) {
            personneSearchInput.addEventListener('input', function () {
                showSuggestionsPersonne(this.value);
            });

            // Événement focus
            personneSearchInput.addEventListener('focus', function () {
                if (this.value.trim()) {
                    showSuggestionsPersonne(this.value);
                }
            });

            // Navigation au clavier
            personneSearchInput.addEventListener('keydown', function (e) {
                const suggestions = suggestionsListPersonne.querySelectorAll('.autocomplete-suggestion');

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, suggestions.length - 1);
                    updateSelectionPersonne(suggestions);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelectionPersonne(suggestions);
                } else if (e.key === 'Enter' && selectedIndex >= 0) {
                    e.preventDefault();
                    suggestions[selectedIndex].click();
                } else if (e.key === 'Escape') {
                    suggestionsListPersonne.classList.remove('active');
                }
            });
        }

        function updateSelectionPersonne(suggestions) {
            suggestions.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.classList.add('selected');
                    item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                } else {
                    item.classList.remove('selected');
                }
            });
        }

        // Fermer les suggestions en cliquant à l'extérieur
        document.addEventListener('click', function (e) {
            if (personneSearchInput && !personneSearchInput.contains(e.target) &&
                suggestionsListPersonne && !suggestionsListPersonne.contains(e.target)) {
                suggestionsListPersonne.classList.remove('active');
            }
        });

        // Vérification manuelle du login si l'utilisateur le modifie
        if (loginUtilisateurInput) {
            let loginTimeout;
            loginUtilisateurInput.addEventListener('input', function () {
                const login = this.value.trim();

                // Nettoyer le login (retirer espaces, accents, caractères spéciaux)
                const cleanLogin = login.toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]/g, '');

                if (cleanLogin !== login) {
                    this.value = cleanLogin;
                }

                // Vérifier avec un délai (debounce) pour éviter trop de requêtes
                clearTimeout(loginTimeout);

                if (cleanLogin.length > 0) {
                    showLoginStatus('loading');
                    loginTimeout = setTimeout(() => {
                        checkLoginAvailability(cleanLogin);
                    }, 500);
                } else {
                    showLoginStatus('error', 'Le login ne peut pas être vide');
                }
            });
        }

        // Gestion de la sélection multiple pour le formulaire en masse
        document.addEventListener('DOMContentLoaded', function () {
            const selectMultiple = document.querySelector('select[name="selected_persons[]"]');
            if (selectMultiple) {
                // Permettre la sélection multiple avec Shift
                selectMultiple.addEventListener('keydown', function (e) {
                    if (e.key === 'Shift' && e.shiftKey) {
                        const options = Array.from(this.options);
                        const selectedIndex = this.selectedIndex;
                        const lastSelectedIndex = this.lastSelectedIndex || selectedIndex;

                        const start = Math.min(selectedIndex, lastSelectedIndex);
                        const end = Math.max(selectedIndex, lastSelectedIndex);

                        for (let i = start; i <= end; i++) {
                            options[i].selected = true;
                        }
                    }
                    this.lastSelectedIndex = this.selectedIndex;
                });

                // Permettre la sélection multiple avec la souris
                selectMultiple.addEventListener('mousedown', function (e) {
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        const option = e.target;
                        if (option.tagName === 'OPTION') {
                            option.selected = !option.selected;
                        }
                    }
                });
            }
        });

        // Gestion du formulaire d'ajout en masse
        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOM Content Loaded');

            const form = document.getElementById('userMasse');
            console.log('Form found:', form);

            if (form) {
                form.addEventListener('submit', function (e) {
                    console.log('Form submitted');

                    const addMultipleButton = this.querySelector('button[name="btn_add_multiple"]');
                    console.log('Add multiple button:', addMultipleButton);

                    if (addMultipleButton) {
                        console.log('Creating loader');
                        // Créer et afficher le loader
                        const loader = document.createElement('div');
                        loader.id = 'loader';
                        loader.innerHTML = `
                        <div class="fixed inset-0 shadow-2xl bg-opacity-50 flex items-center justify-center z-50">
                            <div class="bg-white rounded-lg p-6 max-w-xs w-full mx-4 shadow-2xl">
                                <div class="text-center">
                                    <div class="inline-block relative">
                                        <div class="w-12 h-12 border-3 border-green-200 rounded-full"></div>
                                        <div class="w-12 h-12 border-3 border-green-500 rounded-full absolute top-0 left-0 animate-spin border-t-transparent"></div>
                                    </div>
                                    <h3 class="mt-3 text-base font-medium text-gray-900">Traitement en cours</h3>
                                    <p class="mt-1 text-sm text-gray-500">Ajout des utilisateurs...</p>
                                    <div class="mt-3">
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-green-500 h-1.5 rounded-full progress-bar"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                        document.body.appendChild(loader);
                        console.log('Loader added to DOM');

                        // Vérifier les messages toutes les 100ms
                        const checkForMessages = setInterval(() => {
                            console.log('Checking for messages...');
                            const successMessage =
                            <?= json_encode($GLOBALS['messageSuccess'] ?? '') ?>;
                            const errorMessage =
                            <?= json_encode($GLOBALS['messageErreur'] ?? '') ?>;

                            if (successMessage || errorMessage) {
                                console.log('Message found:', successMessage || errorMessage);
                                const loader = document.getElementById('loader');
                                if (loader) {
                                    loader.remove();
                                }
                                clearInterval(checkForMessages);
                            }
                        }, 100);
                    }
                });
            }

            // Masquer les notifications après 5 secondes
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                setTimeout(() => {
                    notification.classList.remove('animate__fadeIn');
                    notification.classList.add('animate__fadeOut');
                    setTimeout(() => {
                        notification.remove();
                    }, 500);
                }, 5000);
            });
        });

        // Accordion pour grouper les utilisateurs par catégorie (déplier / replier)
        (function () {
            const headers = document.querySelectorAll('.group-header');
            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', function () {
                    const group = this.getAttribute('data-group');
                    const rows = document.querySelectorAll('.group-row[data-group="' + group + '"]');
                    const chevron = this.querySelector('.group-toggle i');
                    let anyVisible = false;
                    rows.forEach(r => { if (getComputedStyle(r).display !== 'none') anyVisible = true; });
                    if (anyVisible) {
                        rows.forEach(r => r.style.display = 'none');
                        if (chevron) { chevron.classList.remove('fa-chevron-up'); chevron.classList.add('fa-chevron-down'); }
                    } else {
                        rows.forEach(r => r.style.display = 'table-row');
                        if (chevron) { chevron.classList.remove('fa-chevron-down'); chevron.classList.add('fa-chevron-up'); }
                    }
                });
            });
        })();

    </script>

</body>

</html>