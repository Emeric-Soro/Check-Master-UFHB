<?php
$semestre_a_modifier = $GLOBALS['semestre_a_modifier'] ?? null;

// Pagination
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';

// Filter the list based on search
$listeSemestres = $GLOBALS['listeSemestres'] ?? [];
if (!empty($search)) {
    $listeSemestres = array_filter($listeSemestres, function ($semestre) use ($search) {
        return stripos($semestre->lib_semestre, $search) !== false;
    });
}

// Total pages calculation
$total_items = count($listeSemestres);
$total_pages = ceil($total_items / $limit);

// Slice the array for pagination
$listeSemestres = array_slice($listeSemestres, $offset, $limit);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Semestres</title>
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

<body style="background-color: #DFF2FF;">
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
                    <i class="fas fa-calendar-alt mr-2 text-green-600"></i>
                    Gestion des Semestres
                </h2>
            </div>

            <!-- Formulaire -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-600 mb-4 flex items-center">
                    <i
                        class="fas <?= isset($_GET['id_semestre']) ? 'fa-edit text-green-500' : 'fa-plus-circle text-green-500' ?> mr-2"></i>
                    <?= isset($_GET['id_semestre']) ? "Modifier le semestre" : "Ajouter un nouveau semestre" ?>
                </h3>

                <form method="POST" action="?page=parametres_generaux&action=semestres" id="semestreForm">
                    <?php if ($semestre_a_modifier): ?>
                        <input type="hidden" name="id_semestre"
                            value="<?= htmlspecialchars($semestre_a_modifier->id_semestre) ?>">
                    <?php endif ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Libellé Semestre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Libellé du semestre</label>
                            <input type="text" id="lib_semestre" name="lib_semestre" required
                                value="<?= $semestre_a_modifier ? htmlspecialchars($semestre_a_modifier->lib_semestre) : '' ?>"
                                placeholder="Ex: Semestre 1"
                                class="form-input w-full px-3 py-2 mb-3 border border-gray-300 rounded-md focus:outline-4 focus:outline-green-300 focus:ring-green-300 focus:border-green-300 focus:ring-opacity-50 transition-all duration-200">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Niveau d'étude</label>
                            <select id="niveau_etude" name="niveau_etude" required
                                class="form-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-4 focus:outline-green-300 focus:ring-green-300 focus:border-green-300 focus:ring-opacity-50 transition-all duration-200">
                                <option value="">Sélectionnez un niveau</option>
                                <?php foreach ($GLOBALS['listeNiveauxEtude'] as $niveau): ?>
                                    <option value="<?= $niveau->id_niv_etude ?>" <?= $semestre_a_modifier && $semestre_a_modifier->id_niv_etude == $niveau->id_niv_etude ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($niveau->lib_niv_etude) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <?php if (isset($_GET['id_semestre'])): ?>
                            <button type="button" name="btn_annuler" id="btnAnnuler"
                                onclick="window.location.href='?page=parametres_generaux&action=semestres'"
                                class="btn-hover px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                <i class="fas fa-times mr-2"></i>Annuler
                            </button>
                            <button type="button" id="btnModifier" name="btn_modifier_semestre"
                                class="btn-hover px-4 py-2 btn-gradient-primary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <i class="fas fa-save mr-2"></i>Modifier
                                <input type="hidden" name="btn_modifier_semestre" id="btn_modifier_semestre_hidden"
                                    value="0">
                            </button>
                        <?php else: ?>
                            <div></div>
                            <button type="submit" name="btn_add_semestre"
                                class="btn-hover px-4 py-2 btn-gradient-primary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <i class="fas fa-plus mr-2"></i>Ajouter un semestre
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Liste des semestres -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-600 mb-4 flex items-center">
                    <i class="fas fa-list-ul text-green-500 mr-2"></i>
                    Liste des semestres
                </h3>
                <div class="flex justify-between items-center mb-4">
                    <!-- Barre de recherche -->
                    <div class="flex-1 max-w-md">
                        <form action="" method="GET" class="flex gap-3">
                            <input type="hidden" name="page" value="parametres_generaux">
                            <input type="hidden" name="action" value="semestres">
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

                <form class="overflow-x-auto" method="POST" action="?page=parametres_generaux&action=semestres"
                    id="formListeSemestres">
                    <input type="hidden" name="submit_delete_multiple" id="submitDeleteHidden" value="0">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="w-[5%] px-4 py-3 text-center">
                                    <input type="checkbox" id="selectAllCheckbox"
                                        class="form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Libellé</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Niveau</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($listeSemestres)): ?>
                                <?php foreach ($listeSemestres as $semestre): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 text-center">
                                            <input type="checkbox" name="selected_ids[]"
                                                value="<?= htmlspecialchars($semestre->id_semestre) ?>"
                                                class="row-checkbox form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                                            <?= htmlspecialchars($semestre->id_semestre) ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            <?= htmlspecialchars($semestre->lib_semestre) ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            <?= htmlspecialchars($semestre->lib_niv_etude) ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex justify-center space-x-2">
                                                <a href="?page=parametres_generaux&action=semestres&id_semestre=<?= $semestre->id_semestre ?>"
                                                    class="text-blue-500 hover:text-blue-700 transition-colors">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-sm text-gray-500 py-4">
                                        Aucun semestre enregistré.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </form>

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
                                        <a href="?page=parametres_generaux&action=semestres&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"
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
                                        <a href="?page=parametres_generaux&action=semestres&p=<?= $i ?>&search=<?= urlencode($search) ?>"
                                            class="relative inline-flex items-center px-4 py-2 border <?= $i === $page ? 'bg-green-50 text-green-600 border-green-500' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor;

                                    if ($end < $total_pages) {
                                        echo '<span class="px-3 py-2 text-gray-500">...</span>';
                                    }
                                    ?>

                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=parametres_generaux&action=semestres&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"
                                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
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
                    Êtes-vous sûr de vouloir supprimer les semestres sélectionnés ?
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
                    Êtes-vous sûr de vouloir modifier ce semestre ?
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
        const formListeSemestres = document.getElementById('formListeSemestres');
        const submitDeleteHidden = document.getElementById('submitDeleteHidden');
        const btnModifier = document.getElementById('btnModifier');
        const modifyModal = document.getElementById('modifyModal');
        const confirmModify = document.getElementById('confirmModify');
        const cancelModify = document.getElementById('cancelModify');
        const semestreForm = document.getElementById('semestreForm');
        const submitModifierHidden = document.getElementById('btn_modifier_semestre_hidden');

        // Initialisation
        updateDeleteButtonState();

        // Select all checkboxes
        selectAllCheckbox.addEventListener('change', function () {
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
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('row-checkbox')) {
                updateDeleteButtonState();
                const allCheckboxes = document.querySelectorAll('.row-checkbox');
                const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
                selectAllCheckbox.checked = checkedBoxes.length === allCheckboxes.length && allCheckboxes.length >
                    0;
            }
        });

        // Delete modal
        deleteButton.addEventListener('click', function () {
            if (!this.disabled) {
                deleteModal.classList.remove('hidden');
            }
        });

        confirmDelete.addEventListener('click', function () {
            submitDeleteHidden.value = '1';
            formListeSemestres.submit();
        });

        cancelDelete.addEventListener('click', function () {
            deleteModal.classList.add('hidden');
        });

        // Modify modal
        if (btnModifier) {
            btnModifier.addEventListener('click', function () {
                modifyModal.classList.remove('hidden');
            });
        }

        confirmModify.addEventListener('click', function () {
            submitModifierHidden.value = '1';
            semestreForm.submit();
        });

        cancelModify.addEventListener('click', function () {
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
            link.setAttribute('download', 'semestres.csv');
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
                    <title>Liste des semestres</title>
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
                    <h2>Liste des semestres</h2>
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
        document.addEventListener('DOMContentLoaded', function () {
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
    unset($_SESSION['messageSucces'], $_SESSION['messageErreur']);
    ?>
</body>

</html>