<?php
/**
 * Écran 1.3.2: Réclamation (Suivi et Traitement)
 * Vue pour le traitement des réclamations par l'administration
 */

// Extraire les variables globales
$reclamations = $GLOBALS['reclamations'] ?? [];
$stats = $GLOBALS['stats'] ?? [];
$filtres = $GLOBALS['filtres'] ?? [];

// Fonction helper pour les badges de statut
function getStatusBadgeReclamation($statut) {
    $classes = [
        'En attente' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'En cours' => 'bg-blue-100 text-blue-800 border-blue-200',
        'Traitée' => 'bg-green-100 text-green-800 border-green-200',
        'Rejetée' => 'bg-red-100 text-red-800 border-red-200'
    ];
    $class = $classes[$statut] ?? 'bg-gray-100 text-gray-800';
    return "<span class='inline-flex px-2 py-1 text-xs font-semibold rounded-full border {$class}'>{$statut}</span>";
}

// Fonction helper pour les badges de type
function getTypeBadge($type) {
    $icons = [
        'RCL' => 'fa-exclamation-circle',
        'DMT' => 'fa-file-alt',
        'Académique' => 'fa-graduation-cap',
        'Administrative' => 'fa-folder',
        'Technique' => 'fa-cogs',
        'Financière' => 'fa-money-bill'
    ];
    $icon = $icons[$type] ?? 'fa-circle';
    return "<span class='inline-flex items-center text-xs'><i class='fa {$icon} mr-1'></i>{$type}</span>";
}

// Fonction helper pour les badges de priorité
function getPrioriteBadge($priorite) {
    $classes = [
        'Haute' => 'bg-red-100 text-red-800',
        'Moyenne' => 'bg-yellow-100 text-yellow-800',
        'Basse' => 'bg-green-100 text-green-800'
    ];
    $class = $classes[$priorite] ?? 'bg-gray-100 text-gray-800';
    return "<span class='inline-flex px-2 py-1 text-xs font-semibold rounded-full {$class}'>{$priorite}</span>";
}

// Générer la référence
function getReference($id, $type) {
    $prefix = ($type === 'Demande de thème' || $type === 'DMT') ? 'DMT' : 'RCL';
    return $prefix . '-' . date('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
}
?>

<div class="p-4 sm:p-6 md:p-8">
    <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- En-tête de la page -->
        <div class="bg-primary px-6 py-6 text-white">
            <h1 class="text-2xl font-bold mb-1">Traitement des Réclamations</h1>
            <p class="text-white/80 text-sm">Gestion Scolarité - Suivi et traitement des réclamations (RCL) et demandes de changement de thème (DMT)</p>
        </div>

        <div class="p-6 md:p-8">
            <!-- Messages de session -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
                    <i class="fa fa-check-circle mr-3 text-green-500"></i>
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
                    <i class="fa fa-exclamation-circle mr-3 text-red-500"></i>
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Alertes SLA -->
            <?php if (($stats['alertes_sla'] ?? 0) > 0): ?>
                <div class="mb-6 bg-orange-50 border border-orange-200 text-orange-800 px-4 py-3 rounded-lg flex items-center">
                    <i class="fa fa-exclamation-triangle mr-3 text-orange-500"></i>
                    <span><strong><?= $stats['alertes_sla'] ?></strong> réclamation(s) en attente depuis plus de 7 jours</span>
                </div>
            <?php endif; ?>

            <!-- Statistiques rapides -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-xs font-medium uppercase">Total</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['total'] ?? 0 ?></p>
                        </div>
                        <div class="text-gray-400 text-2xl">
                            <i class="fa fa-inbox"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-600 text-xs font-medium uppercase">En attente</p>
                            <p class="text-2xl font-bold text-yellow-700"><?= $stats['en_attente'] ?? 0 ?></p>
                        </div>
                        <div class="text-yellow-500 text-2xl">
                            <i class="fa fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-600 text-xs font-medium uppercase">En cours</p>
                            <p class="text-2xl font-bold text-blue-700"><?= $stats['en_cours'] ?? 0 ?></p>
                        </div>
                        <div class="text-blue-500 text-2xl">
                            <i class="fa fa-spinner"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-600 text-xs font-medium uppercase">Traitées</p>
                            <p class="text-2xl font-bold text-green-700"><?= $stats['traitees'] ?? 0 ?></p>
                        </div>
                        <div class="text-green-500 text-2xl">
                            <i class="fa fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-600 text-xs font-medium uppercase">Rejetées</p>
                            <p class="text-2xl font-bold text-red-700"><?= $stats['rejetees'] ?? 0 ?></p>
                        </div>
                        <div class="text-red-500 text-2xl">
                            <i class="fa fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200">
                <form method="GET" action="layout.php" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <input type="hidden" name="page" value="admin_reclamations">
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                        <select name="type" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="tous" <?= $filtres['type'] === 'tous' ? 'selected' : '' ?>>Tous</option>
                            <option value="RCL" <?= $filtres['type'] === 'RCL' ? 'selected' : '' ?>>RCL - Réclamation</option>
                            <option value="DMT" <?= $filtres['type'] === 'DMT' ? 'selected' : '' ?>>DMT - Demande de thème</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Statut</label>
                        <select name="statut" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="tous" <?= $filtres['statut'] === 'tous' ? 'selected' : '' ?>>Tous</option>
                            <option value="En attente" <?= $filtres['statut'] === 'En attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="En cours" <?= $filtres['statut'] === 'En cours' ? 'selected' : '' ?>>En cours</option>
                            <option value="Traitée" <?= $filtres['statut'] === 'Traitée' ? 'selected' : '' ?>>Traitée</option>
                            <option value="Rejetée" <?= $filtres['statut'] === 'Rejetée' ? 'selected' : '' ?>>Rejetée</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Date début</label>
                        <input type="date" name="date_debut" value="<?= htmlspecialchars($filtres['date_debut']) ?>" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Date fin</label>
                        <input type="date" name="date_fin" value="<?= htmlspecialchars($filtres['date_fin']) ?>" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-light transition">
                            <i class="fa fa-filter mr-2"></i>Filtrer
                        </button>
                        <a href="?page=admin_reclamations" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-300 transition">
                            <i class="fa fa-undo"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tableau des réclamations -->
            <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="tableReclamations">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Référence</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Objet</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attente</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($reclamations)): ?>
                                <?php foreach ($reclamations as $rec): ?>
                                    <?php 
                                        $isAlerte = ($rec['jours_attente'] > 7 && $rec['statut_reclamation'] === 'En attente');
                                        $rowClass = $isAlerte ? 'bg-orange-50' : '';
                                    ?>
                                    <tr class="hover:bg-gray-50 transition cursor-pointer <?= $rowClass ?>" onclick="openDetailPanel(<?= $rec['id_reclamation'] ?>)">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-900">
                                            <?= getReference($rec['id_reclamation'], $rec['type_reclamation']) ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('d/m/Y', strtotime($rec['date_creation'])) ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?= getTypeBadge($rec['type_reclamation']) ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($rec['nom_etu'] . ' ' . $rec['prenom_etu']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars($rec['num_etu']) ?></div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm text-gray-900 max-w-xs truncate" title="<?= htmlspecialchars($rec['titre_reclamation']) ?>">
                                                <?= htmlspecialchars($rec['titre_reclamation']) ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?= getStatusBadgeReclamation($rec['statut_reclamation']) ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <?php if ($isAlerte): ?>
                                                <span class="text-red-600 font-medium"><i class="fa fa-exclamation-triangle mr-1"></i><?= $rec['jours_attente'] ?>j</span>
                                            <?php else: ?>
                                                <span class="text-gray-500"><?= $rec['jours_attente'] ?>j</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                            <button type="button" onclick="event.stopPropagation(); openDetailPanel(<?= $rec['id_reclamation'] ?>)" 
                                                    class="text-primary hover:text-primary-light transition">
                                                <i class="fa fa-eye mr-1"></i>Voir
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="text-gray-400">
                                            <i class="fa fa-inbox text-4xl mb-4"></i>
                                            <p class="text-lg font-medium">Aucune réclamation trouvée</p>
                                            <p class="text-sm">Modifiez vos filtres ou revenez plus tard</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Panneau latéral de détail -->
<div id="detailPanel" class="fixed inset-0 overflow-hidden z-50 hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeDetailPanel()"></div>

        <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
            <div class="w-screen max-w-2xl transform transition ease-in-out duration-500 sm:duration-700 translate-x-full" id="detailPanelContent">
                <div class="h-full flex flex-col bg-white shadow-xl">
                    <!-- En-tête -->
                    <div class="px-4 py-6 sm:px-6 bg-primary">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-lg font-medium text-white" id="slide-over-title">Détail de la réclamation</h2>
                                <p class="text-white/70 text-sm mt-1" id="detailReference"></p>
                            </div>
                            <div class="ml-3 h-7 flex items-center">
                                <button type="button" class="bg-primary rounded-md text-gray-200 hover:text-white focus:outline-none" onclick="closeDetailPanel()">
                                    <span class="sr-only">Fermer</span>
                                    <i class="fa fa-times text-xl"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Contenu -->
                    <div class="mt-6 relative flex-1 px-4 sm:px-6 overflow-y-auto">
                        <div id="detailContent" class="space-y-6">
                            <!-- Chargement -->
                            <div class="text-center py-12">
                                <i class="fa fa-spinner fa-spin text-3xl text-primary"></i>
                                <p class="mt-4 text-gray-500">Chargement...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions de traitement -->
                    <div id="traitementActions" class="border-t border-gray-200 px-4 py-6 sm:px-6 bg-gray-50 hidden">
                        <div class="space-y-4">
                            <!-- Bouton Prendre en charge -->
                            <div id="btnTakeCharge" class="hidden">
                                <form method="POST" action="" id="takeChargeForm">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fa fa-hand-paper mr-2"></i>Prendre en charge
                                    </button>
                                </form>
                            </div>

                            <!-- Boutons pour le traiteur -->
                            <div id="btnTraiteurActions" class="hidden space-y-2">
                                <button type="button" onclick="showTraiterForm()" 
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <i class="fa fa-check mr-2"></i>Marquer comme traitée
                                </button>

                                <button type="button" onclick="showRejeterForm()"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <i class="fa fa-times mr-2"></i>Rejeter
                                </button>
                            </div>

                            <!-- Formulaire traitement -->
                            <div id="traiterFormSection" class="hidden bg-green-50 p-4 rounded-lg border border-green-200">
                                <p class="text-sm text-green-800 mb-4">Décrivez la solution apportée :</p>
                                <form method="POST" action="" id="traiterForm">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <div class="space-y-4">
                                        <div>
                                            <textarea name="commentaire_traitement" required rows="3" 
                                                      class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500"
                                                      placeholder="Décrivez la solution apportée à cette réclamation..."></textarea>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700">
                                                Confirmer
                                            </button>
                                            <button type="button" onclick="hideTraiterForm()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-400">
                                                Annuler
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Formulaire rejet -->
                            <div id="rejeterFormSection" class="hidden bg-red-50 p-4 rounded-lg border border-red-200">
                                <p class="text-sm text-red-800 mb-4">Motif du rejet :</p>
                                <form method="POST" action="" id="rejeterForm">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Motif *</label>
                                            <select name="motif_rejet" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500">
                                                <option value="">Sélectionnez un motif</option>
                                                <option value="non_fondee">Réclamation non fondée</option>
                                                <option value="hors_perimetre">Hors périmètre du service</option>
                                                <option value="donnees_incorrectes">Données incorrectes</option>
                                                <option value="autre">Autre</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Commentaire *</label>
                                            <textarea name="commentaire_rejet" required rows="3" 
                                                      class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500"
                                                      placeholder="Précisez les raisons du rejet..."></textarea>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="submit" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">
                                                Confirmer le rejet
                                            </button>
                                            <button type="button" onclick="hideRejeterForm()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-400">
                                                Annuler
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentReclamationId = null;

function openDetailPanel(id) {
    currentReclamationId = id;
    const panel = document.getElementById('detailPanel');
    const content = document.getElementById('detailPanelContent');
    const detailContent = document.getElementById('detailContent');
    const traitementActions = document.getElementById('traitementActions');
    
    // Réinitialiser les formulaires
    hideTraiterForm();
    hideRejeterForm();
    
    // Afficher le panneau
    panel.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('translate-x-full');
    }, 10);
    
    // Charger les détails
    detailContent.innerHTML = `
        <div class="text-center py-12">
            <i class="fa fa-spinner fa-spin text-3xl text-primary"></i>
            <p class="mt-4 text-gray-500">Chargement...</p>
        </div>
    `;
    
    // Faire une requête AJAX
    fetch(`?page=admin_reclamations&action=detail&id=${id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            detailContent.innerHTML = `<div class="text-red-500">${data.error}</div>`;
            return;
        }
        
        renderDetailContent(data);
        
        // Mettre à jour les URLs des formulaires
        document.getElementById('takeChargeForm').action = `?page=admin_reclamations&action=prendre_en_charge&id=${id}`;
        document.getElementById('traiterForm').action = `?page=admin_reclamations&action=terminer&id=${id}`;
        document.getElementById('rejeterForm').action = `?page=admin_reclamations&action=rejeter&id=${id}`;
        
        // Afficher/masquer les actions selon les permissions
        traitementActions.classList.remove('hidden');
        
        if (data.can_take_charge) {
            document.getElementById('btnTakeCharge').classList.remove('hidden');
            document.getElementById('btnTraiteurActions').classList.add('hidden');
        } else if (data.can_traiter) {
            document.getElementById('btnTakeCharge').classList.add('hidden');
            document.getElementById('btnTraiteurActions').classList.remove('hidden');
        } else {
            traitementActions.classList.add('hidden');
        }
    })
    .catch(error => {
        detailContent.innerHTML = `<div class="text-red-500">Erreur de chargement: ${error.message}</div>`;
    });
}

function closeDetailPanel() {
    const panel = document.getElementById('detailPanel');
    const content = document.getElementById('detailPanelContent');
    
    content.classList.add('translate-x-full');
    setTimeout(() => {
        panel.classList.add('hidden');
    }, 500);
}

function renderDetailContent(data) {
    const reclamation = data.reclamation;
    const historique = data.historique || [];
    
    // Mettre à jour la référence
    const prefix = (reclamation.type_reclamation === 'Demande de thème' || reclamation.type_reclamation === 'DMT') ? 'DMT' : 'RCL';
    document.getElementById('detailReference').textContent = `${prefix}-${new Date().getFullYear()}-${String(reclamation.id_reclamation).padStart(4, '0')}`;
    
    let html = `
        <!-- Onglets -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button onclick="switchTab('details')" id="tab-details" class="border-primary text-primary whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Détails
                </button>
                <button onclick="switchTab('traitement')" id="tab-traitement" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Traitement
                </button>
            </nav>
        </div>
        
        <!-- Contenu onglet Détails -->
        <div id="content-details" class="tab-content">
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="text-sm font-semibold text-gray-900">Informations</h3>
                    ${getStatusBadge(reclamation.statut_reclamation)}
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Type:</span>
                        <span class="font-medium">${reclamation.type_reclamation}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Date de dépôt:</span>
                        <span class="font-medium">${new Date(reclamation.date_creation).toLocaleString('fr-FR')}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Priorité:</span>
                        <span class="font-medium">${reclamation.priorite_reclamation || 'Non définie'}</span>
                    </div>
                    ${reclamation.jours_attente > 7 ? `
                    <div class="flex justify-between">
                        <span class="text-gray-500">Jours d'attente:</span>
                        <span class="font-medium text-red-600"><i class="fa fa-exclamation-triangle mr-1"></i>${reclamation.jours_attente} jours</span>
                    </div>
                    ` : `
                    <div class="flex justify-between">
                        <span class="text-gray-500">Jours d'attente:</span>
                        <span class="font-medium">${reclamation.jours_attente} jours</span>
                    </div>
                    `}
                </div>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Étudiant</h3>
                <div class="space-y-2 text-sm">
                    <div><span class="text-gray-500">Nom:</span> <span class="font-medium">${reclamation.nom_etu} ${reclamation.prenom_etu}</span></div>
                    <div><span class="text-gray-500">Matricule:</span> <span class="font-medium">${reclamation.num_etu}</span></div>
                    <div><span class="text-gray-500">Email:</span> <span class="font-medium">${reclamation.email_etu || 'Non renseigné'}</span></div>
                </div>
            </div>
            
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Objet</h3>
                <p class="text-sm text-gray-700 mb-4">${reclamation.titre_reclamation}</p>
                
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Description</h3>
                <div class="text-sm text-gray-700 bg-gray-50 p-3 rounded">
                    ${reclamation.description_reclamation ? reclamation.description_reclamation.replace(/\n/g, '<br>') : 'Non renseigné'}
                </div>
            </div>
        </div>
        
        <!-- Contenu onglet Traitement -->
        <div id="content-traitement" class="tab-content hidden">
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Statut actuel</h3>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">Statut:</span>
                    ${getStatusBadge(reclamation.statut_reclamation)}
                </div>
                ${reclamation.nom_traiteur ? `
                <div class="mt-2 text-sm">
                    <span class="text-gray-500">Traiteur assigné:</span>
                    <span class="font-medium">${reclamation.prenom_traiteur} ${reclamation.nom_traiteur}</span>
                </div>
                ` : ''}
                ${reclamation.date_traitement ? `
                <div class="mt-2 text-sm">
                    <span class="text-gray-500">Date de traitement:</span>
                    <span class="font-medium">${new Date(reclamation.date_traitement).toLocaleString('fr-FR')}</span>
                </div>
                ` : ''}
                ${reclamation.commentaire_traitement ? `
                <div class="mt-4">
                    <span class="text-gray-500 text-sm">Commentaire de traitement:</span>
                    <div class="mt-1 text-sm bg-white p-3 rounded border border-gray-200">
                        ${reclamation.commentaire_traitement.replace(/\n/g, '<br>')}
                    </div>
                </div>
                ` : ''}
            </div>
            
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Historique des actions</h3>
                <div class="flow-root">
                    <ul class="-mb-8">
                        ${historique.length > 0 ? historique.map((h, index) => `
                            <li class="relative pb-8">
                                ${index < historique.length - 1 ? '<div class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></div>' : ''}
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full ${getActionColor(h.action)} flex items-center justify-center ring-8 ring-white">
                                            <i class="fa ${getActionIcon(h.action)} text-white text-xs"></i>
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-gray-500">
                                                ${h.action}
                                                ${h.nom_admin ? `par <span class="font-medium text-gray-900">${h.prenom_admin} ${h.nom_admin}</span>` : ''}
                                            </p>
                                        </div>
                                        <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                            ${new Date(h.date_action).toLocaleString('fr-FR')}
                                        </div>
                                    </div>
                                </div>
                            </li>
                        `).join('') : '<li class="text-gray-500 text-center py-8">Aucun historique disponible</li>'}
                    </ul>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('detailContent').innerHTML = html;
}

function switchTab(tabName) {
    // Cacher tous les contenus
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    // Afficher le contenu sélectionné
    document.getElementById(`content-${tabName}`).classList.remove('hidden');
    
    // Mettre à jour les styles des onglets
    document.querySelectorAll('[id^="tab-"]').forEach(el => {
        el.classList.remove('border-primary', 'text-primary');
        el.classList.add('border-transparent', 'text-gray-500');
    });
    document.getElementById(`tab-${tabName}`).classList.remove('border-transparent', 'text-gray-500');
    document.getElementById(`tab-${tabName}`).classList.add('border-primary', 'text-primary');
}

function showTraiterForm() {
    document.getElementById('traiterFormSection').classList.remove('hidden');
    document.getElementById('rejeterFormSection').classList.add('hidden');
    document.getElementById('btnTraiteurActions').classList.add('hidden');
}

function hideTraiterForm() {
    document.getElementById('traiterFormSection').classList.add('hidden');
    document.getElementById('btnTraiteurActions').classList.remove('hidden');
}

function showRejeterForm() {
    document.getElementById('rejeterFormSection').classList.remove('hidden');
    document.getElementById('traiterFormSection').classList.add('hidden');
    document.getElementById('btnTraiteurActions').classList.add('hidden');
}

function hideRejeterForm() {
    document.getElementById('rejeterFormSection').classList.add('hidden');
    document.getElementById('btnTraiteurActions').classList.remove('hidden');
}

function getStatusBadge(statut) {
    const classes = {
        'En attente': 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'En cours': 'bg-blue-100 text-blue-800 border-blue-200',
        'Traitée': 'bg-green-100 text-green-800 border-green-200',
        'Rejetée': 'bg-red-100 text-red-800 border-red-200'
    };
    const className = classes[statut] || 'bg-gray-100 text-gray-800';
    return `<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border ${className}">${statut}</span>`;
}

function getActionColor(action) {
    const colors = {
        'Création': 'bg-gray-400',
        'Prise en charge': 'bg-blue-500',
        'Traitement': 'bg-green-500'
    };
    return colors[action] || 'bg-gray-400';
}

function getActionIcon(action) {
    const icons = {
        'Création': 'fa-plus',
        'Prise en charge': 'fa-hand-paper',
        'Traitement': 'fa-check'
    };
    return icons[action] || 'fa-circle';
}

// Fermer le panneau avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailPanel();
    }
});
</script>
