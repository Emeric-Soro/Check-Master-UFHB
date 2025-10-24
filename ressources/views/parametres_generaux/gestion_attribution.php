<?php
// Récupération des données
$listeGroupes = $GLOBALS['listeGroupes'] ?? [];
$listeTraitements = $GLOBALS['listeTraitements'] ?? [];
$listeActions = $GLOBALS['listeActions'] ?? [];
$selectedGroupe = $GLOBALS['selectedGroupe'] ?? null;
$permissionsGroupe = $GLOBALS['permissionsGroupe'] ?? [];
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';

// Mapper les actions par ID pour un accès rapide
$actionsMap = [];
foreach ($listeActions as $action) {
    $actionsMap[$action->id_action] = $action->lib_action;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Permissions CRUD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .permission-matrix {
            overflow-x: auto;
        }
        .permission-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .permission-table th {
            background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%);
            color: white;
            padding: 12px 8px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .permission-table td {
            padding: 8px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        .permission-table tbody tr:hover {
            background-color: #f0fdf4;
        }
        .permission-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .permission-checkbox:checked {
            background-color: #22c55e;
            border-color: #22c55e;
        }
        .action-header {
            font-size: 0.75rem;
            font-weight: 600;
        }
        .groupe-btn {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .groupe-btn:hover {
            background-color: #f0fdf4;
        }
        .groupe-btn.selected {
            background-color: #ecfdf5;
            border-left: 4px solid #059669;
        }
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            color: white;
            max-width: 24rem;
            z-index: 50;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.5s ease-out;
        }
        .notification.success {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }
        .notification.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-100">

    <!-- Notifications -->
    <?php if (!empty($messageSuccess)): ?>
    <div id="successNotification" class="notification success">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <p><?= htmlspecialchars($messageSuccess) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($messageErreur)): ?>
    <div id="errorNotification" class="notification error">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <p><?= htmlspecialchars($messageErreur) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="container mx-auto px-4 py-6">
        <!-- En-tête -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                <i class="fas fa-shield-alt text-primary mr-2"></i>
                Gestion des Permissions CRUD
            </h1>
            <p class="text-gray-600">
                Configurez les permissions granulaires (Consulter, Ajouter, Modifier, Supprimer) pour chaque rôle et fonctionnalité.
            </p>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <!-- Liste des groupes -->
            <div class="col-span-3">
                <div class="bg-white rounded-lg shadow-md p-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-users mr-2"></i>
                        Groupes d'utilisateurs
                    </h2>
                    <div class="space-y-2">
                        <?php foreach ($listeGroupes as $groupe): ?>
                        <a href="?page=parametres_generaux&action=gestion_attribution&groupe=<?= $groupe->id_GU ?>" 
                           class="groupe-btn block px-4 py-3 rounded-lg border <?= ($selectedGroupe && $selectedGroupe->id_GU == $groupe->id_GU) ? 'selected' : '' ?>">
                            <div class="flex items-center justify-between">
                                <span class="font-medium"><?= htmlspecialchars($groupe->lib_GU) ?></span>
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Matrice de permissions -->
            <div class="col-span-9">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <?php if ($selectedGroupe): ?>
                        <div class="mb-4">
                            <h2 class="text-xl font-semibold text-gray-800">
                                Permissions pour : <span class="text-primary"><?= htmlspecialchars($selectedGroupe->lib_GU) ?></span>
                            </h2>
                            <p class="text-sm text-gray-600 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Cochez les cases pour attribuer les permissions. Les modifications sont enregistrées automatiquement.
                            </p>
                        </div>

                        <div class="permission-matrix">
                            <table class="permission-table">
                                <thead>
                                    <tr>
                                        <th class="text-left" style="min-width: 200px;">Fonctionnalité</th>
                                        <th class="action-header">
                                            <i class="fas fa-eye"></i><br>Consulter
                                        </th>
                                        <th class="action-header">
                                            <i class="fas fa-plus"></i><br>Ajouter
                                        </th>
                                        <th class="action-header">
                                            <i class="fas fa-edit"></i><br>Modifier
                                        </th>
                                        <th class="action-header">
                                            <i class="fas fa-trash"></i><br>Supprimer
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listeTraitements as $traitement): ?>
                                    <tr>
                                        <td class="text-left font-medium">
                                            <i class="fas <?= htmlspecialchars($traitement->icone_traitement) ?> mr-2 text-primary"></i>
                                            <?= htmlspecialchars($traitement->label_traitement) ?>
                                        </td>
                                        <?php
                                        // Actions dans l'ordre : Consulter (7), Ajouter (1), Modifier (3), Supprimer (6)
                                        $actionOrder = [7, 1, 3, 6];
                                        foreach ($actionOrder as $id_action):
                                            $isChecked = isset($permissionsGroupe[$traitement->id_traitement][$id_action]);
                                        ?>
                                        <td>
                                            <input type="checkbox" 
                                                   class="permission-checkbox"
                                                   data-groupe="<?= $selectedGroupe->id_GU ?>"
                                                   data-traitement="<?= $traitement->id_traitement ?>"
                                                   data-action="<?= $id_action ?>"
                                                   <?= $isChecked ? 'checked' : '' ?>
                                                   onchange="togglePermission(this)">
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                            <p class="text-sm text-blue-800">
                                <i class="fas fa-lightbulb mr-2"></i>
                                <strong>Astuce :</strong> Les modifications sont automatiques. Pensez à tester les permissions après les modifications.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i class="fas fa-arrow-left text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">Sélectionnez un groupe</h3>
                            <p class="text-gray-500">Choisissez un groupe d'utilisateurs dans la liste de gauche pour configurer ses permissions.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fonction pour basculer une permission
        function togglePermission(checkbox) {
            const id_GU = checkbox.dataset.groupe;
            const id_traitement = checkbox.dataset.traitement;
            const id_action = checkbox.dataset.action;
            
            // Désactiver temporairement la checkbox
            checkbox.disabled = true;
            
            // Envoyer la requête AJAX
            fetch('?page=parametres_generaux&action=gestion_attribution', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=true&action=toggle&id_GU=${id_GU}&id_traitement=${id_traitement}&id_action=${id_action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'état de la checkbox
                    checkbox.checked = data.state;
                    // Afficher une notification de succès
                    showNotification(data.message, 'success');
                } else {
                    // Revenir à l'état précédent
                    checkbox.checked = !checkbox.checked;
                    // Afficher une notification d'erreur
                    showNotification(data.message || 'Erreur lors de la modification', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                // Revenir à l'état précédent
                checkbox.checked = !checkbox.checked;
                showNotification('Erreur de communication avec le serveur', 'error');
            })
            .finally(() => {
                // Réactiver la checkbox
                checkbox.disabled = false;
            });
        }

        // Fonction pour afficher une notification
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                    <p>${message}</p>
                </div>
            `;
            document.body.appendChild(notification);
            
            // Supprimer la notification après 3 secondes
            setTimeout(() => {
                notification.style.animation = 'fadeOut 0.5s ease-out';
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }

        // Masquer les notifications après 5 secondes
        document.addEventListener('DOMContentLoaded', function() {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                setTimeout(() => {
                    notification.style.animation = 'fadeOut 0.5s ease-out';
                    setTimeout(() => notification.remove(), 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>
