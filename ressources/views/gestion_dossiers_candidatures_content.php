<?php
// Récupérer les données du contrôleur
$rapportsVerifies = $GLOBALS['rapports_verifies'] ?? [];
$statistiques = $GLOBALS['statistiques'] ?? ['total' => 0, 'approuves' => 0, 'desapprouves' => 0];

// Filtres
$statutFilter = $_GET['statut'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';

// Filtrer les rapports selon les critères
if ($statutFilter !== 'all') {
    $rapportsVerifies = array_filter($rapportsVerifies, function ($rapport) use ($statutFilter) {
        return $rapport['statut_approbation'] === $statutFilter;
    });
}

if (!empty($searchTerm)) {
    $rapportsVerifies = array_filter($rapportsVerifies, function ($rapport) use ($searchTerm) {
        return stripos($rapport['nom_etu'] . ' ' . $rapport['prenom_etu'], $searchTerm) !== false ||
            stripos($rapport['titre_rapport'], $searchTerm) !== false ||
            stripos($rapport['theme_rapport'], $searchTerm) !== false;
    });
}

// Pagination
$perPage = 15;
$totalRapports = count($rapportsVerifies);
$totalPages = ($totalRapports > 0) ? ceil($totalRapports / $perPage) : 1;
$p = isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p'] > 0 ? (int) $_GET['p'] : 1;
if ($p > $totalPages)
    $p = $totalPages;
$startIndex = ($p - 1) * $perPage;
$rapportsPage = array_slice($rapportsVerifies, $startIndex, $perPage);
?>

<div class="container">
    <div class="page-header">
        <h1>Historique des Rapports Vérifiés <span class="text-accent">MIAGE</span></h1>
        <p class="page-subtitle">Consultation des dossiers de candidature vérifiés</p>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card success">
            <div class="stat-value"><?php echo $statistiques['total']; ?></div>
            <div class="stat-label">Total vérifiés</div>
        </div>
        <div class="stat-card primary">
            <div class="stat-value"><?php echo $statistiques['approuves']; ?></div>
            <div class="stat-label">Approuvés</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-value"><?php echo $statistiques['desapprouves']; ?></div>
            <div class="stat-label">Désapprouvés</div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card">
        <form method="get" class="filter-bar">
            <?php if (isset($_GET['page'])): ?>
                <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page']) ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="Rechercher par étudiant, titre ou thème..."
                class="input" style="flex: 1; min-width: 200px;"
                value="<?= htmlspecialchars($searchTerm) ?>">
            <select name="statut" class="input">
                <option value="all" <?php echo $statutFilter === 'all' ? 'selected' : ''; ?>>Tous les statuts</option>
                <option value="approuve" <?php echo $statutFilter === 'approuve' ? 'selected' : ''; ?>>Approuvé</option>
                <option value="desapprouve" <?php echo $statutFilter === 'desapprouve' ? 'selected' : ''; ?>>Désapprouvé</option>
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filtrer
            </button>
        </form>

        <!-- Tableau -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Rapport</th>
                        <th>Thème</th>
                        <th>Date d'envoi</th>
                        <th>Vérifié par</th>
                        <th>Statut</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rapportsPage)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted" style="padding: 3rem;">
                                <i class="fas fa-inbox fa-3x" style="opacity: 0.3; margin-bottom: 1rem;"></i>
                                <p>Aucun rapport vérifié trouvé pour votre recherche.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rapportsPage as $rapport): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-user text-success"></i>
                                        <?php echo htmlspecialchars($rapport['nom_etu'] . ' ' . $rapport['prenom_etu']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($rapport['titre_rapport']); ?></td>
                                <td><?php echo htmlspecialchars($rapport['theme_rapport']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($rapport['date_depot'])); ?></td>
                                <td><?php echo htmlspecialchars($rapport['nom_pers_admin'] . ' ' . $rapport['prenom_pers_admin']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $rapport['statut_approbation'] === 'approuve' ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-<?php echo $rapport['statut_approbation'] === 'approuve' ? 'check-circle' : 'times-circle'; ?>"></i>
                                        <?php echo $rapport['statut_approbation'] === 'approuve' ? 'Approuvé' : 'Désapprouvé'; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="?page=gestion_dossiers_candidatures&action=telecharger_pdf&id_rapport=<?php echo $rapport['id_rapport']; ?>"
                                            title="Télécharger le rapport en PDF" class="btn btn-sm btn-primary">
                                            <i class="fas fa-file-pdf"></i> PDF
                                        </a>
                                        <a href="?page=gestion_dossiers_candidatures&action=consulter_rapport&id_rapport=<?php echo $rapport['id_rapport']; ?>"
                                            target="_blank" title="Consulter le rapport" class="btn btn-sm btn-success">
                                            <i class="fas fa-eye"></i> Consulter
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-info">
            <?php if ($totalRapports > 0): ?>
                Page <?= $p ?> sur <?= $totalPages ?> —
                Affichage de <strong><?= $startIndex + 1 ?></strong>
                à <strong><?= min($startIndex + $perPage, $totalRapports) ?></strong>
                sur <strong><?= $totalRapports ?></strong> rapports vérifiés
            <?php else: ?>
                Aucun rapport à afficher
            <?php endif; ?>
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php
                function buildPageUrl($p)
                {
                    $params = $_GET;
                    $params['p'] = $p;
                    if (isset($_GET['page'])) {
                        $params['page'] = $_GET['page'];
                    }
                    return '?' . http_build_query($params);
                }
                ?>
                <a href="<?= $p > 1 ? buildPageUrl($p - 1) : '#' ?>" 
                   class="pagination-btn <?= $p == 1 ? 'disabled' : '' ?>">&laquo;</a>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="<?= buildPageUrl($i) ?>"
                       class="pagination-btn <?= $i == $p ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <a href="<?= $p < $totalPages ? buildPageUrl($p + 1) : '#' ?>"
                   class="pagination-btn <?= $p == $totalPages ? 'disabled' : '' ?>">&raquo;</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modale résumé de candidature -->
<div id="resumeModal" class="modal">
    <div class="modal-content">
        <button onclick="closeResumeModal()" class="modal-close">
            <i class="fas fa-times"></i>
        </button>
        <h3 class="modal-title">
            <i class="fas fa-user-graduate"></i> Détails du rapport
        </h3>
        <div id="modalContent" class="modal-body">
            <!-- Contenu dynamique du résumé à insérer ici -->
        </div>
    </div>
</div>

<script>
function openResumeModal(idRapport) {
    fetch(`?page=gestion_dossiers_candidatures&action=get_details_rapport&id_rapport=${idRapport}`)
        .then(response => response.json())
        .then(data => {
            const modalContent = document.getElementById('modalContent');
            const badgeClass = data.statut_approbation === 'approuve' ? 'badge-success' : 'badge-danger';
            const badgeText = data.statut_approbation === 'approuve' ? 'Approuvé' : 'Désapprouvé';
            
            modalContent.innerHTML = `
                <p style="margin-bottom: 0.75rem;"><strong>Étudiant :</strong> ${data.nom_etu} ${data.prenom_etu}</p>
                <p style="margin-bottom: 0.75rem;"><strong>Numéro étudiant :</strong> ${data.num_etu}</p>
                <p style="margin-bottom: 0.75rem;"><strong>Rapport :</strong> ${data.nom_rapport}</p>
                <p style="margin-bottom: 0.75rem;"><strong>Thème :</strong> ${data.theme_rapport}</p>
                <p style="margin-bottom: 0.75rem;"><strong>Date de dépôt :</strong> ${new Date(data.date_rapport).toLocaleDateString('fr-FR')}</p>
                <p style="margin-bottom: 0.75rem;"><strong>Statut :</strong> <span class="badge ${badgeClass}">${badgeText}</span></p>
                <p style="margin-bottom: 0.75rem;"><strong>Vérifié par :</strong> ${data.nom_pers_admin} ${data.prenom_pers_admin}</p>
                <p style="margin-bottom: 0.75rem;"><strong>Date de vérification :</strong> ${new Date(data.date_approbation).toLocaleDateString('fr-FR')}</p>
                ${data.commentaire ? `<p style="margin-bottom: 0.75rem;"><strong>Commentaire :</strong> <em>"${data.commentaire}"</em></p>` : ''}
            `;
            document.getElementById('resumeModal').style.display = 'flex';
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des détails:', error);
            alert('Erreur lors de la récupération des détails du rapport');
        });
}

function closeResumeModal() {
    document.getElementById('resumeModal').style.display = 'none';
}

document.getElementById('resumeModal').addEventListener('click', function (e) {
    if (e.target === this) {
        closeResumeModal();
    }
});
</script>