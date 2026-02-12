<?php
/**
 * Écran 1.3.1: Candidature (Validation Administrative et Technique)
 * Vue pour la validation des candidatures de stage
 */

// Extraire les variables globales
$candidatures = $GLOBALS['candidatures'] ?? [];
$annees = $GLOBALS['annees'] ?? [];
$filieres = $GLOBALS['filieres'] ?? [];
$stats = $GLOBALS['stats'] ?? [];
$filtres = $GLOBALS['filtres'] ?? [];

// Fonction helper pour les badges de statut
// Note: La BD utilise 'En attente', 'Validée', 'Rejetée'
function getStatusBadge($statut) {
    $classes = [
        'En attente' => 'bg-yellow-100 text-yellow-800',
        'Validée' => 'bg-green-100 text-green-800',
        'Rejetée' => 'bg-red-100 text-red-800'
    ];
    $labels = [
        'En attente' => 'En attente',
        'Validée' => 'Validée',
        'Rejetée' => 'Rejetée'
    ];
    $class = $classes[$statut] ?? 'bg-gray-100 text-gray-800';
    $label = $labels[$statut] ?? $statut;
    return "<span class='inline-flex px-2 py-1 text-xs font-semibold rounded-full {$class}'>{$label}</span>";
}
?>

<div class="p-4 sm:p-6 md:p-8">
    <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- En-tête de la page -->
        <div class="bg-primary px-6 py-6 text-white">
            <h1 class="text-2xl font-bold mb-1">Validation des Candidatures</h1>
            <p class="text-white/80 text-sm">Gestion Scolarité - Validation administrative et technique des demandes de stage</p>
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

            <!-- Statistiques rapides -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-xs font-medium uppercase">Total</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['total'] ?? 0 ?></p>
                        </div>
                        <div class="text-gray-400 text-2xl">
                            <i class="fa fa-layer-group"></i>
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
                <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-600 text-xs font-medium uppercase">Validées</p>
                            <p class="text-2xl font-bold text-green-700"><?= $stats['validee'] ?? 0 ?></p>
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
                            <p class="text-2xl font-bold text-red-700"><?= $stats['rejetee'] ?? 0 ?></p>
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
                    <input type="hidden" name="page" value="admin_candidatures">
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Statut</label>
                        <select name="statut" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="toutes" <?= $filtres['statut'] === 'toutes' ? 'selected' : '' ?>>Toutes</option>
                            <option value="En attente" <?= $filtres['statut'] === 'En attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="Validée" <?= $filtres['statut'] === 'Validée' ? 'selected' : '' ?>>Validée</option>
                            <option value="Rejetée" <?= $filtres['statut'] === 'Rejetée' ? 'selected' : '' ?>>Rejetée</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Année académique</label>
                        <select name="annee_academique" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Toutes</option>
                            <?php foreach ($annees as $annee): ?>
                                <option value="<?= $annee['id_annee_acad'] ?>" <?= $filtres['annee_academique'] == $annee['id_annee_acad'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($annee['date_deb'] . ' - ' . $annee['date_fin']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Filière</label>
                        <select name="filiere" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Toutes</option>
                            <?php foreach ($filieres as $filiere): ?>
                                <option value="<?= $filiere['id_niv_etude'] ?>" <?= $filtres['filiere'] == $filiere['id_niv_etude'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($filiere['lib_niv_etude']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Recherche</label>
                        <input type="text" name="recherche" value="<?= htmlspecialchars($filtres['recherche']) ?>" 
                               placeholder="Nom, matricule..."
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-light transition">
                            <i class="fa fa-filter mr-2"></i>Filtrer
                        </button>
                        <a href="?page=admin_candidatures" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-300 transition">
                            <i class="fa fa-undo"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tableau des candidatures -->
            <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="tableCandidatures">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° Cand.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date soumission</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matricule</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Filière</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($candidatures)): ?>
                                <?php foreach ($candidatures as $cand): ?>
                                    <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="openDetailPanel(<?= $cand['id_candidature'] ?>)">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?= $cand['id_candidature'] ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('d/m/Y H:i', strtotime($cand['date_candidature'])) ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($cand['num_etu']) ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($cand['nom_etu'] . ' ' . $cand['prenom_etu']) ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($cand['filiere'] ?? 'Non renseigné') ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?= getStatusBadge($cand['statut_candidature']) ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                            <button type="button" onclick="event.stopPropagation(); openDetailPanel(<?= $cand['id_candidature'] ?>)" 
                                                    class="text-primary hover:text-primary-light transition">
                                                <i class="fa fa-eye mr-1"></i>Voir
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="text-gray-400">
                                            <i class="fa fa-inbox text-4xl mb-4"></i>
                                            <p class="text-lg font-medium">Aucune candidature trouvée</p>
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
                            <h2 class="text-lg font-medium text-white" id="slide-over-title">Détail de la candidature</h2>
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

                    <!-- Actions de validation -->
                    <div id="validationActions" class="border-t border-gray-200 px-4 py-6 sm:px-6 bg-gray-50 hidden">
                        <div class="space-y-4">
                            <!-- Bouton Valider -->
                            <div>
                                <button type="button" onclick="showValidationConfirm()" 
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <i class="fa fa-check mr-2"></i>Valider la candidature
                                </button>
                            </div>

                            <!-- Bouton Rejeter -->
                            <div>
                                <button type="button" onclick="showRejectionForm()"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <i class="fa fa-times mr-2"></i>Rejeter la candidature
                                </button>
                            </div>

                            <!-- Section confirmation validation -->
                            <div id="validationConfirmSection" class="hidden bg-green-50 p-4 rounded-lg border border-green-200">
                                <p class="text-sm text-green-800 mb-4">Confirmez-vous la validation de cette candidature ?</p>
                                <form method="POST" action="" id="validerForm">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <div class="flex gap-2">
                                        <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700">
                                            Confirmer la validation
                                        </button>
                                        <button type="button" onclick="hideValidationConfirm()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-400">
                                            Annuler
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Section formulaire rejet -->
                            <div id="rejectionFormSection" class="hidden bg-red-50 p-4 rounded-lg border border-red-200">
                                <form method="POST" action="" id="rejeterForm">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Motif de rejet *</label>
                                            <select name="motif_rejet" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500">
                                                <option value="">Sélectionnez un motif</option>
                                                <option value="scolarite_non_soldee">Scolarité non soldée</option>
                                                <option value="duree_stage_insuffisante">Durée de stage insuffisante</option>
                                                <option value="sujet_inapproprie">Sujet de stage inapproprié</option>
                                                <option value="entreprise_non_valide">Entreprise non validée</option>
                                                <option value="documents_manquants">Documents manquants</option>
                                                <option value="autre">Autre (précisez)</option>
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
                                            <button type="button" onclick="hideRejectionForm()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-400">
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
let currentCandidatureId = null;

function openDetailPanel(id) {
    currentCandidatureId = id;
    const panel = document.getElementById('detailPanel');
    const content = document.getElementById('detailPanelContent');
    const detailContent = document.getElementById('detailContent');
    const validationActions = document.getElementById('validationActions');
    
    // Réinitialiser les formulaires
    hideValidationConfirm();
    hideRejectionForm();
    
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
    fetch(`?page=admin_candidatures&action=detail&id=${id}`, {
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
        document.getElementById('validerForm').action = `?page=admin_candidatures&action=valider&id=${id}`;
        document.getElementById('rejeterForm').action = `?page=admin_candidatures&action=rejeter&id=${id}`;
        
        // Afficher les actions si statut = En attente
        if (data.candidature.statut_candidature === 'En attente') {
            validationActions.classList.remove('hidden');
        } else {
            validationActions.classList.add('hidden');
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
    const candidature = data.candidature;
    const stage = data.stage;
    const historique = data.historique || [];
    const scolarite = data.scolarite;
    
    let html = `
        <!-- Onglets -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button onclick="switchTab('general')" id="tab-general" class="border-primary text-primary whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Informations générales
                </button>
                <button onclick="switchTab('stage')" id="tab-stage" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Stage
                </button>
                <button onclick="switchTab('historique')" id="tab-historique" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Historique
                </button>
            </nav>
        </div>
        
        <!-- Contenu onglet Général -->
        <div id="content-general" class="tab-content">
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Étudiant</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-500">Nom:</span> <span class="font-medium">${candidature.nom_etu} ${candidature.prenom_etu}</span></div>
                    <div><span class="text-gray-500">Matricule:</span> <span class="font-medium">${candidature.num_etu}</span></div>
                    <div><span class="text-gray-500">Filière:</span> <span class="font-medium">${candidature.filiere || 'Non renseigné'}</span></div>
                    <div><span class="text-gray-500">Email:</span> <span class="font-medium">${candidature.email_etu || 'Non renseigné'}</span></div>
                </div>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Candidature</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-500">Date de soumission:</span> <span class="font-medium">${new Date(candidature.date_candidature).toLocaleString('fr-FR')}</span></div>
                    <div><span class="text-gray-500">Nombre de soumissions:</span> <span class="font-medium">${candidature.nombre_soumissions || 1}</span></div>
                    <div><span class="text-gray-500">Statut:</span> <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusClass(candidature.statut_candidature)}">${getStatusLabel(candidature.statut_candidature)}</span></div>
                </div>
            </div>
            
            ${scolarite ? `
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <h3 class="text-sm font-semibold text-blue-900 mb-3">Scolarité</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-blue-600">Montant total:</span> <span class="font-medium">${Number(scolarite.montant_total || 0).toLocaleString('fr-FR')} FCFA</span></div>
                    <div><span class="text-blue-600">Montant payé:</span> <span class="font-medium">${Number(scolarite.montant_paye || 0).toLocaleString('fr-FR')} FCFA</span></div>
                    <div><span class="text-blue-600">Reste à payer:</span> <span class="font-medium ${(scolarite.reste_a_payer || 0) > 0 ? 'text-red-600' : 'text-green-600'}">${Number(scolarite.reste_a_payer || 0).toLocaleString('fr-FR')} FCFA</span></div>
                </div>
                ${(scolarite.reste_a_payer || 0) > 50000 ? `
                <div class="mt-3 p-2 bg-red-100 border border-red-300 rounded text-red-800 text-xs">
                    <i class="fa fa-exclamation-triangle mr-1"></i> Attention: Scolarité non soldée. La validation est bloquée.
                </div>
                ` : ''}
            </div>
            ` : ''}
        </div>
        
        <!-- Contenu onglet Stage -->
        <div id="content-stage" class="tab-content hidden">
            ${stage ? `
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Entreprise</h3>
                        <p class="text-sm text-gray-700">${stage.nom_entreprise || 'Non renseigné'}</p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Sujet du stage</h3>
                        <p class="text-sm text-gray-700">${stage.sujet_stage || 'Non renseigné'}</p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Période</h3>
                        <p class="text-sm text-gray-700">
                            Du ${stage.date_debut_stage ? new Date(stage.date_debut_stage).toLocaleDateString('fr-FR') : '---'} 
                            au ${stage.date_fin_stage ? new Date(stage.date_fin_stage).toLocaleDateString('fr-FR') : '---'}
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Encadrant entreprise</h3>
                        <div class="text-sm text-gray-700">
                            <p><span class="text-gray-500">Nom:</span> ${stage.encadrant_entreprise || 'Non renseigné'}</p>
                            <p><span class="text-gray-500">Email:</span> ${stage.email_encadrant || 'Non renseigné'}</p>
                            <p><span class="text-gray-500">Téléphone:</span> ${stage.telephone_encadrant || 'Non renseigné'}</p>
                        </div>
                    </div>
                </div>
            ` : '<p class="text-gray-500 text-center py-8">Aucune information de stage disponible</p>'}
        </div>
        
        <!-- Contenu onglet Historique -->
        <div id="content-historique" class="tab-content hidden">
            <div class="flow-root">
                <ul class="-mb-8">
                    ${historique.length > 0 ? historique.map((h, index) => `
                        <li class="relative pb-8">
                            ${index < historique.length - 1 ? '<div class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></div>' : ''}
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full ${getStatusBgClass(h.statut_candidature)} flex items-center justify-center ring-8 ring-white">
                                        <i class="fa ${getStatusIcon(h.statut_candidature)} text-white text-xs"></i>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                    <div>
                                        <p class="text-sm text-gray-500">
                                            Candidature ${getStatusLabel(h.statut_candidature).toLowerCase()}
                                            ${h.nom_pers_admin ? `par <span class="font-medium text-gray-900">${h.prenom_pers_admin} ${h.nom_pers_admin}</span>` : ''}
                                        </p>
                                        ${h.motif_rejet ? `<p class="text-xs text-red-600 mt-1">Motif: ${h.motif_rejet}</p>` : ''}
                                    </div>
                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                        ${new Date(h.date_candidature).toLocaleDateString('fr-FR')}
                                    </div>
                                </div>
                            </div>
                        </li>
                    `).join('') : '<li class="text-gray-500 text-center py-8">Aucun historique disponible</li>'}
                </ul>
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

function showValidationConfirm() {
    document.getElementById('validationConfirmSection').classList.remove('hidden');
    document.getElementById('rejectionFormSection').classList.add('hidden');
}

function hideValidationConfirm() {
    document.getElementById('validationConfirmSection').classList.add('hidden');
}

function showRejectionForm() {
    document.getElementById('rejectionFormSection').classList.remove('hidden');
    document.getElementById('validationConfirmSection').classList.add('hidden');
}

function hideRejectionForm() {
    document.getElementById('rejectionFormSection').classList.add('hidden');
}

function getStatusClass(statut) {
    const classes = {
        'En attente': 'bg-yellow-100 text-yellow-800',
        'Validée': 'bg-green-100 text-green-800',
        'Rejetée': 'bg-red-100 text-red-800'
    };
    return classes[statut] || 'bg-gray-100 text-gray-800';
}

function getStatusBgClass(statut) {
    const classes = {
        'En attente': 'bg-yellow-500',
        'Validée': 'bg-green-500',
        'Rejetée': 'bg-red-500'
    };
    return classes[statut] || 'bg-gray-400';
}

function getStatusIcon(statut) {
    const icons = {
        'En attente': 'fa-paper-plane',
        'Validée': 'fa-check',
        'Rejetée': 'fa-times'
    };
    return icons[statut] || 'fa-circle';
}

function getStatusLabel(statut) {
    const labels = {
        'En attente': 'En attente',
        'Validée': 'Validée',
        'Rejetée': 'Rejetée'
    };
    return labels[statut] || statut;
}

// Fermer le panneau avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailPanel();
    }
});
</script>
