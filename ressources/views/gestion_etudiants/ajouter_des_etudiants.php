<?php
$listeEtudiants = $GLOBALS['listeEtudiants'] ?? [];
$etudiant_a_modifier = $GLOBALS['etudiant_a_modifier'] ?? null;
$modalAction = $GLOBALS['modalAction'] ?? '';
$showModal = isset($_GET['modalAction']) && ($_GET['modalAction'] === 'edit' || $_GET['modalAction'] === 'add');

// Pagination
$currentPage = $GLOBALS['currentPage'] ?? 1;
$itemsPerPage = $GLOBALS['itemsPerPage'] ?? 10;
$totalItems = $GLOBALS['totalItems'] ?? 0;
$totalPages = $GLOBALS['totalPages'] ?? 0;
$startIndex = $GLOBALS['startIndex'] ?? 0;
$endIndex = $GLOBALS['endIndex'] ?? 0;
$currentPageItems = $GLOBALS['listeEtudiants'] ?? [];

// Récupérer tous les étudiants pour la recherche
$allEtudiants = $GLOBALS['allEtudiants'] ?? [];

// Debug pour vérifier les valeurs
error_log("View - Current Page: " . $currentPage);
error_log("View - Total Pages: " . $totalPages);
error_log("View - Total Items: " . $totalItems);
error_log("View - Start Index: " . $startIndex);
error_log("View - End Index: " . $endIndex);
error_log("View - Items Per Page: " . $itemsPerPage);
error_log("View - Current Page Items Count: " . count($currentPageItems));
error_log("View - All Etudiants Count: " . count($allEtudiants));
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des étudiants</title>


</head>

<body style="background-color: #DFF2FF;">
    <div class="relative container mx-auto px-4 py-8">
        <!-- Système de notification -->
        <?php if (!empty($GLOBALS['messageSuccess'])): ?>
            <div id="successNotification" class="fixed top-4 right-4 z-50 animate__animated animate__fadeIn">
                <div
                    class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-lg flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium"><?= htmlspecialchars($GLOBALS['messageSuccess']) ?></p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-auto pl-3">
                        <i class="fas fa-times text-green-500 hover:text-green-700"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($GLOBALS['messageErreur'])): ?>
            <div id="errorNotification" class="fixed top-4 right-4 z-50 animate__animated animate__fadeIn">
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-lg flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium"><?= htmlspecialchars($GLOBALS['messageErreur']) ?></p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-auto pl-3">
                        <i class="fas fa-times text-red-500 hover:text-red-700"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Modal de confirmation de suppression -->
        <div id="deleteModal" class="fixed inset-0 bg-opacity-50 hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Confirmer la suppression</h3>
                <p class="text-gray-600 mb-6">Êtes-vous sûr de vouloir supprimer les étudiants sélectionnés ? Cette
                    action est irréversible.</p>
                <div class="flex justify-end space-x-4">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Annuler
                    </button>
                    <button onclick="confirmDelete()" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                        Supprimer
                    </button>
                </div>
            </div>
        </div>

        <!-- Add/Edit User Modal -->
        <div id="userModal"
            class="fixed inset-0 bg-opacity-50 border border-gray-200 overflow-y-auto h-full w-full z-50 flex <?php echo $showModal ? 'add' : 'hidden'; ?> items-center justify-center modal-transition">
            <div class="relative p-8 w-full max-w-2xl shadow-2xl rounded-xl bg-white fade-in transform">
                <div class="absolute top-0 right-0 m-3">
                    <button onclick="closeUserModal()"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none btn-icon">
                        <i class="fas fa-times fa-lg"></i>
                    </button>
                </div>
                <div class="flex items-center justify-between mb-6 pb-2 border-b border-gray-200">
                    <div class="flex">
                        <div class="bg-green-100 p-1.5 rounded-full mr-3">
                            <i class="fas fa-user-plus text-green-500 text-sm"></i>
                        </div>
                        <h3 id="userModalTitle" class="text-2xl font-semibold text-gray-700">
                            <?php echo isset($etudiant_a_modifier) && $_GET['modalAction'] == 'edit' ? 'Modifier un étudiant' : 'Ajouter un étudiant'; ?>
                        </h3>
                    </div>

                    <?php if ($_GET['modalAction'] === 'edit'): ?>
                        <div>
                            <label for="num_etu" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-book text-green-500 mr-2"></i>Numéro étudiant
                            </label>
                            <input type="text" name="num_etu" id="num_etu" required
                                value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->num_etu) : ''; ?>"
                                readonly
                                class="focus:outline-none w-32 px-3 py-2 border border-gray-300 rounded-lg shadow-sm bg-gray-100 cursor-not-allowed">
                        </div>
                    <?php endif; ?>
                </div>
                <form id="userForm" class="space-y-4" method="post"
                    action="?page=gestion_etudiants&action=ajouter_des_etudiants">
                    <input type="hidden" id="num_etu" name="num_etu"
                        value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->num_etu) : ''; ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="nom_etu" class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-user text-green-500 mr-2"></i>Nom
                            </label>
                            <input type="text" name="nom_etu" id="nom_etu" required
                                value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->nom_etu) : ''; ?>"
                                class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                        </div>
                        <div class="space-y-2">
                            <label for="prenom_etu" class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-user text-green-500 mr-2"></i>Prénom
                            </label>
                            <input type="text" name="prenom_etu" id="prenom_etu" required
                                value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->prenom_etu) : ''; ?>"
                                class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="email_etu" class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-envelope text-green-500 mr-2"></i>Email
                            </label>
                            <input type="email" name="email_etu" id="email_etu" required
                                value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->email_etu) : ''; ?>"
                                class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                        </div>
                        <div class="space-y-2">
                            <label for="promotion_etu" class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-graduation-cap text-green-500 mr-2"></i>Promotion
                            </label>
                            <select name="promotion_etu" id="promotion_etu" required
                                class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                                <option value="">Sélectionner une promotion</option>
                                <?php
                                for ($i = 2000; $i <= 2030; $i++) {
                                    $value = $i . '-' . ($i + 1);
                                    $selected = ($etudiant_a_modifier && $etudiant_a_modifier->promotion_etu === $value) ? 'selected' : '';
                                    echo "<option value=\"$value\" $selected>$value</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="date_naiss_etu" class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-calendar text-green-500 mr-2"></i>Date de naissance
                            </label>
                            <input type="date" name="date_naiss_etu" id="date_naiss_etu" required
                                value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->date_naiss_etu) : ''; ?>"
                                class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                        </div>
                        <div class="space-y-2">
                            <label for="genre_etu" class="block text-sm font-medium text-gray-700">
                                <i class="fa-solid fa-venus-mars text-green-500 mr-2"></i>Genre
                            </label>
                            <select name="genre_etu" id="genre_etu" required
                                class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                                <option value="">Sélectionner un genre</option>
                                <option value="Femme" <?php echo ($etudiant_a_modifier && $etudiant_a_modifier->genre_etu === 'Femme') ? 'selected' : ''; ?>>
                                    Féminin</option>
                                <option value="Homme" <?php echo ($etudiant_a_modifier && $etudiant_a_modifier->genre_etu === 'Homme') ? 'selected' : ''; ?>>
                                    Masculin</option>
                                <option value="Neutre" <?php echo ($etudiant_a_modifier && $etudiant_a_modifier->genre_etu === 'Neutre') ? 'selected' : ''; ?>>
                                    Neutre</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-between space-x-4 self-end pt-6">
                        <button type="button" onclick="closeUserModal()"
                            class="px-6 py-2.5 border border-gray-300 text-sm font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200">
                            <i class="fas fa-times mr-2"></i>Annuler
                        </button>
                        <button type="submit"
                            name="<?php echo isset($etudiant_a_modifier) && $_GET['modalAction'] == 'edit' ? 'submit_modifier_etudiant' : 'submit_add_etudiant'; ?>"
                            class="px-6 py-2.5 text-sm font-medium rounded-lg shadow-sm text-white bg-gradient from-green-600 to-green-800 hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-save mr-2"></i><span
                                id="userModalSubmitButton"><?php echo isset($etudiant_a_modifier) && $_GET['modalAction'] == 'edit' ? 'Modifier' : 'Enregistrer'; ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white shadow-card rounded-lg overflow-hidden border border-gray-200 mb-8">
            <!-- Dashboard Header -->
            <div class=" bg-gradient-to-r from-green-600 to-green-800 px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-white">Gestion des étudiants</h2>
                <button onclick="openUserModal()"
                    class="bg-green-500 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                    <i class="fas fa-plus mr-2"></i>Ajouter un étudiant
                </button>
            </div>

            <!-- Action Bar for Table -->
            <div class="px-6 py-4 flex flex-col sm:flex-row justify-between items-center border-b border-gray-200">
                <div class="relative w-full sm:w-1/2 lg:w-1/3 mb-4 sm:mb-0">
                    <input type="text" id="searchInput" placeholder="Rechercher un étudiant..."
                        class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </span>
                </div>
                <div class="flex flex-wrap gap-2 justify-center sm:justify-end">
                    <button onclick="imprimerListe()"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        <i class="fas fa-print mr-2"></i>Imprimer
                    </button>
                    <button onclick="exporterListe()"
                        class="bg-orange-500 hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-opacity-50">
                        <i class="fas fa-file-export mr-2"></i>Exporter
                    </button>
                    <button id="deleteButton" onclick="openDeleteModal()"
                        class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">
                        <i class="fas fa-trash-alt mr-2"></i>Supprimer
                    </button>
                </div>
            </div>

            <!-- Users Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-center">
                                <input type="checkbox" id="selectAllCheckbox"
                                    class="form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 cursor-pointer">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <span>Numéro étudiant</span>
                                    <i class="fas fa-sort ml-1 text-gray-400"></i>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <span>Nom</span>
                                    <i class="fas fa-sort ml-1 text-gray-400"></i>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <span>Prénom</span>
                                    <i class="fas fa-sort ml-1 text-gray-400"></i>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <span>Genre</span>
                                    <i class="fas fa-sort ml-1 text-gray-400"></i>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <span>Email</span>
                                    <i class="fas fa-sort ml-1 text-gray-400"></i>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <span>Promotion</span>
                                    <i class="fas fa-sort ml-1 text-gray-400"></i>
                                </div>
                            </th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="usersTableBody">
                        <?php if (empty($currentPageItems)): ?>
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-users text-gray-300 text-4xl mb-4"></i>
                                        <p>Aucun étudiant trouvé.</p>
                                        <p class="text-sm mt-2">Ajoutez de nouveaux étudiants en cliquant sur le bouton
                                            "Ajouter un étudiant"</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($currentPageItems as $etudiant): ?>
                                <tr class="table-row-hover">
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" name="selected_ids[]"
                                            value="<?php echo htmlspecialchars($etudiant->num_etu); ?>"
                                            class="user-checkbox form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 cursor-pointer">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($etudiant->num_etu); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($etudiant->nom_etu); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($etudiant->prenom_etu); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($etudiant->genre_etu); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($etudiant->email_etu); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($etudiant->promotion_etu); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <button onclick="openUserModal('<?php echo htmlspecialchars($etudiant->num_etu); ?>')"
                                            class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="bg-white rounded-lg shadow-sm p-4 mt-6">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm text-gray-500">
                            Affichage de <?= $startIndex + 1 ?> à <?= min($startIndex + $itemsPerPage, $totalItems) ?> sur
                            <?= $totalItems ?> entrées
                        </div>
                        <div class="flex flex-wrap justify-center gap-2">
                            <?php if ($currentPage > 1): ?>
                                <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=<?= $currentPage - 1 ?><?= !empty($GLOBALS['searchTerm']) ? '&search=' . urlencode($GLOBALS['searchTerm']) : '' ?>"
                                    class="btn-hover px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left mr-1"></i>Précédent
                                </a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $currentPage - 2);
                            $end = min($totalPages, $currentPage + 2);

                            if ($start > 1) {
                                echo '<a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=1' . (!empty($GLOBALS['searchTerm']) ? '&search=' . urlencode($GLOBALS['searchTerm']) : '') . '" class="btn-hover px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                if ($start > 2) {
                                    echo '<span class="px-3 py-2 text-gray-500">...</span>';
                                }
                            }

                            for ($i = $start; $i <= $end; $i++):
                                $searchParam = !empty($GLOBALS['searchTerm']) ? '&search=' . urlencode($GLOBALS['searchTerm']) : '';
                                ?>
                                <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=<?= $i ?><?= $searchParam ?>"
                                    class="btn-hover px-3 py-2 <?= $i === $currentPage ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor;

                            if ($end < $totalPages) {
                                if ($end < $totalPages - 1) {
                                    echo '<span class="px-3 py-2 text-gray-500">...</span>';
                                }
                                $searchParam = !empty($GLOBALS['searchTerm']) ? '&search=' . urlencode($GLOBALS['searchTerm']) : '';
                                echo '<a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=' . $totalPages . $searchParam . '" class="btn-hover px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">' . $totalPages . '</a>';
                            }
                            ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=<?= $currentPage + 1 ?><?= !empty($GLOBALS['searchTerm']) ? '&search=' . urlencode($GLOBALS['searchTerm']) : '' ?>"
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
    <script>
        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = '<?= $GLOBALS['searchTerm'] ?? '' ?>';
            const deleteButton = document.getElementById('deleteButton');

            // Désactiver le bouton de suppression par défaut
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');

            // Si un terme de recherche est présent dans l'URL, l'afficher dans le champ de recherche
            if (searchTerm) {
                searchInput.value = searchTerm;
                // Déclencher l'événement de recherche
                searchInput.dispatchEvent(new Event('input'));
            }

            // Gérer les notifications
            const successNotification = document.getElementById('successNotification');
            const errorNotification = document.getElementById('errorNotification');

            function removeNotification(notification) {
                if (notification) {
                    notification.classList.add('animate__fadeOut');
                    setTimeout(() => notification.remove(), 500);
                }
            }

            if (successNotification) {
                setTimeout(() => removeNotification(successNotification), 5000);
            }

            if (errorNotification) {
                setTimeout(() => removeNotification(errorNotification), 5000);
            }

            // Gérer les checkboxes
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const userCheckboxes = document.querySelectorAll('.user-checkbox');

            // Fonction pour mettre à jour l'état du bouton de suppression
            function updateDeleteButtonState() {
                const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
                const hasChecked = checkedBoxes.length > 0;

                deleteButton.disabled = !hasChecked;
                deleteButton.classList.toggle('opacity-50', !hasChecked);
                deleteButton.classList.toggle('cursor-not-allowed', !hasChecked);

                // Mettre à jour l'état de la case "Tout sélectionner"
                selectAllCheckbox.checked = checkedBoxes.length === userCheckboxes.length && userCheckboxes.length >
                    0;
            }

            // Écouter les changements sur toutes les checkboxes
            document.addEventListener('change', function (e) {
                if (e.target.classList.contains('user-checkbox') || e.target === selectAllCheckbox) {
                    if (e.target === selectAllCheckbox) {
                        // Si c'est la case "Tout sélectionner"
                        userCheckboxes.forEach(checkbox => {
                            checkbox.checked = selectAllCheckbox.checked;
                        });
                    }
                    updateDeleteButtonState();
                }
            });

            // Initialiser l'état du bouton
            updateDeleteButtonState();
        });

        // Manage the user modal
        const userModal = document.getElementById('userModal');
        const userForm = document.getElementById('userForm');
        const userModalTitle = document.getElementById('userModalTitle');
        const userModalSubmitButton = document.getElementById('userModalSubmitButton');
        const searchInput = document.getElementById('searchInput');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const deleteButton = document.getElementById('deleteButton');

        function openUserModal(numEtu = null) {
            if (numEtu) {
                window.location.href =
                    `?page=gestion_etudiants&action=ajouter_des_etudiants&modalAction=edit&num_etu=${numEtu}`;
            } else {
                window.location.href = '?page=gestion_etudiants&action=ajouter_des_etudiants&modalAction=add';
            }
        }

        function closeUserModal() {
            window.location.href = '?page=gestion_etudiants&action=ajouter_des_etudiants';
        }

        // Search functionality
        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#usersTableBody tr');
            let hasResults = false;

            // Convertir les données PHP en JavaScript
            const allEtudiants = <?= json_encode($allEtudiants) ?>;
            const itemsPerPage = <?= $itemsPerPage ?>;

            // Filtrer les étudiants
            const filteredEtudiants = allEtudiants.filter(etudiant => {
                const nom = etudiant.nom_etu.toLowerCase();
                const prenom = etudiant.prenom_etu.toLowerCase();
                return nom.includes(searchTerm) || prenom.includes(searchTerm);
            });

            // Calculer la pagination pour les résultats filtrés
            const totalFilteredItems = filteredEtudiants.length;
            const totalFilteredPages = Math.ceil(totalFilteredItems / itemsPerPage);
            const currentPage = 1; // Toujours commencer à la première page lors d'une recherche
            const startIndex = 0;
            const endIndex = Math.min(itemsPerPage, totalFilteredItems);

            // Mettre à jour l'affichage
            if (filteredEtudiants.length === 0) {
                document.getElementById('usersTableBody').innerHTML = `
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-users text-gray-300 text-4xl mb-4"></i>
                            <p>Aucun étudiant trouvé.</p>
                            <p class="text-sm mt-2">Essayez avec d'autres termes de recherche</p>
                        </div>
                    </td>
                </tr>
            `;
                // Cacher la pagination si aucun résultat
                const paginationContainer = document.querySelector('.bg-white.rounded-lg.shadow-sm.p-4.mt-6');
                if (paginationContainer) {
                    paginationContainer.style.display = 'none';
                }
            } else {
                // Afficher les étudiants de la page courante
                const currentPageItems = filteredEtudiants.slice(startIndex, endIndex);
                let html = '';
                currentPageItems.forEach(etudiant => {
                    html += `
                    <tr class="table-row-hover">
                        <td class="px-4 py-4 text-center">
                            <input type="checkbox" name="selected_ids[]" value="${etudiant.num_etu}"
                                class="user-checkbox form-checkbox h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 cursor-pointer">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">${etudiant.num_etu}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${etudiant.nom_etu}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${etudiant.prenom_etu}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${etudiant.date_naiss_etu}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${etudiant.genre_etu}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${etudiant.email_etu}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${etudiant.promotion_etu}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <button onclick="openUserModal('${etudiant.num_etu}')"
                                class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                `;
                });
                document.getElementById('usersTableBody').innerHTML = html;

                // Mettre à jour la pagination
                const paginationContainer = document.querySelector('.bg-white.rounded-lg.shadow-sm.p-4.mt-6');
                if (paginationContainer) {
                    paginationContainer.style.display = totalFilteredPages > 1 ? 'block' : 'none';

                    // Mettre à jour le texte d'affichage
                    const displayText = paginationContainer.querySelector('.text-sm.text-gray-500');
                    if (displayText) {
                        displayText.textContent =
                            `Affichage de ${startIndex + 1} à ${endIndex} sur ${totalFilteredItems} entrées`;
                    }

                    // Mettre à jour les liens de pagination
                    const paginationLinks = paginationContainer.querySelector(
                        '.flex.flex-wrap.justify-center.gap-2');
                    if (paginationLinks) {
                        let paginationHtml = '';

                        // Bouton Précédent
                        if (currentPage > 1) {
                            paginationHtml += `
                            <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=${currentPage - 1}&search=${encodeURIComponent(searchTerm)}"
                                class="btn-hover px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-chevron-left mr-1"></i>Précédent
                            </a>
                        `;
                        }

                        // Numéros de page
                        const start = Math.max(1, currentPage - 2);
                        const end = Math.min(totalFilteredPages, currentPage + 2);

                        if (start > 1) {
                            paginationHtml += `
                            <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=1&search=${searchTerm}"
                                class="btn-hover px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>
                        `;
                            if (start > 2) {
                                paginationHtml += '<span class="px-3 py-2 text-gray-500">...</span>';
                            }
                        }

                        for (let i = start; i <= end; i++) {
                            paginationHtml += `
                            <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=${i}&search=${searchTerm}"
                                class="btn-hover px-3 py-2 ${i === currentPage ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'} border border-gray-300 rounded-lg text-sm font-medium">
                                ${i}
                            </a>
                        `;
                        }

                        if (end < totalFilteredPages) {
                            if (end < totalFilteredPages - 1) {
                                paginationHtml += '<span class="px-3 py-2 text-gray-500">...</span>';
                            }
                            paginationHtml += `
                            <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=${totalFilteredPages}&search=${searchTerm}"
                                class="btn-hover px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">${totalFilteredPages}</a>
                        `;
                        }

                        // Bouton Suivant
                        if (currentPage < totalFilteredPages) {
                            paginationHtml += `
                            <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=${currentPage + 1}&search=${searchTerm}"
                                class="btn-hover px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Suivant<i class="fas fa-chevron-right ml-1"></i>
                            </a>
                        `;
                        }

                        paginationLinks.innerHTML = paginationHtml;
                    }
                }
            }
        });

        // Fonction pour ouvrir la modale de suppression
        function openDeleteModal() {
            const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
            if (selectedCheckboxes.length === 0) {
                alert('Veuillez sélectionner au moins un étudiant à supprimer.');
                return;
            }
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function confirmDelete() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?page=gestion_etudiants&action=ajouter_des_etudiants';

            const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
            selectedCheckboxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_ids[]';
                input.value = checkbox.value;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }

        // Fonction pour exporter en Excel
        function exporterListe() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const allEtudiants = <?= json_encode($allEtudiants) ?>;

            // Filtrer les étudiants si une recherche est active
            let etudiantsToExport = allEtudiants;
            if (searchTerm) {
                etudiantsToExport = allEtudiants.filter(etudiant => {
                    const nom = etudiant.nom_etu.toLowerCase();
                    const prenom = etudiant.prenom_etu.toLowerCase();
                    return nom.includes(searchTerm) || prenom.includes(searchTerm);
                });
            }

            // Créer le contenu CSV
            let csvContent = "data:text/csv;charset=utf-8,";

            // Ajouter les en-têtes
            csvContent += "Numéro étudiant,Nom,Prénom,Date de naissance,Genre,Email,Promotion\n";

            // Ajouter les données
            etudiantsToExport.forEach(etudiant => {
                const row = [
                    etudiant.num_etu,
                    etudiant.nom_etu,
                    etudiant.prenom_etu,
                    etudiant.date_naiss_etu,
                    etudiant.genre_etu,
                    etudiant.email_etu,
                    etudiant.promotion_etu
                ].map(field => `"${field}"`).join(',');
                csvContent += row + '\n';
            });

            // Créer le lien de téléchargement
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', 'etudiants.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Fonction pour imprimer
        function imprimerListe() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const allEtudiants = <?= json_encode($allEtudiants) ?>;
            const printWindow = window.open('', '_blank');

            // Filtrer les étudiants si une recherche est active
            let etudiantsToPrint = allEtudiants;
            if (searchTerm) {
                etudiantsToPrint = allEtudiants.filter(etudiant => {
                    const nom = etudiant.nom_etu.toLowerCase();
                    const prenom = etudiant.prenom_etu.toLowerCase();
                    return nom.includes(searchTerm) || prenom.includes(searchTerm);
                });
            }

            // Créer le contenu HTML pour l'impression
            let html = `
            <html>
                <head>
                    <title>Liste des étudiants</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f5f5f5; }
                        @media print {
                            body { margin: 0; padding: 15px; }
                        }
                    </style>
                </head>
                <body>
                    <h2>Liste des étudiants</h2>
                    ${searchTerm ? `<p>Résultats de la recherche pour : "${searchTerm}"</p>` : ''}
                    <table>
                        <thead>
                            <tr>
                                <th>Numéro étudiant</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Date de naissance</th>
                                <th>Genre</th>
                                <th>Email</th>
                                <th>Promotion</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

            // Ajouter les données
            etudiantsToPrint.forEach(etudiant => {
                html += `
                <tr>
                    <td>${etudiant.num_etu}</td>
                    <td>${etudiant.nom_etu}</td>
                    <td>${etudiant.prenom_etu}</td>
                    <td>${etudiant.date_naiss_etu}</td>
                    <td>${etudiant.genre_etu}</td>
                    <td>${etudiant.email_etu}</td>
                    <td>${etudiant.promotion_etu}</td>
                </tr>
            `;
            });

            html += `
                        </tbody>
                    </table>
                </body>
            </html>
        `;

            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }
    </script>
</body>

</html>