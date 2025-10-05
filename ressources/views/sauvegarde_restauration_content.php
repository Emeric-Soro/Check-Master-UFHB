<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sauvegarde et restauration</title>
    <style>
    .notification {
        transition: opacity 0.5s ease-in-out;
    }

    .notification.fade-out {
        opacity: 0;
    }
    </style>
</head>

<body>

    <div class="container mx-auto px-4 py-8">

        <!-- Messages de notification -->
        <?php if (isset($_GET['success'])): ?>
        <div id="success-notification"
            class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6 notification"
            role="alert">
            <strong class="font-bold">Succès !</strong>
            <span class="block sm:inline">La sauvegarde a été créée avec succès.</span>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['restored'])): ?>
        <div id="restored-notification"
            class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-6 notification"
            role="alert">
            <strong class="font-bold">Restauration réussie !</strong>
            <span class="block sm:inline">La base de données a été restaurée avec succès.</span>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
        <div id="deleted-notification"
            class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-6 notification"
            role="alert">
            <strong class="font-bold">Suppression réussie !</strong>
            <span class="block sm:inline">La sauvegarde a été supprimée avec succès.</span>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div id="error-notification"
            class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6 notification"
            role="alert">
            <strong class="font-bold">Erreur !</strong>
            <span class="block sm:inline">
                <?php 
                switch ($_GET['error']) {
                    case 'docker_not_available':
                        echo 'Docker n\'est pas disponible. Le système tentera d\'utiliser une méthode alternative.';
                        break;
                    case 'container_not_running':
                        echo 'Le conteneur de base de données n\'est pas en cours d\'exécution. Le système tentera d\'utiliser une méthode alternative.';
                        break;
                    case 'backup_failed':
                        echo 'La sauvegarde a échoué. Vérifiez que la base de données est accessible et que les permissions sont correctes.';
                        break;
                    default:
                        echo 'Une erreur s\'est produite lors de l\'opération.';
                }
                ?>
            </span>
        </div>
        <?php endif; ?>

        <!-- Create Backup Section -->
        <div class="bg-white shadow-sm rounded-lg p-6 md:p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Créer une Nouvelle Sauvegarde</h2>
            <p class="text-gray-600 mb-6">
                Créez une sauvegarde manuelle de la base de données. Il est recommandé de le faire avant toute
                modification majeure du système.
            </p>
            <form id="createBackupForm" method="POST" action="?page=sauvegarde_restauration&action=create">
                <div class="mb-4">
                    <label for="backup_name" class="block text-sm font-medium text-gray-700 mb-3">Nom de la sauvegarde
                        (optionnel)</label>
                    <input type="text" name="backup_name" id="backup_name" placeholder="Ex: avant_mise_a_jour_v2"
                        class="outline-green-500 w-full md:w-1/2 px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500">
                </div>
                <button type="submit"
                    class="px-6 py-2 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-green-500 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-database mr-2"></i>Lancer la Sauvegarde Manuelle
                </button>
            </form>
        </div>

        <!-- Existing Backups Section -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-semibold text-gray-700">Sauvegardes Existantes</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nom du Fichier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Taille</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date de Création</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type</th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($backups)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">Aucune sauvegarde disponible.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 hover:text-blue-800 font-medium">
                                <i
                                    class="fas fa-file-archive mr-2"></i><?php echo htmlspecialchars($backup['filename']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo htmlspecialchars($backup['size']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo htmlspecialchars($backup['created_at']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $backup['type'] === 'Automatique' ? 'bg-purple-100 text-purple-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo htmlspecialchars($backup['type']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <button type="button" class="text-green-600 hover:text-green-900 mr-3" title="Restaurer"
                                    onclick="openRestoreModal('<?php echo htmlspecialchars($backup['filename']); ?>')">
                                    <i class="fas fa-undo-alt"></i> Restaurer
                                </button>
                                <a href="?page=sauvegarde_restauration&action=download&filename=<?php echo urlencode($backup['filename']); ?>"
                                    class="text-blue-600 hover:text-blue-900 mr-3" title="Télécharger">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button type="button" class="text-red-600 hover:text-red-900" title="Supprimer"
                                    onclick="openDeleteModal('<?php echo htmlspecialchars($backup['filename']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteModal"
        class="fixed inset-0 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center">
        <div class="relative mx-auto p-5 w-96 shadow-2xl rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Confirmer la suppression</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 text-center">
                        Êtes-vous sûr de vouloir supprimer la sauvegarde <strong class="text-center"
                            id="deleteFileName"></strong> ?
                    </p>
                    <p class="text-sm text-red-500 mt-2">
                        Cette action est irréversible.
                    </p>
                </div>
                <div class="items-center px-4 py-3">
                    <form id="deleteForm" method="POST" action="?page=sauvegarde_restauration&action=delete">
                        <input type="hidden" name="filename" id="deleteFileInput">
                        <div class="flex justify-center space-x-3">
                            <button type="button" id="cancelDelete"
                                class="px-4 py-2 border-gray-300 bg-gray-300 text-gray-700 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                                Annuler
                            </button>
                            <button type="submit"
                                class="px-4 py-2 border-red-600 bg-red-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                Supprimer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de restauration -->
    <div id="restoreModal"
        class="fixed inset-0 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center">
        <div class="relative mx-auto p-5 w-96 shadow-2xl rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-orange-100">
                    <i class="fas fa-exclamation-triangle text-orange-600 text-xl"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Confirmer la restauration</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 text-center">
                        Êtes-vous sûr de vouloir restaurer la base de données à partir de la sauvegarde <strong
                            class="text-center" id="restoreFileName"></strong> ?
                    </p>
                    <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm text-red-700 font-semibold">
                            ⚠️ ATTENTION : CETTE ACTION EST IRRÉVERSIBLE
                        </p>
                        <p class="text-xs text-red-600 mt-1">
                            Toutes les données actuelles seront écrasées et remplacées par celles de la sauvegarde.
                        </p>
                    </div>
                </div>
                <div class="items-center px-4 py-3">
                    <form id="restoreForm" method="POST" action="?page=sauvegarde_restauration&action=restore">
                        <input type="hidden" name="filename" id="restoreFileInput">
                        <div class="flex justify-center space-x-3">
                            <button type="button" id="cancelRestore"
                                class="px-4 py-2 border-gray-300 bg-gray-300 text-gray-700 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                                Annuler
                            </button>
                            <button type="submit"
                                class="px-4 py-2 border-orange-600 bg-orange-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <i class="fas fa-undo-alt mr-2"></i>Restaurer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Fonction pour faire disparaître les notifications après un délai
    function fadeOutNotification(elementId, delay = 3000) {
        const element = document.getElementById(elementId);
        if (element) {
            setTimeout(() => {
                element.classList.add('fade-out');
                setTimeout(() => {
                    element.remove();
                }, 500);
            }, delay);
        }
    }

    // Fonction pour ouvrir la modal de suppression
    function openDeleteModal(filename) {
        document.getElementById('deleteFileName').textContent = filename;
        document.getElementById('deleteFileInput').value = filename;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    // Fonction pour fermer la modal de suppression
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Fonction pour ouvrir la modal de restauration
    function openRestoreModal(filename) {
        document.getElementById('restoreFileName').textContent = filename;
        document.getElementById('restoreFileInput').value = filename;
        document.getElementById('restoreModal').classList.remove('hidden');
    }

    // Fonction pour fermer la modal de restauration
    function closeRestoreModal() {
        document.getElementById('restoreModal').classList.add('hidden');
    }

    // Appliquer le délai à toutes les notifications
    document.addEventListener('DOMContentLoaded', function() {
        // Notifications de succès
        if (document.getElementById('success-notification')) {
            fadeOutNotification('success-notification', 3000);
        }

        // Notifications de restauration
        if (document.getElementById('restored-notification')) {
            fadeOutNotification('restored-notification', 3000);
        }

        // Notifications de suppression
        if (document.getElementById('deleted-notification')) {
            fadeOutNotification('deleted-notification', 3000);
        }

        // Notifications d'erreur (plus long délai pour les erreurs)
        if (document.getElementById('error-notification')) {
            fadeOutNotification('error-notification', 5000);
        }

        // Gestion de la modal de suppression
        document.getElementById('cancelDelete').addEventListener('click', closeDeleteModal);

        // Fermer la modal en cliquant à l'extérieur
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Gestion de la modal de restauration
        document.getElementById('cancelRestore').addEventListener('click', closeRestoreModal);

        // Fermer la modal de restauration en cliquant à l'extérieur
        document.getElementById('restoreModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRestoreModal();
            }
        });
    });
    </script>

</body>

</html>