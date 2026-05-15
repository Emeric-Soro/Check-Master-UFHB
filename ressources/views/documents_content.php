<?php
/**
 * Vue : Bibliotheque personnelle de documents
 * Route : ?page=documents
 */
$documents = $data['documents'] ?? [];
$typeFilter = $data['type_filter'] ?? '';
$anneeFilter = $data['annee_filter'] ?? null;
$annees = $data['annees'] ?? [];
$userGroup = $data['user_group'] ?? 0;

$typeLabels = [
    'rapport'       => 'Rapport',
    'compte_rendu'  => 'Compte-rendu',
    'pv_commission' => 'PV Commission',
    'pv_final'      => 'PV Final',
    'planning'      => 'Planning',
    'recu'          => 'Recu',
    'bulletin'      => 'Bulletin',
];

$statutBadge = [
    'valider'   => 'success',
    'valide'    => 'success',
    'en_attente' => 'warning',
    'rejeter'   => 'danger',
    'Finalise'  => 'success',
];
?>

<div class="cm-content-area">
    <div class="cm-page-header cm-mb-4">
        <h1 class="cm-page-header__title">
            <i class="fas fa-folder-open cm-mr-sm"></i> Mes documents
        </h1>
        <p class="cm-page-header__subtitle">
            Consultez et telechargez tous vos documents generes.
        </p>
    </div>

    <!-- Filtres -->
    <div class="cm-card cm-mb-4">
        <div class="cm-card__body">
            <form method="get" class="cm-form cm-flex cm-gap-3 cm-flex-wrap cm-items-end">
                <input type="hidden" name="page" value="documents">

                <div class="cm-form-group">
                    <label for="type_doc" class="cm-form-label">Type de document</label>
                    <select name="type_doc" id="type_doc" class="cm-form-control">
                        <option value="">Tous les types</option>
                        <?php foreach ($typeLabels as $code => $label): ?>
                            <option value="<?= $code ?>" <?= $typeFilter === $code ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="cm-form-group">
                    <label for="annee_id" class="cm-form-label">Année académique</label>
                    <select name="annee_id" id="annee_id" class="cm-form-control">
                        <option value="">Toutes les années</option>
                        <?php foreach ($annees as $annee): ?>
                            <?php $anneeId = (int) ($annee['id_annee_acad'] ?? 0); ?>
                            <option value="<?= $anneeId ?>" <?= (int) $anneeFilter === $anneeId ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($annee['libelle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="cm-form-group">
                    <button type="submit" class="cm-btn is-primary">
                        <i class="fas fa-filter cm-mr-xs"></i> Filtrer
                    </button>
                    <a href="?page=documents" class="cm-btn is-ghost cm-ml-sm">Reinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des documents -->
    <div class="cm-card">
        <div class="cm-card__body">
            <?php if (empty($documents)): ?>
                <div class="cm-empty-state cm-text-center cm-py-5">
                    <i class="fas fa-file-alt cm-text-muted" style="font-size: 2.5rem;"></i>
                    <p class="cm-text-muted cm-mt-2">Aucun document trouve.</p>
                </div>
            <?php else: ?>
                <div class="cm-table-responsive">
                    <table class="cm-data-table cm-data-table--hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Titre</th>
                                <?php if ($userGroup !== 13): ?>
                                    <th>Etudiant</th>
                                <?php endif; ?>
                                <th>Date</th>
                                <th>Statut</th>
                                <th class="is-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td>
                                        <span class="cm-badge cm-badge--info">
                                            <?= htmlspecialchars($typeLabels[$doc['type_doc']] ?? $doc['type_doc'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($doc['titre'] ?? 'Sans titre', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <?php if ($userGroup !== 13): ?>
                                        <td><?= htmlspecialchars($doc['etudiant'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <?php
                                        $date = $doc['date_depot'] ?? null;
                                        echo $date ? htmlspecialchars(date('d/m/Y', strtotime($date)), ENT_QUOTES, 'UTF-8') : '—';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statut = $doc['statut'] ?? '';
                                        $badge = $statutBadge[$statut] ?? 'info';
                                        if ($statut !== ''):
                                        ?>
                                            <span class="cm-badge cm-badge--<?= $badge ?>">
                                                <?= htmlspecialchars($statut, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="cm-text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="is-center">
                                        <div class="cm-table-actions">
                                            <button type="button"
                                                class="cm-btn-action is-view" title="Voir"
                                                onclick="CM.openDocViewer('<?= htmlspecialchars($doc['type_doc'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars((string) $doc['id_doc'], ENT_QUOTES, 'UTF-8') ?>', {title: '<?= htmlspecialchars($doc['titre'] ?? 'Document', ENT_QUOTES, 'UTF-8') ?>'})">
                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                            </button>
                                            <a href="?page=docviewer&type=<?= urlencode($doc['type_doc']) ?>&id=<?= urlencode((string) $doc['id_doc']) ?>&action=download"
                                               class="cm-btn-action is-download" title="Telecharger">
                                                <i class="fas fa-download" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="cm-text-muted cm-mt-3" style="font-size: 0.85rem;">
                    <?= count($documents) ?> document(s) affiche(s)
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
