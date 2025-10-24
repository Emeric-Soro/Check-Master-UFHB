<?php
require_once __DIR__ . '/../../../app/utils/permissions.php';
?>
<!-- Contenu de la page de suivi des réclamations -->

<!-- Messages -->
<?php if (isset($_SESSION['message'])): ?>
<div
    class="mb-6 p-4 rounded-lg <?php echo $_SESSION['message']['type'] === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300'; ?>">
    <i
        class="fas <?php echo $_SESSION['message']['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
    <?php echo htmlspecialchars($_SESSION['message']['text']); ?>
</div>
<?php unset($_SESSION['message']); ?>
<?php endif; ?>

<!-- Filtres -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">Filtrer les réclamations</h2>
    <form method="GET" class="flex flex-wrap gap-4">
        <input type="hidden" name="page" value="gestion_reclamations">
        <input type="hidden" name="action" value="suivi_historique_reclamation">

        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
            <select name="status"
                class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <option value="all"
                    <?php echo (!isset($_GET['status']) || $_GET['status'] === 'all') ? 'selected' : ''; ?>>Tous les
                    statuts</option>
                <option value="en_attente"
                    <?php echo (isset($_GET['status']) && $_GET['status'] === 'en_attente') ? 'selected' : ''; ?>>En
                    attente</option>
                <option value="resolue"
                    <?php echo (isset($_GET['status']) && $_GET['status'] === 'resolue') ? 'selected' : ''; ?>>Résolue
                </option>
                <option value="rejetee"
                    <?php echo (isset($_GET['status']) && $_GET['status'] === 'rejetee') ? 'selected' : ''; ?>>Rejetée
                </option>
            </select>
        </div>

        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select name="type"
                class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <option value="all" <?php echo (!isset($_GET['type']) || $_GET['type'] === 'all') ? 'selected' : ''; ?>>
                    Tous les types</option>
                <option value="academic"
                    <?php echo (isset($_GET['type']) && $_GET['type'] === 'academic') ? 'selected' : ''; ?>>Académique
                </option>
                <option value="financial"
                    <?php echo (isset($_GET['type']) && $_GET['type'] === 'financial') ? 'selected' : ''; ?>>Financière
                </option>
                <option value="administrative"
                    <?php echo (isset($_GET['type']) && $_GET['type'] === 'administrative') ? 'selected' : ''; ?>>
                    Administrative</option>
                <option value="technical"
                    <?php echo (isset($_GET['type']) && $_GET['type'] === 'technical') ? 'selected' : ''; ?>>Technique
                </option>
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-200">
                <i class="fas fa-search mr-2"></i>
                Appliquer
            </button>
        </div>

        <div class="flex items-end">
            <a href="?page=gestion_reclamations&action=suivi_historique_reclamation"
                class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md transition duration-200">
                <i class="fas fa-undo mr-2"></i>
                Réinitialiser
            </a>
        </div>
    </form>
</div>

<!-- Statistiques -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total</p>
                <h3 class="text-2xl font-bold"><?php echo isset($statistiques) ? $statistiques['total'] : 0; ?></h3>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <i class="fas fa-file-alt text-blue-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">En attente</p>
                <h3 class="text-2xl font-bold"><?php echo isset($statistiques) ? $statistiques['en_attente'] : 0; ?>
                </h3>
            </div>
            <div class="bg-yellow-100 p-3 rounded-full">
                <i class="fas fa-clock text-yellow-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Résolues</p>
                <h3 class="text-2xl font-bold"><?php echo isset($statistiques) ? $statistiques['resolue'] : 0; ?></h3>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <i class="fas fa-check-circle text-green-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Rejetées</p>
                <h3 class="text-2xl font-bold"><?php echo isset($statistiques) ? $statistiques['rejetee'] : 0; ?></h3>
            </div>
            <div class="bg-red-100 p-3 rounded-full">
                <i class="fas fa-times-circle text-red-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Liste des réclamations -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-700">
            Vos réclamations
            <?php if (isset($reclamations) && !empty($reclamations)): ?>
            <span class="text-sm text-gray-500 font-normal">
                (<?php echo count($reclamations); ?> résultat<?php echo count($reclamations) > 1 ? 's' : ''; ?>)
            </span>
            <?php endif; ?>
        </h2>
    </div>

    <?php if (empty($reclamations)): ?>
    <div class="text-center py-12">
        <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune réclamation trouvée</h3>
        <p class="text-gray-500 mb-4">
            <?php if (isset($_GET['status']) && $_GET['status'] !== 'all'): ?>
            Aucune réclamation ne correspond aux filtres sélectionnés.
            <?php else: ?>
            Vous n'avez pas encore soumis de réclamation.
            <?php endif; ?>
        </p>
        <a href="?page=gestion_reclamations&action=soumettre_reclamation"
            class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
            <i class="fas fa-plus mr-2"></i>
            Créer une réclamation
        </a>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        ID</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Sujet</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Statut</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($reclamations as $rec): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        REC-<?php echo $rec['id_reclamation']; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <div class="max-w-xs truncate"
                            title="<?php echo htmlspecialchars($rec['titre_reclamation']); ?>">
                            <?php echo htmlspecialchars($rec['titre_reclamation']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php
                                switch($rec['type_reclamation']) {
                                    case 'Académique': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'Financière': echo 'bg-green-100 text-green-800'; break;
                                    case 'Administrative': echo 'bg-gray-100 text-gray-800'; break;
                                    case 'Technique': echo 'bg-purple-100 text-purple-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                            <?php echo htmlspecialchars($rec['type_reclamation']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('d/m/Y', strtotime($rec['date_creation'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php
                                switch($rec['statut_reclamation']) {
                                    case 'En attente': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'En cours': echo 'bg-purple-100 text-purple-800'; break;
                                    case 'Résolue': echo 'bg-green-100 text-green-800'; break;
                                    case 'Rejetée': echo 'bg-red-100 text-red-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                            <i class="fas
                                        <?php
                                    switch($rec['statut_reclamation']) {
                                        case 'En attente': echo 'fa-clock'; break;
                                        case 'En cours': echo 'fa-spinner'; break;
                                        case 'Résolue': echo 'fa-check-circle'; break;
                                        case 'Rejetée': echo 'fa-times-circle'; break;
                                        default: echo 'fa-question-circle';
                                    }
                                    ?> mr-1"></i>
                            <?php echo htmlspecialchars($rec['statut_reclamation']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="voirDetailsReclamation(<?php echo $rec['id_reclamation']; ?>)"
                            class="text-blue-600 hover:text-blue-900 mr-3 transition-colors">
                            <i class="fas fa-eye"></i> Voir détail
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
    <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center">
        <div class="text-sm text-gray-500">
            Affichage de
            <span class="font-medium"><?php echo (($page - 1) * 10) + 1; ?></span>
            à
            <span class="font-medium"><?php echo min($page * 10, $totalReclamations); ?></span>
            sur
            <span class="font-medium"><?php echo $totalReclamations; ?></span>
            réclamations
        </div>
        <div class="flex space-x-2">
            <?php if ($page > 1): ?>
            <a href="?page=gestion_reclamations&action=suivi_reclamation&p=<?php echo $page - 1; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['type']) ? '&type=' . urlencode($_GET['type']) : ''; ?>"
                class="px-3 py-1 border rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fas fa-chevron-left mr-1"></i>
                Précédent
            </a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
            <a href="?page=gestion_reclamations&action=suivi_reclamation&p=<?php echo $page + 1; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['type']) ? '&type=' . urlencode($_GET['type']) : ''; ?>"
                class="px-3 py-1 border rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                Suivant
                <i class="fas fa-chevron-right ml-1"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal pour les détails des réclamations -->
<div id="detailsModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-4xl w-full mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-file-alt mr-2 text-blue-500"></i>
                Détails de la réclamation
            </h2>
            <button onclick="fermerModalDetails()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div id="modalContent">
            <!-- Le contenu sera chargé dynamiquement -->
        </div>
    </div>
</div>
</div>

<script>
function voirDetailsReclamation(reclamationId) {
    // Afficher la modal
    document.getElementById('detailsModal').classList.remove('hidden');
    document.getElementById('detailsModal').classList.add('flex');

    // Afficher un loader
    document.getElementById('modalContent').innerHTML = `
            <div class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                <span class="ml-2 text-gray-600">Chargement des détails...</span>
            </div>
        `;

    // Charger les détails via AJAX
    fetch(`?page=gestion_reclamations&action=get_reclamation_details&id=${reclamationId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors du chargement');
            }
            return response.text();
        })
        .then(html => {
            document.getElementById('modalContent').innerHTML = html;
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('modalContent').innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Erreur lors du chargement des détails</p>
                    </div>
                `;
        });
}

function fermerModalDetails() {
    document.getElementById('detailsModal').classList.add('hidden');
    document.getElementById('detailsModal').classList.remove('flex');
}

// Fermer la modal en cliquant à l'extérieur
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('detailsModal');
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            fermerModalDetails();
        }
    });
});
</script>