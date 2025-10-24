<?php
require_once __DIR__ . '/../../../app/utils/permissions.php';

// Pour les groupes
$groupe_a_modifier = $GLOBALS['groupe_a_modifier'] ?? null;
// Pour les types/fonctions utilisateurs
$type_a_modifier = $GLOBALS['type_a_modifier'] ?? null; 

$listeGroupe = $GLOBALS['listeGroupes'] ?? [];
$listeType = $GLOBALS['listeTypes'] ?? [];

// Pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';

// Filter the lists based on search
if (!empty($search)) {
    $listeGroupe = array_filter($listeGroupe, function($groupe) use ($search) {
        return stripos($groupe->lib_GU, $search) !== false;
    });
    $listeType = array_filter($listeType, function($type) use ($search) {
        return stripos($type->lib_type_utilisateur, $search) !== false;
    });
}

// Total pages calculation
$total_items_groupe = count($listeGroupe);
$total_pages_groupe = ceil($total_items_groupe / $limit);

$total_items_type = count($listeType);
$total_pages_type = ceil($total_items_type / $limit);

// Slice the arrays for pagination
$listeGroupe = array_slice($listeGroupe, $offset, $limit);
$listeType = array_slice($listeType, $offset, $limit);

// Déterminer l'onglet actif (par défaut 'groupes')
$activeTab = $_GET['tab'] ?? 'groupes';
if (!in_array($activeTab, ['groupes', 'types'])) {
    $activeTab = 'groupes';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des fonctions utilisateurs</title>
    <style>
    /* Animations et transitions */
    .animate__animated {
        animation-duration: 0.3s;
    }

    .transition-all {
        transition-property: all;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 200ms;
    }

    /* Personnalisation des inputs */
    .form-input:focus {
        border-color: #22c55e;
        box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
        background-color: #f0fdf4;
    }

    /* Style pour le hover des lignes du tableau */
    .table-row:hover {
        background-color: #f0fdf4;
    }

    /* Style pour les checkboxes */
    input[type="checkbox"]:checked {
        background-color: #22c55e;
        border-color: #22c55e;
    }

    /* Style pour la pagination active */
    .pagination-active {
        background-color: #22c55e;
        border-color: #22c55e;
    }

    /* Boutons avec dégradés */
    .btn-gradient-primary {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    }

    .btn-gradient-secondary {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    }

    .btn-gradient-warning {
        background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%);
    }

    .btn-gradient-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    /* Effet de hover sur les boutons */
    .btn-hover {
        transition: all 0.3s ease;
    }

    .btn-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    @media print {
        body * {
            visibility: hidden;
        }

        .container table,
        .container table * {
            visibility: visible;
        }

        .container table {
            position: absolute;
            left: 0;
            top: 0;
        }

        button,
        .actions,
        input[type="checkbox"] {
            display: none !important;
        }
    }

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
    </style>
</head>

<body class="bg-gray-50">
    <!-- Système de notification -->
    <?php if (!empty($GLOBALS['messageSuccess'])): ?>
    <div id="successNotification" class="notification success animate__animated animate__fadeIn">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <p><?= htmlspecialchars($GLOBALS['messageSuccess']) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($GLOBALS['messageErreur'])): ?>
    <div id="errorNotification" class="notification error animate__animated animate__fadeIn">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <p><?= htmlspecialchars($GLOBALS['messageErreur']) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="min-h-screen">
        <main class="container mx-auto px-4 py-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-600">
                    <i class="fas fa-users-cog mr-2 text-green-600"></i>
                    Gestion des Fonctions Utilisateurs
                </h2>
            </div>

            <!-- Système d'onglets -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <a href="?page=parametres_generaux&action=fonction_utilisateur&tab=groupes"
                        class="<?= ($activeTab === 'groupes') ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-users-cog mr-2"></i>Groupes d'Utilisateurs
                    </a>
                    <a href="?page=parametres_generaux&action=fonction_utilisateur&tab=types"
                        class="<?= ($activeTab === 'types') ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-user-tag mr-2"></i>Types d'Utilisateurs
                    </a>
                </nav>
            </div>

            <!-- Contenu de l'onglet Groupes -->
            <?php if ($activeTab === 'groupes'): ?>
            <div class="space-y-6">
                <!-- Formulaire d'ajout/modification -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-600 mb-4 flex items-center">
                        <i
                            class="fas <?= isset($_GET['id_groupe']) ? 'fa-edit text-green-500' : 'fa-plus-circle text-green-500' ?> mr-2"></i>
                        <?= isset($_GET['id_groupe']) ? 'Modifier le groupe' : 'Ajouter un nouveau groupe' ?>
                    </h3>
                    <form method="POST" action="?page=parametres_generaux&action=fonction_utilisateur&tab=groupes"
                        id="groupeForm">
                        <?php if ($groupe_a_modifier): ?>
                        <input type="hidden" name="id_groupe"
                            value="<?= htmlspecialchars($groupe_a_modifier->id_GU) ?>">
                        <?php endif; ?>

                        <div class="mb-4">
                            <label for="lib_groupe" class="block text-sm font-medium text-gray-700 mb-2">Libellé du
                                groupe utilisateur</label>
                            <input type="text" name="lib_groupe" id="lib_groupe" required
                                placeholder="Entrer le libellé du groupe utilisateur"
                                value="<?= $groupe_a_modifier ? htmlspecialchars($groupe_a_modifier->lib_GU) : '' ?>"
                                class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-4 focus:outline-green-300 focus:ring-green-300 focus:border-green-300 focus:ring-opacity-50 transition-all duration-200">
                        </div>

                        <div class="flex justify-between mt-6">
                            <?php if (isset($_GET['id_groupe'])): ?>
                            <button type="button" name="btn_annuler" id="btnAnnuler"
                                onclick="window.location.href='?page=parametres_generaux&action=fonction_utilisateur&tab=groupes'"
                                class="btn-hover px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                <i class="fas fa-times mr-2"></i>Annuler
                            </button>
                            <button type="button" name="btn_modifier_groupe" id="btn_modifier_groupe"
                                class="btn-hover px-4 py-2 btn-gradient-primary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <i class="fas fa-save mr-2"></i>Modifier
                            </button>
                            <input type="hidden" name="btn_modifier_groupe" id="btn_modifier_groupe_hidden" value="0">
                            <?php else: ?>
                            <div></div>
                            <button type="submit" name="submit_add_groupe"
                                class="btn-hover px-4 py-2 btn-gradient-primary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <i class="fas fa-plus mr-2"></i>Ajouter un groupe
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Liste des groupes -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-600 mb-4 flex items-center">
                            <i class="fas fa-list-ul text-green-500 mr-2"></i>
                            Liste des groupes d'utilisateurs
                        </h3>
                        <div class="flex items-center justify-between mb-6">
                            <!-- Barre de recherche -->
                            <div class="flex-1 max-w-md">
                                <form action="" method="GET" class="flex gap-3">
                                    <input type="hidden" name="page" value="parametres_generaux">
                                    <input type="hidden" name="action" value="fonction_utilisateur">
                                    <input type="hidden" name="tab" value="groupes">
                                    <div class="relative flex-1">
                                        <i
                                            class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                        <input type="text" name="search" value="<?= $search ?>"
                                            placeholder="Rechercher..."
                                            class="form-input w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none transition-all duration-200">
                                    </div>
                                    <button type="submit"
                                        class="btn-hover px-4 py-2 btn-gradient-secondary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                        <i class="fas fa-search mr-2"></i>Rechercher
                                    </button>
                                </form>
                            </div>

                            <!-- Boutons d'action -->
                            <div class="flex gap-3">
                                <button id="exportBtn" onclick="exportToExcel()"
                                    class="btn-hover px-4 py-2 btn-gradient-warning text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">
                                    <i class="fas fa-file-export mr-2"></i>Exporter
                                </button>
                                <button id="printBtn" onclick="printTable()"
                                    class="btn-hover px-4 py-2 btn-gradient-secondary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    <i class="fas fa-print mr-2"></i>Imprimer
                                </button>
                                <button type="button" id="deleteSelectedBtn" disabled
                                    class="btn-hover px-4 py-2 btn-gradient-danger text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-trash-alt mr-2"></i>Supprimer
                                </button>
                            </div>
                        </div>

                        <form method="POST" action="?page=parametres_generaux&action=fonction_utilisateur&tab=groupes"
                            id="formListeGroupes">
                            <input type="hidden" name="submit_delete_multiple" id="submitDeleteHidden" value="0">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="w-16 px-6 py-3 text-center">
                                                <input type="checkbox" id="selectAllCheckbox"
                                                    class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                ID
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Libellé
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if (!empty($listeGroupe)): ?>
                                        <?php foreach ($listeGroupe as $groupe): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <input type="checkbox" name="selected_ids[]"
                                                    value="<?= htmlspecialchars($groupe->id_GU) ?>"
                                                    class="row-checkbox rounded border-gray-300 text-green-600 focus:ring-green-500">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($groupe->id_GU) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($groupe->lib_GU) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                                <a href="?page=parametres_generaux&action=fonction_utilisateur&tab=groupes&id_groupe=<?= htmlspecialchars($groupe->id_GU) ?>"
                                                    class="text-green-600 hover:text-green-900">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                                Aucun groupe enregistré
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination pour les groupes -->
                            <?php if ($total_pages_groupe > 1): ?>
                            <div class="flex justify-center mt-4 space-x-2">
                                <?php if ($page > 1): ?>
                                <a href="?page=parametres_generaux&action=fonction_utilisateur&tab=groupes&p=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all duration-200">
                                    <i class="fas fa-chevron-left mr-1"></i>Précédent
                                </a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages_groupe; $i++): ?>
                                <a href="?page=parametres_generaux&action=fonction_utilisateur&tab=groupes&p=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="px-3 py-1 <?= $i === $page ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-md text-sm font-medium transition-all duration-200">
                                    <?= $i ?>
                                </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages_groupe): ?>
                                <a href="?page=parametres_generaux&action=fonction_utilisateur&tab=groupes&p=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all duration-200">
                                    Suivant<i class="fas fa-chevron-right ml-1"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Contenu de l'onglet Types -->
            <?php if ($activeTab === 'types'): ?>
            <div class="space-y-6">
                <!-- Formulaire d'ajout/modification -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-600 mb-4 flex items-center">
                        <i
                            class="fas <?= isset($_GET['id_type']) ? 'fa-edit text-green-500' : 'fa-plus-circle text-green-500' ?> mr-2"></i>
                        <?= isset($_GET['id_type']) ? 'Modifier le type' : 'Ajouter un nouveau type' ?>
                    </h3>
                    <form method="POST" action="?page=parametres_generaux&action=fonction_utilisateur&tab=types"
                        id="typeForm">
                        <?php if ($type_a_modifier): ?>
                        <input type="hidden" name="id_type_utilisateur"
                            value="<?= htmlspecialchars($type_a_modifier->id_type_utilisateur) ?>">
                        <?php endif; ?>

                        <div class="mb-4">
                            <label for="lib_type_utilisateur"
                                class="block text-sm font-medium text-gray-700 mb-2">Libellé du
                                type</label>
                            <input type="text" name="lib_type_utilisateur" id="lib_type_utilisateur" required
                                placeholder="Entrer le libellé du type utilisateur"
                                value="<?= $type_a_modifier ? htmlspecialchars($type_a_modifier->lib_type_utilisateur) : '' ?>"
                                class="form-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-4 focus:outline-green-300 focus:ring-green-300 focus:border-green-300 focus:ring-opacity-50 transition-all duration-200">
                        </div>

                        <div class="flex justify-between mt-6">
                            <?php if (isset($_GET['id_type'])): ?>
                            <button type="button" name="btn_annuler" id="btnAnnulerType"
                                onclick="window.location.href='?page=parametres_generaux&action=fonction_utilisateur&tab=types'"
                                class="btn-hover px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                <i class="fas fa-times mr-2"></i>Annuler
                            </button>
                            <button type="button" name="btn_modifier_type" id="btn_modifier_type"
                                class="btn-hover px-4 py-2 btn-gradient-primary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <i class="fas fa-save mr-2"></i>Modifier
                            </button>
                            <input type="hidden" name="btn_modifier_type" id="btn_modifier_type_hidden" value="0">
                            <?php else: ?>
                            <div></div>
                            <button type="submit" name="submit_add_type"
                                class="btn-hover px-4 py-2 btn-gradient-primary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <i class="fas fa-plus mr-2"></i>Ajouter un type
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Liste des types -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-600 mb-4 flex items-center">
                            <i class="fas fa-list-ul text-green-500 mr-2"></i>
                            Liste des types d'utilisateurs
                        </h3>
                        <div class="flex items-center justify-between mb-6">
                            <!-- Barre de recherche -->
                            <div class="flex-1 max-w-md">
                                <form action="" method="GET" class="flex gap-3">
                                    <input type="hidden" name="page" value="parametres_generaux">
                                    <input type="hidden" name="action" value="fonction_utilisateur">
                                    <input type="hidden" name="tab" value="types">
                                    <div class="relative flex-1">
                                        <i
                                            class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                        <input type="text" name="search" value="<?= $search ?>"
                                            placeholder="Rechercher..."
                                            class="form-input w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none transition-all duration-200">
                                    </div>
                                    <button type="submit"
                                        class="btn-hover px-4 py-2 btn-gradient-secondary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                        <i class="fas fa-search mr-2"></i>Rechercher
                                    </button>
                                </form>
                            </div>

                            <!-- Boutons d'action -->
                            <div class="flex gap-3">
                                <button id="exportBtnTypes" onclick="exportToExcel('types')"
                                    class="btn-hover px-4 py-2 btn-gradient-warning text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">
                                    <i class="fas fa-file-export mr-2"></i>Exporter
                                </button>
                                <button id="printBtnTypes" onclick="printTable('types')"
                                    class="btn-hover px-4 py-2 btn-gradient-secondary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    <i class="fas fa-print mr-2"></i>Imprimer
                                </button>
                                <button type="button" id="deleteSelectedBtnTypes" disabled
                                    class="btn-hover px-4 py-2 btn-gradient-danger text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-trash-alt mr-2"></i>Supprimer
                                </button>
                            </div>
                        </div>

                        <form method="POST" action="?page=parametres_generaux&action=fonction_utilisateur&tab=types"
                            id="formListeTypes">
                            <input type="hidden" name="submit_delete_multiple" id="submitDeleteHiddenTypes" value="0">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="w-16 px-6 py-3 text-center">
                                                <input type="checkbox" id="selectAllCheckboxTypes"
                                                    class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                ID
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Libellé
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if (!empty($listeType)): ?>
                                        <?php foreach ($listeType as $type): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <input type="checkbox" name="selected_ids[]"
                                                    value="<?= htmlspecialchars($type->id_type_utilisateur) ?>"
                                                    class="row-checkbox rounded border-gray-300 text-green-600 focus:ring-green-500">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($type->id_type_utilisateur) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($type->lib_type_utilisateur) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                                <a href="?page=parametres_generaux&action=fonction_utilisateur&tab=types&id_type=<?= htmlspecialchars($type->id_type_utilisateur) ?>"
                                                    class="text-green-600 hover:text-green-900">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                                Aucun type enregistré
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination pour les types -->
                            <?php if ($total_pages_type > 1): ?>
                            <div class="flex justify-center mt-4 space-x-2">
                                <?php if ($page > 1): ?>
                                <a href="?page=parametres_generaux&action=fonction_utilisateur&tab=types&p=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all duration-200">
                                    <i class="fas fa-chevron-left mr-1"></i>Précédent
                                </a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages_type; $i++): ?>
                                <a href="?page=parametres_generaux&action=fonction_utilisateur&tab=types&p=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="px-3 py-1 <?= $i === $page ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-md text-sm font-medium transition-all duration-200">
                                    <?= $i ?>
                                </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages_type): ?>
                                <a href="?page=parametres_generaux&action=fonction_utilisateur&tab=types&p=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all duration-200">
                                    Suivant<i class="fas fa-chevron-right ml-1"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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

    <script>
    // Variables globales pour les formulaires et les boutons
    const groupeForm = document.getElementById('groupeForm');
    const typeForm = document.getElementById('typeForm');
    const formListeGroupes = document.getElementById('formListeGroupes');
    const formListeTypes = document.getElementById('formListeTypes');

    // Variables pour les groupes
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const submitDeleteHidden = document.getElementById('submitDeleteHidden');
    const btnModifierGroupe = document.getElementById('btn_modifier_groupe');
    const submitModifierHidden = document.getElementById('btn_modifier_groupe_hidden');

    // Variables pour les types
    const selectAllCheckboxTypes = document.getElementById('selectAllCheckboxTypes');
    const deleteSelectedBtnTypes = document.getElementById('deleteSelectedBtnTypes');
    const submitDeleteHiddenTypes = document.getElementById('submitDeleteHiddenTypes');
    const btnModifierType = document.getElementById('btn_modifier_type');
    const submitModifierHiddenTypes = document.getElementById('btn_modifier_type_hidden');

    // Variables pour les modales
    const deleteModal = document.getElementById('deleteModal');
    const confirmDelete = document.getElementById('confirmDelete');
    const cancelDelete = document.getElementById('cancelDelete');
    const modifyModal = document.getElementById('modifyModal');
    const confirmModify = document.getElementById('confirmModify');
    const cancelModify = document.getElementById('cancelModify');

    // Initialisation des checkboxes
    function initializeCheckboxes(selectAllCheckbox, deleteSelectedBtn, form) {
        if (selectAllCheckbox && deleteSelectedBtn) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = form.querySelectorAll('input[name="selected_ids[]"]');
                checkboxes.forEach(checkbox => checkbox.checked = this.checked);
                updateDeleteButtonState(deleteSelectedBtn, form);
            });

            form.addEventListener('change', function(e) {
                if (e.target.name === 'selected_ids[]') {
                    updateDeleteButtonState(deleteSelectedBtn, form);
                    const allCheckboxes = form.querySelectorAll('input[name="selected_ids[]"]');
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
    initializeCheckboxes(selectAllCheckbox, deleteSelectedBtn, formListeGroupes);
    initializeCheckboxes(selectAllCheckboxTypes, deleteSelectedBtnTypes, formListeTypes);

    // Gestion de la modale de suppression
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', function(e) {
            e.preventDefault();
            deleteModal.classList.remove('hidden');
        });
    }

    if (deleteSelectedBtnTypes) {
        deleteSelectedBtnTypes.addEventListener('click', function(e) {
            e.preventDefault();
            deleteModal.classList.remove('hidden');
        });
    }

    confirmDelete.addEventListener('click', function() {
        if (formListeGroupes) {
            submitDeleteHidden.value = '1';
            formListeGroupes.submit();
        }
        if (formListeTypes) {
            submitDeleteHiddenTypes.value = '1';
            formListeTypes.submit();
        }
        deleteModal.classList.add('hidden');
    });

    cancelDelete.addEventListener('click', function() {
        deleteModal.classList.add('hidden');
    });

    // Gestion des modales de modification
    if (btnModifierGroupe) {
        btnModifierGroupe.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Opening modify modal for groups'); // Debug log
            modifyModal.classList.remove('hidden');
        });
    }

    if (btnModifierType) {
        btnModifierType.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Opening modify modal for types'); // Debug log
            modifyModal.classList.remove('hidden');
        });
    }

    confirmModify.addEventListener('click', function() {
        if (groupeForm) {
            submitModifierHidden.value = '1';
            groupeForm.submit();
        }
        if (typeForm) {
            submitModifierHiddenTypes.value = '1';
            typeForm.submit();
        }
        modifyModal.classList.add('hidden');
    });

    cancelModify.addEventListener('click', function() {
        modifyModal.classList.add('hidden');
    });

    // Fermer les modales si on clique en dehors
    window.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            deleteModal.classList.add('hidden');
        }
        if (e.target === modifyModal) {
            modifyModal.classList.add('hidden');
        }
    });

    // Fonction pour exporter en Excel
    function exportToExcel(type = 'groupes') {
        const table = document.querySelector(type === 'groupes' ? '#formListeGroupes table' : '#formListeTypes table');
        const rows = Array.from(table.querySelectorAll('tr'));

        // Créer le contenu CSV
        let csvContent = "data:text/csv;charset=utf-8,";

        // Ajouter les en-têtes
        const headers = Array.from(rows[0].querySelectorAll('th'))
            .map(header => header.textContent.trim())
            .filter(header => header !== ''); // Exclure la colonne des checkboxes
        csvContent += headers.join(',') + '\n';

        // Ajouter les données
        rows.slice(1).forEach(row => {
            const cells = Array.from(row.querySelectorAll('td'))
                .slice(1, -1) // Exclure la colonne des checkboxes et des actions
                .map(cell => `"${cell.textContent.trim()}"`);
            csvContent += cells.join(',') + '\n';
        });

        // Créer le lien de téléchargement
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `${type}_utilisateur.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Fonction pour imprimer
    function printTable(type = 'groupes') {
        const table = document.querySelector(type === 'groupes' ? '#formListeGroupes table' : '#formListeTypes table');
        const printWindow = window.open('', '_blank');

        // Créer une copie de la table pour la modification
        const tableClone = table.cloneNode(true);

        // Supprimer les colonnes ID, Actions et Checkboxes
        const rows = tableClone.querySelectorAll('tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('th, td');
            // Supprimer la colonne des checkboxes (première colonne)
            if (cells[0]) cells[0].remove();
            // Supprimer la colonne ID (maintenant première colonne)
            if (cells[0]) cells[0].remove();
            // Supprimer la colonne Actions (dernière colonne)
            if (cells[cells.length - 1]) cells[cells.length - 1].remove();
        });

        printWindow.document.write(`
            <html>
                <head>
                    <title>Liste des ${type} utilisateur</title>
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
                    <h2>Liste des ${type} utilisateur</h2>
                    ${tableClone.outerHTML}
                </body>
            </html>
        `);

        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }

    // Gestion des notifications
    document.addEventListener('DOMContentLoaded', function() {
        const successNotification = document.getElementById('successNotification');
        const errorNotification = document.getElementById('errorNotification');

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
    </script>

    <?php
    unset($GLOBALS['messageErreur'], $GLOBALS['messageSucces']);
    ?>

</body>

</html>