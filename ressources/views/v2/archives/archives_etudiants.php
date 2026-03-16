<?php
/**
 * Archives Étudiants - Liste avec filtres
 */
$etudiants = $data['etudiants'] ?? [];
$annees = $data['annees'] ?? [];
$specialites = $data['specialites'] ?? [];
$filters = $data['filters'] ?? [];
?>
<div class="cm-archive-etudiants">
    <div class="cm-page-header cm-mb-4">
        <p class="cm-page-subtitle">Consultation et recherche des étudiants par année académique</p>
    </div>

    <!-- Filtres -->
    <div class="cm-card cm-mb-4">
        <div class="cm-card-body">
            <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
.cm-content-area form .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}
</style>
<form method="GET" class="cm-grid-4 cm-gap-3">
                <input type="hidden" name="page" value="archives_etudiants">
                
                <div class="cm-form-group">
                    <label class="cm-form-label">Spécialité</label>
                    <select name="filters[specialite]" class="cm-select">
                        <option value="">Toutes</option>
                        <?php foreach ($specialites as $s): ?>
                            <option value="<?php echo $s->id_specialite; ?>" 
                                <?php echo ($filters['specialite'] ?? '') == $s->id_specialite ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s->lib_specialite); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="cm-form-group">
                    <label class="cm-form-label">Statut</label>
                    <select name="filters[statut]" class="cm-select">
                        <option value="">Tous</option>
                        <option value="valider" <?php echo ($filters['statut'] ?? '') == 'valider' ? 'selected' : ''; ?>>Validé</option>
                        <option value="rejeter" <?php echo ($filters['statut'] ?? '') == 'rejeter' ? 'selected' : ''; ?>>Rejeté</option>
                        <option value="en_cours" <?php echo ($filters['statut'] ?? '') == 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                    </select>
                </div>

                <div class="cm-form-group">
                    <label class="cm-form-label">Recherche</label>
                    <input type="text" name="filters[search]" class="cm-input" 
                           placeholder="Nom, matricule..." 
                           value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>

                <div class="cm-form-group cm-flex cm-items-end">
                    <button type="submit" class="cm-btn cm-btn-primary">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <a href="?page=archives_etudiants" class="cm-btn cm-btn-outline cm-ml-2">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Actions -->
    <div class="cm-flex cm-justify-between cm-mb-3">
        <div>
            <span class="cm-text-muted"><?php echo count($etudiants); ?> étudiant(s) trouvé(s)</span>
        </div>
        <div class="cm-flex cm-gap-2">
            <a href="?page=archives_etudiants&action=exportCsv" class="cm-btn cm-btn-outline">
                <i class="fas fa-download"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Tableau -->
    <div class="cm-table-responsive">
        <table class="cm-table cm-table-striped cm-table-hover">
            <thead>
                <tr>
                    <th>Matricule</th>
                    <th>Nom & Prénom</th>
                    <th>Promotion</th>
                    <th>Spécialité</th>
                    <th>Entreprise</th>
                    <th>Thème</th>
                    <th>Moyennes</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($etudiants)): ?>
                    <tr>
                        <td colspan="9" class="cm-text-center cm-p-4 cm-text-muted">
                            <i class="fas fa-inbox cm-text-4xl cm-mb-2"></i><br>
                            Aucun étudiant trouvé pour cette année
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($etudiants as $e): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($e->num_carte_etud); ?></code></td>
                            <td>
                                <strong><?php echo htmlspecialchars($e->nom_etu . ' ' . $e->prenom_etu); ?></strong><br>
                                <small class="cm-text-muted"><?php echo htmlspecialchars($e->email_etu); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($e->promotion_etu); ?></td>
                            <td><?php echo htmlspecialchars($e->lib_specialite ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($e->entreprise ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(substr($e->theme ?? '', 0, 50)) . (strlen($e->theme ?? '') > 50 ? '...' : ''); ?></td>
                            <td>
                                M1: <?php echo $e->moyenne_M1 ?? '-'; ?><br>
                                M2: <?php echo $e->moyenne_M2 ?? '-'; ?>
                            </td>
                            <td>
                                <?php 
                                $badgeClass = match($e->statut ?? 'En cours') {
                                    'Validé' => 'cm-badge-success',
                                    'Rejeté' => 'cm-badge-danger',
                                    default => 'cm-badge-warning'
                                };
                                ?>
                                <span class="cm-badge <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($e->statut ?? 'En cours'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="cm-flex cm-gap-1">
                                    <a href="?page=fiche_etudiant_archive&id=<?php echo urlencode($e->num_carte_etud); ?>" 
                                       class="cm-btn cm-btn-sm cm-btn-primary" title="Voir fiche">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?page=parcours_etudiant&id=<?php echo urlencode($e->num_carte_etud); ?>" 
                                       class="cm-btn cm-btn-sm cm-btn-info" title="Parcours">
                                        <i class="fas fa-route"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

