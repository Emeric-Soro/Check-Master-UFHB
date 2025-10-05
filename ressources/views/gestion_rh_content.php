<?php
// Déterminer l'onglet actif (par défaut 'pers_admin')
$activeTab = $_GET['tab'] ?? 'pers_admin';
if (!in_array($activeTab, ['pers_admin', 'enseignant'])) { // Valider la valeur de l'onglet
    $activeTab = 'pers_admin';
}



// Récupération des messages depuis le contrôleur
$messageErreur = $GLOBALS['messageErreur'] ?? '';
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';

// Récupération des données depuis le contrôleur
$personnel_admin = $GLOBALS['listePersAdmin'] ?? [];
$enseignants = $GLOBALS['listeEnseignants'] ?? [];
$listeGrades = $GLOBALS['listeGrades'] ?? [];
$listeFonctions = $GLOBALS['listeFonctions'] ?? [];
$listeSpecialites = $GLOBALS['listeSpecialites'] ?? [];

// Récupération des données pour édition
$pers_admin_a_modifier = $GLOBALS['pers_admin_a_modifier'] ?? null;
$enseignant_a_modifier = $GLOBALS['enseignant_a_modifier'] ?? null;

// Gestion des actions CRUD
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

// Structure pour le formulaire d'édition
$admin_edit = $pers_admin_a_modifier ?? null;

// Structure pour le formulaire d'édition enseignant
$enseignant_edit = $enseignant_a_modifier ?? null;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des ressources humaines</title>
    <style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 500px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
    </style>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <main class="flex-grow container mx-auto px-4 py-5">

            <!-- Système d'onglets -->
            <div class="mb-8">
                <div class="border-b border-gray-300">
                    <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                        <a href="?page=gestion_rh&tab=pers_admin"
                            class="tab-button whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm hover:text-gray-700
                                  <?= ($activeTab === 'pers_admin') ? 'border-green-500 text-green-600 bg-green-50' : 'border-transparent hover:border-gray-300' ?>">
                            <i class="fas fa-users-cog mr-2"></i> Personnel administratif
                        </a>
                        <a href="?page=gestion_rh&tab=enseignant"
                            class="tab-button whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm hover:text-gray-700
                                  <?= ($activeTab === 'enseignant') ? 'border-green-500 text-green-600 bg-green-50' : 'border-transparent hover:border-gray-300' ?>">
                            <i class="fas fa-user-tag mr-2"></i> Enseignants
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Contenu des onglets -->
            <div>
                <!-- Onglet Personnel administratif -->
                <?php if ($activeTab === 'pers_admin'): ?>
                <div id="tab_pers_admin" class="flex flex-col">
                    <?php if (!empty($messageSuccess)): ?>
                    <div id="success-message"
                        class="bg-green-50 border-l-4 border-green-400 text-green-700 p-4 rounded-md shadow-sm mb-6"
                        role="alert">
                        <p><?= htmlspecialchars($messageSuccess) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($messageErreur)): ?>
                    <div id="error-message"
                        class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-md shadow-sm mb-6"
                        role="alert">
                        <p><?= htmlspecialchars($messageErreur) ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-semibold text-gray-700">Gestion du personnel administratif</h3>
                        <a href="?page=gestion_rh&tab=pers_admin&action=add" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg shadow-sm
                                  transition duration-150 ease-in-out flex items-center">
                            <i class="fas fa-plus mr-2"></i> Ajouter un personnel
                        </a>
                    </div>
                    <!-- Action Bar for Table -->
                    <div
                        class="px-6 py-4 flex flex-col sm:flex-row justify-between items-center border-b border-gray-200">
                        <div class="relative w-full sm:w-1/2 lg:w-1/3 mb-4 sm:mb-0">
                            <input type="text" id="searchInput" placeholder="Rechercher un personnel..."
                                class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-center sm:justify-end">
                            <button type="button" onclick="printTable('pers_admin')"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                                <i class="fas fa-print mr-2"></i>Imprimer
                            </button>
                            <button type="button" onclick="exportToExcel('pers_admin')"
                                class="bg-orange-500 hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-opacity-50">
                                <i class="fas fa-file-export mr-2"></i>Exporter
                            </button>
                            <button type="button" onclick="showDeleteModal('pers_admin', 'multiple')"
                                id="deleteButtonPersAdmin"
                                class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled>
                                <i class="fas fa-trash-alt mr-2"></i>Supprimer
                            </button>
                        </div>
                    </div>

                    <!-- Titre de la liste -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h4 class="text-lg font-semibold text-gray-700 flex items-center">
                            <i class="fas fa-list-ul mr-2 text-green-500"></i>
                            Liste du personnel administratif
                        </h4>
                    </div>

                    <!-- Table de listing du personnel administratif -->
                    <form class="bg-white shadow-md rounded-lg overflow-hidden mb-6" method="POST"
                        action="?page=gestion_rh&tab=pers_admin">
                        <input type="hidden" name="submit_delete_multiple" id="submitDeletePersHidden" value="0">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-center">
                                            <input type="checkbox" id="selectAllCheckbox"
                                                class="form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 cursor-pointer">
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nom</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Prénom</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Email</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Téléphone</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Poste</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date d'embauche</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($personnel_admin)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                            Aucun personnel administratif trouvé
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($personnel_admin as $admin): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 text-center">
                                            <input type="checkbox" name="selected_ids[]"
                                                value="<?= htmlspecialchars($admin->id_pers_admin) ?>"
                                                class="form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 cursor-pointer">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($admin->nom_pers_admin) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($admin->prenom_pers_admin) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($admin->email_pers_admin) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($admin->tel_pers_admin) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($admin->poste) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($admin->date_embauche) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="#"
                                                    onclick="showModifyModal('pers_admin', <?= $admin->id_pers_admin ?>); return false;"
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>

                    <!-- Formulaire pour ajouter/modifier un personnel administratif -->
                    <?php if ($action === 'add' || $action === 'edit'): ?>
                    <div class="fixed inset-0 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50"
                        id="modal-admin">
                        <div class="relative mx-auto p-6 w-full max-w-2xl bg-white rounded-lg shadow-xl">
                            <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
                                <h3 class="text-xl font-semibold text-green-600">
                                    <?= ($action === 'edit' && isset($pers_admin_a_modifier)) ? 'Modifier un membre du personnel' : 'Ajouter un membre du personnel' ?>
                                </h3>
                                <a href="?page=gestion_rh&tab=pers_admin"
                                    class="text-gray-400 hover:text-gray-500 transition-colors duration-200">
                                    <i class="fas fa-times text-xl"></i>
                                </a>
                            </div>

                            <form action="?page=gestion_rh&tab=pers_admin" method="POST" class="space-y-6">
                                <?php if ($action === 'edit' && isset($pers_admin_a_modifier)): ?>
                                <input type="hidden" name="id_pers_admin"
                                    value="<?= htmlspecialchars($pers_admin_a_modifier->id_pers_admin) ?>">
                                <?php endif; ?>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="nom"
                                            class="block text-sm font-medium text-gray-700 mb-2">Nom</label>
                                        <input type="text" name="nom" id="nom" style="outline: none;"
                                            value="<?= ($action === 'edit' && isset($pers_admin_a_modifier)) ? htmlspecialchars($pers_admin_a_modifier->nom_pers_admin) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>

                                    <div>
                                        <label for="prenom"
                                            class="block text-sm font-medium text-gray-700 mb-2">Prénom</label>
                                        <input type="text" name="prenom" id="prenom" style="outline: none;"
                                            value="<?= ($action === 'edit' && isset($pers_admin_a_modifier)) ? htmlspecialchars($pers_admin_a_modifier->prenom_pers_admin) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="email"
                                            class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                        <input type="email" name="email" id="email" style="outline: none;"
                                            value="<?= ($action === 'edit' && isset($pers_admin_a_modifier)) ? htmlspecialchars($pers_admin_a_modifier->email_pers_admin) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                    <div>
                                        <label for="telephone"
                                            class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                                        <input type="tel" name="telephone" id="telephone" style="outline: none;"
                                            value="<?= ($action === 'edit' && isset($pers_admin_a_modifier)) ? htmlspecialchars($pers_admin_a_modifier->tel_pers_admin) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="poste"
                                            class="block text-sm font-medium text-gray-700 mb-2">Poste</label>
                                        <input type="text" name="poste" id="poste" style="outline: none;"
                                            value="<?= ($action === 'edit' && isset($pers_admin_a_modifier)) ? htmlspecialchars($pers_admin_a_modifier->poste) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                    <div>
                                        <label for="date_embauche"
                                            class="block text-sm font-medium text-gray-700 mb-2">Date
                                            d'embauche</label>
                                        <input type="date" name="date_embauche" id="date_embauche"
                                            style="outline: none;"
                                            value="<?= ($action === 'edit' && isset($pers_admin_a_modifier)) ? htmlspecialchars($pers_admin_a_modifier->date_embauche) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                </div>


                                <div class="flex justify-between space-x-4 pt-6 border-t border-gray-200">
                                    <a href="?page=gestion_rh&tab=pers_admin"
                                        class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors duration-200">
                                        Annuler
                                    </a>
                                    <button type="submit"
                                        name="<?= ($action === 'edit') ? 'btn_modifier_pers_admin' : 'btn_add_pers_admin' ?>"
                                        class="px-6 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors duration-200">
                                        <?= ($action === 'edit' && isset($pers_admin_a_modifier)) ? 'Modifier' : 'Enregistrer' ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Onglet Enseignants -->
                <?php if ($activeTab === 'enseignant'): ?>
                <div id="tab_enseignant" class="flex flex-col">
                    <?php if (!empty($messageSuccess)): ?>
                    <div id="success-message"
                        class="bg-green-50 border-l-4 border-green-400 text-green-700 p-4 rounded-md shadow-sm mb-6"
                        role="alert">
                        <p><?= htmlspecialchars($messageSuccess) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($messageErreur)): ?>
                    <div id="error-message"
                        class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-md shadow-sm mb-6"
                        role="alert">
                        <p><?= htmlspecialchars($messageErreur) ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-semibold text-gray-700">Gestion des enseignants</h3>
                        <a href="?page=gestion_rh&tab=enseignant&action=add" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg shadow-sm
                                  transition duration-150 ease-in-out flex items-center">
                            <i class="fas fa-plus mr-2"></i> Ajouter un enseignant
                        </a>
                    </div>

                    <!-- Action Bar for Table -->
                    <div
                        class="px-6 py-4 flex flex-col sm:flex-row justify-between items-center border-b border-gray-200">
                        <div class="relative w-full sm:w-1/2 lg:w-1/3 mb-4 sm:mb-0">
                            <input type="text" id="searchInputEnseignant" placeholder="Rechercher un enseignant..."
                                class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-center sm:justify-end">
                            <button type="button" onclick="printTable('enseignant')"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                                <i class="fas fa-print mr-2"></i>Imprimer
                            </button>
                            <button type="button" onclick="exportToExcel('enseignant')"
                                class="bg-orange-500 hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-opacity-50">
                                <i class="fas fa-file-export mr-2"></i>Exporter
                            </button>
                            <button type="button" onclick="showDeleteModal('enseignant')" id="deleteButtonEnseignant"
                                class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled>
                                <i class="fas fa-trash-alt mr-2"></i>Supprimer
                            </button>
                        </div>
                    </div>

                    <!-- Titre de la liste -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h4 class="text-lg font-semibold text-gray-700 flex items-center">
                            <i class="fas fa-chalkboard-teacher mr-2 text-green-500"></i>
                            Liste des enseignants
                        </h4>
                    </div>

                    <!-- Table de listing des enseignants -->
                    <form class="bg-white shadow-md rounded-lg overflow-hidden mb-6" method="post"
                        action="?page=gestion_rh&tab=enseignant">
                        <input type="hidden" name="submit_delete_multiple" id="submitDeleteHidden" value="0">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-center">
                                            <input type="checkbox" id="selectAllCheckboxEnseignant"
                                                class="form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 cursor-pointer">
                                        </th>

                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nom & Prénom</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Email</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Spécialité</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Fonction</th>

                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Grade</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date grade</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($enseignants)): ?>
                                    <tr>
                                        <td colspan="11" class="px-6 py-4 text-center text-gray-500">
                                            Aucun enseignant trouvé
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($enseignants as $enseignant): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 text-center">
                                            <input type="checkbox" name="selected_ids[]"
                                                value="<?= htmlspecialchars($enseignant->id_enseignant) ?>"
                                                class="user-checkbox form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 cursor-pointer">
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($enseignant->nom_enseignant) ?>
                                            <?= htmlspecialchars($enseignant->prenom_enseignant) ?>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($enseignant->mail_enseignant) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($enseignant->lib_specialite) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($enseignant->lib_fonction) ?>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($enseignant->lib_grade) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($enseignant->date_grade) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="#"
                                                    onclick="showModifyModal('enseignant', <?= $enseignant->id_enseignant ?>); return false;"
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>

                    <!-- Formulaire pour ajouter/modifier un enseignant -->
                    <?php if ($action === 'add' || $action === 'edit'): ?>
                    <div class="fixed inset-0  bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50"
                        id="modal-enseignant">
                        <div class="relative mx-auto p-6 w-full max-w-2xl bg-white rounded-lg shadow-xl">
                            <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
                                <h3 class="text-xl font-semibold text-green-600">
                                    <?= ($action === 'edit' && isset($enseignant_a_modifier)) ? 'Modifier un enseignant' : 'Ajouter un enseignant' ?>
                                </h3>
                                <a href="?page=gestion_rh&tab=enseignant"
                                    class="text-gray-400 hover:text-gray-500 transition-colors duration-200">
                                    <i class="fas fa-times text-xl"></i>
                                </a>
                            </div>

                            <form action="?page=gestion_rh&tab=enseignant" method="POST" class="space-y-6">
                                <?php if ($action === 'edit' && isset($enseignant_a_modifier)): ?>
                                <input type="hidden" name="id_enseignant"
                                    value="<?= htmlspecialchars($enseignant_a_modifier->id_enseignant) ?>">
                                <?php endif; ?>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="nom_enseignant"
                                            class="block text-sm font-medium text-gray-700 mb-2">Nom</label>
                                        <input type="text" name="nom" id="nom_enseignant" style="outline: none;"
                                            value="<?= ($action === 'edit' && isset($enseignant_a_modifier)) ? htmlspecialchars($enseignant_a_modifier->nom_enseignant) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>

                                    <div>
                                        <label for="prenom_enseignant"
                                            class="block text-sm font-medium text-gray-700 mb-2">Prénom</label>
                                        <input type="text" name="prenom" id="prenom_enseignant" style="outline: none;"
                                            value="<?= ($action === 'edit' && isset($enseignant_a_modifier)) ? htmlspecialchars($enseignant_a_modifier->prenom_enseignant) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="email_enseignant"
                                            class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                        <input type="email" name="email" id="email_enseignant" style="outline: none;"
                                            value="<?= ($action === 'edit' && isset($enseignant_a_modifier)) ? htmlspecialchars($enseignant_a_modifier->mail_enseignant) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>

                                    <div>
                                        <label for="specialite"
                                            class="block text-sm font-medium text-gray-700 mb-2">Spécialité</label>
                                        <select name="id_specialite" id="specialite" style="outline: none;" required
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200">
                                            <?php foreach ($listeSpecialites as $specialite): ?>
                                            <option value="<?= htmlspecialchars($specialite->id_specialite) ?>"
                                                <?= ($action === 'edit' && isset($enseignant_a_modifier) && $enseignant_a_modifier->id_specialite == $specialite->id_specialite) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($specialite->lib_specialite) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="fonction"
                                            class="block text-sm font-medium text-gray-700 mb-2">Fonction</label>
                                        <select name="id_fonction" id="fonction" style="outline: none;" required
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200">
                                            <?php foreach ($listeFonctions as $fonction): ?>
                                            <option value="<?= htmlspecialchars($fonction->id_fonction) ?>"
                                                <?= ($action === 'edit' && isset($enseignant_a_modifier) && $enseignant_a_modifier->id_fonction == $fonction->id_fonction) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($fonction->lib_fonction) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="date_fonction"
                                            class="block text-sm font-medium text-gray-700 mb-2">Date
                                            d'occupation de la
                                            fonction</label>
                                        <input type="date" name="date_fonction" id="date_fonction"
                                            style="outline: none;"
                                            value="<?= ($action === 'edit' && isset($enseignant_a_modifier)) ? htmlspecialchars($enseignant_a_modifier->date_occupation) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="grade"
                                            class="block text-sm font-medium text-gray-700 mb-2">Grade</label>
                                        <select name="id_grade" id="grade" style="outline: none;" required
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200">
                                            <?php foreach ($listeGrades as $grade): ?>
                                            <option value="<?= htmlspecialchars($grade->id_grade) ?>"
                                                <?= ($action === 'edit' && isset($enseignant_a_modifier) && $enseignant_a_modifier->id_grade == $grade->id_grade) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($grade->lib_grade) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>

                                    </div>

                                    <div>
                                        <label for="date_grade"
                                            class="block text-sm font-medium text-gray-700 mb-2">Date
                                            d'obtention du
                                            grade</label>
                                        <input type="date" name="date_grade" id="date_grade" style="outline: none;"
                                            value="<?= ($action === 'edit' && isset($enseignant_a_modifier)) ? htmlspecialchars($enseignant_a_modifier->date_grade) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label for="type_enseignant" class="block text-sm font-medium text-gray-700">
                                            Type d'enseignant
                                        </label>
                                        <select name="type_enseignant" id="type_enseignant" required
                                            class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                                            <option value="Simple"
                                                <?php echo ($enseignant_a_modifier && $enseignant_a_modifier->type_enseignant) ? 'selected' : ''; ?>>
                                                Simple</option>
                                            <option value="Administratif"
                                                <?php echo ($enseignant_a_modifier && !$enseignant_a_modifier->type_enseignant) ? 'selected' : ''; ?>>
                                                Administratif</option>
                                        </select>
                                    </div>

                                </div>

                                <div class="flex justify-between space-x-4 pt-6 border-t border-gray-200">
                                    <a href="?page=gestion_rh&tab=enseignant"
                                        class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors duration-200">
                                        Annuler
                                    </a>
                                    <button type="submit"
                                        name="<?= ($action === 'edit') ? 'btn_modifier_enseignant' : 'btn_add_enseignant' ?>"
                                        class="px-6 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors duration-200">
                                        <?= ($action === 'edit' && isset($enseignant_a_modifier)) ? 'Modifier' : 'Ajouter' ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modale de confirmation de suppression -->
    <div id="deleteModal"
        class="fixed inset-0 flex items-center justify-center z-50 hidden animate__animated animate__fadeIn">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 animate__animated animate__zoomIn">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmation de suppression</h3>
                <p class="text-sm text-gray-500 mb-6">
                    <i class="fas fa-info-circle mr-2"></i>
                    Êtes-vous sûr de vouloir supprimer les éléments sélectionnés ?
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

    <!-- Formulaire caché pour la suppression -->
    <form id="delete-form" method="POST" style="display: none;">
        <input type="hidden" name="selected_ids[]" id="delete_id">
    </form>

    <!-- Modale de confirmation de modification -->
    <div id="modifyModal"
        class="fixed inset-0 flex items-center justify-center z-50 hidden animate__animated animate__fadeIn">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 animate__animated animate__zoomIn">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                    <i class="fas fa-edit text-blue-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmation de modification</h3>
                <p class="text-sm text-gray-500 mb-6">
                    <i class="fas fa-info-circle mr-2"></i>
                    Êtes-vous sûr de vouloir modifier cet élément ?
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

</body>
<script>
// Variables globales pour les formulaires et les boutons
const formListePersAdmin = document.querySelector('#tab_pers_admin form');
const formListeEnseignant = document.querySelector('#tab_enseignant form');

// Variables pour le personnel administratif
const selectAllCheckbox = document.getElementById('selectAllCheckbox');
const deleteButtonPersAdmin = document.getElementById('deleteButtonPersAdmin');
const submitDeletePersHidden = document.getElementById('submitDeletePersHidden');

// Variables pour les enseignants
const selectAllCheckboxEnseignant = document.getElementById('selectAllCheckboxEnseignant');
const deleteButtonEnseignant = document.getElementById('deleteButtonEnseignant');
const submitDeleteHidden = document.getElementById('submitDeleteHidden');

// Variables pour les modales
const deleteModal = document.getElementById('deleteModal');
const confirmDelete = document.getElementById('confirmDelete');
const cancelDelete = document.getElementById('cancelDelete');
const modifyModal = document.getElementById('modifyModal');
const confirmModify = document.getElementById('confirmModify');
const cancelModify = document.getElementById('cancelModify');

// Initialisation des checkboxes
function initializeCheckboxes(selectAllCheckbox, deleteButton, form) {
    if (selectAllCheckbox && deleteButton) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = form.querySelectorAll('input[name="selected_ids[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
            updateDeleteButtonState(deleteButton, form);
        });

        form.addEventListener('change', function(e) {
            if (e.target.name === 'selected_ids[]') {
                updateDeleteButtonState(deleteButton, form);
                const allCheckboxes = form.querySelectorAll('input[name="selected_ids[]"]:checked');
                const checkedBoxes = form.querySelectorAll('input[name="selected_ids[]"]:checked');
                selectAllCheckbox.checked = checkedBoxes.length === allCheckboxes.length && allCheckboxes
                    .length > 0;
            }
        });
    }
}

// Mise à jour de l'état du bouton de suppression
function updateDeleteButtonState(deleteBtn, form) {
    if (deleteBtn && form) {
        const checkedBoxes = form.querySelectorAll('input[name="selected_ids[]"]:checked');
        deleteBtn.disabled = checkedBoxes.length === 0;
    }
}

// Initialisation des deux sections
initializeCheckboxes(selectAllCheckbox, deleteButtonPersAdmin, formListePersAdmin);
initializeCheckboxes(selectAllCheckboxEnseignant, deleteButtonEnseignant, formListeEnseignant);

// Gestion de la modale de suppression
function showDeleteModal(type) {
    if (!deleteModal) return;

    const form = type === 'pers_admin' ? formListePersAdmin : formListeEnseignant;
    const checkboxes = form.querySelectorAll('input[name="selected_ids[]"]:checked');

    if (checkboxes.length === 0) {
        alert('Veuillez sélectionner au moins un élément à supprimer');
        return;
    }

    deleteModal.classList.remove('hidden');
}

// Gestion de la suppression
if (confirmDelete) {
    confirmDelete.addEventListener('click', function() {
        const activeTab = document.querySelector('.tab-button.border-green-500').getAttribute('href').split(
            'tab=')[1];
        const form = activeTab === 'pers_admin' ? formListePersAdmin : formListeEnseignant;

        if (form) {
            const submitHidden = activeTab === 'pers_admin' ? submitDeletePersHidden : submitDeleteHidden;
            submitHidden.value = '1';
            form.submit();
        }
        deleteModal.classList.add('hidden');
    });
}

if (cancelDelete) {
    cancelDelete.addEventListener('click', function() {
        deleteModal.classList.add('hidden');
    });
}

// Gestion de la modale de modification
function showModifyModal(type, id) {
    if (!modifyModal) return;

    // Rediriger vers la page d'édition
    window.location.href = `?page=gestion_rh&tab=${type}&action=edit&id_${type}=${id}`;
}

// Fermer les modales si on clique en dehors
window.addEventListener('click', function(e) {
    if (e.target === deleteModal) {
        deleteModal.classList.add('hidden');
    }
    if (e.target === modifyModal) {
        modifyModal.classList.add('hidden');
    }
});

// Fonction pour exporter vers Excel
function exportToExcel(type) {
    const table = document.querySelector(`#tab_${type} table`);
    const rows = Array.from(table.querySelectorAll('tr'));

    // Créer le contenu CSV
    let csvContent = "data:text/csv;charset=utf-8,";

    // Ajouter les en-têtes
    const headers = Array.from(rows[0].querySelectorAll('th'))
        .slice(1, -1) // Exclure la première colonne (checkbox) et la dernière (actions)
        .map(th => th.textContent.trim());
    csvContent += headers.join(',') + '\n';

    // Ajouter les données
    rows.slice(1).forEach(row => {
        const cells = Array.from(row.querySelectorAll('td'))
            .slice(1, -1) // Exclure la première colonne (checkbox) et la dernière (actions)
            .map(td => `"${td.textContent.trim()}"`);
        csvContent += cells.join(',') + '\n';
    });

    // Créer le lien de téléchargement
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `${type === 'pers_admin' ? 'personnel_administratif' : 'enseignants'}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Fonction pour imprimer
function printTable(type) {
    const printWindow = window.open('', '_blank');
    const table = document.querySelector(`#tab_${type} table`);

    // Création du contenu HTML pour l'impression
    const content = `
        <html>
        <head>
            <title>Impression - ${type === 'pers_admin' ? 'Personnel Administratif' : 'Enseignants'}</title>
            <style>
                body { font-family: Arial, sans-serif; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f5f5f5; }
                h1 { text-align: center; color: #333; }
                @media print {
                    body { margin: 0; padding: 20px; }
                    table { page-break-inside: auto; }
                    tr { page-break-inside: avoid; page-break-after: auto; }
                }
            </style>
        </head>
        <body>
            <h1>${type === 'pers_admin' ? 'Liste du Personnel Administratif' : 'Liste des Enseignants'}</h1>
            <table>
                <thead>
                    <tr>
                        ${Array.from(table.querySelectorAll('th')).slice(1, -1).map(th => `<th>${th.textContent}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${Array.from(table.querySelectorAll('tbody tr')).map(row => `
                        <tr>
                            ${Array.from(row.querySelectorAll('td')).slice(1, -1).map(td => `<td>${td.textContent}</td>`).join('')}
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </body>
        </html>
    `;

    printWindow.document.write(content);
    printWindow.document.close();
    printWindow.focus();

    // Attendre que le contenu soit chargé avant d'imprimer
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}

// Gestion des messages temporisés
document.addEventListener('DOMContentLoaded', function() {
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');

    function hideMessage(element) {
        if (element) {
            element.style.transition = 'opacity 0.3s ease-out';
            element.style.opacity = '0';
            setTimeout(() => {
                element.style.display = 'none';
            }, 500);
        }
    }

    // Masquer les messages après 5 secondes
    if (successMessage) {
        setTimeout(() => hideMessage(successMessage), 3000);
    }
    if (errorMessage) {
        setTimeout(() => hideMessage(errorMessage), 3000);
    }
});

// Fonction de recherche
function searchTable(inputId, type) {
    const input = document.getElementById(inputId);
    const table = document.querySelector(`#tab_${type} table`);
    const filter = input.value.toUpperCase();
    const rows = table.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let found = false;

        cells.forEach(cell => {
            const text = cell.textContent || cell.innerText;
            if (text.toUpperCase().indexOf(filter) > -1) {
                found = true;
            }
        });

        row.style.display = found ? "" : "none";
    });

    // Mettre à jour l'état des checkboxes après la recherche
    const selectAllCheckbox = document.getElementById(type === 'pers_admin' ? 'selectAllCheckbox' :
        'selectAllCheckboxEnseignant');
    const deleteButton = document.getElementById(type === 'pers_admin' ? 'deleteButtonPersAdmin' :
        'deleteButtonEnseignant');
    const form = type === 'pers_admin' ? formListePersAdmin : formListeEnseignant;

    if (selectAllCheckbox && deleteButton && form) {
        const visibleCheckboxes = form.querySelectorAll(
            'tbody tr:not([style*="display: none"]) input[name="selected_ids[]"]');
        const checkedVisibleBoxes = form.querySelectorAll(
            'tbody tr:not([style*="display: none"]) input[name="selected_ids[]"]:checked');

        selectAllCheckbox.checked = checkedVisibleBoxes.length === visibleCheckboxes.length && visibleCheckboxes
            .length > 0;
        deleteButton.disabled = checkedVisibleBoxes.length === 0;
    }
}

// Écouteurs d'événements pour la recherche
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchInputEnseignant = document.getElementById('searchInputEnseignant');

    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            searchTable('searchInput', 'pers_admin');
        });
    }

    if (searchInputEnseignant) {
        searchInputEnseignant.addEventListener('keyup', function() {
            searchTable('searchInputEnseignant', 'enseignant');
        });
    }
});
</script>
</body>

</html>