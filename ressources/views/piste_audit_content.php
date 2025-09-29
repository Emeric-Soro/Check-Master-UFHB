<?php

require_once __DIR__ . '/../../app/utils/StyleManager.php';
function getActionColor($action) {
    switch ($action) {
        case 'Création':
            return 'bg-primary-100 text-primary-800';
        case 'Modification':
            return 'bg-blue-100 text-blue-800';
        case 'Suppression':
            return 'bg-red-100 text-red-800';
        case 'Connexion':
            return 'bg-primary-100 text-primary-800';
        case 'Déconnexion':
            return 'bg-gray-100 text-gray-800';
        case 'Validation':
            return 'bg-primary-100 text-primary-800';
        case 'Rejet':
            return 'bg-red-100 text-red-800';
        case 'Sauvegarde':
            return 'bg-purple-100 text-purple-800';
        case 'Restauration':
            return 'bg-orange-100 text-orange-800';
        case 'Impression':
            return 'bg-blue-100 text-blue-800';
        case 'Exportation':
            return 'bg-amber-100 text-amber-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
$auditLog = $GLOBALS['auditLog'];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Piste D'Audit</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
</head>

<body class="bg-gray-50 min-h-screen animated-bg font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header with Logo and Title -->
        <div class="flex justify-between items-center mb-8">
            <div class="flex items-center">
                <div class="bg-primary-500 text-white p-3 rounded-full mr-4">
                    <i class="fas fa-shield-alt text-xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Piste d'Audit</h1>
            </div>
            <div class="date-picker relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date du jour</label>
                <input type="text"
                    class="w-40 px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-primary-600 focus:border-0 text-sm"
                    readonly value="<?php echo date('d/m/Y'); ?>">
            </div>
        </div>

        <!-- Messages de notification -->
        <?php if (isset($_GET['success']) && $_GET['success'] === 'cleanup'): ?>
        <div class="bg-primary-100 border border-green-400 text-primary-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Succès !</strong>
            <span class="block sm:inline"><?php echo $_GET['deleted'] ?? 0; ?> enregistrements d'audit ont été
                supprimés.</span>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'log_deleted'): ?>
        <div class="bg-primary-100 border border-green-400 text-primary-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Succès !</strong>
            <span class="block sm:inline">Le log d'audit a été supprimé avec succès.</span>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="<?= StyleManager::getNotificationClass('error') ?> relative mb-6" role="alert">
            <strong class="font-bold">Erreur !</strong>
            <span class="block sm:inline">
                <?php 
                switch ($_GET['error']) {
                    case 'invalid_days':
                        echo 'Nombre de jours invalide pour le nettoyage.';
                        break;
                    case 'invalid_id':
                        echo 'ID de log invalide.';
                        break;
                    case 'log_not_found':
                        echo 'Log d\'audit introuvable.';
                        break;
                    case 'cleanup_failed':
                        echo 'Erreur lors du nettoyage des logs.';
                        break;
                    case 'delete_failed':
                        echo 'Erreur lors de la suppression du log.';
                        break;
                    case 'invalid_method':
                        echo 'Méthode de requête invalide.';
                        break;
                    default:
                        echo 'Une erreur s\'est produite.';
                }
                ?>
            </span>
        </div>
        <?php endif; ?>

        <!-- Main Content Card -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden transition-all hover:shadow-xl">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-green-600 to-green-800 px-6 py-4">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    <i class="fas fa-history mr-2"></i>
                    Piste d'Audit - Historique des Actions
                </h2>
            </div>

            <!-- Filters Section -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <form method="GET" action="?page=piste_audit"
                    class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                    <input type="hidden" name="page" value="piste_audit">

                    <!-- Date Range Filters -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="w-full sm:w-auto">
                            <label class="block text-xs text-gray-500 mb-1">Date début</label>
                            <input type="text" name="date_debut" id="date_debut"
                                value="<?php echo htmlspecialchars($_GET['date_debut'] ?? ''); ?>"
                                placeholder="Date de début"
                                class="w-full sm:w-28 px-2 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                        <div class="w-full sm:w-auto">
                            <label class="block text-xs text-gray-500 mb-1">Date fin</label>
                            <input type="text" name="date_fin" id="date_fin"
                                value="<?php echo htmlspecialchars($_GET['date_fin'] ?? ''); ?>"
                                placeholder="Date de fin"
                                class="w-full sm:w-28 px-2 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                    </div>

                    <!-- Action Filter -->
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs text-gray-500 mb-1">Action</label>
                        <select name="action"
                            class="w-full sm:w-32 px-2 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                            <option value="">Toutes les actions</option>
                            <option value="Création"
                                <?php echo (isset($_GET['action']) && $_GET['action'] === 'Création') ? 'selected' : ''; ?>>
                                Création</option>
                            <option value="Modification"
                                <?php echo (isset($_GET['action']) && $_GET['action'] === 'Modification') ? 'selected' : ''; ?>>
                                Modification</option>
                            <option value="Suppression"
                                <?php echo (isset($_GET['action']) && $_GET['action'] === 'Suppression') ? 'selected' : ''; ?>>
                                Suppression</option>
                            <option value="Connexion"
                                <?php echo (isset($_GET['action']) && $_GET['action'] === 'Connexion') ? 'selected' : ''; ?>>
                                Connexion</option>
                            <option value="Déconnexion"
                                <?php echo (isset($_GET['action']) && $_GET['action'] === 'Déconnexion') ? 'selected' : ''; ?>>
                                Déconnexion</option>
                            <option value="Validation"
                                <?php echo (isset($_GET['action']) && $_GET['action'] === 'Validation') ? 'selected' : ''; ?>>
                                Validation</option>
                            <option value="Rejet"
                                <?php echo (isset($_GET['action']) && $_GET['action'] === 'Rejet') ? 'selected' : ''; ?>>
                                Rejet</option>
                            <option value="Sauvegarde"
                                <?php echo (isset($_GET['action']) && $_GET['action'] === 'Sauvegarde') ? 'selected' : ''; ?>>
                                Sauvegarde</option>
                            <option value="Restauration"
                                <?php echo (isset($_GET['action']) && $_GET['action'] === 'Restauration') ? 'selected' : ''; ?>>
                                Restauration</option>
                            <option value="Impression"
                                <?php echo (isset($_GET['action']) && $_GET['action'] === 'Impression') ? 'selected' : ''; ?>>
                                Impression</option>
                            <option value="Exportation"
                                <?php echo (isset($_GET['action']) && $_GET['action'] === 'Exportation') ? 'selected' : ''; ?>>
                                Exportation</option>
                        </select>
                    </div>

                    <!-- Table Filter -->
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs text-gray-500 mb-1">Table</label>
                        <select name="table"
                            class="w-full sm:w-32 px-2 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                            <option value="">Toutes les tables</option>
                            <option value="utilisateur"
                                <?php echo (isset($_GET['table']) && $_GET['table'] === 'utilisateur') ? 'selected' : ''; ?>>
                                Utilisateur</option>
                            <option value="etudiant"
                                <?php echo (isset($_GET['table']) && $_GET['table'] === 'etudiant') ? 'selected' : ''; ?>>
                                Étudiant</option>
                            <option value="enseignant"
                                <?php echo (isset($_GET['table']) && $_GET['table'] === 'enseignant') ? 'selected' : ''; ?>>
                                Enseignant</option>
                            <option value="rapport_etudiants"
                                <?php echo (isset($_GET['table']) && $_GET['table'] === 'rapport_etudiants') ? 'selected' : ''; ?>>
                                Rapport Étudiants</option>
                            <option value="candidature_soutenance"
                                <?php echo (isset($_GET['table']) && $_GET['table'] === 'candidature_soutenance') ? 'selected' : ''; ?>>
                                Candidature Soutenance</option>
                            <option value="versement"
                                <?php echo (isset($_GET['table']) && $_GET['table'] === 'versement') ? 'selected' : ''; ?>>
                                Versement</option>
                            <option value="base_de_donnees"
                                <?php echo (isset($_GET['table']) && $_GET['table'] === 'base_de_donnees') ? 'selected' : ''; ?>>
                                Base de Données</option>
                            <option value="sauvegarde"
                                <?php echo (isset($_GET['table']) && $_GET['table'] === 'sauvegarde') ? 'selected' : ''; ?>>
                                Sauvegarde</option>
                            <option value="pister"
                                <?php echo (isset($_GET['table']) && $_GET['table'] === 'pister') ? 'selected' : ''; ?>>
                                Piste d'Audit</option>
                        </select>
                    </div>

                    <!-- Statut Filter -->
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs text-gray-500 mb-1">Statut</label>
                        <select name="statut"
                            class="w-full sm:w-28 px-2 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                            <option value="">Tous les statuts</option>
                            <option value="Succès"
                                <?php echo (isset($_GET['statut']) && $_GET['statut'] === 'Succès') ? 'selected' : ''; ?>>
                                Succès</option>
                            <option value="Erreur"
                                <?php echo (isset($_GET['statut']) && $_GET['statut'] === 'Erreur') ? 'selected' : ''; ?>>
                                Erreur</option>
                        </select>
                    </div>

                    <!-- Search Filter -->
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs text-gray-500 mb-1">Recherche</label>
                        <input type="text" name="search" id="search"
                            value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Rechercher..."
                            class="w-full sm:w-40 px-2 py-2 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                    </div>

                    <!-- Filter Buttons -->
                    <div class="flex gap-2">
                        <button type="submit"
                            class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg text-sm shadow-sm transition-all hover-scale focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 flex items-center">
                            <i class="fas fa-search mr-2"></i> Filtrer
                        </button>
                        <a href="?page=piste_audit"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm shadow-sm transition-all hover-scale focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 flex items-center">
                            <i class="fas fa-times mr-2"></i> Réinitialiser
                        </a>
                    </div>
                </form>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center px-6 py-3 bg-white border-b border-gray-200">
                <div class="text-sm text-gray-500">
                    <span class="font-medium"><?php echo count($auditLog); ?></span> enregistrements trouvés
                </div>
                <div class="flex space-x-2">
                    <a href="?page=piste_audit&action=export&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>"
                        class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm shadow-sm transition-all hover-scale focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 flex items-center">
                        <i class="fas fa-file-export mr-2"></i> Exporter
                    </a>
                    <button onclick="window.print()"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm shadow-sm transition-all hover-scale focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center">
                        <i class="fas fa-print mr-2"></i> Imprimer
                    </button>
                    <a href="?page=piste_audit"
                        class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg text-sm shadow-sm transition-all hover-scale focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 flex items-center">
                        <i class="fas fa-sync-alt mr-2"></i> Actualiser
                    </a>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-auto">
                <table class="w-full table-hover">
                    <thead class="bg-gray-50 text-gray-700 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4 text-left text-xs font-medium uppercase tracking-wider">Date</th>
                            <th class="py-3 px-4 text-left text-xs font-medium uppercase tracking-wider">Heure</th>
                            <th class="py-3 px-4 text-left text-xs font-medium uppercase tracking-wider">Action</th>
                            <th class="py-3 px-4 text-left text-xs font-medium uppercase tracking-wider">Statut</th>
                            <th class="py-3 px-4 text-left text-xs font-medium uppercase tracking-wider">Table</th>
                            <th class="py-3 px-4 text-left text-xs font-medium uppercase tracking-wider">Utilisateur
                            </th>
                            <th class="py-3 px-4 text-left text-xs font-medium uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($auditLog)): ?>
                        <tr>
                            <td colspan="7" class="py-8 px-4 text-center text-gray-500">
                                <i class="fas fa-search text-4xl mb-4 text-gray-300"></i>
                                <p>Aucun log d'audit trouvé pour les critères sélectionnés.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($auditLog as $log): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-4 text-sm"><?php echo date('d/m/Y', strtotime($log['date_creation'])); ?>
                            </td>
                            <td class="py-3 px-4 text-sm"><?php echo date('H:i:s', strtotime($log['date_creation'])); ?>
                            </td>
                            <td class="py-3 px-4">
                                <span
                                    class="px-2 py-1 text-xs font-semibold rounded-full <?php echo getActionColor($log['action']); ?>">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span
                                    class="px-2 py-1 text-xs font-semibold rounded-full <?php echo ($log['statut_action'] === 'Succès') ? 'bg-primary-100 text-primary-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo htmlspecialchars($log['statut_action']); ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($log['nom_table']); ?></td>
                            <td class="py-3 px-4 text-sm">
                                <div>
                                    <div class="font-medium">
                                        <?php echo htmlspecialchars($log['login_utilisateur'] ?? 'N/A'); ?></div>
                                    <div class="text-gray-500 text-xs">
                                        <?php echo htmlspecialchars($log['nom_utilisateur'] ?? 'N/A'); ?></div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-sm">
                                <button type="button"
                                    class="text-red-600 hover:text-red-800 transition-colors open-delete-modal"
                                    data-log-id="<?php echo $log['id_piste']; ?>" title="Supprimer ce log">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (($totalPages ?? 1) > 1): ?>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Affichage de <span class="font-medium"><?php echo (($page - 1) * $perPage) + 1; ?></span> à <span
                        class="font-medium"><?php echo min($page * $perPage, $totalLogs); ?></span> sur <span
                        class="font-medium"><?php echo $totalLogs; ?></span> enregistrements
                </div>
                <div class="flex items-center space-x-1">
                    <?php if ($page > 1): ?>
                    <a href="?page=piste_audit&page_num=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return !in_array($key, ['page', 'page_num']); }, ARRAY_FILTER_USE_KEY)); ?>"
                        class="pagination-item">
                        <i class="fas fa-chevron-left mr-2"></i>
                    </a>
                    <?php else: ?>
                    <button class="pagination-item" disabled>
                        <i class="fas fa-chevron-left mr-2"></i>
                    </button>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                    <span class="pagination-item bg-primary-500 text-white"><?php echo $i; ?></span>
                    <?php else: ?>
                    <a href="?page=piste_audit&page_num=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return !in_array($key, ['page', 'page_num']); }, ARRAY_FILTER_USE_KEY)); ?>"
                        class="pagination-item"><?php echo $i; ?></a>
                    <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <a href="?page=piste_audit&page_num=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return !in_array($key, ['page', 'page_num']); }, ARRAY_FILTER_USE_KEY)); ?>"
                        class="pagination-item">
                        <i class="fas fa-chevron-right ml-2"></i>
                    </a>
                    <?php else: ?>
                    <button class="pagination-item" disabled>
                        <i class="fas fa-chevron-right ml-2"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Cleanup Section -->
        <div class="mt-6 bg-white shadow-lg rounded-xl overflow-hidden">
            <div class="bg-gradient-to-r from-red-600 to-red-800 px-6 py-4">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i>
                    Nettoyage des Logs
                </h3>
            </div>
            <div class="px-6 py-4">
                <form id="cleanupForm" method="POST" action="?page=piste_audit&action=cleanup"
                    class="flex items-end gap-4">
                    <div>
                        <label class="<?= StyleManager::getLabelClass() ?>">Supprimer les logs de plus
                            de</label>
                        <input type="number" name="days" min="1" max="365" value="30" required
                            class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <span class="text-sm text-gray-500 ml-2">jours</span>
                    </div>
                    <button type="button" id="openCleanupModalBtn"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm shadow-sm transition-all hover-scale focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 flex items-center">
                        <i class="fas fa-trash-alt mr-2"></i> Nettoyer
                    </button>
                </form>
                <p class="text-sm text-gray-600 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Cette action supprimera définitivement tous les logs d'audit antérieurs à la période spécifiée.
                </p>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-sm w-full">
            <h2 class="text-lg font-semibold mb-4 text-gray-800 flex items-center"><i
                    class="fas fa-exclamation-triangle text-red-500 mr-2"></i> Confirmation de suppression</h2>
            <p class="mb-6 text-gray-700">Êtes-vous sûr de vouloir supprimer ce log d'audit ? Cette action est
                irréversible.</p>
            <form id="deleteLogForm" method="POST" action="?page=piste_audit&action=delete_log">
                <input type="hidden" name="log_id" id="deleteLogId" value="">
                <div class="flex justify-end gap-2">
                    <button type="button" id="cancelDeleteBtn"
                        class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">Annuler</button>
                    <button type="submit"
                        class="px-4 py-2 rounded bg-red-500 text-white hover:bg-red-600">Supprimer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmation de nettoyage -->
    <div id="cleanupModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-sm w-full">
            <h2 class="text-lg font-semibold mb-4 text-gray-800 flex items-center"><i
                    class="fas fa-exclamation-triangle text-red-500 mr-2"></i> Confirmation du nettoyage</h2>
            <p class="mb-6 text-gray-700">Êtes-vous sûr de vouloir supprimer tous les logs d'audit plus anciens que la
                période spécifiée ? Cette action est irréversible.</p>
            <div class="flex justify-end gap-2">
                <button type="button" id="cancelCleanupBtn"
                    class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">Annuler</button>
                <button type="button" id="confirmCleanupBtn"
                    class="px-4 py-2 rounded bg-red-500 text-white hover:bg-red-600">Nettoyer</button>
            </div>
        </div>
    </div>

    <style>
    .animated-bg {
        background: linear-gradient(-45deg, #f0f9ff, #f0fdf4, #fefce8, #fef2f2);
        background-size: 400% 400%;
        animation: gradient 15s ease infinite;
    }

    @keyframes gradient {
        0% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }

        100% {
            background-position: 0% 50%;
        }
    }

    .hover-scale:hover {
        transform: scale(1.05);
    }

    .table-hover tbody tr:hover {
        background-color: #f9fafb;
    }

    .pagination-item {
        @apply px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover: bg-gray-50 hover:text-gray-700 transition-colors;
    }

    .pagination-item:disabled {
        @apply opacity-50 cursor-not-allowed;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: white !important;
        }

        .bg-white {
            background: white !important;
        }
    }
    </style>

    <script src="public/js/audit.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr("#date_debut", {
            locale: "fr",
            dateFormat: "Y-m-d",
            allowInput: true,
            placeholder: "Date de début"
        });

        flatpickr("#date_fin", {
            locale: "fr",
            dateFormat: "Y-m-d",
            allowInput: true,
            placeholder: "Date de fin"
        });
    });

    let searchTimeout;
    document.getElementById('search').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            this.form.submit();
        }, 500);
    });

    // Modal logic
    const deleteModal = document.getElementById('deleteModal');
    const deleteLogForm = document.getElementById('deleteLogForm');
    const deleteLogIdInput = document.getElementById('deleteLogId');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

    // Ouvre la modal quand on clique sur la poubelle
    Array.from(document.getElementsByClassName('open-delete-modal')).forEach(btn => {
        btn.addEventListener('click', function() {
            const logId = this.getAttribute('data-log-id');
            deleteLogIdInput.value = logId;
            deleteModal.classList.remove('hidden');
        });
    });
    // Ferme la modal
    cancelDeleteBtn.addEventListener('click', function(e) {
        e.preventDefault();
        deleteModal.classList.add('hidden');
    });
    // Ferme la modal si on clique en dehors
    window.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            deleteModal.classList.add('hidden');
        }
    });

    // Modal de nettoyage
    const cleanupModal = document.getElementById('cleanupModal');
    const openCleanupModalBtn = document.getElementById('openCleanupModalBtn');
    const cancelCleanupBtn = document.getElementById('cancelCleanupBtn');
    const confirmCleanupBtn = document.getElementById('confirmCleanupBtn');
    const cleanupForm = document.getElementById('cleanupForm');

    openCleanupModalBtn.addEventListener('click', function() {
        cleanupModal.classList.remove('hidden');
    });
    cancelCleanupBtn.addEventListener('click', function() {
        cleanupModal.classList.add('hidden');
    });
    confirmCleanupBtn.addEventListener('click', function() {
        cleanupModal.classList.add('hidden');
        cleanupForm.submit();
    });
    window.addEventListener('click', function(e) {
        if (e.target === cleanupModal) {
            cleanupModal.classList.add('hidden');
        }
    });
    </script>
</body>

</html>