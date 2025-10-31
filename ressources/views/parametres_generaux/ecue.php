<?php

require_once __DIR__ . '/../../../app/utils/permissions.php';

$ecue_a_modifier = $ecue_a_modifier ?? null;
$listeEcues = $listeEcues ?? [];
$listeUes = $listeUes ?? [];

// Si on est en mode modification, récupérer l'UE correspondante
if ($ecue_a_modifier) {
    foreach ($listeUes as $ue) {
        if ($ue->id_ue == $ecue_a_modifier->id_ue) {
            $ue_selected = $ue;
            break;
        }
    }
} else {
    // Récupérer les informations de l'UE sélectionnée seulement si on n'est pas en mode modification
    $ue_selected = null;
    if (isset($_POST['id_ue']) && !empty($_POST['id_ue']) && !isset($_POST['btn_add_ecue'])) {
        foreach ($listeUes as $ue) {
            if ($ue->id_ue == $_POST['id_ue']) {
                $ue_selected = $ue;
                break;
            }
        }
    }
}

// Pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';

if (!empty($search)) {
    $listeEcues = array_filter($listeEcues, function($ecue) use ($search) {
        return stripos($ecue->lib_ecue, $search) !== false;
    });
}

// Total pages calculation
$total_items = count($listeUes);
$total_pages = ceil($total_items / $limit);

// Slice the array for pagination
$listeEcues = array_slice($listeEcues, $offset, $limit);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des ECUE</title>
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
    <?php if (!empty($messageSuccess)): ?>
    <div id="successNotification" class="notification success animate__animated animate__fadeIn">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <p><?= htmlspecialchars($messageSuccess) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($messageErreur)): ?>
    <div id="errorNotification" class="notification error animate__animated animate__fadeIn">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <p><?= htmlspecialchars($messageErreur) ?></p>
        </div>
    </div>
    <?php endif; ?>
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-600">
                <i class="fas fa-book mr-2 text-green-600"></i>
                Gestion des ECUE
            </h2>
        </div>

        <!-- Formulaire d'ajout/modification -->
        <form method="POST" action="?page=parametres_generaux&action=ecue" id="ecueForm" class="space-y-4">
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-600 mb-4">
                        <i
                            class="fas <?= $ecue_a_modifier ? 'fa-edit text-green-500' : 'fa-plus-circle text-green-500' ?> mr-2"></i>
                        <?= $ecue_a_modifier ? 'Modifier l\'ECUE' : 'Ajouter un nouvel ECUE' ?>
                    </h3>
                    <div>
                        <label for="id_annee_acad" class="block text-sm font-medium text-gray-700 mb-3">
                            <i class="fas fa-calendar text-green-500 mr-2"></i>Année académique
                        </label>
                        <input type="text" name="id_annee_acad" id="id_annee_acad" required disabled
                            value="<?= isset($ue_selected) ? htmlspecialchars($ue_selected->annee) : '' ?>"
                            class="form-input w-50 px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm bg-gray-50">
                    </div>
                </div>

                <?php if ($ecue_a_modifier): ?>
                <input type="hidden" name="id_ecue" value="<?= htmlspecialchars($ecue_a_modifier->id_ecue) ?>">
                <?php endif; ?>

                <div class="gap-6 ">
                    <div class="flex gap-6 items-center">
                        <div>
                            <label for="niveau_etude" class="block text-sm font-medium text-gray-700 mb-3">
                                <i class="fas fa-graduation-cap text-green-500 mr-2"></i>Niveau d'étude
                            </label>
                            <input type="text" name="niveau_etude" id="niveau_etude" required disabled
                                value="<?= isset($ue_selected) ? htmlspecialchars($ue_selected->lib_niv_etude) : '' ?>"
                                class="form-input w-2/3 px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm bg-gray-50">
                        </div>
                        <div>
                            <label for="semestre" class="block text-sm font-medium text-gray-700 mb-3">
                                <i class="fas fa-calendar-alt text-green-500 mr-2"></i>Semestre
                            </label>
                            <input type="text" name="semestre" id="semestre" required disabled
                                value="<?= isset($ue_selected) ? htmlspecialchars($ue_selected->lib_semestre) : '' ?>"
                                class="form-input w-2/3 px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm bg-gray-50">
                        </div>
                        <div>
                            <label for="id_ue" class="block text-sm font-medium text-gray-700 mb-4">
                                <i class="fas fa-graduation-cap text-green-500 mr-2"></i>Unité d'Enseignement
                            </label>
                            <select id="id_ue" name="id_ue" required onchange="updateFields(this.value)"
                                class="form-select w-50 px-3 py-2 mb-3 border border-gray-300 rounded-md focus:outline-4 focus:outline-green-300 focus:ring-green-300 focus:border-green-300 focus:ring-opacity-50 transition-all duration-200">
                                <option value="">Sélectionnez une UE</option>
                                <?php if (!empty($listeUes)): ?>
                                <?php foreach ($listeUes as $ue): ?>
                                <option value="<?= htmlspecialchars($ue->id_ue) ?>"
                                    <?= ($ecue_a_modifier && $ecue_a_modifier->id_ue == $ue->id_ue) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ue->lib_ue) ?>
                                </option>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div>
                            <label for="professeur_responsable" class="block text-sm font-medium text-gray-700 mb-3">
                                <i class="fas fa-user-tie text-green-500 mr-2"></i>Professeur responsable
                            </label>
                            <select id="professeur_responsable" name="professeur_responsable"
                                class="form-select w-50 px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 focus:outline-green-500 bg-white transition-all duration-200">
                                <option value="">Sélectionnez un professeur</option>
                                <?php foreach ($listeEnseignants ?? [] as $enseignant): ?>
                                <option value="<?= $enseignant->id_enseignant ?>"
                                    <?= $ecue_a_modifier && $ecue_a_modifier->id_enseignant == $enseignant->id_enseignant ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($enseignant->nom_enseignant . ' ' . $enseignant->prenom_enseignant) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2 mt-3 flex gap-6 w-full ">
                        <div class="w-full">
                            <label for="lib_ecue" class="block text-sm font-medium text-gray-700 mb-3">
                                <i class="fas fa-book text-green-500 mr-2"></i>Libellé de l'ECUE
                            </label>
                            <input type="text" name="lib_ecue" id="lib_ecue" required
                                placeholder="Ex: Mathématiques appliquées"
                                value="<?= $ecue_a_modifier ? htmlspecialchars($ecue_a_modifier->lib_ecue) : '' ?>"
                                class="form-input w-full px-4 py-2.5  border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 focus:outline-green-500 outline-0 bg-white transition-all duration-200">
                        </div>


                        <div>
                            <label for="credit" class="block text-sm font-medium text-gray-700 mb-3">
                                <i class="fas fa-star text-green-500 mr-2"></i>Crédits
                            </label>
                            <input type="number" name="credit" id="credit" required min="1" max="10"
                                value="<?= $ecue_a_modifier ? htmlspecialchars($ecue_a_modifier->credit) : '' ?>"
                                class="form-input w-50 px-4 py-2.5  border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:outline-2 focus:outline-green-500  focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                        </div>


                    </div>

                </div>

                <div class="flex justify-between mt-6">
                    <?php if (isset($_GET['id_ecue'])): ?>
                    <button type="button" name="btn_annuler" id="btnAnnuler"
                        onclick="window.location.href='?page=parametres_generaux&action=ecue'"
                        class="btn-hover px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </button>
                    <button type="button" id="btnModifier" name="btn_modifier_ecue"
                        class="btn-hover px-4 py-2 btn-gradient-primary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        <i class="fas fa-save mr-2"></i>Modifier
                        <input type="hidden" name="btn_modifier_ecue" id="btn_modifier_ecue_hidden" value="0">
                    </button>
                    <?php else: ?>
                    <div></div>
                    <button type="submit" name="btn_add_ecue"
                        class="btn-hover px-4 py-2 btn-gradient-primary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        <i class="fas fa-plus mr-2"></i>Ajouter une ECUE
                    </button>
                    <?php endif; ?>

                </div>
        </form>
    </div>

    <!-- Liste des ECUE -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-600 mb-4">
                <i class="fas fa-list-ul text-green-500 mr-2"></i>
                Liste des ECUE
            </h3>
            <div class="flex justify-between items-center mb-4">
                <!-- Barre de recherche -->
                <div class="flex-1 max-w-md">
                    <form action="" method="GET" class="flex gap-3">
                        <input type="hidden" name="page" value="parametres_generaux">
                        <input type="hidden" name="action" value="ue">
                        <div class="relative flex-1">
                            <i
                                class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="text" name="search" value="<?= $search ?>" placeholder="Rechercher..."
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

            <form action="?page=parametres_generaux&action=ecue" method="POST" id="formListeEcues">
                <input type="hidden" name="submit_delete_multiple" id="submitDeleteHidden" value="0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="w-[5%] px-4 py-3 text-center">
                                    <input type="checkbox" id="selectAllCheckbox"
                                        class="form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Année académique
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Niveau d'étude
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Semestre
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    UE
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Libellé ECUE
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Crédits
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Professeur responsable
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($listeEcues)): ?>
                            <?php foreach ($listeEcues as $ecue): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox" name="selected_ids[]"
                                        value="<?= htmlspecialchars($ecue->id_ecue) ?>"
                                        class="row-checkbox form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($ecue->id_annee_acad) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($ecue->lib_niv_etude) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($ecue->lib_semestre) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($ecue->lib_ue) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($ecue->lib_ecue) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($ecue->credit) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($ecue->nom_professeur ?? 'Non assigné') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                    <a href="?page=parametres_generaux&action=ecue&id_ecue=<?= htmlspecialchars($ecue->id_ecue) ?>"
                                        class="text-green-600 hover:text-green-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr class="items-center">
                                <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Aucun ECUE enregistré
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Affichage de <span class="font-medium"><?= $offset + 1 ?></span>
                                à <span class="font-medium"><?= min($offset + $limit, $total_items) ?></span>
                                sur <span class="font-medium"><?= $total_items ?></span> résultats
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                                aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                <a href="?page=parametres_generaux&action=ecue&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <?php endif; ?>

                                <?php
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);
                                
                                if ($start > 1) {
                                    echo '<span class="px-3 py-2 text-gray-500">...</span>';
                                }
                                
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                <a href="?page=parametres_generaux&action=ecue&p=<?= $i ?>&search=<?= urlencode($search) ?>"
                                    class="relative inline-flex items-center px-4 py-2 border <?= $i === $page ? 'bg-green-50 text-green-600 border-green-500' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' ?>">
                                    <?= $i ?>
                                </a>
                                <?php endfor;

                                if ($end < $total_pages) {
                                    echo '<span class="px-3 py-2 text-gray-500">...</span>';
                                }
                                ?>

                                <?php if ($page < $total_pages): ?>
                                <a href="?page=parametres_generaux&action=ecue&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

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
                    Êtes-vous sûr de vouloir supprimer les ECUE sélectionnées ?
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
                    Êtes-vous sûr de vouloir modifier cette ECUE ?
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
    // Gestion des checkboxes et du bouton de suppression
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const deleteButton = document.getElementById('deleteSelectedBtn');
    const deleteModal = document.getElementById('deleteModal');
    const confirmDelete = document.getElementById('confirmDelete');
    const cancelDelete = document.getElementById('cancelDelete');
    const formListeEcues = document.getElementById('formListeEcues');
    const submitDeleteHidden = document.getElementById('submitDeleteHidden');
    const btnModifier = document.getElementById('btnModifier');
    const modifyModal = document.getElementById('modifyModal');
    const confirmModify = document.getElementById('confirmModify');
    const cancelModify = document.getElementById('cancelModify');
    const ecueForm = document.getElementById('ecueForm');
    const submitModifierHidden = document.getElementById('btn_modifier_ecue_hidden');

    // Initialisation
    updateDeleteButtonState();

    // Select all checkboxes
    selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        updateDeleteButtonState();
    });

    // Update delete button state
    function updateDeleteButtonState() {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        deleteButton.disabled = checkedBoxes.length === 0;
    }

    // Checkbox change events
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-checkbox')) {
            updateDeleteButtonState();
            const allCheckboxes = document.querySelectorAll('.row-checkbox');
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            selectAllCheckbox.checked = checkedBoxes.length === allCheckboxes.length && allCheckboxes
                .length >
                0;
        }
    });

    // Delete modal
    deleteButton.addEventListener('click', function() {
        if (!this.disabled) {
            deleteModal.classList.remove('hidden');
        }
    });

    confirmDelete.addEventListener('click', function() {
        submitDeleteHidden.value = '1';
        formListeEcues.submit();
    });

    cancelDelete.addEventListener('click', function() {
        deleteModal.classList.add('hidden');
    });

    // Modify modal
    if (btnModifier) {
        btnModifier.addEventListener('click', function() {
            modifyModal.classList.remove('hidden');
        });
    }

    confirmModify.addEventListener('click', function() {
        submitModifierHidden.value = '1';
        ecueForm.submit();
    });

    cancelModify.addEventListener('click', function() {
        modifyModal.classList.add('hidden');
    });

    // Fonction pour exporter en Excel
    function exportToExcel() {
        const table = document.querySelector('table');
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
        link.setAttribute('download', 'ecue.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Fonction pour imprimer
    function printTable() {
        const table = document.querySelector('table');
        const printWindow = window.open('', '_blank');

        // Créer une copie de la table pour la modification
        const tableClone = table.cloneNode(true);

        // Supprimer les colonnes ID, Actions et Checkboxes
        const rows = tableClone.querySelectorAll('tr');
        rows.forEach(row => {
            // Supprimer la colonne des checkboxes (première colonne)
            const checkboxCell = row.querySelector('th:first-child, td:first-child');
            if (checkboxCell) checkboxCell.remove();

            // Supprimer la colonne ID (maintenant première colonne)
            const idCell = row.querySelector('th:first-child, td:first-child');
            if (idCell) idCell.remove();

            // Supprimer la colonne Actions (dernière colonne)
            const actionCell = row.querySelector('th:last-child, td:last-child');
            if (actionCell) actionCell.remove();
        });

        printWindow.document.write(`
            <html>
                <head>
                    <title>Liste des ECUE</title>
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
                    <h2>Liste des ECUE</h2>
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

    function updateFields(ueId) {
        // Récupérer l'UE sélectionnée depuis le tableau PHP
        const ues = <?= json_encode($listeUes) ?>;

        // Si aucun ID n'est fourni ou si l'ID est vide, vider tous les champs
        if (!ueId || ueId === '') {
            document.getElementById('id_annee_acad').value = '';
            document.getElementById('niveau_etude').value = '';
            document.getElementById('semestre').value = '';
            return;
        }

        // Convertir ueId en nombre pour la comparaison
        const selectedUe = ues.find(ue => parseInt(ue.id_ue) === parseInt(ueId));

        if (selectedUe) {
            document.getElementById('id_annee_acad').value = selectedUe.annee || '';
            document.getElementById('niveau_etude').value = selectedUe.lib_niv_etude || '';
            document.getElementById('semestre').value = selectedUe.lib_semestre || '';
        } else {
            // Si l'UE n'est pas trouvée, vider les champs
            document.getElementById('id_annee_acad').value = '';
            document.getElementById('niveau_etude').value = '';
            document.getElementById('semestre').value = '';
        }
    }

    // Appeler updateFields au chargement de la page si une UE est déjà sélectionnée
    document.addEventListener('DOMContentLoaded', function() {
        const ueSelect = document.getElementById('id_ue');
        // Toujours appeler updateFields au chargement pour s'assurer que les champs sont correctement initialisés
        updateFields(ueSelect.value);
    });

    // Ajouter un écouteur d'événement pour le changement de sélection
    document.getElementById('id_ue').addEventListener('change', function() {
        updateFields(this.value);
    });
    </script>

    <?php
    unset($_SESSION['messageSucces'], $_SESSION['messageErreur']);
    ?>

</body>

</html>