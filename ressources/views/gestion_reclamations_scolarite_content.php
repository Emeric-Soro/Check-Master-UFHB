<?php
// Supposons que $reclamationsEnCours et $reclamationsTraitees sont passés par le contrôleur
// $reclamationsEnCours : réclamations statut 'en attente' ou 'en cours'
// $reclamationsTraitees : réclamations statut 'traitée' ou 'clôturée'

// Extraire les variables globales si elles existent
$reclamationsEnCours = $GLOBALS['reclamationsEnCours'] ?? [];
$reclamationsTraitees = $GLOBALS['reclamationsTraitees'] ?? [];
?>
<div class="container">
    <div class="page-header">
        <h1>Gestion des Réclamations</h1>
        <p>Service de la Scolarité</p>
    </div>

    <!-- Barre de recherche et actions -->
    <div class="filter-bar">
        <div class="filter-bar-search">
            <i class="fa fa-search"></i>
            <input type="text" id="searchInput" placeholder="Rechercher une réclamation..." class="input">
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="stats-grid">
        <div class="stat-card stat-card-warning">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <p class="stat-card-label">En attente</p>
                    <p class="stat-card-value" id="countEnAttente">0</p>
                </div>
                <div class="stat-card-icon">
                    <i class="fa fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="stat-card stat-card-success">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <p class="stat-card-label">Résolue</p>
                    <p class="stat-card-value" id="countResolue">0</p>
                </div>
                <div class="stat-card-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="stat-card stat-card-danger">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <p class="stat-card-label">Rejeté</p>
                    <p class="stat-card-value" id="countRejete">0</p>
                </div>
                <div class="stat-card-icon">
                    <i class="fa fa-times-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des réclamations en cours -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fa fa-exclamation-triangle"></i>
                Réclamations à traiter
            </h2>
            <div class="card-actions">
                <button onclick="exportTableToCSV('tableReclamationsEnCours', 'reclamations_a_traiter')" class="btn btn-warning btn-sm">
                    <i class="fa fa-download"></i>Exporter
                </button>
                <button onclick="printTable('tableReclamationsEnCours', 'Réclamations à traiter')" class="btn btn-primary btn-sm">
                    <i class="fa fa-print"></i>Imprimer
                </button>
                <span class="badge badge-warning" id="countEnCoursBadge">0</span>
            </div>
        </div>

        <div class="table-wrapper">
            <table id="tableReclamationsEnCours">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Étudiant</th>
                        <th>Objet</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reclamationsEnCours)): ?>
                    <?php foreach ($reclamationsEnCours as $i => $rec): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($rec->nom_etu . ' ' . $rec->prenom_etu) ?></strong>
                        </td>
                        <td>
                            <span title="<?= htmlspecialchars($rec->titre_reclamation ?? '') ?>">
                                <?= htmlspecialchars($rec->titre_reclamation ?? '') ?>
                            </span>
                        </td>
                        <td>
                            <span title="<?= htmlspecialchars($rec->description_reclamation ?? '') ?>">
                                <?= htmlspecialchars($rec->description_reclamation ?? '') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($rec->date_creation ?? '') ?></td>
                        <td>
                            <span class="badge 
                                <?php if($rec->statut_reclamation === 'en attente') echo 'badge-warning';
                                      elseif($rec->statut_reclamation === 'en cours') echo 'badge-info';
                                      ?>">
                                <?= htmlspecialchars($rec->statut_reclamation) ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if (canEdit()): ?>
                                <form method="post"
                                    action="?page=gestion_reclamations_scolarite&action=changer_statut&id=<?= $rec->id_reclamation ?>"
                                    class="inline-form">
                                    <select name="nouveau_statut" class="input input-sm">
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
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fa fa-check"></i>Valider
                                    </button>
                                </form>
                                <?php endif; ?>
                                <button type="button"
                                    onclick='showReclamationDetails(<?= json_encode($rec, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    class="btn btn-primary btn-sm">
                                    <i class="fa fa-eye"></i>Détails
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>Aucune réclamation à traiter</p>
                                <small>Toutes les réclamations ont été traitées</small>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Historique des réclamations -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fa fa-history"></i>
                Historique des réclamations
            </h2>
            <div class="card-actions">
                <button onclick="exportTableToCSV('tableReclamationsTraitees', 'historique_reclamations')" class="btn btn-secondary btn-sm">
                    <i class="fa fa-download"></i>Exporter
                </button>
                <button onclick="printTable('tableReclamationsTraitees', 'Historique des réclamations')" class="btn btn-primary btn-sm">
                    <i class="fa fa-print"></i>Imprimer
                </button>
                <span class="badge badge-secondary" id="countTraiteesBadge">0</span>
            </div>
        </div>

        <div class="table-wrapper">
            <table id="tableReclamationsTraitees">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Étudiant</th>
                        <th>Objet</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reclamationsTraitees)): ?>
                    <?php foreach ($reclamationsTraitees as $i => $rec): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($rec->nom_etu . ' ' . $rec->prenom_etu) ?></strong>
                        </td>
                        <td>
                            <span title="<?= htmlspecialchars($rec->titre_reclamation ?? '') ?>">
                                <?= htmlspecialchars($rec->titre_reclamation ?? '') ?>
                            </span>
                        </td>
                        <td>
                            <span title="<?= htmlspecialchars($rec->description_reclamation ?? '') ?>">
                                <?= htmlspecialchars($rec->description_reclamation ?? '') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($rec->date_creation ?? '') ?></td>
                        <td>
                            <span class="badge 
                                <?php if(strtolower($rec->statut_reclamation) === 'résolue' || strtolower($rec->statut_reclamation) === 'traitée') echo 'badge-success';
                                      elseif(strtolower($rec->statut_reclamation) === 'rejeté' || strtolower($rec->statut_reclamation) === 'rejetée') echo 'badge-danger';
                                      else echo 'badge-secondary'; ?>">
                                <?= htmlspecialchars($rec->statut_reclamation) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fa fa-archive"></i>
                                <p>Aucun historique de réclamation</p>
                                <small>Les réclamations traitées apparaîtront ici</small>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="card-footer">
            <span id="paginationInfo">Affichage des réclamations traitées</span>
            <div id="pagination"></div>
        </div>
    </div>
</div>

<!-- Modal détails réclamation -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <button onclick="closeDetailsModal()" class="modal-close">
            <i class="fa fa-times"></i>
        </button>
        <div class="modal-header">
            <div class="modal-icon">
                <i class="fa fa-file-text"></i>
            </div>
            <div>
                <h3>Détails de la réclamation</h3>
                <p>Informations complètes</p>
            </div>
        </div>
        <div id="detailsContent" class="modal-body">
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
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-toast`;
    notification.innerHTML = `
        <span class="alert-icon">
            ${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}
        </span>
        <span>${message}</span>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('show');
    }, 100);

    setTimeout(() => {
        notification.classList.remove('show');
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
        prevBtn.className = `btn btn-sm ${currentPage === 1 ? 'btn-disabled' : 'btn-secondary'}`;
        prevBtn.disabled = currentPage === 1;
        prevBtn.onclick = () => showPage(currentPage - 1);
        pagination.appendChild(prevBtn);

        // Pages
        for (let i = 1; i <= pageCount; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = `btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-secondary'}`;
            btn.onclick = () => showPage(i);
            pagination.appendChild(btn);
        }

        // Bouton suivant
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = '<i class="fa fa-chevron-right"></i>';
        nextBtn.className = `btn btn-sm ${currentPage === pageCount ? 'btn-disabled' : 'btn-secondary'}`;
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
        <div class="detail-grid">
            <div class="detail-item">
                <h4>Informations étudiant</h4>
                <p><strong>Nom complet :</strong> ${rec.nom_etu} ${rec.prenom_etu}</p>
            </div>
            <div class="detail-item">
                <h4>Statut</h4>
                <span class="badge 
                    ${rec.statut_reclamation === 'en attente' ? 'badge-warning' : 
                      rec.statut_reclamation === 'résolue' || rec.statut_reclamation === 'traitée' ? 'badge-success' :
                      rec.statut_reclamation === 'rejeté' || rec.statut_reclamation === 'rejetée' ? 'badge-danger' : 'badge-secondary'}">
                    ${rec.statut_reclamation}
                </span>
            </div>
        </div>
        <div class="detail-item">
            <h4>Objet de la réclamation</h4>
            <p>${rec.titre_reclamation || 'Non spécifié'}</p>
        </div>
        <div class="detail-item">
            <h4>Description détaillée</h4>
            <p>${rec.description_reclamation || 'Aucune description fournie'}</p>
        </div>
        <div class="detail-item">
            <h4>Date de création</h4>
            <p>${rec.date_creation || 'Date non disponible'}</p>
        </div>
    `;

    detailsContent.innerHTML = html;
    document.getElementById('detailsModal').classList.remove('hidden');
    document.getElementById('detailsModal').classList.add('active');
}

function closeDetailsModal() {
    const modal = document.getElementById('detailsModal');
    modal.classList.remove('active');
    setTimeout(() => {
        modal.classList.add('hidden');
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