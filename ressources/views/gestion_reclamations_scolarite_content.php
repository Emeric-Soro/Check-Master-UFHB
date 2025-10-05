<?php
// Supposons que $reclamationsEnCours et $reclamationsTraitees sont passés par le contrôleur
// $reclamationsEnCours : réclamations statut 'en attente' ou 'en cours'
// $reclamationsTraitees : réclamations statut 'traitée' ou 'clôturée'

// Extraire les variables globales si elles existent
$reclamationsEnCours = $GLOBALS['reclamationsEnCours'] ?? [];
$reclamationsTraitees = $GLOBALS['reclamationsTraitees'] ?? [];
?>
<div class="p-4 sm:p-6 md:p-8">
    <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- En-tête de la page -->
        <div class="bg-green-600 px-6 py-8 text-white">
            <h1 class="text-3xl font-bold text-center mb-2">Gestion des Réclamations</h1>
            <p class="text-green-100 text-center">Service de la Scolarité</p>
        </div>

        <div class="p-6 md:p-8">
            <!-- Barre de recherche et actions -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8 gap-4">
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Rechercher une réclamation..."
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm transition duration-200">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Statistiques rapides -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-yellow-500 rounded-lg p-6 text-white shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-yellow-100 text-sm font-medium">En attente</p>
                            <p class="text-2xl font-bold" id="countEnAttente">0</p>
                        </div>
                        <div class="text-3xl opacity-75">
                            <i class="fa fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-green-500 rounded-lg p-6 text-white shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-green-100 text-sm font-medium">Résolue</p>
                            <p class="text-2xl font-bold" id="countResolue">0</p>
                        </div>
                        <div class="text-3xl opacity-75">
                            <i class="fa fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-red-500 rounded-lg p-6 text-white shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-red-100 text-sm font-medium">Rejeté</p>
                            <p class="text-2xl font-bold" id="countRejete">0</p>
                        </div>
                        <div class="text-3xl opacity-75">
                            <i class="fa fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des réclamations en cours -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                        <i class="fa fa-exclamation-triangle text-yellow-500 mr-3"></i>
                        Réclamations à traiter
                    </h2>
                    <div class="flex items-center gap-3">
                        <button onclick="exportTableToCSV('tableReclamationsEnCours', 'reclamations_a_traiter')"
                            class="inline-flex items-center px-3 py-1 bg-yellow-600 text-white text-sm rounded-md hover:bg-yellow-700 transition duration-200">
                            <i class="fa fa-download mr-1"></i>Exporter
                        </button>
                        <button onclick="printTable('tableReclamationsEnCours', 'Réclamations à traiter')"
                            class="inline-flex items-center px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition duration-200">
                            <i class="fa fa-print mr-1"></i>Imprimer
                        </button>
                        <span class="bg-yellow-100 text-yellow-800 text-sm font-medium px-3 py-1 rounded-full"
                            id="countEnCoursBadge">0</span>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="tableReclamationsEnCours">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        #</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Étudiant</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Objet</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Message</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Statut</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($reclamationsEnCours)): ?>
                                <?php foreach ($reclamationsEnCours as $i => $rec): ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= $i+1 ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($rec->nom_etu . ' ' . $rec->prenom_etu) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate"
                                            title="<?= htmlspecialchars($rec->titre_reclamation ?? '') ?>">
                                            <?= htmlspecialchars($rec->titre_reclamation ?? '') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-600 max-w-xs truncate"
                                            title="<?= htmlspecialchars($rec->description_reclamation ?? '') ?>">
                                            <?= htmlspecialchars($rec->description_reclamation ?? '') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($rec->date_creation ?? '') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php if($rec->statut_reclamation === 'en attente') echo 'bg-yellow-100 text-yellow-800';
                                                  elseif($rec->statut_reclamation === 'en cours') echo 'bg-blue-100 text-blue-800';
                                                  ?>">
                                            <?= htmlspecialchars($rec->statut_reclamation) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-3">
                                            <form method="post"
                                                action="?page=gestion_reclamations_scolarite&action=changer_statut&id=<?= $rec->id_reclamation ?>"
                                                class="flex items-center space-x-2">
                                                <select name="nouveau_statut"
                                                    class="text-sm border border-gray-300 rounded-md px-2 py-1 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                                    <option value="En attente"
                                                        <?= strtolower($rec->statut_reclamation) === 'En attente' ? 'selected' : '' ?>>
                                                        En attente</option>
                                                    <option value="Résolue"
                                                        <?= strtolower($rec->statut_reclamation) === 'Résolue' ? 'selected' : '' ?>>
                                                        Résolue</option>
                                                    <option value="Rejetée"
                                                        <?= strtolower($rec->statut_reclamation) === 'Rejetée' ? 'selected' : '' ?>>
                                                        Rejeté</option>
                                                </select>
                                                <button type="submit"
                                                    class="inline-flex items-center px-3 py-1 bg-green-600 text-white text-xs rounded-md hover:bg-green-700 transition duration-200">
                                                    <i class="fa fa-check mr-1"></i>Valider
                                                </button>
                                            </form>
                                            <button type="button"
                                                onclick='showReclamationDetails(<?= json_encode($rec, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                class="inline-flex items-center px-3 py-1 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700 transition duration-200">
                                                <i class="fa fa-eye mr-1"></i>Détails
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="text-gray-500">
                                            <i class="fa fa-inbox text-4xl mb-4 text-gray-300"></i>
                                            <p class="text-lg font-medium">Aucune réclamation à traiter</p>
                                            <p class="text-sm">Toutes les réclamations ont été traitées</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Historique des réclamations -->
            <div>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                        <i class="fa fa-history text-gray-500 mr-3"></i>
                        Historique des réclamations
                    </h2>
                    <div class="flex items-center gap-3">
                        <button onclick="exportTableToCSV('tableReclamationsTraitees', 'historique_reclamations')"
                            class="inline-flex items-center px-3 py-1 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700 transition duration-200">
                            <i class="fa fa-download mr-1"></i>Exporter
                        </button>
                        <button onclick="printTable('tableReclamationsTraitees', 'Historique des réclamations')"
                            class="inline-flex items-center px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition duration-200">
                            <i class="fa fa-print mr-1"></i>Imprimer
                        </button>
                        <span class="bg-gray-100 text-gray-800 text-sm font-medium px-3 py-1 rounded-full"
                            id="countTraiteesBadge">0</span>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="tableReclamationsTraitees">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        #</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Étudiant</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Objet</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Message</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Statut</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($reclamationsTraitees)): ?>
                                <?php foreach ($reclamationsTraitees as $i => $rec): ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= $i+1 ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($rec->nom_etu . ' ' . $rec->prenom_etu) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate"
                                            title="<?= htmlspecialchars($rec->titre_reclamation ?? '') ?>">
                                            <?= htmlspecialchars($rec->titre_reclamation ?? '') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-600 max-w-xs truncate"
                                            title="<?= htmlspecialchars($rec->description_reclamation ?? '') ?>">
                                            <?= htmlspecialchars($rec->description_reclamation ?? '') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($rec->date_creation ?? '') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php if(strtolower($rec->statut_reclamation) === 'résolue' || strtolower($rec->statut_reclamation) === 'traitée') echo 'bg-green-100 text-green-800';
                                                  elseif(strtolower($rec->statut_reclamation) === 'rejeté' || strtolower($rec->statut_reclamation) === 'rejetée') echo 'bg-red-100 text-red-800';
                                                  else echo 'bg-gray-100 text-gray-800'; ?>">
                                            <?= htmlspecialchars($rec->statut_reclamation) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="text-gray-500">
                                            <i class="fa fa-archive text-4xl mb-4 text-gray-300"></i>
                                            <p class="text-lg font-medium">Aucun historique de réclamation</p>
                                            <p class="text-sm">Les réclamations traitées apparaîtront ici</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-6 gap-4">
                    <div class="text-gray-600 text-sm">
                        <span id="paginationInfo">Affichage des réclamations traitées</span>
                    </div>
                    <div class="flex justify-center" id="pagination">
                        <!-- La pagination sera générée par JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal détails réclamation -->
<div id="detailsModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-2xl mx-4 relative transform transition-all">
        <button onclick="closeDetailsModal()"
            class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl transition duration-200">
            <i class="fa fa-times"></i>
        </button>
        <div class="flex items-center mb-6">
            <div class="bg-blue-100 p-3 rounded-full mr-4">
                <i class="fa fa-file-text text-blue-600 text-xl"></i>
            </div>
            <div>
                <h3 class="text-2xl font-bold text-gray-900">Détails de la réclamation</h3>
                <p class="text-gray-600">Informations complètes</p>
            </div>
        </div>
        <div id="detailsContent" class="space-y-4 text-sm">
            <!-- Le contenu sera généré par JavaScript -->
        </div>
    </div>
</div>

<script>
// Mise à jour des compteurs
function updateCounters() {
    const enCoursTable = document.getElementById('tableReclamationsEnCours');
    const traiteesTable = document.getElementById('tableReclamationsTraitees');

    // Compter les réclamations selon les statuts
    let enAttenteCount = 0;
    let resolueCount = 0;
    let rejeteCount = 0;

    // Compter dans la table des réclamations en cours (statut "En attente")
    if (enCoursTable) {
        const rows = enCoursTable.querySelectorAll('tbody tr');
        enAttenteCount = rows.length;
    }

    // Compter dans la table des réclamations traitées (historique)
    if (traiteesTable) {
        const rows = traiteesTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const statusCell = row.querySelector('td:nth-child(6) span');
            if (statusCell) {
                const status = statusCell.textContent.trim().toLowerCase();
                if (status === 'résolue' || status === 'traitée') {
                    resolueCount++;
                } else if (status === 'rejeté' || status === 'rejetée') {
                    rejeteCount++;
                }
            }
        });
    }

    // Mettre à jour les affichages
    document.getElementById('countEnAttente').textContent = enAttenteCount;
    document.getElementById('countResolue').textContent = resolueCount;
    document.getElementById('countRejete').textContent = rejeteCount;

    // Mettre à jour les badges
    const countEnCoursBadge = document.getElementById('countEnCoursBadge');
    if (countEnCoursBadge) {
        countEnCoursBadge.textContent = enAttenteCount;
    }

    const countTraiteesBadge = document.getElementById('countTraiteesBadge');
    if (countTraiteesBadge) {
        countTraiteesBadge.textContent = resolueCount + rejeteCount;
    }
}

function filterReclamations() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const tables = [document.getElementById('tableReclamationsEnCours'), document.getElementById(
        'tableReclamationsTraitees')];

    tables.forEach(function(table) {
        const tbody = table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');

        rows.forEach(function(row) {
            const cells = row.querySelectorAll('td');
            let show = false;

            cells.forEach(function(cell) {
                if (cell.textContent.toLowerCase().includes(filter)) {
                    show = true;
                }
            });

            row.style.display = show ? '' : 'none';
        });
    });

    updateCounters();
}

// Fonction pour exporter une table spécifique
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        showFeedback('Table non trouvée', 'error');
        return;
    }

    let csv = [];

    // En-têtes du tableau
    const headers = Array.from(table.querySelectorAll('thead th'));
    const headerRow = headers.map(th => th.textContent.trim().replace(/\r?\n|\r/g, ' ')).join(';');
    csv.push(headerRow);

    // Données visibles uniquement
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cells = Array.from(row.querySelectorAll('td'));
            const rowData = cells.map(td => {
                let text = td.textContent.trim().replace(/\s+/g, ' ');
                text = text.replace(/\r?\n|\r/g, ' '); // Supprimer les retours à la ligne
                text = text.replace(/"/g, '""');
                return '"' + text + '"';
            });
            csv.push(rowData.join(';'));
        }
    });

    // Télécharger
    const csvContent = csv.join('\n');
    const blob = new Blob(['\ufeff' + csvContent], {
        type: 'text/csv;charset=utf-8;'
    });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `${filename}_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showFeedback(`Export ${filename} terminé avec succès`, 'success');
}

// Fonction pour imprimer une table spécifique
function printTable(tableId, title) {
    const table = document.getElementById(tableId);
    if (!table) {
        showFeedback('Table non trouvée', 'error');
        return;
    }

    // Créer une nouvelle fenêtre pour l'impression
    const printWindow = window.open('', '_blank', 'width=800,height=600');

    // Créer le contenu HTML pour l'impression
    let printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>${title}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    font-size: 12px;
                }
                .print-header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .print-header h1 {
                    margin: 0;
                    color: #333;
                    font-size: 18px;
                }
                .print-header p {
                    margin: 5px 0 0 0;
                    color: #666;
                    font-size: 12px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                    font-size: 11px;
                }
                th {
                    background-color: #f8f9fa;
                    font-weight: bold;
                }
                .status-badge {
                    padding: 2px 6px;
                    border-radius: 12px;
                    font-size: 10px;
                    font-weight: bold;
                }
                .status-en-attente { background-color: #fef3c7; color: #92400e; }
                .status-en-cours { background-color: #dbeafe; color: #1e40af; }
                .status-resolue { background-color: #d1fae5; color: #065f46; }
                .status-rejetee { background-color: #fee2e2; color: #991b1b; }
                .print-footer {
                    margin-top: 20px;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1>${title}</h1>
                <p>Service de la Scolarité - Université</p>
                <p>Date d'impression: ${new Date().toLocaleDateString('fr-FR')}</p>
            </div>
    `;

    // Cloner la table pour l'impression
    const tableClone = table.cloneNode(true);

    // Supprimer les boutons d'action de la table clonée
    const actionCells = tableClone.querySelectorAll('td:last-child');
    actionCells.forEach(cell => {
        cell.innerHTML = '';
    });

    // Supprimer la colonne Actions si elle existe
    const actionHeaders = tableClone.querySelectorAll('th:last-child');
    actionHeaders.forEach(header => {
        if (header.textContent.trim().toLowerCase().includes('actions')) {
            header.remove();
        }
    });

    // Ajuster les classes CSS pour les badges de statut
    const statusSpans = tableClone.querySelectorAll('span');
    statusSpans.forEach(span => {
        const text = span.textContent.trim().toLowerCase();
        span.className = 'status-badge';
        if (text.includes('en attente')) {
            span.classList.add('status-en-attente');
        } else if (text.includes('en cours')) {
            span.classList.add('status-en-cours');
        } else if (text.includes('résolue') || text.includes('traitée')) {
            span.classList.add('status-resolue');
        } else if (text.includes('rejeté') || text.includes('rejetée')) {
            span.classList.add('status-rejetee');
        }
    });

    printContent += tableClone.outerHTML;
    printContent += `
            <div class="print-footer">
                <p>Document généré automatiquement par le système de gestion des réclamations</p>
            </div>
        </body>
        </html>
    `;

    // Écrire le contenu dans la nouvelle fenêtre
    printWindow.document.write(printContent);
    printWindow.document.close();

    // Attendre que le contenu soit chargé puis imprimer
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };

    showFeedback(`Impression de ${title} lancée`, 'success');
}

// Fonction pour afficher les messages de feedback
function showFeedback(message, type = 'info') {
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <span class="mr-2">
                ${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}
            </span>
            <span>${message}</span>
        </div>
    `;

    // Ajouter au DOM
    document.body.appendChild(notification);

    // Animer l'entrée
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);

    // Supprimer après 3 secondes
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Pagination améliorée
function initializePagination() {
    const table = document.getElementById('tableReclamationsTraitees');
    const tbody = table ? table.querySelector('tbody') : null;
    const pagination = document.getElementById('pagination');
    const paginationInfo = document.getElementById('paginationInfo');

    if (!tbody || !pagination) return;

    const rows = Array.from(tbody.querySelectorAll('tr'));
    const rowsPerPage = 10;
    let currentPage = 1;

    function showPage(page) {
        currentPage = page;
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        rows.forEach((row, i) => {
            row.style.display = (i >= start && i < end) ? '' : 'none';
        });

        renderPagination();
        updatePaginationInfo();
    }

    function updatePaginationInfo() {
        const totalRows = rows.length;
        const start = (currentPage - 1) * rowsPerPage + 1;
        const end = Math.min(currentPage * rowsPerPage, totalRows);

        if (totalRows > 0) {
            paginationInfo.textContent = `Affichage de ${start} à ${end} sur ${totalRows} réclamations traitées`;
        } else {
            paginationInfo.textContent = 'Aucune réclamation traitée à afficher';
        }
    }

    function renderPagination() {
        const pageCount = Math.ceil(rows.length / rowsPerPage);
        pagination.innerHTML = '';

        if (pageCount <= 1) return;

        // Bouton précédent
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = '<i class="fa fa-chevron-left"></i>';
        prevBtn.className =
            `px-3 py-2 border border-gray-300 bg-white text-sm font-medium ${currentPage === 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50'} rounded-l-md transition duration-200`;
        prevBtn.disabled = currentPage === 1;
        prevBtn.onclick = () => showPage(currentPage - 1);
        pagination.appendChild(prevBtn);

        // Pages
        for (let i = 1; i <= pageCount; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className =
                `px-3 py-2 border border-gray-300 text-sm font-medium ${i === currentPage ? 'bg-green-100 text-green-700 font-bold' : 'bg-white text-gray-700 hover:bg-gray-50'} transition duration-200`;
            btn.onclick = () => showPage(i);
            pagination.appendChild(btn);
        }

        // Bouton suivant
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = '<i class="fa fa-chevron-right"></i>';
        nextBtn.className =
            `px-3 py-2 border border-gray-300 bg-white text-sm font-medium ${currentPage === pageCount ? 'text-gray-300 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50'} rounded-r-md transition duration-200`;
        nextBtn.disabled = currentPage === pageCount;
        nextBtn.onclick = () => showPage(currentPage + 1);
        pagination.appendChild(nextBtn);
    }

    showPage(1);
}

function showReclamationDetails(rec) {
    const detailsContent = document.getElementById('detailsContent');
    let html = '';

    html += `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-semibold text-gray-900 mb-2">Informations étudiant</h4>
                <p class="text-gray-700"><span class="font-medium">Nom complet :</span> ${rec.nom_etu} ${rec.prenom_etu}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-semibold text-gray-900 mb-2">Statut</h4>
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                    ${rec.statut_reclamation === 'en attente' ? 'bg-yellow-100 text-yellow-800' : 
                      rec.statut_reclamation === 'résolue' || rec.statut_reclamation === 'traitée' ? 'bg-green-100 text-green-800' :
                      rec.statut_reclamation === 'rejeté' || rec.statut_reclamation === 'rejetée' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'}">
                    ${rec.statut_reclamation}
                </span>
            </div>
        </div>
        <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="font-semibold text-gray-900 mb-2">Objet de la réclamation</h4>
            <p class="text-gray-700">${rec.titre_reclamation || 'Non spécifié'}</p>
        </div>
        <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="font-semibold text-gray-900 mb-2">Description détaillée</h4>
            <p class="text-gray-700 whitespace-pre-wrap">${rec.description_reclamation || 'Aucune description fournie'}</p>
        </div>
        <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="font-semibold text-gray-900 mb-2">Date de création</h4>
            <p class="text-gray-700">${rec.date_creation || 'Date non disponible'}</p>
        </div>
    `;

    detailsContent.innerHTML = html;
    document.getElementById('detailsModal').classList.remove('hidden');

    // Animation d'entrée
    setTimeout(() => {
        document.querySelector('#detailsModal > div').classList.add('scale-100');
    }, 10);
}

function closeDetailsModal() {
    const modal = document.getElementById('detailsModal');
    const modalContent = modal.querySelector('div');

    modalContent.classList.remove('scale-100');
    modalContent.classList.add('scale-95');

    setTimeout(() => {
        modal.classList.add('hidden');
        modalContent.classList.remove('scale-95');
    }, 200);
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    updateCounters();
    initializePagination();

    // Fermer le modal en cliquant à l'extérieur
    document.getElementById('detailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDetailsModal();
        }
    });

    // Recherche en temps réel
    document.getElementById('searchInput').addEventListener('input', filterReclamations);
});
</script>

</rewritten_file>