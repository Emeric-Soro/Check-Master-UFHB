<?php

$utilisateur_a_modifier = $GLOBALS['utilisateur_a_modifier'];
$showModal = isset($_GET['action']) && ($_GET['action'] === 'edit' || $_GET['action'] === 'add' || $_GET['action'] === 'addMasse');

$utilisateurs = $GLOBALS['utilisateurs'] ?? [];
$niveau_acces = $GLOBALS['niveau_acces'];
$types_utilisateur =$GLOBALS['types_utilisateur'];
$groupes_utilisateur =$GLOBALS['groupes_utilisateur'] ;


// Calculer les statistiques sur l'ensemble des utilisateurs
$allUtilisateurs = $GLOBALS['utilisateurs'] ?? [];
$totalUtilisateurs = count($allUtilisateurs);
$utilisateursActifs = count(array_filter($allUtilisateurs, function($u) { return $u->statut_utilisateur === 'Actif'; }));
$utilisateursInactifs = $totalUtilisateurs - $utilisateursActifs;

// Pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';

// Filter the list based on search
if (!empty($search)) {
    $allUtilisateurs = array_filter($allUtilisateurs, function($utilisateur) use ($search) {
        return stripos($utilisateur->nom_utilisateur, $search) !== false ||
                stripos($utilisateur->prenom_utilisateur, $search) !== false ||
                stripos($utilisateur->email_utilisateur, $search) !== false;
    });
}

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
    </style>
    <style>
        :root {
            --primary: #1a5276;
            --primary-light: #2980b9;
            --accent: #4caf50;
            --secondary: #ff8c00;
            --danger: #e74c3c;
            --muted: #6b7280;
            --surface: #ffffff;
            --surface-2: #f8fafc;
            --card-radius: 12px;
        }

        body { background: var(--surface-2); font-family: Poppins, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; color: #102a43; }

        .bg-green-100 { background-color: rgba(26,82,118,0.06) !important; }
        .bg-green-500 { background-color: var(--primary) !important; color: #fff !important; }
        .bg-green-600 { background-color: #154360 !important; color: #fff !important; }
        .text-green-500 { color: var(--primary) !important; }
        .focus\:ring-green-500 { box-shadow: 0 0 0 3px rgba(26,82,118,0.12) !important; }
        .border-green-500 { border-color: var(--primary) !important; }

        .bg-blue-100 { background-color: rgba(41,128,185,0.06) !important; }
        .bg-blue-500 { background-color: var(--primary-light) !important; color: #fff !important; }
        .bg-blue-600 { background-color: #2471a3 !important; color: #fff !important; }
        .text-blue-600 { color: var(--primary-light) !important; }

        .bg-red-100 { background-color: rgba(231,76,60,0.06) !important; }
        .bg-red-500 { background-color: var(--danger) !important; color: #fff !important; }
        .bg-red-600 { background-color: #cb4335 !important; color: #fff !important; }

        .bg-orange-500 { background-color: var(--secondary) !important; color: #fff !important; }
        .bg-orange-600 { background-color: #d35400 !important; color: #fff !important; }

        .notification.success { background: linear-gradient(135deg, var(--primary-light), var(--primary)) !important; }
        .notification.error { background: linear-gradient(135deg, var(--danger), #cb4335) !important; }

        .bg-gradient-to-r.from-green-600.to-green-800 { background: linear-gradient(90deg, var(--primary), var(--primary-light)) !important; }
        .bg-gradient-to-r.from-primary.to-primary-light { background: linear-gradient(90deg, var(--primary), var(--primary-light)) !important; }

        .shadow-card { box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06) !important; border-radius: var(--card-radius) !important; }
        .card { background: var(--surface); border-radius: var(--card-radius); box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04); }

        .btn-primary, button[type="submit"], .bg-gradient { background: linear-gradient(90deg, var(--primary), var(--primary-light)) !important; color: #fff !important; border: none !important; }
        .btn-primary:hover, .bg-gradient:hover { filter: brightness(0.96); }

        .table-row-hover:hover { background-color: rgba(26,82,118,0.04); transition: background-color 0.15s ease; }

        .btn-hover { transition: all 0.15s ease; }
        .btn-hover:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(26,82,118,0.08); }

        .modal-transition { transition: opacity .25s ease, transform .25s ease; }

        input[type="text"], input[type="email"], select { border-radius: 10px; border: 1px solid #e6eef6; box-shadow: none; }
        .rounded-lg { border-radius: 10px; }
        .rounded-xl { border-radius: 14px; }

        .placeholder-empty { display:flex; align-items:center; justify-content:center; padding:24px; color:var(--muted); border-radius:8px; background:var(--surface); box-shadow:0 6px 18px rgba(15,23,42,0.03); }

        .top-gradient { background: linear-gradient(90deg, rgba(26,82,118,0.95), rgba(41,128,185,0.95)); color: #fff; }

        @media (max-width: 768px) {
            .menu-wrapper { max-height: 46vh; overflow-y: auto; }
        }
    </style>
</head>

<body class="bg-gray-50">

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
<div class="relative container mx-auto px-4 py-8">
    <!-- Add/Edit User Modal -->
    <div id="userModal"
         class="fixed inset-0 bg-opacity-50 border border-gray-200 overflow-y-auto h-full w-full z-50 flex <?php echo $showModal ? 'add' : 'hidden'; ?> items-center justify-center modal-transition">
        <div class="relative p-8  w-full max-w-2xl shadow-2xl rounded-xl bg-white fade-in transform">
            <div class="absolute top-0 right-0 m-3">
                <button onclick="closeUserModal()"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none btn-icon">
                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>
            <div class="flex items-center mb-6 pb-2 border-b border-gray-200">
                <div class="bg-green-100 p-2 rounded-full mr-3">
                    <i class="fas fa-user-plus text-green-500"></i>
                </div>
                <h3 id="userModalTitle" class="text-2xl font-semibold text-gray-700">
                    <?php echo isset($utilisateur_a_modifier) && $_GET['action']=='edit' ? 'Modifier un utilisateur' : 'Ajouter un Utilisateur' ?>
                </h3>
            </div>
            <form id="userForm" class="space-y-4" method="POST" action="?page=gestion_utilisateurs">
                <input type="hidden" id="userId" name="id_utilisateur"
                       value="<?php echo $utilisateur_a_modifier ? $utilisateur_a_modifier->id_utilisateur : ''; ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label for="nom_utilisateur" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-user text-green-500 mr-2"></i>Nom d'utilisateur
                        </label>
                        <?php if ($_GET['action'] === 'add'): ?>
                            <select name="nom_utilisateur" id="nom_utilisateur" required
                                    class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                                <option value="">Sélectionner une personne</option>
                                <optgroup label="Enseignants">
                                    <?php foreach($enseignantsNonUtilisateurs as $enseignant): ?>
                                        <option
                                                value="<?php echo htmlspecialchars($enseignant->nom_enseignant . ' ' . $enseignant->prenom_enseignant); ?>"
                                                data-login="<?php echo htmlspecialchars($enseignant->mail_enseignant); ?>">
                                            <?php echo htmlspecialchars($enseignant->nom_enseignant . ' ' . $enseignant->prenom_enseignant); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Personnel Administratif">
                                    <?php foreach($personnelNonUtilisateurs as $personnel): ?>
                                        <option
                                                value="<?php echo htmlspecialchars($personnel->nom_pers_admin . ' ' . $personnel->prenom_pers_admin); ?>"
                                                data-login="<?php echo htmlspecialchars($personnel->email_pers_admin); ?>">
                                            <?php echo htmlspecialchars($personnel->nom_pers_admin . ' ' . $personnel->prenom_pers_admin); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Étudiants">
                                    <?php foreach($etudiantsNonUtilisateurs as $etudiant): ?>
                                        <option
                                                value="<?php echo htmlspecialchars($etudiant->nom_etu . ' ' . $etudiant->prenom_etu); ?>"
                                                data-login="<?php echo htmlspecialchars($etudiant->email_etu); ?>">
                                            <?php echo htmlspecialchars($etudiant->nom_etu . ' ' . $etudiant->prenom_etu); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        <?php else: ?>
                            <input type="text" name="nom_utilisateur" id="nom_utilisateur" required
                                   value="<?php echo $utilisateur_a_modifier ? htmlspecialchars($utilisateur_a_modifier->nom_utilisateur) : ''; ?>"
                                   class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                        <?php endif; ?>
                    </div>
                    <div class="space-y-2">
                        <label for="login_utilisateur" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-envelope text-green-500 mr-2"></i>Login
                        </label>
                        <input type="email" name="login_utilisateur" id="login_utilisateur" required
                               value="<?php echo $utilisateur_a_modifier ? htmlspecialchars($utilisateur_a_modifier->login_utilisateur) : ''; ?>"
                               class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label for="id_type_utilisateur" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-id-badge text-green-500 mr-2"></i>Type utilisateur
                        </label>
                        <select name="id_type_utilisateur" id="id_type_utilisateur" required
                                class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                            <option value="">Sélectionner un type utilisateur</option>
                            <?php foreach($types_utilisateur as $type): ?>
                                <option value="<?php echo htmlspecialchars($type->id_type_utilisateur); ?>"
                                        <?php echo ($utilisateur_a_modifier && $type->id_type_utilisateur == $utilisateur_a_modifier->id_type_utilisateur) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type->lib_type_utilisateur); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label for="statut_utilisateur" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-toggle-on text-green-500 mr-2"></i>Statut
                        </label>
                        <select name="statut_utilisateur" id="statut_utilisateur" required
                                class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                            <option value="">Sélectionner un statut</option>
                            <option value="Actif"
                                    <?php echo ($utilisateur_a_modifier && $utilisateur_a_modifier->statut_utilisateur === 'Actif') ? 'selected' : ''; ?>>
                                Actif</option>
                            <option value="Inactif"
                                    <?php echo ($utilisateur_a_modifier && $utilisateur_a_modifier->statut_utilisateur === 'Inactif') ? 'selected' : ''; ?>>
                                Inactif</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label for="id_GU" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-users text-green-500 mr-2"></i>Groupe utilisateur
                        </label>
                        <select name="id_GU" id="id_GU" required
                                class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                            <option value="">Sélectionner un groupe utilisateur</option>
                            <?php foreach($groupes_utilisateur as $groupe): ?>
                                <option value="<?php echo htmlspecialchars($groupe->id_GU); ?>"
                                        <?php echo ($utilisateur_a_modifier && $groupe->id_GU == $utilisateur_a_modifier->id_GU) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($groupe->lib_GU); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label for="id_niveau_acces" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-lock text-green-500 mr-2"></i>Niveau d'accès
                        </label>
                        <select name="id_niveau_acces" id="id_niveau_acces" required
                                class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                            <option value="">Sélectionner un niveau</option>
                            <?php foreach($niveau_acces as $niveau): ?>
                                <option value="<?php echo htmlspecialchars($niveau->id_niveau_acces_donnees); ?>"
                                        <?php echo ($utilisateur_a_modifier && $niveau->id_niveau_acces_donnees == $utilisateur_a_modifier->id_niv_acces_donnee) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($niveau->lib_niveau_acces_donnees); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>


                <div class="flex justify-between">
                    <button type="button" onclick="closeUserModal()"
                            class="px-6 py-2.5 border border-gray-300 text-sm font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </button>
                    <?php if (isset($utilisateur_a_modifier) && $_GET['action']=='edit'): ?>
                        <button type="button" onclick="submitModifyForm()"
                                class="px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-gradient hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-save mr-2"></i>Modifier
                        </button>
                    <?php else: ?>
                        <button type="submit" name="btn_add_utilisateur"
                                class="px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-gradient hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-save mr-2"></i>Enregistrer
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!--Ajouter en masse les utilisateurs-->
    <div id="userMasseModal"
         class="fixed inset-0 bg-opacity-50 border border-gray-200 overflow-y-auto h-full w-full z-50 flex hidden items-center justify-center modal-transition">
        <div class="relative p-8 w-full max-w-2xl shadow-2xl rounded-xl bg-white fade-in transform">
            <div class="absolute top-0 right-0 m-3">
                <button onclick="closeMasseModal()"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none btn-icon">
                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>
            <div class="flex items-center mb-6 pb-2 border-b border-gray-200">
                <div class="bg-blue-100 p-2 rounded-full mr-3">
                    <i class="fas fa-users text-blue-500"></i>
                </div>
                <h3 class="text-2xl font-semibold text-gray-700">Ajout en masse d'utilisateurs</h3>
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
                                <?php foreach($enseignantsNonUtilisateurs as $enseignant): ?>
                                    <option value="ens_<?php echo $enseignant->id_enseignant; ?>"
                                            class="py-1 px-2 hover:bg-green-50 cursor-pointer">
                                        <?php echo htmlspecialchars($enseignant->nom_enseignant . ' ' . $enseignant->prenom_enseignant); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Personnel Administratif">
                                <?php foreach($personnelNonUtilisateurs as $personnel): ?>
                                    <option value="pers_<?php echo $personnel->id_pers_admin; ?>"
                                            class="py-1 px-2 hover:bg-green-50 cursor-pointer">
                                        <?php echo htmlspecialchars($personnel->nom_pers_admin . ' ' . $personnel->prenom_pers_admin); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Étudiants">
                                <?php foreach($etudiantsNonUtilisateurs as $etudiant): ?>
                                    <option value="etu_<?php echo $etudiant->num_etu; ?>"
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
                                <?php foreach($types_utilisateur as $type): ?>
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
                                <?php foreach($groupes_utilisateur as $groupe): ?>
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
                                <?php foreach($niveau_acces as $niveau): ?>
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
                    <button type="button" onclick="closeMasseModal()"
                            class="px-6 py-2.5 border border-gray-300 text-sm font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </button>
                    <button type="submit" name="btn_add_multiple"
                            class="px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-gradient hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-users mr-2"></i>Ajouter en masse

                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- User Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-card p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 mr-4">
                    <i class="fas fa-users text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Total Utilisateurs</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $totalUtilisateurs; ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-card p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 mr-4">
                    <i class="fas fa-user-check text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Utilisateurs Actifs</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $utilisateursActifs; ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-card p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 mr-4">
                    <i class="fas fa-user-times text-red-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Utilisateurs Inactifs</p>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $utilisateursInactifs; ?></h3>
                </div>
            </div>
        </div>
    </div>


    <!-- Main Content -->
    <div class="bg-white shadow-card rounded-lg overflow-hidden border border-gray-200 mb-8">

        <!-- Dashboard Header -->
        <div class=" top-gradient px-6 py-4 flex justify-between items-center">
            <h2 class="text-xl font-bold text-white">Gestion des Utilisateurs</h2>
            <div class="flex gap-4">
                <a href="?page=gestion_utilisateurs&action=add"
                   class="bg-green-500 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                    <i class="fas fa-plus mr-2"></i>Ajouter un Utilisateur
                </a>
                <a href="?page=gestion_utilisateurs&action=addMasse"
                   class="bg-blue-500  text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                    <i class="fas fa-plus mr-2"></i>Ajouter en masse
                </a>
            </div>

        </div>

        <!-- Action Bar for Table -->
        <div class="px-6 py-4 flex flex-col sm:flex-row justify-between items-center border-b border-gray-200">
            <div class="relative w-full sm:w-1/2 lg:w-1/3 mb-4 sm:mb-0">
                <input type="text" id="searchInput" placeholder="Rechercher un utilisateur..."
                       class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </span>
            </div>
            <div class="flex flex-wrap gap-2 justify-center sm:justify-end">
                <button onclick="printTable()"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                    <i class="fas fa-print mr-2"></i>Imprimer
                </button>
                <button onclick="exportToExcel()"
                        class="bg-orange-500 hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-opacity-50">
                    <i class="fas fa-file-export mr-2"></i>Exporter
                </button>
                <button id="desactiverButton" type="button"
                        class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">
                    <i class="fa-solid fa-eye-slash mr-2"></i>Désactiver
                </button>
                <button id="activerButton" type="button"
                        class="bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                    <i class="fa-solid fa-eye-slash mr-2"></i>Activer
                </button>
            </div>
        </div>

        <!-- Users Table -->
        <form class="overflow-x-auto" method="POST" action="?page=gestion_utilisateurs" id="formListeUtilisateurs">
            <input type="hidden" name="submit_disable_multiple" id="submitDisableHidden" value="0">
            <input type="hidden" name="submit_enable_multiple" id="submitEnableHidden" value="0">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-center">
                        <input type="checkbox" id="selectAllCheckbox"
                               class="form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 cursor-pointer">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center">
                            <span>Nom d'utilisateur</span>
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center">
                            <span>Groupe utilisateur</span>
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center">
                            <span>Statut</span>
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center">
                            <span>Login</span>
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="usersTableBody">
                <?php if (empty($utilisateurs)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-users text-gray-300 text-4xl mb-4"></i>
                                <p>Aucun utilisateur trouvé.</p>
                                <p class="text-sm mt-2">Ajoutez de nouveaux utilisateurs en cliquant sur le bouton
                                    "Ajouter un Utilisateur"</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($utilisateurs as $index => $user): ?>
                        <tr class="table-row-hover">

                            <td class="px-4 py-4 text-center">
                                <input type="checkbox" name="selected_ids[]"
                                       value="<?php echo htmlspecialchars($user->id_utilisateur); ?>"
                                       class="user-checkbox form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 cursor-pointer">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div class="flex items-center">

                                    <span><?php echo htmlspecialchars($user->nom_utilisateur); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div class="flex items-center">

                                    <span><?php echo htmlspecialchars($user->lib_GU); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div class="flex items-center">

                                    <span><?php echo htmlspecialchars($user->statut_utilisateur); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div class="flex items-center">
                                    <i class="fas fa-envelope text-gray-400 mr-2"></i>
                                    <?php echo htmlspecialchars($user->login_utilisateur); ?>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-3">
                                    <a href="?page=gestion_utilisateurs&action=edit&id_utilisateur=<?php echo $user->id_utilisateur; ?>"
                                       class="text-blue-500 hover:text-blue-700 transition-colors btn-icon"
                                       title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </form>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="bg-white rounded-lg shadow-sm p-4 mt-6">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-500">
                        Affichage de <?= $offset + 1 ?> à <?= min($offset + $limit, $total_items) ?> sur
                        <?= $total_items ?> entrées
                    </div>
                    <div class="flex flex-wrap justify-center gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=gestion_utilisateurs&p=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                               class="btn-hover px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-chevron-left mr-1"></i>Précédent
                            </a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);

                        if ($start > 1) {
                            echo '<a href="?page=gestion_utilisateurs&p=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . '" class="btn-hover px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                            if ($start > 2) {
                                echo '<span class="px-3 py-2 text-gray-500">...</span>';
                            }
                        }

                        for ($i = $start; $i <= $end; $i++):
                            $searchParam = !empty($search) ? '&search=' . urlencode($search) : '';
                            ?>
                            <a href="?page=gestion_utilisateurs&p=<?= $i ?><?= $searchParam ?>"
                               class="btn-hover px-3 py-2 <?= $i === $page ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-sm font-medium">
                                <?= $i ?>
                            </a>
                        <?php endfor;

                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1) {
                                echo '<span class="px-3 py-2 text-gray-500">...</span>';
                            }
                            $searchParam = !empty($search) ? '&search=' . urlencode($search) : '';
                            echo '<a href="?page=gestion_utilisateurs&p=' . $total_pages . $searchParam . '" class="btn-hover px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=gestion_utilisateurs&p=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                               class="btn-hover px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Suivant<i class="fas fa-chevron-right ml-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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

<script>
    // Variables pour le modal utilisateur
    const userModal = document.getElementById('userModal');
    const userForm = document.getElementById('userForm');
    const userModalTitle = document.getElementById('userModalTitle');
    const userModalSubmitButton = document.getElementById('userModalSubmitButton');

    const searchInput = document.getElementById('searchInput');

    const selectAllCheckbox = document.getElementById('selectAllCheckbox');

    const disableButton = document.getElementById('desactiverButton');
    const enableButton = document.getElementById('activerButton');
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

    const submitEnableHidden = document.getElementById('submitEnableHidden');
    const submitModifierHidden = document.getElementById('btn_modifier_utilisateur_hidden');

    const formListeUser = document.getElementById('formListeUtilisateurs');

    function openUserModal() {
        userModal.classList.remove('hidden');
    }

    // Fermer la modal utilisateur
    function closeUserModal() {
        window.location.href = '?page=gestion_utilisateurs';
    }

    // Fermer la modal si on clique en dehors
    userModal.addEventListener('click', function(e) {
        if (e.target === userModal) {
            closeUserModal();
        }
    });

    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const tableBody = document.getElementById('usersTableBody');
        let hasVisibleResults = false;

        // Récupérer tous les utilisateurs depuis PHP
        const allUsers = <?php echo json_encode(array_map(function($user) {
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


    // Select all checkboxes
    selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updatedisableButtonState();
        updateenableButtonState();
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

    // Event listener for checkbox changes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('user-checkbox')) {
            updatedisableButtonState();
            updateenableButtonState();
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
        const allUsers = <?php echo json_encode(array_map(function($user) {
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
        const allUsers = <?php echo json_encode(array_map(function($user) {
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
    document.addEventListener('DOMContentLoaded', function() {
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
    disableButton.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
        if (checkedBoxes.length > 0) {
            disableModal.classList.remove('hidden');
        }
    });

    enableButton.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
        if (checkedBoxes.length > 0) {
            enableModal.classList.remove('hidden');
        }
    });

    // Confirmation de désactivation
    confirmDelete.addEventListener('click', function() {
        // Réinitialiser d'abord les deux inputs
        document.getElementById('submitDisableHidden').value = '0';
        document.getElementById('submitEnableHidden').value = '0';
        // Puis définir la valeur pour la désactivation
        document.getElementById('submitDisableHidden').value = '2';
        formListeUser.submit();
    });

    // Confirmation de l'activation
    confirmEnable.addEventListener('click', function() {
        // Réinitialiser d'abord les deux inputs
        document.getElementById('submitDisableHidden').value = '0';
        document.getElementById('submitEnableHidden').value = '0';
        // Puis définir la valeur pour l'activation
        document.getElementById('submitEnableHidden').value = '3';
        formListeUser.submit();
    });

    // Annulation de la désactivation
    cancelDelete.addEventListener('click', function() {
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
    cancelEnable.addEventListener('click', function() {
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
    disableModal.addEventListener('click', function(e) {
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
    enableModal.addEventListener('click', function(e) {
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
    confirmModify.addEventListener('click', function() {
        // Ajouter un champ caché pour indiquer que c'est une modification
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'btn_modifier_utilisateur';
        hiddenInput.value = '1';
        userForm.appendChild(hiddenInput);

        // Soumettre le formulaire
        userForm.submit();
    });

    cancelModify.addEventListener('click', function() {
        modifyModal.classList.add('hidden');
    });

    // Fermer la modale si on clique en dehors
    modifyModal.addEventListener('click', function(e) {
        if (e.target === modifyModal) {
            modifyModal.classList.add('hidden');
        }
    });

    // Mettre à jour automatiquement le champ login lors de la sélection d'une personne
    document.getElementById('nom_utilisateur')?.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const loginInput = document.getElementById('login_utilisateur');
        if (selectedOption && selectedOption.dataset.login) {
            loginInput.value = selectedOption.dataset.login;
        }
    });

    // Gestion du modal d'ajout en masse
    const masseModal = document.getElementById('userMasseModal');

    function openMasseModal() {
        masseModal.classList.remove('hidden');
    }

    function closeMasseModal() {
        window.location.href = '?page=gestion_utilisateurs';
    }

    // Fermer la modal si on clique en dehors
    masseModal.addEventListener('click', function(e) {
        if (e.target === masseModal) {
            closeMasseModal();
        }
    });

    // Gérer l'affichage du modal en fonction de l'action
    document.addEventListener('DOMContentLoaded', function() {
        const action = new URLSearchParams(window.location.search).get('action');
        if (action === 'addMasse') {
            openMasseModal();
        }
    });

    // Gestion de la sélection multiple
    document.addEventListener('DOMContentLoaded', function() {
        const selectMultiple = document.querySelector('select[name="selected_persons[]"]');
        if (selectMultiple) {
            // Permettre la sélection multiple avec Shift
            selectMultiple.addEventListener('keydown', function(e) {
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
            selectMultiple.addEventListener('mousedown', function(e) {
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
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Content Loaded');

        const form = document.getElementById('userMasse');
        console.log('Form found:', form);

        if (form) {
            form.addEventListener('submit', function(e) {
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

                    // Masquer le loader quand la modale d'ajout en masse se ferme
                    const masseModal = document.getElementById('userMasseModal');
                    console.log('Masse modal:', masseModal);

                    if (masseModal) {
                        const observer = new MutationObserver(function(mutations) {
                            mutations.forEach(function(mutation) {
                                if (mutation.target.classList.contains('hidden')) {
                                    console.log('Modal hidden, removing loader');
                                    const loader = document.getElementById('loader');
                                    if (loader) {
                                        loader.remove();
                                    }
                                }
                            });
                        });

                        observer.observe(masseModal, {
                            attributes: true,
                            attributeFilter: ['class']
                        });
                    }

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
</script>

</body>

</html>