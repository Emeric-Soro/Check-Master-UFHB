<?php
// Connexion à la base de données et récupération des données
$listeGroupes = $GLOBALS['listeGroupes'];
$listeTraitements = $GLOBALS['listeTraitements'];
$selectedGroupe = $GLOBALS['selectedGroupe'] ?? null;
$attributionsGroupe = $GLOBALS['attributionsGroupe'] ?? [];
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';

// Gestion de la recherche
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchType = isset($_GET['search_type']) ? $_GET['search_type'] : '';

// Filtrer les groupes si une recherche est effectuée
if ($searchType === 'groupe' && !empty($searchTerm)) {
    $listeGroupes = array_filter($listeGroupes, function($groupe) use ($searchTerm) {
        return stripos($groupe->lib_GU, $searchTerm) !== false;
    });
}

// Filtrer les traitements si une recherche est effectuée
if ($searchType === 'traitement' && !empty($searchTerm)) {
    $listeTraitements = array_filter($listeTraitements, function($traitement) use ($searchTerm) {
        return stripos($traitement->label_traitement, $searchTerm) !== false;
    });
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Attributions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

    /* Centrage du message quand aucun groupe n'est sélectionné */
    #noSelectionMessage {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        min-height: 300px;
    }

    /* Amélioration du scroll pour la liste des groupes */
    #groupesList {
        max-height: calc(100vh - 300px);
        overflow-y: auto;
    }

    /* Style pour les éléments de traitement */
    .traitement-item {
        transition: all 0.3s ease;
    }

    .traitement-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .traitement-checkbox:checked+label {
        color: #059669;
    }

    /* Style pour les boutons de groupe */
    .groupe-btn {
        transition: all 0.2s ease;
    }

    .groupe-btn:hover {
        background-color: #f0fdf4;
    }

    .groupe-btn.selected {
        background-color: #ecfdf5;
        border-left: 4px solid #059669;
    }

    /* Animation pour la modale */
    .modal-enter {
        animation: modalEnter 0.3s ease-out;
    }

    @keyframes modalEnter {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Style pour le compteur d'attributions */
    #attributionCounter {
        transition: all 0.3s ease;
    }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">

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
        <header class="mb-8">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
                        <i class="fas fa-tasks mr-3 text-emerald-600"></i>
                        Gestion des Attributions
                    </h1>
                    <p class="text-gray-600 mt-2">Attribuez des traitements aux groupes d'utilisateurs</p>
                </div>

            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Liste des groupes -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                <div class="bg-gradient-to-r from-green-500 to-green-600 p-4">
                    <h3 class="text-lg font-semibold text-white">
                        <i class="fas fa-users mr-2"></i>
                        Groupes d'utilisateurs
                    </h3>
                    <p class="text-green-100 text-sm mt-1">Sélectionnez un groupe pour gérer ses traitements</p>
                </div>

                <div class="p-4">
                    <form method="GET" class="mb-4">
                        <input type="hidden" name="page" value="parametres_generaux">
                        <input type="hidden" name="action" value="gestion_attribution">
                        <input type="hidden" name="search_type" value="groupe">
                        <div class="relative">
                            <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>"
                                placeholder="Rechercher un groupe..."
                                class="pl-10 pr-4 py-2 rounded-lg border border-gray-300 w-full">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </form>

                    <div class="space-y-2 overflow-y-auto min-h-screen" id="groupesList">
                        <?php foreach ($listeGroupes as $groupe): ?>
                        <a href="?page=parametres_generaux&action=gestion_attribution&groupe=<?= $groupe->id_GU ?>"
                            class="block w-full text-left px-4 py-3 rounded-lg transition-all groupe-btn <?= ($selectedGroupe && $selectedGroupe->id_GU == $groupe->id_GU) ? 'selected' : '' ?>">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div
                                        class="w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center mr-3">
                                        <i class="fas fa-user-group text-sm"></i>
                                    </div>
                                    <span
                                        class="font-medium text-gray-700"><?= htmlspecialchars($groupe->lib_GU) ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Détails du groupe et attributions -->
            <div class="lg:col-span-2">
                <div id="attributionContainer"
                    class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 h-full flex flex-col">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 p-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white">
                                <i class="fas fa-cogs mr-2"></i>
                                Gestion des traitements
                            </h3>
                            <span id="attributionCounter"
                                class="bg-white text-green-700 text-sm px-3 py-1 rounded-full font-medium">
                                <?= count($attributionsGroupe) ?>
                                traitement<?= count($attributionsGroupe) > 1 ? 's' : '' ?>
                                attribué<?= count($attributionsGroupe) > 1 ? 's' : '' ?>
                            </span>
                        </div>
                        <p class="text-green-100 text-sm mt-1">Attribuez des traitements au groupe sélectionné</p>
                    </div>

                    <?php if ($selectedGroupe): ?>
                    <div id="attributionContent" class="p-6 flex-1 flex flex-col">
                        <div class="mb-6">
                            <div class="flex items-center mb-4">
                                <div
                                    class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mr-3">
                                    <i class="fas fa-user-group text-green-500"></i>
                                </div>
                                <div>
                                    <h4 id="selectedGroupeName" class="text-lg font-semibold text-gray-900">
                                        <?= htmlspecialchars($selectedGroupe->lib_GU) ?>
                                    </h4>
                                    <p class="text-gray-500 text-sm">Sélectionnez les traitements à attribuer</p>
                                </div>
                            </div>

                            <form method="GET" class="mb-4">
                                <input type="hidden" name="page" value="parametres_generaux">
                                <input type="hidden" name="action" value="gestion_attribution">
                                <input type="hidden" name="groupe" value="<?= $selectedGroupe->id_GU ?>">
                                <input type="hidden" name="search_type" value="traitement">
                                <div class="relative">
                                    <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>"
                                        placeholder="Filtrer les traitements..."
                                        class="pl-10 pr-4 py-2 rounded-lg border border-gray-300 w-full">
                                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                </div>
                            </form>
                        </div>

                        <form method="POST" class="space-y-4 flex-1 flex flex-col">
                            <input type="hidden" name="id_GU" value="<?= $selectedGroupe->id_GU ?>">

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php if (!empty($listeTraitements)): ?>
                                <?php foreach ($listeTraitements as $traitement): ?>
                                <div
                                    class="traitement-item border border-gray-200 rounded-lg p-4 hover:shadow-sm transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="relative">
                                                <input type="checkbox" id="traitement_<?= $traitement->id_traitement ?>"
                                                    name="traitements[]" value="<?= $traitement->id_traitement ?>"
                                                    class="h-5 w-5 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500"
                                                    <?= in_array($traitement->id_traitement, array_column($attributionsGroupe, 'id_traitement')) ? 'checked' : '' ?>>
                                            </div>
                                            <label for="traitement_<?= $traitement->id_traitement ?>"
                                                class="text-gray-700 font-medium cursor-pointer hover:text-emerald-600 transition-colors">
                                                <?= htmlspecialchars($traitement->label_traitement) ?>
                                            </label>
                                        </div>
                                        <?php if (isset($traitement->description)): ?>
                                        <button type="button"
                                            onclick="showTraitementDetails(<?= $traitement->id_traitement ?>)"
                                            class="text-gray-400 hover:text-emerald-600 transition-colors">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <div class="col-span-full text-center text-gray-500 py-4">
                                    Aucun traitement disponible
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                                <a href="?page=parametres_generaux&action=gestion_attribution&groupe=<?= $selectedGroupe->id_GU ?>"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all">
                                    <i class="fas fa-undo mr-2"></i>Réinitialiser
                                </a>
                                <button type="submit"
                                    class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm font-medium hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all">
                                    <i class="fas fa-save mr-2"></i>Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="p-6 text-center text-gray-500">
                        Veuillez sélectionner un groupe d'utilisateurs
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale de détails du traitement -->
    <div id="traitementDetailsModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900" id="traitementDetailsTitle"></h3>
                <button type="button" onclick="closeTraitementDetails()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="text-gray-600" id="traitementDetailsContent"></div>
        </div>
    </div>

    <script>
    // Variables globales
    let currentGroupeId = null;
    let currentGroupeName = null;
    const groupeButtons = document.querySelectorAll('.groupe-btn');
    const traitementItems = document.querySelectorAll('.traitement-item');
    const traitementCheckboxes = document.querySelectorAll('.traitement-checkbox');
    const selectedGroupeId = document.getElementById('selectedGroupeId');
    const selectedGroupeName = document.getElementById('selectedGroupeName');
    const attributionForm = document.getElementById('attributionForm');
    const saveButton = document.getElementById('saveButton');
    const noSelectionMessage = document.getElementById('noSelectionMessage');
    const attributionContent = document.getElementById('attributionContent');
    const attributionCounter = document.getElementById('attributionCounter');
    const searchInput = document.getElementById('searchInput');
    const mobileSearchGroupe = document.getElementById('mobileSearchGroupe');
    const searchTraitements = document.getElementById('searchTraitements');
    const traitementDetailsModal = document.getElementById('traitementDetailsModal');

    // Structure pour stocker les attributions existantes
    const existingAttributions = attributionsMap || {};

    // Initialisation
    document.addEventListener('DOMContentLoaded', function() {
        setupSearch();
        saveButton.disabled = true;
        saveButton.classList.add('opacity-50', 'cursor-not-allowed');

        // Sélection automatique si groupe dans l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const groupeIdFromUrl = urlParams.get('groupe');

        if (groupeIdFromUrl) {
            const groupeButton = document.querySelector(`.groupe-btn[data-groupe-id="${groupeIdFromUrl}"]`);
            if (groupeButton) {
                const groupeId = groupeButton.dataset.groupeId;
                const groupeName = groupeButton.dataset.groupeName;
                selectGroupe(groupeId, groupeName);
            }
        }

        // Fermeture de la modale au clic en dehors
        window.addEventListener('click', function(e) {
            if (e.target === traitementDetailsModal) {
                closeTraitementDetails();
            }
        });
    });

    // Configuration des champs de recherche
    function setupSearch() {
        // Recherche globale
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            filterGroupes(searchTerm);
            if (currentGroupeId) filterTraitements(searchTerm);
        });

        // Recherche mobile pour les groupes
        mobileSearchGroupe.addEventListener('input', function(e) {
            filterGroupes(e.target.value.toLowerCase());
        });

        // Recherche des traitements
        searchTraitements.addEventListener('input', function(e) {
            filterTraitements(e.target.value.toLowerCase());
        });
    }

    // Filtrer les groupes
    function filterGroupes(searchTerm) {
        document.querySelectorAll('.groupe-btn').forEach(btn => {
            const groupeName = btn.dataset.groupeName.toLowerCase();
            btn.style.display = groupeName.includes(searchTerm) ? 'block' : 'none';
        });
    }

    // Filtrer les traitements
    function filterTraitements(searchTerm) {
        const items = document.querySelectorAll('.traitement-item');
        items.forEach(item => {
            const traitementName = item.dataset.traitementName.toLowerCase();
            const traitementId = item.dataset.traitementId;
            const isVisible = traitementName.includes(searchTerm.toLowerCase()) ||
                traitementId.toString().includes(searchTerm);
            item.style.display = isVisible ? 'block' : 'none';
        });
    }

    // Sélectionner un groupe
    function selectGroupe(id, name) {
        currentGroupeId = id;
        currentGroupeName = name;
        selectedGroupeId.value = id;
        selectedGroupeName.textContent = name;

        // Mettre à jour l'URL
        const newUrl = '?page=parametres_generaux&action=gestion_attribution&groupe=' + id;
        window.history.replaceState({
            path: newUrl
        }, '', newUrl);

        // Activer le bouton d'enregistrement
        saveButton.disabled = false;
        saveButton.classList.remove('opacity-50', 'cursor-not-allowed');

        // Vérifier si 'groupe' est présent dans l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const hasGroupParam = urlParams.has('groupe');

        if (noSelectionMessage && attributionContent) {
            if (hasGroupParam) {
                // S'il y a un paramètre 'groupe' dans l'URL
                noSelectionMessage.classList.add('hidden');
                attributionContent.classList.remove('hidden');
            } else {
                // S'il n'y a pas de paramètre 'groupe'
                noSelectionMessage.classList.remove('hidden');
                attributionContent.classList.add('hidden');
            }
        }

        // Mettre à jour l'apparence des boutons
        groupeButtons.forEach(btn => {
            btn.classList.toggle('selected', btn.dataset.groupeId == id);
        });

        // Récupérer les attributions pour ce groupe
        const groupeAttributions = existingAttributions[id] || [];
        const groupeAttributionsNumeric = groupeAttributions.map(attr => Number(attr.id_traitement));

        // Mettre à jour les cases à cocher
        traitementCheckboxes.forEach(checkbox => {
            const traitementId = Number(checkbox.value);
            checkbox.checked = groupeAttributionsNumeric.includes(traitementId);
        });

        updateAttributionCounter();
    }

    // Mettre à jour le compteur d'attributions
    function updateAttributionCounter() {
        const count = document.querySelectorAll('.traitement-checkbox:checked').length;
        attributionCounter.textContent =
            `${count} traitement${count !== 1 ? 's' : ''} attribué${count !== 1 ? 's' : ''}`;
        attributionCounter.classList.toggle('bg-emerald-100', count > 0);
        attributionCounter.classList.toggle('text-emerald-700', count > 0);
    }

    // Réinitialiser le formulaire
    function resetForm() {
        if (currentGroupeId) {
            const groupeAttributions = existingAttributions[currentGroupeId] || [];
            const groupeAttributionsNumeric = groupeAttributions.map(attr => Number(attr.id_traitement));

            traitementCheckboxes.forEach(checkbox => {
                const traitementId = Number(checkbox.value);
                checkbox.checked = groupeAttributionsNumeric.includes(traitementId);
            });

            updateAttributionCounter();

            // Notification visuelle
            showNotification('Les attributions ont été réinitialisées', 'info');
        }
    }

    // Afficher une notification
    function showNotification(message, type = 'info') {
        const colors = {
            info: 'bg-blue-100 border-blue-500 text-blue-700',
            success: 'bg-green-100 border-green-500 text-green-700',
            error: 'bg-red-100 border-red-500 text-red-700'
        };

        const icons = {
            info: 'fa-info-circle',
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle'
        };

        const notification = document.createElement('div');
        notification.className =
            `fixed top-4 right-4 border-l-4 p-4 rounded-lg shadow-md flex items-center ${colors[type]} animate__animated animate__fadeInRight`;
        notification.innerHTML = `<i class="fas ${icons[type]} mr-3"></i><span>${message}</span>`;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('animate__fadeOutRight');
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }

    // Gérer la soumission du formulaire
    attributionForm.addEventListener('submit', function(e) {
        if (!currentGroupeId) {
            e.preventDefault();
            showNotification('Veuillez sélectionner un groupe d\'utilisateurs.', 'error');
            return;
        }

        // Vérifier les modifications
        const currentAttributions = Array.from(document.querySelectorAll('.traitement-checkbox:checked')).map(
            cb => Number(cb.value));
        const originalAttributions = (existingAttributions[currentGroupeId] || []).map(attr => Number(attr
            .id_traitement));

        const noChanges = currentAttributions.length === originalAttributions.length &&
            currentAttributions.every(attr => originalAttributions.includes(attr)) &&
            originalAttributions.every(attr => currentAttributions.includes(attr));

        if (noChanges) {
            e.preventDefault();
            showNotification('Aucune modification n\'a été effectuée.', 'info');
            return;
        }

        // Effet de chargement
        saveButton.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i>Enregistrement...';
        saveButton.disabled = true;
    });

    // Fonction pour afficher les détails d'un traitement
    function showTraitementDetails(traitementId) {
        const traitement = <?= json_encode($listeTraitements) ?>.find(t => t.id_traitement === traitementId);
        if (traitement) {
            document.getElementById('traitementDetailsTitle').textContent = traitement.label_traitement;
            document.getElementById('traitementDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <p><strong>Description:</strong> ${traitement.description || 'Non disponible'}</p>
                    <p><strong>ID:</strong> ${traitement.id_traitement}</p>
                    ${traitement.permissions ? `<p><strong>Permissions:</strong> ${traitement.permissions}</p>` : ''}
                </div>
            `;
            traitementDetailsModal.classList.remove('hidden');
        }
    }

    // Fonction pour fermer la modale de détails
    function closeTraitementDetails() {
        traitementDetailsModal.classList.add('hidden');
    }
    </script>

    <!-- Passer les attributions à JavaScript -->
    <script>
    const attributionsMap = <?php echo json_encode($GLOBALS['attributionsMap'] ?? []); ?>;
    </script>

    <?php if (isset($_GET['debug']) && $_GET['debug'] === 'attributions'): ?>
    <div class="fixed bottom-4 left-4 p-4 bg-gray-800 text-white rounded-lg text-xs max-w-lg max-h-64 overflow-auto">
        <h4 class="font-bold mb-2">Débug des attributions:</h4>
        <pre><?php echo json_encode($GLOBALS['attributionsMap'] ?? [], JSON_PRETTY_PRINT); ?></pre>
    </div>
    <?php endif; ?>
</body>

</html>