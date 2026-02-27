<?php
require_once __DIR__ . '/../../../app/utils/permissions_helper.php';

$annee_a_modifier = $GLOBALS['annee_a_modifier'] ?? null;

// Pagination
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';

// Filter the list based on search
$listeAnnees = $GLOBALS['listeAnnees'] ?? [];
$totalAnnees = $listeAnnees;
if (!empty($search)) {
    $listeAnnees = array_filter($listeAnnees, function ($annee) use ($search) {
        $anneeStr = date('Y', strtotime($annee->date_deb)) . '-' . date('Y', strtotime($annee->date_fin));
        return stripos($anneeStr, $search) !== false;
    });
}

// Total pages calculation
$total_items = count($listeAnnees);
$total_pages = ceil($total_items / $limit);

// Slice the array for pagination
$listeAnnees = array_slice($listeAnnees, $offset, $limit);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Années Académiques</title>
    <style>
        /* Styles pour les notifications */
        .notification {
            padding: 1rem;
            border-radius: 0.5rem;
            color: white;
            max-width: 24rem;
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

        /* Dégradé pour les boutons */
        .bg-gradient {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }

        .bg-gradient:hover {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        }

        /* Ombres pour les cartes */
        .shadow-card {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
    </style>
</head>

<body style="background-color: #DFF2FF;">
    <div class="relative container mx-auto px-3 py-3">
        <!-- Système de notification -->
        <?php if (!empty($GLOBALS['messageSuccess'])): ?>
                <div id="successNotification" class="fixed top-4 right-4 z-50 animate__animated animate__fadeIn">
                    <div class="notification success">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <p><?= htmlspecialchars($GLOBALS['messageSuccess']) ?></p>
                        </div>
                    </div>
                </div>
        <?php endif; ?>

        <?php if (!empty($GLOBALS['messageErreur'])): ?>
                <div id="errorNotification" class="fixed top-4 right-4 z-50 animate__animated animate__fadeIn">
                    <div class="notification error">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <p><?= htmlspecialchars($GLOBALS['messageErreur']) ?></p>
                        </div>
                    </div>
                </div>
        <?php endif; ?>

        <!-- Formulaire compact d'ajout/mise à jour année académique -->
        <div class="bg-white rounded-lg shadow-card p-4 mb-4 border border-gray-200">
            <div class="flex items-center mb-3 pb-2 border-b border-gray-200">
                <div class="bg-green-100 p-2 rounded-full mr-3">
                    <i class="fas fa-calendar-alt text-green-500 text-base"></i>
                </div>
                <h2 class="text-lg font-semibold text-gray-700">
                    <?php echo $annee_a_modifier ? 'Modifier une année académique' : 'Ajouter une Année Académique' ?>
                </h2>
            </div>
            
            <form id="anneeForm" class="space-y-3" method="POST" action="?page=parametres_generaux&action=annees_academiques">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\CheckMaster\Core\Csrf::token()); ?>">
                <?php if ($annee_a_modifier): ?>
                    <input type="hidden" name="id_annee_acad" value="<?= htmlspecialchars($annee_a_modifier->id_annee_acad) ?>">
                <?php endif; ?>
                
                <!-- Ligne: Date début et Date fin -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label for="date_debut" class="block text-xs font-semibold text-gray-700">
                            <i class="fas fa-play text-green-500 mr-1"></i>Date de début <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="date_debut" name="date_debut" required
                            value="<?= $annee_a_modifier ? date('Y-m-d', strtotime($annee_a_modifier->date_deb)) : '' ?>"
                            class="focus:outline-none w-full px-3 py-2 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label for="date_fin" class="block text-xs font-semibold text-gray-700">
                            <i class="fas fa-stop text-green-500 mr-1"></i>Date de fin <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="date_fin" name="date_fin" required
                            value="<?= $annee_a_modifier ? date('Y-m-d', strtotime($annee_a_modifier->date_fin)) : '' ?>"
                            class="focus:outline-none w-full px-3 py-2 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm">
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-200 mt-3">
                    <?php if ($annee_a_modifier): ?>
                            <a href="?page=parametres_generaux&action=annees_academiques"
                                class="px-4 py-1.5 border border-gray-300 text-xs font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200">
                                <i class="fas fa-times mr-1"></i>Annuler
                            </a>
                            <?php if (canEdit()): ?>
                            <button type="button" id="btnModifier" name="btn_modifier_annees_academiques"
                                class="px-4 py-1.5 border border-transparent text-xs font-medium rounded-lg shadow-sm text-white bg-gradient hover:shadow-lg transition-all duration-200">
                                <i class="fas fa-save mr-1"></i>Modifier
                                <input type="hidden" name="btn_modifier_annees_academiques"
                                    id="btn_modifier_annees_academiques_hidden" value="0">
                            </button>
                            <?php endif; ?>
                    <?php else: ?>
                            <button type="reset"
                                class="px-4 py-1.5 border border-gray-300 text-xs font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200">
                                <i class="fas fa-redo mr-1"></i>Réinitialiser
                            </button>
                            <?php if (canCreate()): ?>
                            <button type="submit" name="btn_add_annees_academiques"
                                class="px-4 py-1.5 border border-transparent text-xs font-medium rounded-lg shadow-sm text-white bg-gradient hover:shadow-lg transition-all duration-200">
                                <i class="fas fa-save mr-1"></i>Enregistrer
                            </button>
                            <?php endif; ?>
                    <?php endif; ?>
                </div>
            </form>
        </div>


        <!-- Main Content -->
        <div class="bg-white shadow-card rounded-lg overflow-hidden border border-gray-200">
            <!-- Dashboard Header -->
            <div class="bg-gradient-to-r from-green-600 to-green-800 px-4 py-3">
                <h2 class="text-lg font-bold text-white">Liste des années académiques</h2>
            </div>

            <!-- Action Bar for Table -->
            <div class="px-4 py-2 flex flex-col sm:flex-row justify-between items-center border-b border-gray-200 gap-2">
                <div class="flex gap-2 w-full sm:w-auto">
                    <div class="relative flex-1 sm:flex-initial sm:w-64">
                        <form action="" method="GET" class="flex gap-2">
                            <input type="hidden" name="page" value="parametres_generaux">
                            <input type="hidden" name="action" value="annees_academiques">
                            <input type="text" name="search" value="<?= $search ?>" placeholder="Rechercher une année..."
                                class="w-full px-3 py-1.5 pl-8 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                                <i class="fas fa-search text-gray-400 text-xs"></i>
                            </span>
                            <button type="submit" class="hidden"></button>
                        </form>
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
                    <?php if (canDelete() && count($totalAnnees) > 0): ?>
                    <button type="button" id="deleteSelectedBtn" disabled
                        class="bg-red-500 hover:bg-red-600 text-white font-medium py-1 px-2.5 text-xs rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-trash-alt mr-1"></i>Supprimer
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Table with Scroll -->
            <div class="cm-table-wrapper" style="max-height: 200px;">
                <form method="POST" action="?page=parametres_generaux&action=annees_academiques" id="formListeAnnees">
                    <input type="hidden" name="submit_delete_multiple" id="submitDeleteHidden" value="0">
                    <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <?php if (canDelete()): ?>
                                    <th class="w-12 px-3 py-2">
                                        <input type="checkbox" id="selectAllCheckbox"
                                            class="rounded border-gray-300 text-green-600 focus:ring-green-500 transition-all duration-200">
                                    </th>
                                    <?php endif; ?>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-hashtag mr-1"></i>ID
                                    </th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-calendar mr-1"></i>Année académique
                                    </th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-play mr-1"></i>Date de début
                                    </th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-stop mr-1"></i>Date de fin
                                    </th>
                                    <?php if (canEdit()): ?>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-cog mr-1"></i>Actions
                                    </th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($listeAnnees)): ?>
                                    <?php foreach ($listeAnnees as $annee): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                                            <?php if (canDelete()): ?>
                                            <td class="px-3 py-2">
                                                <input type="checkbox" name="selected_ids[]"
                                                    value="<?= htmlspecialchars($annee->id_annee_acad) ?>"
                                                    class="row-checkbox text-center rounded border-gray-300 text-green-600 focus:ring-green-500 transition-all duration-200">
                                            </td>
                                            <?php endif; ?>
                                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
                                                <?= htmlspecialchars($annee->id_annee_acad) ?>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm font-medium">
                                                <?= date('Y', strtotime($annee->date_deb)) . '-' . date('Y', strtotime($annee->date_fin)) ?>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
                                                <?= date('d/m/Y', strtotime($annee->date_deb)) ?>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
                                                <?= date('d/m/Y', strtotime($annee->date_fin)) ?>
                                            </td>
                                            <?php if (canEdit()): ?>
                                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                                <a href="?page=parametres_generaux&action=annees_academiques&id_annee_acad=<?= $annee->id_annee_acad ?>"
                                                    class="text-blue-600 hover:text-blue-900 mr-2" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo canEdit() ? (canDelete() ? '6' : '5') : (canDelete() ? '5' : '4'); ?>" class="px-4 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-calendar-times text-gray-300 text-3xl mb-3"></i>
                                                <p class="text-sm">Aucune année académique trouvée.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                    </table>
                </form>
            </div>

            <!-- Pagination en bas du tableau -->
            <?php if ($total_pages > 1): ?>
                <div class="bg-white rounded-lg shadow-sm p-4 mt-6">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm text-gray-500">
                            Affichage de <?= $offset + 1 ?> à <?= min($offset + $limit, $total_items) ?> sur
                            <?= $total_items ?> entrées
                        </div>
                        <div class="flex flex-wrap justify-center gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=parametres_generaux&action=annees_academiques&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"
                                    class="btn-hover px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left mr-1"></i>Précédent
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
                                <a href="?page=parametres_generaux&action=annees_academiques&p=<?= $i ?>&search=<?= urlencode($search) ?>"
                                    class="btn-hover px-3 py-2 <?= $i === $page ? 'btn-gradient-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor;

                            if ($end < $total_pages) {
                                echo '<span class="px-3 py-2 text-gray-500">...</span>';
                            }
                            ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=parametres_generaux&action=annees_academiques&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"
                                    class="btn-hover px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Suivant<i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            <?php endif; ?>
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
                    Êtes-vous sûr de vouloir supprimer les années académiques sélectionnées ?
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
                    Êtes-vous sûr de vouloir modifier cette année académique ?
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
        const formListeAnnees = document.getElementById('formListeAnnees');
        const submitDeleteHidden = document.getElementById('submitDeleteHidden');
        const btnModifier = document.getElementById('btnModifier');
        const modifyModal = document.getElementById('modifyModal');
        const confirmModify = document.getElementById('confirmModify');
        const cancelModify = document.getElementById('cancelModify');
        const anneeForm = document.getElementById('anneeForm');
        const submitModifierHidden = document.getElementById('btn_modifier_annees_academiques_hidden');

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
            formListeAnnees.submit();
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
            anneeForm.submit();
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
            link.setAttribute('download', 'annees_academiques.csv');
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
                    <title>Liste des années académiques</title>
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
                    <h2>Liste des années académiques</h2>
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