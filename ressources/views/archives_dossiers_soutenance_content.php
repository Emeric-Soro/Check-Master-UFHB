<?php
global $archives;
$archivesData = $archives ?? [];
$rapportsArchives = $archivesData['rapports_archives'] ?? [];
$statistiques = $archivesData['statistiques'] ?? [];
$filtres = $archivesData['filtres'] ?? [];

function renderBadge($status) {
    $mapping = [
        'valider' => ['class' => 'success', 'icon' => 'fa-check-circle', 'label' => 'Validé'],
        'rejeter' => ['class' => 'danger', 'icon' => 'fa-times-circle', 'label' => 'Rejeté']
    ];
    $data = $mapping[$status] ?? ['class' => 'muted', 'icon' => 'fa-question-circle', 'label' => ucfirst($status)];
    return "<span class='badge badge-{$data['class']}'><i class='fas {$data['icon']} mr-1'></i>{$data['label']}</span>";
}

function formatDate($date) {
    return $date ? date('d/m/Y', strtotime($date)) : 'N/A';
}

function renderStatsCard($label, $value, $icon, $variant = 'primary') {
    $icons = [
        'archive' => 'fa-archive',
        'check' => 'fa-check-circle',
        'times' => 'fa-times-circle',
        'clock' => 'fa-clock'
    ];
    $iconClass = $icons[$icon] ?? $icon;
    $suffix = ($icon === 'clock') ? 'j' : '';
    
    echo "<div class='stats-card'>
        <div class='stats-icon icon-{$variant}'>
            <i class='fas {$iconClass}'></i>
        </div>
        <div class='stats-content'>
            <div class='stats-label'>{$label}</div>
            <div class='stats-value'>{$value}{$suffix}</div>
        </div>
    </div>";
}

function renderButton($text, $icon, $onclick) {
    return "<button class='btn btn-primary' onclick='{$onclick}'>
        <i class='fas {$icon}'></i> {$text}
    </button>";
}
?>

<div class="container p-lg">
    <!-- Page Header -->
    <div class="page-header mb-lg">
        <div class="page-title">
            <i class="fas fa-archive"></i>
            <div>
                <h1>Archives des Dossiers de Soutenance</h1>
                <p class="text-muted">Consultation des rapports validés et rejetés par la commission</p>
            </div>
        </div>
        <div class="page-actions">
            <?= renderButton('Exporter', 'fa-download', 'exportArchives()') ?>
            <?= renderButton('Imprimer', 'fa-print', 'printArchives()') ?>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid mb-lg">
        <?php
        renderStatsCard('Total Archives', $statistiques['total_archives'] ?? 0, 'archive', 'primary');
        
        $valides = 0;
        if (!empty($statistiques['repartition_statuts'])) {
            foreach ($statistiques['repartition_statuts'] as $stat) {
                if ($stat['statut'] === 'valider') {
                    $valides = $stat['nombre'];
                    break;
                }
            }
        }
        renderStatsCard('Validés', $valides, 'check', 'success');
        
        $rejetes = 0;
        if (!empty($statistiques['repartition_statuts'])) {
            foreach ($statistiques['repartition_statuts'] as $stat) {
                if ($stat['statut'] === 'rejeter') {
                    $rejetes = $stat['nombre'];
                    break;
                }
            }
        }
        renderStatsCard('Rejetés', $rejetes, 'times', 'danger');
        renderStatsCard('Temps Moyen', $statistiques['temps_moyen_traitement'] ?? 0, 'clock', 'info');
        ?>
    </div>
    <!-- Filters Section -->
    <div class="card mb-lg">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter"></i> Filtres de recherche</h3>
        </div>
        <div class="card-content">
            <form method="GET" class="form-grid">
                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-input">
                        <option value="">Tous les statuts</option>
                        <option value="valider" <?= ($filtres['statut'] ?? '') === 'valider' ? 'selected' : '' ?>>Validés</option>
                        <option value="rejeter" <?= ($filtres['statut'] ?? '') === 'rejeter' ? 'selected' : '' ?>>Rejetés</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Année</label>
                    <select name="annee" class="form-input">
                        <option value="">Toutes les années</option>
                        <?php
                        if (!empty($statistiques['repartition_annees'])) {
                            foreach ($statistiques['repartition_annees'] as $annee) {
                                $selected = ($filtres['annee'] ?? '') == $annee['annee'] ? 'selected' : '';
                                echo "<option value=\"{$annee['annee']}\" {$selected}>{$annee['annee']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Enseignant</label>
                    <input type="text" name="enseignant" value="<?= htmlspecialchars($filtres['enseignant'] ?? '') ?>" 
                           placeholder="Nom ou prénom" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Étudiant</label>
                    <input type="text" name="etudiant" value="<?= htmlspecialchars($filtres['etudiant'] ?? '') ?>" 
                           placeholder="Nom ou prénom" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Date début</label>
                    <input type="date" name="date_debut" value="<?= $filtres['date_debut'] ?? '' ?>" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="date_fin" value="<?= $filtres['date_fin'] ?? '' ?>" class="form-input">
                </div>

                <div class="form-actions" style="grid-column: 1 / -1;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                    <a href="?page=archives_dossiers_soutenance" class="btn btn-outline">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- View Mode Selector -->
    <div class="flex-between mb-md">
        <h3 class="section-title">
            <i class="fas fa-list"></i>
            Rapports archivés (<?= count($rapportsArchives) ?> résultat<?= count($rapportsArchives) > 1 ? 's' : '' ?>)
        </h3>
        <div class="flex-center gap-sm">
            <span class="text-muted">Affichage :</span>
            <select id="displayMode" class="form-input" style="width: auto;">
                <option value="cards">Cartes</option>
                <option value="table">Tableau</option>
            </select>
        </div>
    </div>

    <!-- Cards View -->
    <div id="cardsView" class="grid-cards">
        <?php if (!empty($rapportsArchives)): ?>
            <?php foreach ($rapportsArchives as $rapport): ?>
                <div class="card">
                    <div class="card-header border-b">
                        <div class="flex-between">
                            <div>
                                <h4 class="card-title">
                                    <?= htmlspecialchars($rapport['nom_rapport'] ?? 'Rapport #' . $rapport['id_rapport']) ?>
                                </h4>
                                <p class="text-muted text-sm">
                                    <i class="fas fa-tag"></i>
                                    <?= htmlspecialchars($rapport['theme_rapport'] ?? 'Thème non spécifié') ?>
                                </p>
                                <p class="text-muted text-sm">
                                    <i class="fas fa-calendar"></i>
                                    Promotion : <?= htmlspecialchars($rapport['promotion_etu'] ?? 'N/A') ?>
                                </p>
                            </div>
                            <div>
                                <?= renderBadge($rapport['decision_validation']) ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-content">
                        <div class="info-section">
                            <h5 class="info-label">
                                <i class="fas fa-user-graduate"></i> Étudiant
                            </h5>
                            <p class="info-value">
                                <?= htmlspecialchars(($rapport['prenom_etu'] ?? '') . ' ' . ($rapport['nom_etu'] ?? '')) ?>
                            </p>
                            <p class="text-muted text-sm"><?= htmlspecialchars($rapport['email_etu'] ?? '') ?></p>
                        </div>

                        <div class="info-section">
                            <h5 class="info-label">
                                <i class="fas fa-chalkboard-teacher"></i> Enseignant responsable
                            </h5>
                            <p class="info-value">
                                <?= htmlspecialchars(($rapport['prenom_enseignant'] ?? '') . ' ' . ($rapport['nom_enseignant'] ?? '')) ?>
                            </p>
                            <p class="text-muted text-sm"><?= htmlspecialchars($rapport['email_enseignant'] ?? '') ?></p>
                        </div>

                        <div class="info-section">
                            <h5 class="info-label">
                                <i class="fas fa-calendar-alt"></i> Dates importantes
                            </h5>
                            <div class="grid-2-cols gap-sm text-sm">
                                <div>
                                    <span class="text-muted">Dépôt :</span>
                                    <p><?= formatDate($rapport['date_rapport']) ?></p>
                                </div>
                                <div>
                                    <span class="text-muted">Validation :</span>
                                    <p><?= formatDate($rapport['date_validation']) ?></p>
                                </div>
                            </div>
                            <p class="text-muted text-sm mt-sm">
                                <i class="fas fa-clock"></i>
                                Temps de traitement : <?= $rapport['temps_traitement'] ?? 0 ?> jours
                            </p>
                        </div>

                        <?php if (!empty($rapport['commentaire_validation'])): ?>
                            <div class="info-section">
                                <h5 class="info-label">
                                    <i class="fas fa-comment"></i> Commentaire de validation
                                </h5>
                                <p class="text-muted bg-muted p-sm rounded">
                                    <?= htmlspecialchars($rapport['commentaire_validation']) ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="info-section">
                            <h5 class="info-label">
                                <i class="fas fa-chart-bar"></i> Statistiques
                            </h5>
                            <div class="flex-between text-sm">
                                <span class="text-muted">Évaluations :</span>
                                <span class="font-medium"><?= $rapport['nombre_evaluations'] ?? 0 ?></span>
                            </div>
                        </div>

                        <div class="card-actions border-t">
                            <button onclick="viewDetails(<?= $rapport['id_rapport'] ?>)" class="btn btn-ghost btn-sm">
                                <i class="fas fa-eye"></i> Détails
                            </button>
                            <button onclick="downloadRapport(<?= $rapport['id_rapport'] ?>)" class="btn btn-ghost btn-sm text-success">
                                <i class="fas fa-download"></i> Télécharger
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="fas fa-archive empty-icon"></i>
                <h3 class="empty-title">Aucun rapport trouvé</h3>
                <p class="empty-text">Aucun rapport ne correspond aux critères de recherche.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Table View -->
    <div id="tableView" class="hidden">
        <div class="card">
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Statut</th>
                            <th>Rapport</th>
                            <th>Étudiant</th>
                            <th>Enseignant</th>
                            <th>Date validation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rapportsArchives)): ?>
                            <?php foreach ($rapportsArchives as $rapport): ?>
                                <tr>
                                    <td><?= renderBadge($rapport['decision_validation']) ?></td>
                                    <td>
                                        <div class="font-medium">
                                            <?= htmlspecialchars($rapport['nom_rapport'] ?? 'Rapport #' . $rapport['id_rapport']) ?>
                                        </div>
                                        <div class="text-muted text-sm">
                                            <?= htmlspecialchars($rapport['theme_rapport'] ?? 'Thème non spécifié') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <?= htmlspecialchars(($rapport['prenom_etu'] ?? '') . ' ' . ($rapport['nom_etu'] ?? '')) ?>
                                        </div>
                                        <div class="text-muted text-sm">
                                            <?= htmlspecialchars($rapport['email_etu'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <?= htmlspecialchars(($rapport['prenom_enseignant'] ?? '') . ' ' . ($rapport['nom_enseignant'] ?? '')) ?>
                                        </div>
                                        <div class="text-muted text-sm">
                                            <?= htmlspecialchars($rapport['email_enseignant'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td class="text-muted"><?= formatDate($rapport['date_validation']) ?></td>
                                    <td>
                                        <div class="flex gap-sm">
                                            <button onclick="viewDetails(<?= $rapport['id_rapport'] ?>)" 
                                                    class="btn btn-ghost btn-sm" title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="downloadRapport(<?= $rapport['id_rapport'] ?>)" 
                                                    class="btn btn-ghost btn-sm text-success" title="Télécharger">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-archive empty-icon"></i>
                                        <p class="empty-text">Aucun rapport trouvé</p>
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

<script>
document.getElementById('displayMode').addEventListener('change', function() {
    const mode = this.value;
    const cardsView = document.getElementById('cardsView');
    const tableView = document.getElementById('tableView');
    
    if (mode === 'cards') {
        cardsView.classList.remove('hidden');
        tableView.classList.add('hidden');
    } else {
        cardsView.classList.add('hidden');
        tableView.classList.remove('hidden');
    }
});

function viewDetails(idRapport) {
    window.open(`?page=evaluations_dossiers_soutenance&detail=${idRapport}`, '_blank');
}

function downloadRapport(idRapport) {
    window.open(`?page=archives_dossiers_soutenance&action=download_rapport&id=${idRapport}`, '_blank');
}

function exportArchives() {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', '1');
    window.open(currentUrl.toString(), '_blank');
}

function printArchives() {
    window.print();
}
</script>