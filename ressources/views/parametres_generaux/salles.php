<?php
// Traitement des formulaires et récupération des données
$salle_a_modifier = null;
$messageErreur = '';
$messageSuccess = '';

try {
    $pdo = Database::getConnection();

    // Ajout ou modification
    if (isset($_POST['btn_add_salle']) || isset($_POST['btn_modifier_salle'])) {
        $lib_salle = $_POST['lib_salle'];

        if (!empty($_POST['id_salle'])) {
            // MODIFICATION
            $stmt = $pdo->prepare("UPDATE salles SET lib_salle = ? WHERE id_salle = ?");
            if ($stmt->execute([trim($lib_salle), $_POST['id_salle']])) {
                $messageSuccess = "Salle modifiée avec succès.";
            } else {
                $messageErreur = "Erreur lors de la modification de la salle.";
            }
        } else {
            // AJOUT - Vérifier si la salle existe déjà
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM salles WHERE lib_salle = ?");
            $checkStmt->execute([trim($lib_salle)]);
            if ($checkStmt->fetchColumn() > 0) {
                $messageErreur = "Une salle avec ce nom existe déjà.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO salles (lib_salle) VALUES (?)");
                if ($stmt->execute([trim($lib_salle)])) {
                    $messageSuccess = "Salle ajoutée avec succès.";
                } else {
                    $messageErreur = "Erreur lors de l'ajout de la salle.";
                }
            }
        }
    }

    // Suppression multiple
    if (isset($_POST['submit_delete_multiple']) && $_POST['submit_delete_multiple'] == '1') {
        $selected_ids = $_POST['selected_ids'] ?? [];

        if (!empty($selected_ids)) {
            $success = true;
            foreach ($selected_ids as $id) {
                // Vérifier si la salle est utilisée
                $usageStmt = $pdo->prepare("SELECT COUNT(*) FROM programmer WHERE id_salle = ?");
                $usageStmt->execute([$id]);
                if ($usageStmt->fetchColumn() > 0) {
                    $messageErreur = "Une ou plusieurs salles sont utilisées dans des programmations et ne peuvent pas être supprimées.";
                    $success = false;
                    break;
                }

                $stmt = $pdo->prepare("DELETE FROM salles WHERE id_salle = ?");
                if (!$stmt->execute([$id])) {
                    $success = false;
                    break;
                }
            }

            if ($success && empty($messageErreur)) {
                $messageSuccess = "Salles supprimées avec succès.";
            } elseif (empty($messageErreur)) {
                $messageErreur = "Erreur lors de la suppression des salles.";
            }
        }
    }

    // Récupération de la salle à modifier pour affichage dans le formulaire
    if (isset($_GET['id_salle'])) {
        $stmt = $pdo->prepare("SELECT * FROM salles WHERE id_salle = ?");
        $stmt->execute([$_GET['id_salle']]);
        $salle_a_modifier = $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Pagination
    $page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Search functionality
    $search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';

    // Récupération des salles avec recherche
    if (!empty($search)) {
        $stmt = $pdo->prepare("SELECT * FROM salles WHERE lib_salle LIKE ? ORDER BY lib_salle ASC");
        $stmt->execute(['%' . $search . '%']);
        $listeSalles = $stmt->fetchAll(PDO::FETCH_OBJ);
    } else {
        $stmt = $pdo->query("SELECT * FROM salles ORDER BY lib_salle ASC");
        $listeSalles = $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Total pages calculation
    $total_items = count($listeSalles);
    $total_pages = ceil($total_items / $limit);

    // Slice the array for pagination
    $listeSalles = array_slice($listeSalles, $offset, $limit);

} catch (Exception $e) {
    error_log('Erreur gestionSalles: ' . $e->getMessage());
    $messageErreur = "Erreur de connexion à la base de données.";
    $listeSalles = [];
    $total_pages = 0;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Salles</title>
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

        /* Boutons avec dégradés */
        .btn-gradient-primary {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }

        .btn-gradient-secondary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
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
    </style>
</head>

<body style="background-color: #DFF2FF;">
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

    <div class="min-h-screen">
        <main class="container mx-auto px-4 py-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-600">
                    <i class="fas fa-building mr-2 text-green-600"></i>
                    Gestion des Salles
                </h2>
            </div>

            <!-- Formulaire -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-600 mb-4 flex items-center">
                    <i
                        class="fas <?= isset($_GET['id_salle']) ? 'fa-edit text-green-500' : 'fa-plus-circle text-green-500' ?> mr-2"></i>
                    <?php if (isset($_GET['id_salle'])): ?>
                        Modifier la salle
                    <?php else: ?>
                        Ajouter une salle
                    <?php endif; ?>
                </h3>

                <form method="POST" action="?page=parametres_generaux&action=salles" id="salleForm">
                    <?php if ($salle_a_modifier): ?>
                        <input type="hidden" name="id_salle" value="<?= htmlspecialchars($salle_a_modifier->id_salle) ?>">
                    <?php endif; ?>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom de la salle</label>
                        <input type="text" name="lib_salle" required placeholder="Entrez le nom de la salle"
                            value="<?= $salle_a_modifier ? htmlspecialchars($salle_a_modifier->lib_salle) : '' ?>"
                            class="form-input w-75 px-3 py-2 border border-gray-300 rounded-md focus:outline-4 focus:outline-green-300 focus:ring-green-300 focus:border-green-300 focus:ring-opacity-50 transition-all duration-200">
                    </div>
                    <div class="flex justify-between mt-6">
                        <?php if (isset($_GET['id_salle'])): ?>
                            <button type="button" name="btn_annuler" id="btnAnnuler"
                                onclick="window.location.href='?page=parametres_generaux&action=salles'"
                                class="btn-hover px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                <i class="fas fa-times mr-2"></i>Annuler
                            </button>
                            <button type="submit" name="btn_modifier_salle"
                                class="btn-hover px-4 py-2 btn-gradient-primary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <i class="fas fa-save mr-2"></i>Modifier
                            </button>

                        <?php else: ?>
                            <div></div>
                            <button type="submit" name="btn_add_salle"
                                class="btn-hover px-4 py-2 btn-gradient-primary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <i class="fas fa-plus mr-2"></i>Ajouter une salle
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Zone de recherche et actions -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-600 mb-4 flex items-center">
                    <i class="fas fa-list-ul text-green-500 mr-2"></i>
                    Liste des salles
                </h3>
                <div class="flex items-center justify-between mb-6">
                    <!-- Barre de recherche -->
                    <div class="flex-1 max-w-md">
                        <form action="" method="GET" class="flex gap-3">
                            <input type="hidden" name="page" value="parametres_generaux">
                            <input type="hidden" name="action" value="salles">
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
                        <button type="button" id="deleteSelectedBtn" disabled
                            class="btn-hover px-4 py-2 btn-gradient-danger text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-trash-alt mr-2"></i>Supprimer
                        </button>
                    </div>
                </div>

                <!-- Tableau -->
                <div class="overflow-x-auto">
                    <form method="POST" action="?page=parametres_generaux&action=salles" id="formListeSalles">
                        <input type="hidden" name="submit_delete_multiple" id="submitDeleteHidden" value="0">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="w-12 px-3 py-3">
                                        <input type="checkbox" id="selectAllCheckbox"
                                            class="rounded border-gray-300 text-green-600 focus:ring-green-500 transition-all duration-200">
                                    </th>
                                    <th
                                        class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-hashtag mr-1"></i>ID
                                    </th>
                                    <th
                                        class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-building mr-1"></i>Nom de la salle
                                    </th>
                                    <th
                                        class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-cog mr-1"></i>Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($listeSalles)): ?>
                                    <?php foreach ($listeSalles as $salle): ?>
                                        <tr class="hover:bg-green-50 transition-colors duration-200">
                                            <td class="px-3 py-4">
                                                <input type="checkbox" name="selected_ids[]"
                                                    value="<?= htmlspecialchars($salle->id_salle) ?>"
                                                    class="row-checkbox rounded border-gray-300 text-green-600 focus:ring-green-500 transition-all duration-200">
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-900 text-center">
                                                <?= htmlspecialchars($salle->id_salle) ?>
                                            </td>
                                            <td class="px-3 py-4 text-sm text-gray-900 font-medium text-center">
                                                <?= htmlspecialchars($salle->lib_salle) ?>
                                            </td>
                                            <td class="px-3 py-4 text-sm text-center">
                                                <a href="?page=parametres_generaux&action=salles&id_salle=<?= $salle->id_salle ?>"
                                                    class="text-blue-600 hover:text-blue-800 mr-3 transition-colors duration-200">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-3 py-4 text-sm text-gray-500 text-center">
                                            <i class="fas fa-info-circle mr-2"></i>Aucune salle enregistrée.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>

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
                                <a href="?page=parametres_generaux&action=salles&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"
                                    class="btn-hover px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left mr-1"></i>Précédent
                                </a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);

                            for ($i = $start; $i <= $end; $i++):
                                ?>
                                <a href="?page=parametres_generaux&action=salles&p=<?= $i ?>&search=<?= urlencode($search) ?>"
                                    class="btn-hover px-3 py-2 <?= $i === $page ? 'btn-gradient-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=parametres_generaux&action=salles&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"
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
        class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden animate__animated animate__fadeIn">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 animate__animated animate__zoomIn">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmation de suppression</h3>
                <p class="text-sm text-gray-500 mb-6">
                    <i class="fas fa-info-circle mr-2"></i>
                    Êtes-vous sûr de vouloir supprimer les salles sélectionnées ?
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

    <script>
        // Gestion des checkboxes et du bouton de suppression
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const deleteButton = document.getElementById('deleteSelectedBtn');
        const deleteModal = document.getElementById('deleteModal');
        const confirmDelete = document.getElementById('confirmDelete');
        const cancelDelete = document.getElementById('cancelDelete');
        const formListeSalles = document.getElementById('formListeSalles');
        const submitDeleteHidden = document.getElementById('submitDeleteHidden');

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
                selectAllCheckbox.checked = checkedBoxes.length === allCheckboxes.length && allCheckboxes.length > 0;
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
            formListeSalles.submit();
        });

        cancelDelete.addEventListener('click', function () {
            deleteModal.classList.add('hidden');
        });

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

</body>

</html>